<?php
require_once '../config.php';

// Verificar se o usuário está logado e é administrador
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$message = '';
$error = '';

// Processar alocação automática
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $concurso_id = (int)$_POST['concurso_id'];
        
        if (!$concurso_id) {
            throw new Exception("Concurso não especificado.");
        }
        
        // Obter fiscais aprovados não alocados
        $fiscais_aprovados = getFiscaisAprovadosNaoAlocados($concurso_id);
        
        if (empty($fiscais_aprovados)) {
            throw new Exception("Não há fiscais aprovados disponíveis para alocação.");
        }
        
        // Obter salas disponíveis
        $salas_disponiveis = getSalasDisponiveis($concurso_id);
        
        if (empty($salas_disponiveis)) {
            throw new Exception("Não há salas disponíveis para alocação.");
        }
        
        $alocacoes_realizadas = 0;
        $fiscal_index = 0;
        
        foreach ($salas_disponiveis as $sala) {
            // Alocar 2 fiscais por sala
            for ($i = 0; $i < 2; $i++) {
                if ($fiscal_index >= count($fiscais_aprovados)) {
                    break 2; // Não há mais fiscais disponíveis
                }
                
                $fiscal = $fiscais_aprovados[$fiscal_index];
                
                // Verificar se a sala ainda tem capacidade
                $fiscais_na_sala = countFiscaisNaSala($sala['id']);
                if ($fiscais_na_sala >= 2) {
                    continue; // Sala já tem 2 fiscais
                }
                
                // Criar alocação
                $alocacao_id = criarAlocacao($fiscal['id'], $sala['id']);
                
                if ($alocacao_id) {
                    $alocacoes_realizadas++;
                }
                
                $fiscal_index++;
            }
        }
        
        if ($alocacoes_realizadas > 0) {
            $message = "Alocação automática realizada com sucesso! $alocacoes_realizadas fiscais foram alocados.";
            logActivity("Alocação automática realizada: $alocacoes_realizadas fiscais alocados no concurso ID: $concurso_id", 'INFO');
        } else {
            $message = "Nenhuma alocação foi realizada. Verifique se há fiscais aprovados e salas disponíveis.";
        }
        
    } catch (Exception $e) {
        $error = 'Erro: ' . $e->getMessage();
        logActivity("Erro na alocação automática: " . $e->getMessage(), 'ERROR');
    }
}

// Obter concursos ativos
$concursos = getConcursosAtivos();

// Funções auxiliares
function getFiscaisAprovadosNaoAlocados($concurso_id) {
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("
            SELECT f.* FROM fiscais f 
            LEFT JOIN alocacoes a ON f.id = a.fiscal_id 
            WHERE f.concurso_id = ? AND f.status = 'aprovado' AND a.id IS NULL
            ORDER BY f.nome
        ");
        $stmt->execute([$concurso_id]);
        return $stmt->fetchAll();
    } else {
        // Fallback para CSV
        $fiscais = getFiscaisFromCSV();
        $alocacoes = getAlocacoesFromCSV();
        
        $fiscais_alocados = [];
        foreach ($alocacoes as $alocacao) {
            $fiscais_alocados[] = $alocacao['fiscal_id'];
        }
        
        $fiscais_disponiveis = [];
        foreach ($fiscais as $fiscal) {
            if ($fiscal['concurso_id'] == $concurso_id && 
                $fiscal['status'] == 'aprovado' && 
                !in_array($fiscal['id'], $fiscais_alocados)) {
                $fiscais_disponiveis[] = $fiscal;
            }
        }
        
        return $fiscais_disponiveis;
    }
}

function getSalasDisponiveis($concurso_id) {
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("
            SELECT s.*, e.nome as escola_nome 
            FROM salas s 
            JOIN escolas e ON s.escola_id = e.id 
            WHERE e.concurso_id = ? AND s.status = 'ativo'
            ORDER BY e.nome, s.nome
        ");
        $stmt->execute([$concurso_id]);
        return $stmt->fetchAll();
    } else {
        // Fallback para CSV
        $escolas = getEscolasFromCSV($concurso_id);
        $salas_disponiveis = [];
        
        foreach ($escolas as $escola) {
            $salas = getSalasFromCSV($escola['id']);
            foreach ($salas as $sala) {
                if ($sala['status'] == 'ativo') {
                    $sala['escola_nome'] = $escola['nome'];
                    $salas_disponiveis[] = $sala;
                }
            }
        }
        
        return $salas_disponiveis;
    }
}

