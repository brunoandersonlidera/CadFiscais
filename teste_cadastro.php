<?php
require_once 'config.php';
require_once 'ddi.php';

echo "<h1>🧪 Teste de Cadastro</h1>";

// Verificar se o cadastro está aberto
$cadastro_aberto = getConfig('cadastro_aberto');
echo "<h2>1. Status do Cadastro</h2>";
echo "Cadastro aberto: " . ($cadastro_aberto == '1' ? '✅ SIM' : '❌ NÃO') . "<br>";

if ($cadastro_aberto != '1') {
    echo "<p>❌ O cadastro está fechado. Abra o cadastro primeiro.</p>";
    echo "<a href='admin/configuracoes.php' class='btn btn-warning'>Abrir Cadastro</a>";
    exit;
}

// Verificar concursos disponíveis
echo "<h2>2. Concursos Disponíveis</h2>";
$db = getDB();
if ($db) {
    $stmt = $db->query("SELECT * FROM concursos WHERE status = 'ativo'");
    $concursos = $stmt->fetchAll();
    
    if (empty($concursos)) {
        echo "❌ Nenhum concurso ativo encontrado<br>";
        exit;
    }
    
    foreach ($concursos as $concurso) {
        echo "✅ Concurso ID: {$concurso['id']} - {$concurso['titulo']}<br>";
    }
} else {
    echo "❌ Erro na conexão com banco<br>";
    exit;
}

// Simular dados de teste
echo "<h2>3. Dados de Teste</h2>";
$dados_teste = [
    'concurso_id' => 2,
    'nome' => 'João Silva Teste',
    'email' => 'joao.teste@email.com',
    'ddi' => '+55',
    'celular' => '(11) 99999-9999',
    'genero' => 'M',
    'cpf' => '12345678901',
    'data_nascimento' => '1990-01-01',
    'endereco' => 'Rua Teste, 123 - São Paulo/SP',
    'melhor_horario' => 'manha',
    'observacoes' => 'Teste de cadastro',
    'aceite_termos' => 'on'
];

echo "Dados que serão enviados:<br>";
foreach ($dados_teste as $campo => $valor) {
    echo "- $campo: $valor<br>";
}

// Verificar se CPF já existe
echo "<h2>4. Verificação de Duplicidade</h2>";
$cpf_limpo = preg_replace('/[^0-9]/', '', $dados_teste['cpf']);
$stmt = $db->prepare("SELECT COUNT(*) FROM fiscais WHERE cpf = ? AND concurso_id = ?");
$stmt->execute([$cpf_limpo, $dados_teste['concurso_id']]);
$existe = $stmt->fetchColumn() > 0;

if ($existe) {
    echo "⚠️ CPF já cadastrado neste concurso<br>";
    echo "Gerando CPF único para teste...<br>";
    $dados_teste['cpf'] = '98765432100';
    $cpf_limpo = '98765432100';
}

// Verificar vagas disponíveis
echo "<h2>5. Verificação de Vagas</h2>";
$stmt = $db->prepare("SELECT vagas_disponiveis FROM concursos WHERE id = ?");
$stmt->execute([$dados_teste['concurso_id']]);
$concurso = $stmt->fetch();

if ($concurso) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM fiscais WHERE concurso_id = ?");
    $stmt->execute([$dados_teste['concurso_id']]);
    $cadastrados = $stmt->fetchColumn();
    
    echo "Vagas disponíveis: {$concurso['vagas_disponiveis']}<br>";
    echo "Fiscais cadastrados: $cadastrados<br>";
    
    if ($cadastrados >= $concurso['vagas_disponiveis']) {
        echo "❌ Não há vagas disponíveis<br>";
        exit;
    } else {
        echo "✅ Há vagas disponíveis<br>";
    }
} else {
    echo "❌ Concurso não encontrado<br>";
    exit;
}

// Testar inserção direta
echo "<h2>6. Teste de Inserção Direta</h2>";
try {
    $dados_insercao = [
        'concurso_id' => $dados_teste['concurso_id'],
        'nome' => $dados_teste['nome'],
        'email' => $dados_teste['email'],
        'ddi' => $dados_teste['ddi'],
        'celular' => $dados_teste['celular'],
        'whatsapp' => '',
        'cpf' => $cpf_limpo,
        'data_nascimento' => $dados_teste['data_nascimento'],
        'genero' => $dados_teste['genero'],
        'endereco' => $dados_teste['endereco'],
        'melhor_horario' => $dados_teste['melhor_horario'],
        'observacoes' => $dados_teste['observacoes'],
        'status' => 'pendente',
        'status_contato' => 'nao_contatado',
        'aceite_termos' => 1,
        'data_aceite_termos' => date('Y-m-d H:i:s'),
        'ip_cadastro' => '127.0.0.1',
        'user_agent' => 'Teste Script'
    ];
    
    $stmt = $db->prepare("
        INSERT INTO fiscais (
            concurso_id, nome, email, ddi, celular, whatsapp, cpf, 
            data_nascimento, genero, endereco, melhor_horario, observacoes, 
            status, status_contato, aceite_termos, data_aceite_termos, 
            ip_cadastro, user_agent, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    
    $resultado = $stmt->execute([
        $dados_insercao['concurso_id'], $dados_insercao['nome'], $dados_insercao['email'], 
        $dados_insercao['ddi'], $dados_insercao['celular'], $dados_insercao['whatsapp'], $dados_insercao['cpf'],
        $dados_insercao['data_nascimento'], $dados_insercao['genero'], $dados_insercao['endereco'],
        $dados_insercao['melhor_horario'], $dados_insercao['observacoes'], $dados_insercao['status'],
        $dados_insercao['status_contato'], $dados_insercao['aceite_termos'], $dados_insercao['data_aceite_termos'],
        $dados_insercao['ip_cadastro'], $dados_insercao['user_agent']
    ]);
    
    if ($resultado) {
        $fiscal_id = $db->lastInsertId();
        echo "✅ Inserção direta bem-sucedida! ID: $fiscal_id<br>";
        
        // Verificar se foi realmente inserido
        $stmt = $db->prepare("SELECT * FROM fiscais WHERE id = ?");
        $stmt->execute([$fiscal_id]);
        $fiscal = $stmt->fetch();
        
        if ($fiscal) {
            echo "✅ Fiscal encontrado no banco:<br>";
            echo "- Nome: {$fiscal['nome']}<br>";
            echo "- Email: {$fiscal['email']}<br>";
            echo "- CPF: {$fiscal['cpf']}<br>";
            echo "- Status: {$fiscal['status']}<br>";
        }
    } else {
        echo "❌ Erro na inserção direta<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro na inserção direta: " . $e->getMessage() . "<br>";
}

// Verificar logs
echo "<h2>7. Logs do Sistema</h2>";
$log_file = 'logs/system.log';
if (file_exists($log_file)) {
    echo "Últimas 5 linhas do log:<br>";
    $lines = file($log_file);
    $last_lines = array_slice($lines, -5);
    foreach ($last_lines as $line) {
        echo htmlspecialchars($line) . "<br>";
    }
} else {
    echo "❌ Arquivo de log não encontrado<br>";
}

echo "<h2>8. Próximos Passos</h2>";
echo "<a href='debug_cadastro.php' class='btn btn-primary'>Debug Completo</a> ";
echo "<a href='cadastro_fixo.php?concurso=2' class='btn btn-success'>Testar Cadastro Real</a> ";
echo "<a href='admin/' class='btn btn-secondary'>Painel Admin</a>";
?> 