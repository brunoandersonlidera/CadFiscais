<?php
require_once 'config.php';

echo "<h1>Adicionar Fiscais de Teste</h1>";

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo "<p style='color: red;'>❌ Usuário não está logado</p>";
    echo "<p><a href='login.php'>Fazer Login</a></p>";
    exit;
}

$db = getDB();

echo "<h2>1. Verificar Fiscais Existentes</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM fiscais");
    $total_fiscais = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>Total de fiscais: <strong>{$total_fiscais}</strong></p>";
    
    if ($total_fiscais > 0) {
        echo "<p style='color: orange;'>⚠️ Já existem fiscais cadastrados. Deseja adicionar mais?</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar fiscais: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Verificar Concursos Disponíveis</h2>";
try {
    $stmt = $db->query("SELECT id, titulo FROM concursos WHERE status = 'ativo' LIMIT 1");
    $concurso = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$concurso) {
        echo "<p style='color: red;'>❌ Nenhum concurso ativo encontrado. Crie um concurso primeiro.</p>";
        echo "<p><a href='admin/novo_concurso.php' class='btn btn-primary'>Criar Concurso</a></p>";
        exit;
    }
    
    echo "<p>Concurso selecionado: <strong>{$concurso['titulo']}</strong> (ID: {$concurso['id']})</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar concursos: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>3. Adicionar Fiscais de Teste</h2>";

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

echo "<p><strong>Fiscais que serão adicionados:</strong></p>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Nome</th><th>Email</th><th>Idade</th><th>Data Nascimento</th></tr>";
foreach ($fiscais_teste as $fiscal) {
    echo "<tr>";
    echo "<td>{$fiscal['nome']}</td>";
    echo "<td>{$fiscal['email']}</td>";
    echo "<td>{$fiscal['idade']}</td>";
    echo "<td>{$fiscal['data_nascimento']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Adicionar Fiscais de Teste?</h3>";
echo "<p>Isso irá adicionar 5 fiscais de teste com idades diferentes.</p>";
echo "<button onclick=\"adicionarFiscais()\" class='btn btn-success'>Adicionar Fiscais de Teste</button>";

echo "<script>";
echo "function adicionarFiscais() {";
echo "    if (confirm('Tem certeza que deseja adicionar os fiscais de teste?')) {";
echo "        fetch('adicionar_fiscais_ajax.php', {";
echo "            method: 'POST'";
echo "        })";
echo "        .then(response => response.json())";
echo "        .then(data => {";
echo "            if (data.success) {";
echo "                alert('Fiscais adicionados com sucesso! ' + data.message);";
echo "                location.reload();";
echo "            } else {";
echo "                alert('Erro: ' + data.message);";
echo "            }";
echo "        })";
echo "        .catch(error => {";
echo "            alert('Erro ao adicionar fiscais');";
echo "        });";
echo "    }";
echo "}";
echo "</script>";

echo "<h2>4. Próximos Passos</h2>";
echo "<p><a href='verificar_dados_grafico.php' class='btn btn-info'>📊 Verificar Dados do Gráfico</a></p>";
echo "<p><a href='admin/dashboard.php' class='btn btn-primary'>🏠 Acessar Dashboard</a></p>";

echo "<hr>";
echo "<p><strong>Script concluído!</strong></p>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?> 