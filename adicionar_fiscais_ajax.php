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
    // Buscar concurso ativo
    $stmt = $db->query("SELECT id FROM concursos WHERE status = 'ativo' LIMIT 1");
    $concurso = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$concurso) {
        echo json_encode(['success' => false, 'message' => 'Nenhum concurso ativo encontrado']);
        exit;
    }
    
    $concurso_id = $concurso['id'];
    
    // Fiscais de teste
    $fiscais_teste = [
        [
            'nome' => 'João Silva',
            'email' => 'joao.silva@email.com',
            'celular' => '11987654321',
            'cpf' => '12345678901',
            'data_nascimento' => '1990-05-15',
            'idade' => 33
        ],
        [
            'nome' => 'Maria Santos',
            'email' => 'maria.santos@email.com',
            'celular' => '11987654322',
            'cpf' => '12345678902',
            'data_nascimento' => '1985-08-20',
            'idade' => 38
        ],
        [
            'nome' => 'Pedro Oliveira',
            'email' => 'pedro.oliveira@email.com',
            'celular' => '11987654323',
            'cpf' => '12345678903',
            'data_nascimento' => '1995-03-10',
            'idade' => 28
        ],
        [
            'nome' => 'Ana Costa',
            'email' => 'ana.costa@email.com',
            'celular' => '11987654324',
            'cpf' => '12345678904',
            'data_nascimento' => '1980-12-25',
            'idade' => 43
        ],
        [
            'nome' => 'Carlos Ferreira',
            'email' => 'carlos.ferreira@email.com',
            'celular' => '11987654325',
            'cpf' => '12345678905',
            'data_nascimento' => '1992-07-08',
            'idade' => 31
        ]
    ];
    
    $fiscais_adicionados = 0;
    
    foreach ($fiscais_teste as $fiscal) {
        try {
            $stmt = $db->prepare("
                INSERT INTO fiscais (
                    nome, email, celular, cpf, data_nascimento, idade, 
                    concurso_id, status, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, 'ativo', NOW(), NOW()
                )
            ");
            
            $stmt->execute([
                $fiscal['nome'],
                $fiscal['email'],
                $fiscal['celular'],
                $fiscal['cpf'],
                $fiscal['data_nascimento'],
                $fiscal['idade'],
                $concurso_id
            ]);
            
            $fiscais_adicionados++;
        } catch (Exception $e) {
            // Ignorar erros de duplicação
            continue;
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "{$fiscais_adicionados} fiscais de teste foram adicionados com sucesso!"
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?> 