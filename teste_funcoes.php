<?php
require_once 'config.php';

echo "<h1>Teste das Funções</h1>";

// Testar se as funções estão disponíveis
if (function_exists('formatCPF')) {
    echo "✅ Função formatCPF() está disponível<br>";
    echo "Teste: " . formatCPF('12345678901') . "<br>";
} else {
    echo "❌ Função formatCPF() não está disponível<br>";
}

if (function_exists('formatPhone')) {
    echo "✅ Função formatPhone() está disponível<br>";
    echo "Teste: " . formatPhone('11987654321') . "<br>";
} else {
    echo "❌ Função formatPhone() não está disponível<br>";
}

if (function_exists('isAdmin')) {
    echo "✅ Função isAdmin() está disponível<br>";
} else {
    echo "❌ Função isAdmin() não está disponível<br>";
}

if (function_exists('isLoggedIn')) {
    echo "✅ Função isLoggedIn() está disponível<br>";
} else {
    echo "❌ Função isLoggedIn() não está disponível<br>";
}

// Testar conexão com banco
$db = getDB();
if ($db) {
    echo "✅ Conexão com banco de dados OK<br>";
} else {
    echo "❌ Erro na conexão com banco de dados<br>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?> 