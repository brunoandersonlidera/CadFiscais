<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$fiscal_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDB();

if (!$fiscal_id) {
    showMessage('ID do fiscal não fornecido', 'error');
    redirect('fiscais.php');
}

// Buscar dados do fiscal
try {
    $stmt = $db->prepare("
        SELECT f.*, c.titulo as concurso_titulo
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        WHERE f.id = ?
    ");
    $stmt->execute([$fiscal_id]);
    $fiscal = $stmt->fetch();
    
    if (!$fiscal) {
        showMessage('Fiscal não encontrado', 'error');
        redirect('fiscais.php');
    }
} catch (Exception $e) {
    logActivity('Erro ao buscar fiscal: ' . $e->getMessage(), 'ERROR');
    showMessage('Erro ao buscar fiscal', 'error');
    redirect('fiscais.php');
}

// Buscar escolas disponíveis
$escolas = [];
try {
    $stmt = $db->query("SELECT * FROM escolas WHERE status = 'ativo' ORDER BY nome");
    $escolas = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar escolas: ' . $e->getMessage(), 'ERROR');
}

// Buscar salas disponíveis
$salas = [];
try {
    $stmt = $db->query("SELECT * FROM salas WHERE status = 'ativo' ORDER BY nome");
    $salas = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar salas: ' . $e->getMessage(), 'ERROR');
}

$pageTitle = 'Alocar Fiscal';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-map-marker-alt me-2"></i>
                Alocar Fiscal
            </h1>
            <a href="fiscais.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Voltar
            </a>
        </div>
    </div>
</div>

<!-- Informações do Fiscal -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>
                    Informações do Fiscal
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nome:</strong> <?= htmlspecialchars($fiscal['nome']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($fiscal['email']) ?></p>
                        <p><strong>CPF:</strong> <?= formatCPF($fiscal['cpf']) ?></p>
                        <p><strong>Celular:</strong> <?= formatPhone($fiscal['celular']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Concurso:</strong> <?= htmlspecialchars($fiscal['concurso_titulo']) ?></p>
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?= getStatusColor($fiscal['status']) ?>">
                                <?= ucfirst($fiscal['status']) ?>
                            </span>
                        </p>
                        <p><strong>Data Cadastro:</strong> <?= date('d/m/Y H:i', strtotime($fiscal['created_at'])) ?></p>
                        <p><strong>Endereço:</strong> <?= htmlspecialchars($fiscal['endereco']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulário de Alocação -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-building me-2"></i>
                    Alocação em Escola e Sala
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="salvar_alocacao.php">
                    <input type="hidden" name="fiscal_id" value="<?= $fiscal_id ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="escola_id" class="form-label">Escola *</label>
                                <select class="form-select" id="escola_id" name="escola_id" required>
                                    <option value="">Selecione uma escola</option>
                                    <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>">
                                        <?= htmlspecialchars($escola['nome']) ?> - 
                                        <?= htmlspecialchars($escola['endereco']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sala_id" class="form-label">Sala *</label>
                                <select class="form-select" id="sala_id" name="sala_id" required>
                                    <option value="">Selecione primeiro uma escola</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tipo_alocacao" class="form-label">Tipo de Alocação</label>
                                <select class="form-select" id="tipo_alocacao" name="tipo_alocacao">
                                    <option value="sala">Sala de Aula</option>
                                    <option value="corredor">Corredor</option>
                                    <option value="entrada">Entrada/Saída</option>
                                    <option value="banheiro">Banheiro</option>
                                    <option value="outro">Outro</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="observacoes_alocacao" class="form-label">Observações</label>
                                <textarea class="form-control" id="observacoes_alocacao" name="observacoes_alocacao" rows="3" 
                                          placeholder="Observações sobre a alocação..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="data_alocacao" class="form-label">Data da Alocação</label>
                                <input type="date" class="form-control" id="data_alocacao" name="data_alocacao" 
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="horario_alocacao" class="form-label">Horário</label>
                                <input type="time" class="form-control" id="horario_alocacao" name="horario_alocacao" 
                                       value="07:00" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" class="btn btn-secondary" onclick="history.back()">
                            <i class="fas fa-times me-2"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>
                            Salvar Alocação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Histórico de Alocações -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Histórico de Alocações
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="historicoTable">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Escola</th>
                                <th>Sala</th>
                                <th>Tipo</th>
                                <th>Observações</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Buscar histórico de alocações
                            try {
                                $stmt = $db->prepare("
                                    SELECT a.*, e.nome as escola_nome, s.nome as sala_nome
                                    FROM alocacoes_fiscais a
                                    LEFT JOIN escolas e ON a.escola_id = e.id
                                    LEFT JOIN salas s ON a.sala_id = s.id
                                    WHERE a.fiscal_id = ?
                                    ORDER BY a.data_alocacao DESC, a.horario_alocacao DESC
                                ");
                                $stmt->execute([$fiscal_id]);
                                $alocacoes = $stmt->fetchAll();
                                
                                foreach ($alocacoes as $alocacao):
                            ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($alocacao['data_alocacao'] . ' ' . $alocacao['horario_alocacao'])) ?></td>
                                <td><?= htmlspecialchars($alocacao['escola_nome']) ?></td>
                                <td><?= htmlspecialchars($alocacao['sala_nome']) ?></td>
                                <td><?= ucfirst($alocacao['tipo_alocacao']) ?></td>
                                <td><?= htmlspecialchars($alocacao['observacoes']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $alocacao['status'] == 'ativo' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($alocacao['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-warning" 
                                                onclick="editarAlocacao(<?= $alocacao['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="removerAlocacao(<?= $alocacao['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endforeach;
                            } catch (Exception $e) {
                                echo "<tr><td colspan='7' class='text-center'>Nenhuma alocação encontrada</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable do histórico
    $('#historicoTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        responsive: true,
        pageLength: 10,
        order: [[0, 'desc']],
        columnDefs: [
            { targets: [6], orderable: false, searchable: false }
        ]
    });
    
    // Carregar salas quando escola for selecionada
    $('#escola_id').on('change', function() {
        const escola_id = $(this).val();
        const salaSelect = $('#sala_id');
        
        salaSelect.html('<option value="">Carregando...</option>');
        
        if (escola_id) {
            fetch(`get_salas.php?escola_id=${escola_id}`)
                .then(response => response.json())
                .then(data => {
                    salaSelect.html('<option value="">Selecione uma sala</option>');
                    data.forEach(sala => {
                        salaSelect.append(`<option value="${sala.id}">${sala.nome}</option>`);
                    });
                })
                .catch(error => {
                    salaSelect.html('<option value="">Erro ao carregar salas</option>');
                });
        } else {
            salaSelect.html('<option value="">Selecione primeiro uma escola</option>');
        }
    });
    
    // Validação simples do formulário (opcional)
    $('form').on('submit', function() {
        const escola_id = $('#escola_id').val();
        const sala_id = $('#sala_id').val();
        
        if (!escola_id || !sala_id) {
            showMessage('Por favor, selecione uma escola e uma sala', 'error');
            return false;
        }
        
        showLoading();
        return true; // Permite o envio normal do formulário
    });
});

function editarAlocacao(id) {
    window.location.href = `editar_alocacao.php?id=${id}`;
}

function removerAlocacao(id) {
    confirmAction('Tem certeza que deseja remover esta alocação?', function() {
        showLoading();
        
        fetch('remover_alocacao.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showMessage('Alocação removida com sucesso!');
                setTimeout(() => location.reload(), 1500);
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showMessage('Erro ao remover alocação', 'error');
        });
    });
}
</script>

<?php 
// Funções auxiliares
function formatPhone($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) === 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
    }
    return $phone;
}

function formatCPF($cpf) {
    $cpf = preg_replace('/\D/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

function getStatusColor($status) {
    switch ($status) {
        case 'aprovado': return 'success';
        case 'pendente': return 'warning';
        case 'reprovado': return 'danger';
        case 'cancelado': return 'secondary';
        default: return 'secondary';
    }
}

include '../includes/footer.php'; 
?> 