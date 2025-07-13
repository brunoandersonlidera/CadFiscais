<?php
require_once '../config.php';

// Verificar se está logado e é admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

// Verificar se o ID foi fornecido
$input = json_decode(file_get_contents('php://input'), true);
$fiscal_id = isset($input['id']) ? (int)$input['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if (!$fiscal_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do fiscal não fornecido']);
    exit;
}

try {
    $db = getDB();
    
    // Buscar dados do fiscal
    $stmt = $db->prepare("
        SELECT f.*, c.titulo as concurso_nome, c.ano_concurso as concurso_ano
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        WHERE f.id = ?
    ");
    $stmt->execute([$fiscal_id]);
    $fiscal = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$fiscal) {
        http_response_code(404);
        echo json_encode(['error' => 'Fiscal não encontrado']);
        exit;
    }
    
    // Buscar alocações do fiscal
    $stmt = $db->prepare("
        SELECT a.*, e.nome as escola_nome, s.nome as sala_nome
        FROM alocacoes_fiscais a
        LEFT JOIN salas s ON a.sala_id = s.id
        LEFT JOIN escolas e ON s.escola_id = e.id
        WHERE a.fiscal_id = ?
        ORDER BY e.nome, s.nome
    ");
    $stmt->execute([$fiscal_id]);
    $alocacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar presença do fiscal
    $stmt = $db->prepare("
        SELECT p.*, c.titulo as concurso_nome
        FROM presenca_fiscais p
        LEFT JOIN concursos c ON p.concurso_id = c.id
        WHERE p.fiscal_id = ?
        ORDER BY p.concurso_id, p.created_at
    ");
    $stmt->execute([$fiscal_id]);
    $presencas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar pagamentos do fiscal
    $stmt = $db->prepare("
        SELECT pag.*, c.titulo as concurso_nome
        FROM pagamentos_fiscais pag
        LEFT JOIN concursos c ON pag.concurso_id = c.id
        WHERE pag.fiscal_id = ?
        ORDER BY pag.concurso_id, pag.created_at
    ");
    $stmt->execute([$fiscal_id]);
    $pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Preparar resposta
    $response = [
        'fiscal' => $fiscal,
        'alocacoes' => $alocacoes,
        'presencas' => $presencas,
        'pagamentos' => $pagamentos
    ];
    
    // Definir cabeçalhos para JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
?> 