<?php
// Gerar escolas fictícias
function gerarEscolas() {
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
    
    $escolas = [
        [
            'nome' => 'Escola Municipal Professor João Silva',
            'endereco' => 'Rua das Flores, 123 - Centro',
            'telefone' => '(11) 3333-1111',
            'responsavel' => 'Maria Santos',
            'capacidade' => 500
        ],
        [
            'nome' => 'Escola Municipal Dona Ana Costa',
            'endereco' => 'Av. Principal, 456 - Jardim',
            'telefone' => '(11) 3333-2222',
            'responsavel' => 'João Oliveira',
            'capacidade' => 400
        ],
        [
            'nome' => 'Escola Municipal São José',
            'endereco' => 'Rua São José, 789 - Vila',
            'telefone' => '(11) 3333-3333',
            'responsavel' => 'Pedro Lima',
            'capacidade' => 350
        ],
        [
            'nome' => 'Escola Municipal Santa Maria',
            'endereco' => 'Av. Santa Maria, 321 - Bairro',
            'telefone' => '(11) 3333-4444',
            'responsavel' => 'Ana Pereira',
            'capacidade' => 450
        ],
        [
            'nome' => 'Escola Municipal Dom Pedro',
            'endereco' => 'Rua Dom Pedro, 654 - Centro',
            'telefone' => '(11) 3333-5555',
            'responsavel' => 'Carlos Ferreira',
            'capacidade' => 380
        ]
    ];
    
    $escolas_criadas = [];
    
    if ($db) {
        // Verificar se já existem escolas de teste
        $stmt = $db->prepare("SELECT COUNT(*) FROM escolas WHERE concurso_id = ?");
        $stmt->execute([$concurso_teste['id']]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Já existem escolas de teste para este concurso. Remova-as primeiro.");
        }
        
        // Inserir escolas
        $stmt = $db->prepare("
            INSERT INTO escolas (
                concurso_id, nome, endereco, telefone, responsavel, 
                capacidade, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        foreach ($escolas as $escola) {
            $stmt->execute([
                $concurso_teste['id'],
                $escola['nome'],
                $escola['endereco'],
                $escola['telefone'],
                $escola['responsavel'],
                $escola['capacidade'],
                'ativo'
            ]);
            $escolas_criadas[] = $db->lastInsertId();
        }
    } else {
        // Fallback para CSV
        $csv_file = 'data/escolas.csv';
        
        // Criar arquivo se não existir
        if (!file_exists($csv_file)) {
            $header = "id,concurso_id,nome,endereco,telefone,responsavel,capacidade,status,created_at\n";
            file_put_contents($csv_file, $header);
        }
        
        // Verificar se já existem escolas de teste
        $escolas_existentes = getEscolasFromCSV($concurso_teste['id']);
        if (!empty($escolas_existentes)) {
            throw new Exception("Já existem escolas de teste para este concurso. Remova-as primeiro.");
        }
        
        // Gerar IDs únicos
        $todas_escolas = getEscolasFromCSV(0); // Todas as escolas
        $novo_id = 1;
        if (!empty($todas_escolas)) {
            $novo_id = max(array_column($todas_escolas, 'id')) + 1;
        }
        
        // Adicionar escolas ao CSV
        $handle = fopen($csv_file, 'a');
        foreach ($escolas as $escola) {
            $linha = [
                $novo_id,
                $concurso_teste['id'],
                $escola['nome'],
                $escola['endereco'],
                $escola['telefone'],
                $escola['responsavel'],
                $escola['capacidade'],
                'ativo',
                date('Y-m-d H:i:s')
            ];
            fputcsv($handle, $linha);
            $escolas_criadas[] = $novo_id;
            $novo_id++;
        }
        fclose($handle);
    }
    
    return $escolas_criadas;
}

// Executar geração
$escolas_ids = gerarEscolas();
logActivity("Escolas fictícias geradas: " . implode(', ', $escolas_ids), 'INFO');
?> 