<?php
require_once 'config.php';

echo "<h1>Criação de Dados de Teste</h1>";

try {
    $db = getDB();
    if (!$db) {
        throw new Exception("Erro de conexão com banco de dados");
    }

    // Evitar duplicidade: remover concurso de teste anterior
    $db->exec("DELETE FROM concursos WHERE titulo = 'Concurso Público para Fiscais de Prova'");

    // 1. Criar Concurso
    echo "<h2>1. Criando Concurso...</h2>";
    $stmt = $db->prepare("
        INSERT INTO concursos (
            titulo, orgao, numero_concurso, ano_concurso, cidade, estado, 
            data_prova, horario_inicio, horario_fim, valor_pagamento, 
            vagas_disponiveis, status, descricao, termos_aceite
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        'Concurso Público para Fiscais de Prova',
        'Instituto Dignidade Humana',
        '001/2024',
        2024,
        'São Paulo',
        'SP',
        '2024-12-15',
        '08:00:00',
        '12:00:00',
        150.00,
        200,
        'ativo',
        'Concurso público para seleção de fiscais para aplicação de provas.',
        'Ao se inscrever, o candidato concorda com os termos e condições estabelecidos.'
    ]);
    $concurso_id = $db->lastInsertId();
    echo "<p>✓ Concurso criado com ID: $concurso_id</p>";

    // 2. Criar 5 Escolas
    echo "<h2>2. Criando Escolas...</h2>";
    $escolas = [
        [
            'nome' => 'Escola Estadual Professor João Silva',
            'endereco' => 'Rua das Flores, 123 - Centro',
            'telefone' => '(11) 3333-4444',
            'email' => 'escola.joao.silva@edu.sp.gov.br',
            'responsavel' => 'Maria Santos',
            'coordenador_idh' => 'Carlos Oliveira',
            'coordenador_comissao' => 'Ana Paula Costa'
        ],
        [
            'nome' => 'Colégio Municipal São José',
            'endereco' => 'Av. Principal, 456 - Jardim São José',
            'telefone' => '(11) 4444-5555',
            'email' => 'colegio.saojose@prefeitura.sp.gov.br',
            'responsavel' => 'José Pereira',
            'coordenador_idh' => 'Roberto Almeida',
            'coordenador_comissao' => 'Fernanda Lima'
        ],
        [
            'nome' => 'Instituto Educacional Futuro',
            'endereco' => 'Rua do Comércio, 789 - Vila Industrial',
            'telefone' => '(11) 5555-6666',
            'email' => 'contato@institutofuturo.com.br',
            'responsavel' => 'Pedro Mendes',
            'coordenador_idh' => 'Lucia Ferreira',
            'coordenador_comissao' => 'Marcos Rodrigues'
        ],
        [
            'nome' => 'Centro Educacional Santa Maria',
            'endereco' => 'Travessa da Paz, 321 - Bairro Santa Maria',
            'telefone' => '(11) 6666-7777',
            'email' => 'santamaria@educacao.com.br',
            'responsavel' => 'Claudia Souza',
            'coordenador_idh' => 'Ricardo Santos',
            'coordenador_comissao' => 'Patricia Oliveira'
        ],
        [
            'nome' => 'Escola Técnica Municipal',
            'endereco' => 'Av. Tecnologia, 654 - Parque Industrial',
            'telefone' => '(11) 7777-8888',
            'email' => 'etm@prefeitura.sp.gov.br',
            'responsavel' => 'Antonio Costa',
            'coordenador_idh' => 'Silvia Martins',
            'coordenador_comissao' => 'João Carlos Silva'
        ]
    ];
    $escola_ids = [];
    foreach ($escolas as $escola) {
        $stmt = $db->prepare("
            INSERT INTO escolas (
                concurso_id, nome, endereco, telefone, email, responsavel,
                coordenador_idh, coordenador_comissao, tipo, capacidade, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $concurso_id,
            $escola['nome'],
            $escola['endereco'],
            $escola['telefone'],
            $escola['email'],
            $escola['responsavel'],
            $escola['coordenador_idh'],
            $escola['coordenador_comissao'],
            'publica',
            500,
            'ativo'
        ]);
        $escola_ids[] = $db->lastInsertId();
        echo "<p>✓ Escola criada: {$escola['nome']}</p>";
    }

    // 3. Criar Salas para cada escola
    echo "<h2>3. Criando Salas...</h2>";
    $salas_por_escola = [];
    foreach ($escola_ids as $escola_id) {
        $salas = [];
        // 10 salas de aula
        for ($i = 1; $i <= 10; $i++) {
            $stmt = $db->prepare("
                INSERT INTO salas (
                    escola_id, nome, tipo, capacidade, descricao, status
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $escola_id,
                "Sala $i",
                'sala_aula',
                30,
                "Sala de aula padrão com 30 lugares",
                'ativo'
            ]);
            $salas[] = $db->lastInsertId();
        }
        // 1 corredor
        $stmt = $db->prepare("
            INSERT INTO salas (
                escola_id, nome, tipo, capacidade, descricao, status
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $escola_id,
            "Corredor Principal",
            'sala_reuniao',
            50,
            "Corredor principal para circulação",
            'ativo'
        ]);
        $salas[] = $db->lastInsertId();
        // 1 portaria
        $stmt = $db->prepare("
            INSERT INTO salas (
                escola_id, nome, tipo, capacidade, descricao, status
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $escola_id,
            "Portaria",
            'sala_reuniao',
            20,
            "Portaria da escola",
            'ativo'
        ]);
        $salas[] = $db->lastInsertId();
        $salas_por_escola[$escola_id] = $salas;
        echo "<p>✓ 12 salas criadas para escola ID: $escola_id</p>";
    }

    // 4. Criar Fiscais e Alocações
    echo "<h2>4. Criando Fiscais e Alocações...</h2>";
    $nomes_fiscais = [
        'Ana Silva', 'João Santos', 'Maria Oliveira', 'Pedro Costa', 'Lucia Ferreira',
        'Carlos Almeida', 'Fernanda Lima', 'Roberto Santos', 'Patricia Oliveira', 'Marcos Rodrigues',
        'Silvia Martins', 'Ricardo Costa', 'Claudia Souza', 'Antonio Pereira', 'Lucia Santos',
        'Roberto Almeida', 'Ana Paula Costa', 'Carlos Oliveira', 'Maria Santos', 'João Silva',
        'Pedro Mendes', 'Fernanda Lima', 'Marcos Rodrigues', 'Lucia Ferreira', 'Roberto Santos'
    ];
    $cpfs = [
        '123.456.789-01', '234.567.890-12', '345.678.901-23', '456.789.012-34', '567.890.123-45',
        '678.901.234-56', '789.012.345-67', '890.123.456-78', '901.234.567-89', '012.345.678-90',
        '111.222.333-44', '222.333.444-55', '333.444.555-66', '444.555.666-77', '555.666.777-88',
        '666.777.888-99', '777.888.999-00', '888.999.000-11', '999.000.111-22', '000.111.222-33',
        '111.222.333-44', '222.333.444-55', '333.444.555-66', '444.555.666-77', '555.666.777-88'
    ];
    $emails = [
        'ana.silva@email.com', 'joao.santos@email.com', 'maria.oliveira@email.com', 'pedro.costa@email.com', 'lucia.ferreira@email.com',
        'carlos.almeida@email.com', 'fernanda.lima@email.com', 'roberto.santos@email.com', 'patricia.oliveira@email.com', 'marcos.rodrigues@email.com',
        'silvia.martins@email.com', 'ricardo.costa@email.com', 'claudia.souza@email.com', 'antonio.pereira@email.com', 'lucia.santos@email.com',
        'roberto.almeida@email.com', 'ana.paula.costa@email.com', 'carlos.oliveira@email.com', 'maria.santos@email.com', 'joao.silva@email.com',
        'pedro.mendes@email.com', 'fernanda.lima2@email.com', 'marcos.rodrigues2@email.com', 'lucia.ferreira2@email.com', 'roberto.santos2@email.com'
    ];
    $fiscal_count = 0;
    foreach ($salas_por_escola as $escola_id => $salas) {
        foreach ($salas as $sala_id) {
            for ($i = 0; $i < 2; $i++) {
                $nome = $nomes_fiscais[$fiscal_count % count($nomes_fiscais)];
                $cpf = $cpfs[$fiscal_count % count($cpfs)];
                $email = $emails[$fiscal_count % count($emails)];
                $stmt = $db->prepare("
                    INSERT INTO fiscais (
                        concurso_id, nome, cpf, email, celular, whatsapp,
                        ddi, genero, data_nascimento, endereco, melhor_horario, status, status_contato,
                        aceite_termos, data_aceite_termos
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $concurso_id,
                    $nome,
                    $cpf,
                    $email,
                    '(11) 9' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                    '(11) 9' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                    '+55',
                    rand(0, 1) ? 'M' : 'F',
                    date('Y-m-d', strtotime('-' . rand(25, 65) . ' years')),
                    'Endereço fictício para teste',
                    'manha',
                    'aprovado',
                    'confirmado',
                    1
                ]);
                $fiscal_id = $db->lastInsertId();
                // Alocar fiscal na sala
                $stmt = $db->prepare("
                    INSERT INTO alocacoes_fiscais (
                        fiscal_id, sala_id, data_alocacao, status
                    ) VALUES (?, ?, CURDATE(), 'confirmada')
                ");
                $stmt->execute([$fiscal_id, $sala_id]);
                $fiscal_count++;
            }
        }
    }
    echo "<p>✓ $fiscal_count fiscais criados e alocados</p>";

    // 5. Estatísticas finais
    echo "<h2>5. Estatísticas Finais</h2>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM concursos WHERE id = $concurso_id");
    $concursos = $stmt->fetch()['total'];
    $stmt = $db->query("SELECT COUNT(*) as total FROM escolas WHERE concurso_id = $concurso_id");
    $escolas = $stmt->fetch()['total'];
    $stmt = $db->query("SELECT COUNT(*) as total FROM salas s JOIN escolas e ON s.escola_id = e.id WHERE e.concurso_id = $concurso_id");
    $salas = $stmt->fetch()['total'];
    $stmt = $db->query("SELECT COUNT(*) as total FROM fiscais WHERE concurso_id = $concurso_id");
    $fiscais = $stmt->fetch()['total'];
    echo "<p>✓ Concursos: $concursos</p>";
    echo "<p>✓ Escolas: $escolas</p>";
    echo "<p>✓ Salas: $salas</p>";
    echo "<p>✓ Fiscais: $fiscais</p>";
    echo "<h2>✅ Dados de teste criados com sucesso!</h2>";
    echo "<p>Você pode agora testar o sistema com dados fictícios.</p>";
    echo "<p><strong>ID do Concurso criado:</strong> $concurso_id</p>";
} catch (Exception $e) {
    echo "<h2>Erro durante a criação dos dados:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
echo "<br><a href='admin/dashboard.php'>Ir para o Painel Administrativo</a>";
echo "<br><a href='relatorios.php'>Gerar Relatórios</a>";
?> 