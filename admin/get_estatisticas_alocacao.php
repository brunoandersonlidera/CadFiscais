<?php
require_once '../config.php';

// Verificar se o usuário está logado e é administrador
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Verificar se o parâmetro concurso_id foi enviado
if (!isset($_POST['concurso_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Concurso ID não fornecido']);
    exit;
}

try {
    $concurso_id = (int)$_POST['concurso_id'];
    
    // Funções auxiliares (copiadas do arquivo principal)
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
    
    // Obter estatísticas
    $fiscais_disponiveis = getFiscaisAprovadosNaoAlocados($concurso_id);
    $salas_disponiveis = getSalasDisponiveis($concurso_id);
    
    $response = [
        'fiscais_disponiveis' => count($fiscais_disponiveis),
        'salas_disponiveis' => count($salas_disponiveis),
        'success' => true
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor: ' . $e->getMessage()]);
    logActivity('Erro ao obter estatísticas de alocação: ' . $e->getMessage(), 'ERROR');
}
?> 