function countFiscaisNaSala($sala_id) {
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM alocacoes WHERE sala_id = ?");
        $stmt->execute([$sala_id]);
        return $stmt->fetchColumn();
    } else {
        // Fallback para CSV
        $alocacoes = getAlocacoesFromCSV();
        $count = 0;
        foreach ($alocacoes as $alocacao) {
            if ($alocacao['sala_id'] == $sala_id) {
                $count++;
            }
        }
        return $count;
    }
}

function criarAlocacao($fiscal_id, $sala_id) {
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("
            INSERT INTO alocacoes (fiscal_id, sala_id, data_alocacao, status, created_at)
            VALUES (?, ?, CURRENT_TIMESTAMP, 'ativo', CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$fiscal_id, $sala_id]);
        return $db->lastInsertId();
    } else {
        // Fallback para CSV
        $csv_file = 'data/alocacoes.csv';
        
        // Criar arquivo se não existir
        if (!file_exists($csv_file)) {
            $header = "id,fiscal_id,sala_id,data_alocacao,status,created_at\n";
            file_put_contents($csv_file, $header);
        }
        
        // Gerar ID único
        $alocacoes = getAlocacoesFromCSV();
        $novo_id = 1;
        if (!empty($alocacoes)) {
            $novo_id = max(array_column($alocacoes, 'id')) + 1;
        }
        
        // Adicionar alocação ao CSV
        $handle = fopen($csv_file, 'a');
        $linha = [
            $novo_id,
            $fiscal_id,
            $sala_id,
            date('Y-m-d H:i:s'),
            'ativo',
            date('Y-m-d H:i:s')
        ];
        fputcsv($handle, $linha);
        fclose($handle);
        
        return $novo_id;
    }
}

function getAlocacoesFromCSV() {
    $csv_file = 'data/alocacoes.csv';
    $alocacoes = [];
    
    if (file_exists($csv_file)) {
        $handle = fopen($csv_file, 'r');
        $header = fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= count($header)) {
                $alocacao = array_combine($header, $data);
                $alocacoes[] = $alocacao;
            }
        }
        fclose($handle);
    }
    
    return $alocacoes;
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-magic"></i> 
                        Alocação Automática de Fiscais
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i>
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Como Funciona</h5>
                        <p>A alocação automática irá:</p>
                        <ul>
                            <li>Buscar fiscais com status "aprovado" que ainda não foram alocados</li>
                            <li>Distribuir 2 fiscais por sala automaticamente</li>
                            <li>Respeitar a capacidade máxima de cada sala</li>
                            <li>Alocar fiscais em ordem alfabética</li>
                        </ul>
                    </div>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="concurso_id"><i class="fas fa-graduation-cap"></i> Selecione o Concurso</label>
                            <select class="form-control" id="concurso_id" name="concurso_id" required>
                                <option value="">Escolha um concurso...</option>
                                <?php foreach ($concursos as $concurso): ?>
                                    <option value="<?= $concurso['id'] ?>">
                                        <?= htmlspecialchars($concurso['titulo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-magic"></i> Executar Alocação Automática
                            </button>
                            <a href="alocar_fiscais.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </a>
                        </div>
                    </form>

                    <!-- Estatísticas -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h5><i class="fas fa-users"></i></h5>
                                    <h4 id="fiscais-disponiveis">-</h4>
                                    <small>Fiscais Aprovados Disponíveis</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5><i class="fas fa-door-open"></i></h5>
                                    <h4 id="salas-disponiveis">-</h4>
                                    <small>Salas Disponíveis</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const concursoSelect = document.getElementById('concurso_id');
    
    concursoSelect.addEventListener('change', function() {
        const concursoId = this.value;
        if (concursoId) {
            // Atualizar estatísticas via AJAX
            fetch('get_estatisticas_alocacao.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `concurso_id=${concursoId}`
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('fiscais-disponiveis').textContent = data.fiscais_disponiveis || 0;
                document.getElementById('salas-disponiveis').textContent = data.salas_disponiveis || 0;
            })
            .catch(error => {
                console.error('Erro ao carregar estatísticas:', error);
            });
        } else {
            document.getElementById('fiscais-disponiveis').textContent = '-';
            document.getElementById('salas-disponiveis').textContent = '-';
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?> 