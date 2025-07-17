<?php
// Gerar salas fictícias
function gerarSalas() {
    $db = getDB();
    
    // Obter concurso de teste
    $concursos = getConcursosAtivos();
    $concurso_teste = null;
    
    foreach ($concursos as $concurso) {
        if (strpos($concurso['titulo'], 'TESTE') !== false || strpos($concurso['titulo'], 'FICTÍCIO') !== false) {
            $concurso_teste = $concurso;
            break;
        }
    }
    
    if (!$concurso_teste) {
        throw new Exception("Nenhum concurso de teste encontrado. Gere um concurso primeiro.");
    }
    
    // Obter escolas do concurso (usando função que busca do banco)
    $escolas = getEscolasByConcurso($concurso_teste['id']);
    if (empty($escolas)) {
        throw new Exception("Nenhuma escola encontrada para este concurso. Gere escolas primeiro.");
    }
    
    $salas_criadas = [];
    
    foreach ($escolas as $escola) {
        // Para cada escola, criar 5 locais: 3 salas + 1 corredor + 1 portaria
        $locais = [
            [
                'nome' => 'Sala 101',
                'tipo' => 'sala_aula',
                'capacidade' => 30,
                'descricao' => 'Sala de aula - 1º andar'
            ],
            [
                'nome' => 'Sala 102',
                'tipo' => 'sala_aula',
                'capacidade' => 30,
                'descricao' => 'Sala de aula - 1º andar'
            ],
            [
                'nome' => 'Sala 103',
                'tipo' => 'sala_aula',
                'capacidade' => 30,
                'descricao' => 'Sala de aula - 1º andar'
            ],
            [
                'nome' => 'Corredor Principal',
                'tipo' => 'sala_aula',
                'capacidade' => 50,
                'descricao' => 'Corredor de acesso às salas'
            ],
            [
                'nome' => 'Portaria',
                'tipo' => 'sala_aula',
                'capacidade' => 20,
                'descricao' => 'Entrada principal da escola'
            ]
        ];
        
        if ($db) {
            // Verificar se já existem salas de teste para esta escola
            $stmt = $db->prepare("SELECT COUNT(*) FROM salas WHERE escola_id = ?");
            $stmt->execute([$escola['id']]);
            if ($stmt->fetchColumn() > 0) {
                continue; // Pular esta escola se já tem salas
            }
            
            // Inserir salas
            $stmt = $db->prepare("
                INSERT INTO salas (
                    escola_id, nome, tipo, capacidade, descricao, 
                    status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            
            foreach ($locais as $local) {
                $stmt->execute([
                    $escola['id'],
                    $local['nome'],
                    $local['tipo'],
                    $local['capacidade'],
                    $local['descricao'],
                    'ativo'
                ]);
                $salas_criadas[] = $db->lastInsertId();
            }
        } else {
            // Fallback para CSV
            $csv_file = 'data/salas.csv';
            
            // Criar arquivo se não existir
            if (!file_exists($csv_file)) {
                $header = "id,escola_id,nome,tipo,capacidade,descricao,status,created_at\n";
                file_put_contents($csv_file, $header);
            }
            
            // Verificar se já existem salas de teste para esta escola
            $salas_existentes = getSalasFromCSV($escola['id']);
            if (!empty($salas_existentes)) {
                continue; // Pular esta escola se já tem salas
            }
            
            // Gerar IDs únicos
            $todas_salas = getSalasFromCSV(0); // Todas as salas
            $novo_id = 1;
            if (!empty($todas_salas)) {
                $novo_id = max(array_column($todas_salas, 'id')) + 1;
            }
            
            // Adicionar salas ao CSV
            $handle = fopen($csv_file, 'a');
            foreach ($locais as $local) {
                $linha = [
                    $novo_id,
                    $escola['id'],
                    $local['nome'],
                    $local['tipo'],
                    $local['capacidade'],
                    $local['descricao'],
                    'ativo',
                    date('Y-m-d H:i:s')
                ];
                fputcsv($handle, $linha);
                $salas_criadas[] = $novo_id;
                $novo_id++;
            }
            fclose($handle);
        }
    }
    
    return $salas_criadas;
}

// Executar geração
$salas_ids = gerarSalas();
logActivity("Salas fictícias geradas: " . implode(', ', $salas_ids), 'INFO');
?> 