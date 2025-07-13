<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $concurso_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$concurso_id) {
        echo json_encode(['success' => false, 'error' => 'ID do concurso não fornecido']);
        exit;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT titulo, termos_aceite FROM concursos WHERE id = ?");
    $stmt->execute([$concurso_id]);
    $concurso = $stmt->fetch();
    
    if (!$concurso) {
        echo json_encode(['success' => false, 'error' => 'Concurso não encontrado']);
        exit;
    }
    
    if (empty($concurso['termos_aceite'])) {
        echo json_encode(['success' => false, 'error' => 'Este concurso não possui termos de aceite configurados']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'titulo' => $concurso['titulo'],
        'termos' => $concurso['termos_aceite']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 