<?php
require_once '../config.php';

// Verificar se o usuário está logado e é administrador
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$message = '';
$error = '';

// Processar formulário de configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDB();
        
        if ($db) {
            // Atualizar configurações
            $configuracoes = [
                'site_name' => sanitizeInput($_POST['site_name'] ?? ''),
                'admin_email' => sanitizeInput($_POST['admin_email'] ?? ''),
                'whatsapp_number' => sanitizeInput($_POST['whatsapp_number'] ?? ''),
                'max_fiscais_por_concurso' => (int)($_POST['max_fiscais_por_concurso'] ?? 100),
                'cadastro_aberto' => (int)($_POST['cadastro_aberto'] ?? 1),
                'idade_minima' => (int)($_POST['idade_minima'] ?? 18),
                'ddi_padrao' => sanitizeInput($_POST['ddi_padrao'] ?? '+55')
            ];
            
            foreach ($configuracoes as $chave => $valor) {
                $stmt = $db->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = ?");
                $stmt->execute([$chave, $valor, $valor]);
            }
            
            $message = 'Configurações atualizadas com sucesso!';
            logActivity("Configurações atualizadas por " . $_SESSION['user_name'], 'INFO');
        } else {
            $error = 'Erro ao conectar com o banco de dados.';
        }
    } catch (Exception $e) {
        $error = 'Erro interno do sistema: ' . $e->getMessage();
        logActivity("Erro ao atualizar configurações: " . $e->getMessage(), 'ERROR');
    }
}

// Buscar configurações atuais
$configuracoes = [];
try {
    $db = getDB();
    if ($db) {
        $stmt = $db->query("SELECT chave, valor FROM configuracoes");
        while ($row = $stmt->fetch()) {
            $configuracoes[$row['chave']] = $row['valor'];
        }
    }
} catch (Exception $e) {
    $error = 'Erro ao carregar configurações: ' . $e->getMessage();
}

