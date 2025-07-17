<?php
// Gerar concurso fictício
function gerarConcurso() {
    $db = getDB();
    
    if ($db) {
        // Verificar se já existe um concurso de teste
        $stmt = $db->prepare("SELECT COUNT(*) FROM concursos WHERE titulo LIKE '%TESTE%' OR titulo LIKE '%FICTÍCIO%'");
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Já existe um concurso de teste. Remova-o primeiro.");
        }
        
        // Inserir concurso fictício
        $stmt = $db->prepare("
            INSERT INTO concursos (
                titulo, orgao, data_prova, horario_inicio, horario_fim, 
                valor_pagamento, vagas_disponiveis, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        $stmt->execute([
            'Concurso Público Municipal 2025 - TESTE',
            'Prefeitura Municipal',
            '2025-02-15',
            '08:00',
            '12:00',
            150.00,
            50,
            'ativo'
        ]);
        
        return $db->lastInsertId();
    } else {
        // Fallback para CSV
        $csv_file = 'data/concursos.csv';
        
        // Criar arquivo se não existir
        if (!file_exists($csv_file)) {
            $header = "id,titulo,orgao,data_prova,horario_inicio,horario_fim,valor_pagamento,vagas_disponiveis,status,created_at\n";
            file_put_contents($csv_file, $header);
        }
        
        // Gerar ID único
        $concursos = getConcursosFromCSV();
        $novo_id = 1;
        if (!empty($concursos)) {
            $novo_id = max(array_column($concursos, 'id')) + 1;
        }
        
        // Preparar linha para CSV
        $linha = [
            $novo_id,
            'Concurso Público Municipal 2025 - TESTE',
            'Prefeitura Municipal',
            '2025-02-15',
            '08:00',
            '12:00',
            150.00,
            50,
            'ativo',
            date('Y-m-d H:i:s')
        ];
        
        // Adicionar ao CSV
        $handle = fopen($csv_file, 'a');
        fputcsv($handle, $linha);
        fclose($handle);
        
        return $novo_id;
    }
}

// Executar geração
$concurso_id = gerarConcurso();
logActivity("Concurso fictício gerado com ID: $concurso_id", 'INFO');
?> 