<?php
// Gerar fiscais fictícios
function gerarFiscais() {
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
    
    // Obter escolas do banco de dados
    $escolas = getEscolasByConcurso($concurso_teste['id']);
    if (empty($escolas)) {
        throw new Exception("Nenhuma escola encontrada para este concurso. Gere escolas primeiro.");
    }
    
    $fiscais_criados = [];
    
    // Nomes fictícios para fiscais
    $nomes_masculinos = [
        'João Silva', 'Pedro Santos', 'Carlos Oliveira', 'Roberto Lima', 'Marcos Costa',
        'André Pereira', 'Ricardo Ferreira', 'Fernando Almeida', 'Lucas Rodrigues', 'Daniel Souza',
        'Paulo Martins', 'Thiago Barbosa', 'Rafael Cardoso', 'Bruno Gomes', 'Felipe Santos',
        'Alexandre Silva', 'Vinícius Costa', 'Matheus Oliveira', 'Gabriel Lima', 'Leonardo Santos',
        'Diego Pereira', 'Rodrigo Ferreira', 'Eduardo Almeida', 'Fábio Rodrigues', 'Guilherme Souza'
    ];
    
    $nomes_femininos = [
        'Maria Silva', 'Ana Santos', 'Juliana Oliveira', 'Fernanda Lima', 'Patrícia Costa',
        'Camila Pereira', 'Carolina Ferreira', 'Amanda Almeida', 'Beatriz Rodrigues', 'Isabela Souza',
        'Gabriela Martins', 'Larissa Barbosa', 'Mariana Cardoso', 'Natália Gomes', 'Vanessa Santos',
        'Priscila Silva', 'Renata Costa', 'Daniela Oliveira', 'Monique Lima', 'Tatiane Santos',
        'Bianca Pereira', 'Raquel Ferreira', 'Cristiane Almeida', 'Luciana Rodrigues', 'Simone Souza'
    ];
    
    $emails = [
        'joao.silva', 'pedro.santos', 'carlos.oliveira', 'roberto.lima', 'marcos.costa',
        'andre.pereira', 'ricardo.ferreira', 'fernando.almeida', 'lucas.rodrigues', 'daniel.souza',
        'paulo.martins', 'thiago.barbosa', 'rafael.cardoso', 'bruno.gomes', 'felipe.santos',
        'alexandre.silva', 'vinicius.costa', 'matheus.oliveira', 'gabriel.lima', 'leonardo.santos',
        'diego.pereira', 'rodrigo.ferreira', 'eduardo.almeida', 'fabio.rodrigues', 'guilherme.souza',
        'maria.silva', 'ana.santos', 'juliana.oliveira', 'fernanda.lima', 'patricia.costa',
        'camila.pereira', 'carolina.ferreira', 'amanda.almeida', 'beatriz.rodrigues', 'isabela.souza',
        'gabriela.martins', 'larissa.barbosa', 'mariana.cardoso', 'natalia.gomes', 'vanessa.santos',
        'priscila.silva', 'renata.costa', 'daniela.oliveira', 'monique.lima', 'tatiane.santos',
        'bianca.pereira', 'raquel.ferreira', 'cristiane.almeida', 'luciana.rodrigues', 'simone.souza'
    ];
    
    $cpfs = [
        '111.444.777-35', '222.555.888-46', '333.666.999-57', '444.777.000-68', '555.888.111-79',
        '666.999.222-80', '777.000.333-91', '888.111.444-02', '999.222.555-13', '000.333.666-24',
        '111.444.777-35', '222.555.888-46', '333.666.999-57', '444.777.000-68', '555.888.111-79',
        '666.999.222-80', '777.000.333-91', '888.111.444-02', '999.222.555-13', '000.333.666-24',
        '111.444.777-35', '222.555.888-46', '333.666.999-57', '444.777.000-68', '555.888.111-79',
        '666.999.222-80', '777.000.333-91', '888.111.444-02', '999.222.555-13', '000.333.666-24',
        '111.444.777-35', '222.555.888-46', '333.666.999-57', '444.777.000-68', '555.888.111-79',
        '666.999.222-80', '777.000.333-91', '888.111.444-02', '999.222.555-13', '000.333.666-24',
        '111.444.777-35', '222.555.888-46', '333.666.999-57', '444.777.000-68', '555.888.111-79',
        '666.999.222-80', '777.000.333-91', '888.111.444-02', '999.222.555-13', '000.333.666-24'
    ];
    
    $fiscal_index = 0;
    
    foreach ($escolas as $escola) {
        // Buscar salas do banco de dados
        $salas = getSalasByEscola($escola['id']);
        
        foreach ($salas as $sala) {
            // Criar 2 fiscais para cada sala
            for ($i = 1; $i <= 2; $i++) {
                $nome = $fiscal_index < 25 ? $nomes_masculinos[$fiscal_index] : $nomes_femininos[$fiscal_index - 25];
                $email = $emails[$fiscal_index] . '@teste.com';
                $cpf = preg_replace('/[^0-9]/', '', $cpfs[$fiscal_index]);
                $genero = $fiscal_index < 25 ? 'M' : 'F';
                
                $fiscal = [
                    'concurso_id' => $concurso_teste['id'],
                    'nome' => $nome,
                    'email' => $email,
                    'ddi' => '+55',
                    'celular' => '(11) 99999-' . str_pad($fiscal_index + 1, 4, '0', STR_PAD_LEFT),
                    'whatsapp' => '(11) 99999-' . str_pad($fiscal_index + 1, 4, '0', STR_PAD_LEFT),
                    'cpf' => $cpf,
                    'data_nascimento' => '198' . rand(0, 9) . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT),
                    'genero' => $genero,
                    'endereco' => 'Rua Teste, ' . rand(1, 999) . ' - Bairro Teste - Cidade Teste - SP',
                    'melhor_horario' => ['manha', 'tarde', 'noite', 'qualquer'][rand(0, 3)],
                    'observacoes' => 'Fiscal de teste - ' . $sala['nome'] . ' - ' . $escola['nome'],
                    'status' => 'aprovado',
                    'status_contato' => 'contatado',
                    'aceite_termos' => 1,
                    'data_aceite_termos' => date('Y-m-d H:i:s'),
                    'ip_cadastro' => '127.0.0.1',
                    'user_agent' => 'Gerador de Dados Fictícios'
                ];
                
                if ($db) {
                    // Verificar se já existem fiscais de teste
                    $stmt = $db->prepare("SELECT COUNT(*) FROM fiscais WHERE concurso_id = ? AND nome LIKE '%TESTE%'");
                    $stmt->execute([$concurso_teste['id']]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception("Já existem fiscais de teste para este concurso. Remova-os primeiro.");
                    }
                    
                    // Inserir fiscal
                    $stmt = $db->prepare("
                        INSERT INTO fiscais (
                            concurso_id, nome, email, ddi, celular, whatsapp, cpf, 
                            data_nascimento, genero, endereco, melhor_horario, observacoes, 
                            status, status_contato, aceite_termos, data_aceite_termos, 
                            ip_cadastro, user_agent, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                    ");
                    
                    $stmt->execute([
                        $fiscal['concurso_id'], $fiscal['nome'], $fiscal['email'], 
                        $fiscal['ddi'], $fiscal['celular'], $fiscal['whatsapp'], $fiscal['cpf'],
                        $fiscal['data_nascimento'], $fiscal['genero'], $fiscal['endereco'],
                        $fiscal['melhor_horario'], $fiscal['observacoes'], $fiscal['status'],
                        $fiscal['status_contato'], $fiscal['aceite_termos'], $fiscal['data_aceite_termos'],
                        $fiscal['ip_cadastro'], $fiscal['user_agent']
                    ]);
                    
                    $fiscais_criados[] = $db->lastInsertId();
                }
                
                $fiscal_index++;
            }
        }
    }
    
    return $fiscais_criados;
}

// Executar geração
$fiscais_ids = gerarFiscais();
logActivity("Fiscais fictícios gerados: " . implode(', ', $fiscais_ids), 'INFO');
?> 