// Buscar estatísticas do sistema
$estatisticas = [];
try {
    if ($db) {
        // Total de fiscais
        $stmt = $db->query("SELECT COUNT(*) FROM fiscais");
        $estatisticas['total_fiscais'] = $stmt->fetchColumn();
        
        // Fiscais por status
        $stmt = $db->query("SELECT status, COUNT(*) FROM fiscais GROUP BY status");
        $estatisticas['fiscais_por_status'] = $stmt->fetchAll();
        
        // Total de concursos
        $stmt = $db->query("SELECT COUNT(*) FROM concursos");
        $estatisticas['total_concursos'] = $stmt->fetchColumn();
        
        // Total de escolas
        $stmt = $db->query("SELECT COUNT(*) FROM escolas");
        $estatisticas['total_escolas'] = $stmt->fetchColumn();
        
        // Total de usuários
        $stmt = $db->query("SELECT COUNT(*) FROM usuarios");
        $estatisticas['total_usuarios'] = $stmt->fetchColumn();
    }
} catch (Exception $e) {
    $error = 'Erro ao carregar estatísticas: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Sistema IDH</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ Configurações do Sistema</h1>
            <p>Sistema de Cadastro de Fiscais - IDH</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <h2>Configurações Gerais</h2>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="site_name">Nome do Sistema:</label>
                            <input type="text" id="site_name" name="site_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($configuracoes['site_name'] ?? 'Sistema de Cadastro de Fiscais - IDH'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_email">Email do Administrador:</label>
                            <input type="email" id="admin_email" name="admin_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($configuracoes['admin_email'] ?? 'admin@idh.com'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="whatsapp_number">Número do WhatsApp:</label>
                            <input type="text" id="whatsapp_number" name="whatsapp_number" class="form-control" 
                                   value="<?php echo htmlspecialchars($configuracoes['whatsapp_number'] ?? '+5511999999999'); ?>" 
                                   placeholder="+5511999999999">
                        </div>
                        
                        <div class="form-group">
                            <label for="max_fiscais_por_concurso">Máximo de Fiscais por Concurso:</label>
                            <input type="number" id="max_fiscais_por_concurso" name="max_fiscais_por_concurso" class="form-control" 
                                   value="<?php echo htmlspecialchars($configuracoes['max_fiscais_por_concurso'] ?? '100'); ?>" min="1" max="1000">
                        </div>
                        
                        <div class="form-group">
                            <label for="cadastro_aberto">Cadastro de Fiscais:</label>
                            <select id="cadastro_aberto" name="cadastro_aberto" class="form-control">
                                <option value="1" <?php echo ($configuracoes['cadastro_aberto'] ?? '1') == '1' ? 'selected' : ''; ?>>Aberto</option>
                                <option value="0" <?php echo ($configuracoes['cadastro_aberto'] ?? '1') == '0' ? 'selected' : ''; ?>>Fechado</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="idade_minima">Idade Mínima para Cadastro:</label>
                            <input type="number" id="idade_minima" name="idade_minima" class="form-control" 
                                   value="<?php echo htmlspecialchars($configuracoes['idade_minima'] ?? '18'); ?>" min="16" max="100">
                        </div>
                        
                        <div class="form-group">
                            <label for="ddi_padrao">DDI Padrão:</label>
                            <input type="text" id="ddi_padrao" name="ddi_padrao" class="form-control" 
                                   value="<?php echo htmlspecialchars($configuracoes['ddi_padrao'] ?? '+55'); ?>" placeholder="+55">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                            <a href="dashboard.php" class="btn btn-secondary">Voltar</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <h3>📊 Estatísticas do Sistema</h3>
                    
                    <div class="stats">
                        <div class="stat-item">
                            <strong>Total de Fiscais:</strong>
                            <span class="stat-value"><?php echo $estatisticas['total_fiscais'] ?? 0; ?></span>
                        </div>
                        
                        <div class="stat-item">
                            <strong>Total de Concursos:</strong>
                            <span class="stat-value"><?php echo $estatisticas['total_concursos'] ?? 0; ?></span>
                        </div>
                        
                        <div class="stat-item">
                            <strong>Total de Escolas:</strong>
                            <span class="stat-value"><?php echo $estatisticas['total_escolas'] ?? 0; ?></span>
                        </div>
                        
                        <div class="stat-item">
                            <strong>Total de Usuários:</strong>
                            <span class="stat-value"><?php echo $estatisticas['total_usuarios'] ?? 0; ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($estatisticas['fiscais_por_status'])): ?>
                    <h4>Fiscais por Status:</h4>
                    <div class="stats">
                        <?php foreach ($estatisticas['fiscais_por_status'] as $status): ?>
                        <div class="stat-item">
                            <strong><?php echo ucfirst($status['status']); ?>:</strong>
                            <span class="stat-value"><?php echo $status['COUNT(*)']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <h3>🔧 Informações do Sistema</h3>
                    
                    <div class="info-item">
                        <strong>Versão PHP:</strong>
                        <span><?php echo PHP_VERSION; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <strong>Banco de Dados:</strong>
                        <span><?php echo $db ? 'MySQL' : 'CSV (Fallback)'; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <strong>Usuário Logado:</strong>
                        <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'N/A'); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <strong>Último Login:</strong>
                        <span><?php echo date('d/m/Y H:i:s'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3>🛠️ Ações do Sistema</h3>
            
            <div class="row">
                <div class="col-md-3">
                    <a href="backup_dados.php" class="btn btn-info btn-block">
                        📦 Fazer Backup
                    </a>
                </div>
                
                <div class="col-md-3">
                    <a href="../verificar_mysql.php" class="btn btn-warning btn-block">
                        🔍 Verificar Sistema
                    </a>
                </div>
                
                <div class="col-md-3">
                    <a href="../logs/system.log" class="btn btn-secondary btn-block" target="_blank">
                        📋 Ver Logs
                    </a>
                </div>
                
                <div class="col-md-3">
                    <a href="logout.php" class="btn btn-danger btn-block">
                        🚪 Sair
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .stats {
        margin: 15px 0;
    }
    .stat-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }
    .stat-value {
        font-weight: bold;
        color: #007bff;
    }
    .info-item {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        font-size: 14px;
    }
    .btn-block {
        display: block;
        width: 100%;
        margin-bottom: 10px;
    }
    </style>
</body>
</html> 