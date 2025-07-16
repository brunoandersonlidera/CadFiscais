<?php
require_once '../config.php';

header('Content-Type: application/json');

$db = getDB();

// Se receber concurso_id via GET, retorna todas as escolas desse concurso
if (isset($_GET['concurso_id'])) {
    $concurso_id = (int)$_GET['concurso_id'];
    $sql = "SELECT id, nome FROM escolas WHERE status = 'ativo' AND concurso_id = ? ORDER BY nome";
    $stmt = $db->prepare($sql);
    $stmt->execute([$concurso_id]);
    $escolas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($escolas);
    exit;
}

// Se não receber concurso_id, retorna todas as escolas ativas
if (!isset($_GET['concurso_id'])) {
    $sql = "SELECT id, nome FROM escolas WHERE status = 'ativo' ORDER BY nome";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $escolas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($escolas);
    exit;
}

// Compatibilidade: busca por escola_id via POST (uso antigo)
$input = json_decode(file_get_contents('php://input'), true);
$escola_id = isset($input['escola_id']) ? (int)$input['escola_id'] : null;

if ($escola_id) {
    $sql = "SELECT * FROM escolas WHERE id = ? AND status = 'ativo'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$escola_id]);
    $escola = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($escola) {
        echo json_encode($escola);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Escola não encontrada']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID da escola não fornecido']);
}
?> 