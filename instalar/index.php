<?php
// instalar/index.php - Instalador web do CadFiscais

function render_form($error = '') {
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <title>Instalador - CadFiscais</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f9f9f9; }
            .container { max-width: 500px; margin: 40px auto; background: #fff; padding: 2em; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
            h1 { text-align: center; }
            .error { color: #b00; margin-bottom: 1em; }
        </style>
    </head>
    <body>
    <div class="container">
        <h1>Instalação do CadFiscais</h1>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <label>Host do Banco de Dados:<br><input type="text" name="db_host" value="localhost" required></label><br><br>
            <label>Nome do Banco:<br><input type="text" name="db_name" required></label><br><br>
            <label>Usuário do Banco:<br><input type="text" name="db_user" required></label><br><br>
            <label>Senha do Banco:<br><input type="password" name="db_pass"></label><br><br>
            <label>Charset:<br><input type="text" name="db_charset" value="utf8mb4" required></label><br><br>
            <label><input type="checkbox" name="importar_teste" value="1"> Importar dados de teste (recomendado para avaliação)</label><br><br>
            <button type="submit">Instalar</button>
        </form>
    </div>
    </body>
    </html>
    <?php
}

function executar_sql($pdo, $sql) {
    $comandos = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($comandos as $comando) {
        if ($comando) {
            $pdo->exec($comando);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? '';
    $db_name = $_POST['db_name'] ?? '';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_charset = $_POST['db_charset'] ?? 'utf8mb4';
    $importar_teste = !empty($_POST['importar_teste']);

    try {
        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";
        $pdo = new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (Exception $e) {
        render_form('Erro ao conectar ao banco: ' . $e->getMessage());
        exit;
    }

    // Executar script SQL principal
    $sql = file_get_contents(__DIR__ . '/../database/cadfiscais.sql');
    try {
        executar_sql($pdo, $sql);
    } catch (Exception $e) {
        render_form('Erro ao executar script SQL: ' . $e->getMessage());
        exit;
    }

    // Importar dados de teste se solicitado
    if ($importar_teste) {
        $arquivos = glob(__DIR__ . '/../database/dados_teste/*.php');
        foreach ($arquivos as $arq) {
            include $arq;
        }
    }

    // Gerar config.php
    $config = "<?php\n" .
        "define('DB_HOST', '" . addslashes($db_host) . "');\n" .
        "define('DB_NAME', '" . addslashes($db_name) . "');\n" .
        "define('DB_USER', '" . addslashes($db_user) . "');\n" .
        "define('DB_PASS', '" . addslashes($db_pass) . "');\n" .
        "define('DB_CHARSET', '" . addslashes($db_charset) . "');\n";
    file_put_contents(__DIR__ . '/../config.php', $config);

    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head><meta charset="utf-8"><title>Instalação concluída</title></head>
    <body style="font-family:Arial,sans-serif;background:#f9f9f9;">
    <div style="max-width:600px;margin:40px auto;background:#fff;padding:2em;border-radius:8px;box-shadow:0 2px 8px #0001;">
        <h1>Instalação concluída!</h1>
        <p>O sistema foi instalado com sucesso.</p>
        <ul>
            <li><b>Arquivo config.php gerado.</b></li>
            <li><b>Tabelas criadas no banco de dados.</b></li>
            <?php if ($importar_teste): ?><li><b>Dados de teste importados.</b></li><?php endif; ?>
        </ul>
        <p>Agora você pode acessar o sistema normalmente.</p>
        <p><a href="../index.php">Ir para o sistema</a></p>
    </div>
    </body>
    </html>
    <?php
    exit;
}

render_form(); 