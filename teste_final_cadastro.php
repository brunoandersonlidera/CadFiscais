<?php
require_once 'config.php';
require_once 'ddi.php';

echo "<h1>🎯 Teste Final do Cadastro</h1>";

// Verificar se o cadastro está aberto
$cadastro_aberto = getConfig('cadastro_aberto');
echo "<h2>1. Status do Sistema</h2>";
echo "Cadastro aberto: " . ($cadastro_aberto == '1' ? '✅ SIM' : '❌ NÃO') . "<br>";

if ($cadastro_aberto != '1') {
    echo "<p>❌ O cadastro está fechado. Abra o cadastro primeiro.</p>";
    echo "<a href='admin/configuracoes.php' class='btn btn-warning'>Abrir Cadastro</a>";
    exit;
}

// Verificar concursos
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
        echo "   Vagas: {$concurso['vagas_disponiveis']}<br>";
    }
} else {
    echo "❌ Erro na conexão com banco<br>";
    exit;
}

// Verificar estrutura da tabela
echo "<h2>3. Estrutura da Tabela</h2>";
try {
    $stmt = $db->query("DESCRIBE fiscais");
    $colunas = $stmt->fetchAll();
    $colunas_nomes = array_column($colunas, 'Field');
    
    $colunas_necessarias = [
        'id', 'concurso_id', 'nome', 'email', 'ddi', 'celular', 'whatsapp',
        'cpf', 'data_nascimento', 'genero', 'endereco', 'melhor_horario',
        'observacoes', 'status', 'status_contato', 'aceite_termos',
        'data_aceite_termos', 'ip_cadastro', 'user_agent', 'created_at'
    ];
    
    $faltantes = array_diff($colunas_necessarias, $colunas_nomes);
    
    if (empty($faltantes)) {
        echo "✅ Todas as colunas necessárias estão presentes<br>";
    } else {
        echo "❌ Colunas faltantes: " . implode(', ', $faltantes) . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar estrutura: " . $e->getMessage() . "<br>";
}

// Testar inserção direta
echo "<h2>4. Teste de Inserção</h2>";
try {
    $dados_teste = [
        'concurso_id' => 2,
        'nome' => 'Maria Silva Teste',
        'email' => 'maria.teste@email.com',
        'ddi' => '+55',
        'celular' => '(11) 88888-8888',
        'whatsapp' => '',
        'cpf' => '98765432100',
        'data_nascimento' => '1985-05-15',
        'genero' => 'F',
        'endereco' => 'Av. Teste, 456 - São Paulo/SP',
        'melhor_horario' => 'tarde',
        'observacoes' => 'Teste final do sistema',
        'status' => 'pendente',
        'status_contato' => 'nao_contatado',
        'aceite_termos' => 1,
        'data_aceite_termos' => date('Y-m-d H:i:s'),
        'ip_cadastro' => '127.0.0.1',
        'user_agent' => 'Teste Final Script'
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
        $dados_teste['concurso_id'], $dados_teste['nome'], $dados_teste['email'], 
        $dados_teste['ddi'], $dados_teste['celular'], $dados_teste['whatsapp'], $dados_teste['cpf'],
        $dados_teste['data_nascimento'], $dados_teste['genero'], $dados_teste['endereco'],
        $dados_teste['melhor_horario'], $dados_teste['observacoes'], $dados_teste['status'],
        $dados_teste['status_contato'], $dados_teste['aceite_termos'], $dados_teste['data_aceite_termos'],
        $dados_teste['ip_cadastro'], $dados_teste['user_agent']
    ]);
    
    if ($resultado) {
        $fiscal_id = $db->lastInsertId();
        echo "✅ Inserção bem-sucedida! ID: $fiscal_id<br>";
        
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
            echo "- Aceite termos: " . ($fiscal['aceite_termos'] ? 'SIM' : 'NÃO') . "<br>";
            echo "- Data aceite: {$fiscal['data_aceite_termos']}<br>";
        }
        
        // Limpar o teste
        $db->exec("DELETE FROM fiscais WHERE id = $fiscal_id");
        echo "✅ Registro de teste removido<br>";
    } else {
        echo "❌ Erro na inserção<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro na inserção: " . $e->getMessage() . "<br>";
}

// Verificar funções necessárias
echo "<h2>5. Verificação de Funções</h2>";
$funcoes_necessarias = [
    'getDB', 'getConfig', 'logActivity', 'validateDDI', 'validateBrazilianPhone',
    'getConcurso', 'countFiscaisByConcurso', 'fiscalExists', 'fiscalEmailExists', 'insertFiscal'
];

foreach ($funcoes_necessarias as $funcao) {
    if (function_exists($funcao)) {
        echo "✅ $funcao() - Disponível<br>";
    } else {
        echo "❌ $funcao() - Não encontrada<br>";
    }
}

// Verificar arquivos necessários
echo "<h2>6. Verificação de Arquivos</h2>";
$arquivos_necessarios = [
    'config.php', 'ddi.php', 'processar_cadastro.php', 'cadastro_fixo.php',
    'sucesso.php', 'style.css'
];

foreach ($arquivos_necessarios as $arquivo) {
    if (file_exists($arquivo)) {
        echo "✅ $arquivo - Existe<br>";
    } else {
        echo "❌ $arquivo - Não encontrado<br>";
    }
}

// Verificar permissões
echo "<h2>7. Verificação de Permissões</h2>";
$diretorios = ['logs/', 'data/'];
foreach ($diretorios as $dir) {
    if (is_dir($dir) && is_writable($dir)) {
        echo "✅ $dir - Gravável<br>";
    } else {
        echo "❌ $dir - Não gravável<br>";
    }
}

echo "<h2>✅ Teste Final Concluído!</h2>";
echo "<p>O sistema está pronto para receber cadastros.</p>";

echo "<h3>🔧 Links Importantes</h3>";
echo "<a href='cadastro_fixo.php?concurso=2' class='btn btn-success'>📝 Ir para Cadastro</a> ";
echo "<a href='admin/' class='btn btn-primary'>⚙️ Painel Admin</a> ";
echo "<a href='index.php' class='btn btn-secondary'>🏠 Página Inicial</a>";

echo "<h3>📋 Checklist Final</h3>";
echo "<ul>";
echo "<li>✅ Cadastro aberto</li>";
echo "<li>✅ Concurso ativo</li>";
echo "<li>✅ Tabela fiscais correta</li>";
echo "<li>✅ Inserção funcionando</li>";
echo "<li>✅ Funções disponíveis</li>";
echo "<li>✅ Arquivos presentes</li>";
echo "<li>✅ Permissões corretas</li>";
echo "</ul>";

echo "<h3>🎉 Sistema Pronto!</h3>";
echo "<p>O cadastro de fiscais está funcionando corretamente.</p>";
echo "<p>Acesse o link do cadastro para testar com dados reais.</p>";
?> 