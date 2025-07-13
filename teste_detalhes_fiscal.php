<?php
require_once 'config.php';

echo "<h1>Teste da Funcionalidade de Detalhes do Fiscal</h1>";

// Verificar se o arquivo get_fiscal.php existe
if (file_exists('admin/get_fiscal.php')) {
    echo "<p style='color: green;'>✅ Arquivo admin/get_fiscal.php existe</p>";
} else {
    echo "<p style='color: red;'>❌ Arquivo admin/get_fiscal.php não existe</p>";
}

// Verificar se há erros de sintaxe
$output = shell_exec('php -l admin/get_fiscal.php 2>&1');
if (strpos($output, 'No syntax errors') !== false) {
    echo "<p style='color: green;'>✅ Arquivo admin/get_fiscal.php não tem erros de sintaxe</p>";
} else {
    echo "<p style='color: red;'>❌ Erro de sintaxe no arquivo admin/get_fiscal.php:</p>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
}

// Testar conexão com banco de dados
echo "<h2>Teste de Conexão com Banco de Dados</h2>";

try {
    $db = getDB();
    if ($db) {
        echo "<p style='color: green;'>✅ Conexão com banco de dados OK</p>";
        
        // Testar consulta de fiscais
        $stmt = $db->query("SELECT COUNT(*) as total FROM fiscais WHERE status = 'aprovado' LIMIT 1");
        $total_fiscais = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>Fiscais aprovados disponíveis: $total_fiscais</p>";
        
        // Buscar um fiscal para teste
        $stmt = $db->query("SELECT id, nome FROM fiscais WHERE status = 'aprovado' LIMIT 1");
        $fiscal_teste = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($fiscal_teste) {
            echo "<p>Fiscal para teste: <strong>{$fiscal_teste['nome']}</strong> (ID: {$fiscal_teste['id']})</p>";
            
            // Testar a API get_fiscal.php
            echo "<h2>Teste da API get_fiscal.php</h2>";
            
            $url = "http://localhost:8000/admin/get_fiscal.php";
            $data = json_encode(['id' => $fiscal_teste['id']]);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            echo "<p>Status HTTP: $http_code</p>";
            
            if ($response) {
                $json_response = json_decode($response, true);
                if ($json_response) {
                    echo "<p style='color: green;'>✅ Resposta JSON válida</p>";
                    
                    if (isset($json_response['fiscal'])) {
                        echo "<p style='color: green;'>✅ Dados do fiscal encontrados</p>";
                        echo "<p><strong>Nome:</strong> {$json_response['fiscal']['nome']}</p>";
                        echo "<p><strong>Email:</strong> {$json_response['fiscal']['email']}</p>";
                        echo "<p><strong>Status:</strong> {$json_response['fiscal']['status']}</p>";
                        
                        if (isset($json_response['alocacoes'])) {
                            echo "<p><strong>Alocações:</strong> " . count($json_response['alocacoes']) . "</p>";
                        }
                        
                        if (isset($json_response['presencas'])) {
                            echo "<p><strong>Presenças:</strong> " . count($json_response['presencas']) . "</p>";
                        }
                        
                        if (isset($json_response['pagamentos'])) {
                            echo "<p><strong>Pagamentos:</strong> " . count($json_response['pagamentos']) . "</p>";
                        }
                    } else {
                        echo "<p style='color: red;'>❌ Dados do fiscal não encontrados na resposta</p>";
                        echo "<pre>" . htmlspecialchars($response) . "</pre>";
                    }
                } else {
                    echo "<p style='color: red;'>❌ Resposta não é JSON válido</p>";
                    echo "<pre>" . htmlspecialchars($response) . "</pre>";
                }
            } else {
                echo "<p style='color: red;'>❌ Sem resposta da API</p>";
            }
            
        } else {
            echo "<p style='color: orange;'>⚠️ Nenhum fiscal aprovado encontrado para teste</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Erro na conexão com banco de dados</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao conectar com banco de dados: " . $e->getMessage() . "</p>";
}

// Verificar se o usuário está logado
echo "<h2>Verificação de Login</h2>";

if (isLoggedIn()) {
    echo "<p style='color: green;'>✅ Usuário está logado</p>";
    echo "<p><strong>Nome:</strong> " . htmlspecialchars($_SESSION['user_name'] ?? 'N/A') . "</p>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($_SESSION['user_email'] ?? 'N/A') . "</p>";
    echo "<p><strong>Tipo:</strong> " . ($_SESSION['user_type'] == 1 ? 'Administrador' : 'Usuário') . "</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Usuário não está logado</p>";
    echo "<p><a href='login.php'>Fazer Login</a></p>";
}

echo "<h2>Teste Manual da Funcionalidade</h2>";

echo "<p><strong>Para testar manualmente:</strong></p>";
echo "<ol>";
echo "<li>Acesse <a href='http://localhost:8000/admin/alocar_fiscais.php' target='_blank'>Página de Alocar Fiscais</a></li>";
echo "<li>Clique no ícone do olho (👁️) em qualquer fiscal</li>";
echo "<li>Verifique se o modal abre com os detalhes</li>";
echo "<li>Verifique se as informações estão corretas</li>";
echo "</ol>";

echo "<h2>Possíveis Problemas e Soluções</h2>";

echo "<h3>1. Erro 400 - ID não fornecido</h3>";
echo "<p><strong>Causa:</strong> JavaScript não está enviando o ID corretamente</p>";
echo "<p><strong>Solução:</strong> Verificar se a função verDetalhes() está sendo chamada com o ID correto</p>";

echo "<h3>2. Erro 403 - Acesso negado</h3>";
echo "<p><strong>Causa:</strong> Usuário não está logado ou não é admin</p>";
echo "<p><strong>Solução:</strong> Fazer login como administrador</p>";

echo "<h3>3. Erro 404 - Fiscal não encontrado</h3>";
echo "<p><strong>Causa:</strong> ID do fiscal não existe no banco</p>";
echo "<p><strong>Solução:</strong> Verificar se o fiscal existe na base de dados</p>";

echo "<h3>4. Erro 500 - Erro interno</h3>";
echo "<p><strong>Causa:</strong> Erro na consulta SQL ou estrutura do banco</p>";
echo "<p><strong>Solução:</strong> Verificar logs do servidor e estrutura das tabelas</p>";

echo "<h2>Debug da Função JavaScript</h2>";

echo "<p>Para debugar, adicione este código no console do navegador:</p>";
echo "<pre>";
echo "function testarDetalhes(id) {
    console.log('Testando fiscal ID:', id);
    
    fetch('admin/get_fiscal.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => {
        console.log('Status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Resposta:', data);
    })
    .catch(error => {
        console.error('Erro:', error);
    });
}
";
echo "</pre>";

echo "<hr>";
echo "<p><strong>Teste concluído!</strong></p>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?> 