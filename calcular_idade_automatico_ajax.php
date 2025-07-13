<?php
require_once 'config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não está logado']);
    exit;
}

$db = getDB();

try {
    // Buscar fiscais com data de nascimento
    $stmt = $db->query("
        SELECT id, data_nascimento
        FROM fiscais 
        WHERE data_nascimento IS NOT NULL AND data_nascimento != ''
    ");
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $fiscais_atualizados = 0;
    $hoje = new DateTime();
    
    foreach ($fiscais as $fiscal) {
        try {
            $data_nasc = new DateTime($fiscal['data_nascimento']);
            $idade = $hoje->diff($data_nasc)->y;
            
            // Atualizar apenas a idade
            $stmt = $db->prepare("
                UPDATE fiscais 
                SET idade = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$idade, $fiscal['id']]);
            
            $fiscais_atualizados++;
        } catch (Exception $e) {
            // Ignorar datas inválidas
            continue;
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "{$fiscais_atualizados} fiscais tiveram a idade calculada com sucesso!"
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?> 