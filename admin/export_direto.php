<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Aceitar tanto GET quanto POST
$format = 'csv';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $format = $input['format'] ?? 'csv';
} else {
    $format = $_GET['format'] ?? 'csv';
}

try {
    $db = getDB();
    
    // Buscar todos os fiscais
    $stmt = $db->query("
        SELECT 
            id,
            nome,
            email,
            celular,
            cpf,
            data_nascimento,
            idade,
            status,
            endereco,
            observacoes,
            created_at,
            updated_at
        FROM fiscais 
        ORDER BY created_at DESC
    ");
    
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($format === 'csv') {
        // Gerar CSV
        $output = fopen('php://temp', 'w+');
        
        // Cabeçalho
        fputcsv($output, [
            'ID',
            'Nome',
            'Email',
            'Celular',
            'CPF',
            'Data Nascimento',
            'Idade',
            'Status',
            'Endereço',
            'Observações',
            'Data Cadastro',
            'Última Atualização'
        ]);
        
        // Dados
        foreach ($fiscais as $fiscal) {
            fputcsv($output, [
                $fiscal['id'],
                $fiscal['nome'],
                $fiscal['email'],
                formatPhone($fiscal['celular']),
                formatCPF($fiscal['cpf']),
                date('d/m/Y', strtotime($fiscal['data_nascimento'])),
                $fiscal['idade'],
                ucfirst($fiscal['status']),
                $fiscal['endereco'],
                $fiscal['observacoes'] ?? '',
                date('d/m/Y H:i', strtotime($fiscal['created_at'])),
                date('d/m/Y H:i', strtotime($fiscal['updated_at']))
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        // Se for GET, fazer download direto
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="fiscais_' . date('Y-m-d') . '.csv"');
            echo $csv;
            exit;
        }
        
        // Log da atividade
        logActivity("Exportação CSV realizada por " . $_SESSION['username'], 'INFO');
        
        echo json_encode([
            'success' => true,
            'data' => $csv
        ]);
        
    } elseif ($format === 'excel') {
        // Para Excel, vamos gerar um CSV que pode ser aberto no Excel
        $output = fopen('php://temp', 'w+');
        
        // BOM para UTF-8 (necessário para Excel)
        fwrite($output, "\xEF\xBB\xBF");
        
        // Cabeçalho
        fputcsv($output, [
            'ID',
            'Nome',
            'Email',
            'Celular',
            'CPF',
            'Data Nascimento',
            'Idade',
            'Status',
            'Endereço',
            'Observações',
            'Data Cadastro',
            'Última Atualização'
        ]);
        
        // Dados
        foreach ($fiscais as $fiscal) {
            fputcsv($output, [
                $fiscal['id'],
                $fiscal['nome'],
                $fiscal['email'],
                formatPhone($fiscal['celular']),
                formatCPF($fiscal['cpf']),
                date('d/m/Y', strtotime($fiscal['data_nascimento'])),
                $fiscal['idade'],
                ucfirst($fiscal['status']),
                $fiscal['endereco'],
                $fiscal['observacoes'] ?? '',
                date('d/m/Y H:i', strtotime($fiscal['created_at'])),
                date('d/m/Y H:i', strtotime($fiscal['updated_at']))
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        // Se for GET, fazer download direto
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="fiscais_' . date('Y-m-d') . '.xlsx"');
            echo $csv;
            exit;
        }
        
        // Log da atividade
        logActivity("Exportação Excel realizada por " . $_SESSION['username'], 'INFO');
        
        echo json_encode([
            'success' => true,
            'data' => $csv
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Formato não suportado'
        ]);
    }
    
} catch (Exception $e) {
    logActivity('Erro na exportação: ' . $e->getMessage(), 'ERROR');
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do sistema'
    ]);
}
?> 