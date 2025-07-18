<?php
// Exemplo de configuração para o CadFiscais
// Copie este arquivo para config.php e preencha os dados de conexão do banco.
// NÃO remova as funções utilitárias, pois o sistema depende delas!

// Configurações do sistema
// define('SITE_NAME', 'Sistema de Cadastro de Fiscais - IDH');
define('SITE_URL', 'https://seudominio.com.br/cadfiscais/'); // Altere para seu domínio real
define('LOG_PATH', __DIR__ . '/logs');

date_default_timezone_set('America/Sao_Paulo');

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
// ini_set('session.cookie_secure', 1); // Ative se usar SSL
session_start();

// Configurações do MySQL/MariaDB
// Preencha com os dados do seu banco
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'cadfiscais');
// define('DB_USER', 'usuario');
// define('DB_PASS', 'senha');
define('DB_CHARSET', 'utf8mb4');

// Outras configurações importantes
// define('ADMIN_EMAIL', 'admin@idh.com');

// ================= FUNÇÕES UTILITÁRIAS =================

function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            // Verificar se MySQL está disponível
            if (extension_loaded('pdo_mysql')) {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
                ];
                $db = new PDO($dsn, DB_USER, DB_PASS, $options);
                // Opcional: criar tabelas se não existirem
                // createTablesIfNotExist($db);
            } else {
                $db = null; // Usar CSV como fallback
            }
        } catch (Exception $e) {
            error_log("Erro ao conectar com MySQL: " . $e->getMessage());
            $db = null; // Usar CSV como fallback
        }
    }
    return $db;
}

// Outras funções utilitárias podem ser copiadas do config.php conforme necessidade do sistema.

// Função para criar tabelas se não existirem
function createTablesIfNotExist($db) {
    try {
        // Tabela de tipos de usuário
        $db->exec("CREATE TABLE IF NOT EXISTS tipos_usuario (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            descricao TEXT,
            permissoes JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Tabela de usuários
        $db->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo_usuario_id INT NOT NULL,
            nome VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            cpf VARCHAR(14) UNIQUE NOT NULL,
            status ENUM('ativo', 'inativo') DEFAULT 'ativo',
            ultimo_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (tipo_usuario_id) REFERENCES tipos_usuario(id)
        )");
        
        // Tabela de configurações
        $db->exec("CREATE TABLE IF NOT EXISTS configuracoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chave VARCHAR(255) UNIQUE NOT NULL,
            valor TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Tabela de concursos
        $db->exec("CREATE TABLE IF NOT EXISTS concursos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(255) NOT NULL,
            orgao VARCHAR(255) NOT NULL,
            numero_concurso VARCHAR(50),
            ano_concurso INT,
            cidade VARCHAR(100) NOT NULL,
            estado VARCHAR(2) NOT NULL,
            data_prova DATE NOT NULL,
            horario_inicio TIME NOT NULL,
            horario_fim TIME NOT NULL,
            valor_pagamento DECIMAL(10,2) NOT NULL,
            vagas_disponiveis INT DEFAULT 0,
            descricao TEXT,
            termos_aceite TEXT,
            logo_orgao VARCHAR(255),
            status ENUM('ativo', 'inativo') DEFAULT 'ativo',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Tabela de escolas (estrutura completa)
        $db->exec("CREATE TABLE IF NOT EXISTS escolas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            concurso_id INT,
            nome VARCHAR(255) NOT NULL,
            endereco TEXT,
            telefone VARCHAR(20),
            email VARCHAR(255),
            responsavel VARCHAR(255),
            coordenador_idh VARCHAR(255),
            coordenador_comissao VARCHAR(255),
            capacidade INT DEFAULT 0,
            observacoes TEXT,
            tipo ENUM('publica', 'privada') DEFAULT 'publica',
            data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('ativo', 'inativo') DEFAULT 'ativo',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (concurso_id) REFERENCES concursos(id) ON DELETE CASCADE
        )");
        
        // Tabela de salas
        $db->exec("CREATE TABLE IF NOT EXISTS salas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            escola_id INT,
            nome VARCHAR(255) NOT NULL,
            tipo ENUM('sala_aula', 'auditorio', 'laboratorio', 'biblioteca', 'sala_reuniao') DEFAULT 'sala_aula',
            capacidade INT DEFAULT 0,
            descricao TEXT,
            data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('ativo', 'inativo') DEFAULT 'ativo',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (escola_id) REFERENCES escolas(id) ON DELETE CASCADE
        )");
        
        // Tabela de fiscais
        $db->exec("CREATE TABLE IF NOT EXISTS fiscais (
            id INT AUTO_INCREMENT PRIMARY KEY,
            concurso_id INT,
            escola_id INT,
            sala_id INT,
            nome VARCHAR(255) NOT NULL,
            cpf VARCHAR(14) UNIQUE NOT NULL,
            email VARCHAR(255),
            celular VARCHAR(20),
            whatsapp VARCHAR(20),
            ddi VARCHAR(5) DEFAULT '+55',
            genero ENUM('M', 'F', 'O'),
            data_nascimento DATE,
            endereco TEXT,
            melhor_horario VARCHAR(50),
            observacoes TEXT,
            status ENUM('pendente', 'ativo', 'validado', 'confirmado', 'rejeitado') DEFAULT 'pendente',
            status_contato ENUM('nao_contatado', 'confirmado', 'nao_respondeu', 'desistiu') DEFAULT 'nao_contatado',
            aceite_termos TINYINT(1) DEFAULT 0,
            data_aceite_termos TIMESTAMP NULL,
            ip_cadastro VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (concurso_id) REFERENCES concursos(id) ON DELETE CASCADE,
            FOREIGN KEY (escola_id) REFERENCES escolas(id) ON DELETE SET NULL,
            FOREIGN KEY (sala_id) REFERENCES salas(id) ON DELETE SET NULL
        )");
        
        // Tabela de pagamentos
        $db->exec("CREATE TABLE IF NOT EXISTS pagamentos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fiscal_id INT NOT NULL,
            concurso_id INT NOT NULL,
            usuario_id INT NOT NULL,
            valor DECIMAL(10,2) NOT NULL,
            forma_pagamento ENUM('dinheiro', 'pix', 'transferencia', 'cheque') DEFAULT 'dinheiro',
            status ENUM('pendente', 'pago', 'cancelado') DEFAULT 'pendente',
            data_pagamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            observacoes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (fiscal_id) REFERENCES fiscais(id) ON DELETE CASCADE,
            FOREIGN KEY (concurso_id) REFERENCES concursos(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        )");
        
        // Tabela de presença
        $db->exec("CREATE TABLE IF NOT EXISTS presenca (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fiscal_id INT NOT NULL,
            concurso_id INT NOT NULL,
            escola_id INT,
            sala_id INT,
            data_presenca DATE NOT NULL,
            horario_entrada TIME,
            horario_saida TIME,
            tipo_presenca ENUM('prova', 'treinamento') DEFAULT 'prova',
            status ENUM('presente', 'ausente', 'justificado') DEFAULT 'presente',
            observacoes TEXT,
            usuario_registro_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (fiscal_id) REFERENCES fiscais(id) ON DELETE CASCADE,
            FOREIGN KEY (concurso_id) REFERENCES concursos(id) ON DELETE CASCADE,
            FOREIGN KEY (escola_id) REFERENCES escolas(id) ON DELETE SET NULL,
            FOREIGN KEY (sala_id) REFERENCES salas(id) ON DELETE SET NULL,
            FOREIGN KEY (usuario_registro_id) REFERENCES usuarios(id) ON DELETE SET NULL
        )");
        
        // Tabela de alocações
        $db->exec("CREATE TABLE IF NOT EXISTS alocacoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fiscal_id INT NOT NULL,
            concurso_id INT NOT NULL,
            escola_id INT NOT NULL,
            sala_id INT NOT NULL,
            data_alocacao DATE NOT NULL,
            horario_inicio TIME,
            horario_fim TIME,
            status ENUM('agendada', 'confirmada', 'cancelada') DEFAULT 'agendada',
            observacoes TEXT,
            usuario_alocacao_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (fiscal_id) REFERENCES fiscais(id) ON DELETE CASCADE,
            FOREIGN KEY (concurso_id) REFERENCES concursos(id) ON DELETE CASCADE,
            FOREIGN KEY (escola_id) REFERENCES escolas(id) ON DELETE CASCADE,
            FOREIGN KEY (sala_id) REFERENCES salas(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_alocacao_id) REFERENCES usuarios(id) ON DELETE SET NULL
        )");
        
        // Inserir tipos de usuário padrão
        $db->exec("INSERT IGNORE INTO tipos_usuario (id, nome, descricao, permissoes) VALUES 
            (1, 'Administrador', 'Acesso total ao sistema - pode criar/editar usuários, tipos de usuário e todas as funcionalidades', '{\"admin\": true, \"relatorios\": true, \"cadastros\": true, \"usuarios\": true, \"tipos_usuario\": true, \"alocacoes\": true, \"presenca\": true, \"pagamentos\": true}'),
            (2, 'Colaborador', 'Pode alocar fiscais, mudar status e editar fiscais', '{\"admin\": false, \"relatorios\": false, \"cadastros\": false, \"usuarios\": false, \"tipos_usuario\": false, \"alocacoes\": true, \"presenca\": false, \"pagamentos\": false}'),
            (3, 'Coordenador', 'Pode alocar fiscais, mudar status, editar fiscais, dar presenças e fazer pagamentos', '{\"admin\": false, \"relatorios\": true, \"cadastros\": false, \"usuarios\": false, \"tipos_usuario\": false, \"alocacoes\": true, \"presenca\": true, \"pagamentos\": true}')
        ");
        
        // Inserir usuário administrador padrão
        $senha_admin = password_hash('admin123', PASSWORD_DEFAULT);
        $db->exec("INSERT IGNORE INTO usuarios (id, tipo_usuario_id, nome, email, senha, cpf, status) VALUES 
            (1, 1, 'Administrador', 'admin@idh.com', '$senha_admin', '00000000000', 'ativo')
        ");
        
        // Inserir configurações padrão
        $db->exec("INSERT IGNORE INTO configuracoes (chave, valor) VALUES 
            ('site_name', 'Sistema de Cadastro de Fiscais - IDH'),
            ('admin_email', 'admin@idh.com'),
            ('whatsapp_number', '+5511999999999'),
            ('max_fiscais_por_concurso', '100'),
            ('cadastro_aberto', '1'),
            ('idade_minima', '18')
        ");
        
    } catch (Exception $e) {
        error_log("Erro ao criar tabelas MySQL: " . $e->getMessage());
    }
}

// Função para obter configuração
function getConfig($chave, $padrao = '') {
    $db = getDB();
    
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT valor FROM configuracoes WHERE chave = ?");
            $stmt->execute([$chave]);
            $resultado = $stmt->fetch();
            return $resultado ? $resultado['valor'] : $padrao;
        } catch (Exception $e) {
            error_log("Erro ao buscar configuração: " . $e->getMessage());
        }
    }
    
    // Fallback para CSV
    $configuracoes = getConfiguracoesFromCSV();
    return isset($configuracoes[$chave]) ? $configuracoes[$chave] : $padrao;
}

// Função para obter concursos ativos
function getConcursosAtivos() {
    $db = getDB();
    
    if ($db) {
        try {
            $stmt = $db->query("SELECT * FROM concursos WHERE status = 'ativo' ORDER BY data_prova ASC");
            $concursos = $stmt->fetchAll();
            
            // Filtrar concursos com vagas disponíveis
            $resultado = [];
            foreach ($concursos as $concurso) {
                $fiscais_cadastrados = countFiscaisByConcurso($concurso['id']);
                $vagas_restantes = $concurso['vagas_disponiveis'] - $fiscais_cadastrados;
                
                if ($vagas_restantes > 0) {
                    $concurso['fiscais_cadastrados'] = $fiscais_cadastrados;
                    $concurso['vagas_restantes'] = $vagas_restantes;
                    $resultado[] = $concurso;
                }
            }
            
            return $resultado;
        } catch (Exception $e) {
            error_log("Erro ao buscar concursos: " . $e->getMessage());
        }
    }
    
    // Fallback para CSV
    return getConcursosAtivosFromCSV();
}

// Função para obter concurso específico
function getConcurso($id) {
    $db = getDB();
    
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT * FROM concursos WHERE id = ? AND status = 'ativo'");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Erro ao buscar concurso: " . $e->getMessage());
        }
    }
    
    // Fallback para CSV
    $concursos = getConcursosFromCSV();
    foreach ($concursos as $concurso) {
        if ($concurso['id'] == $id && $concurso['status'] === 'ativo') {
            return $concurso;
        }
    }
    
    return null;
}

// Função para contar fiscais por concurso
function countFiscaisByConcurso($concurso_id) {
    $db = getDB();
    
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM fiscais WHERE concurso_id = ? AND status IN ('ativo', 'pendente', 'validado', 'confirmado')");
            $stmt->execute([$concurso_id]);
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Erro ao contar fiscais: " . $e->getMessage());
        }
    }
    
    // Fallback para CSV
    $fiscais = getFiscaisFromCSV();
    $count = 0;
    foreach ($fiscais as $fiscal) {
        if ($fiscal['concurso_id'] == $concurso_id && in_array($fiscal['status'], ['ativo', 'pendente', 'validado', 'confirmado'])) {
            $count++;
        }
    }
    return $count;
}

// Função para obter escolas por concurso
function getEscolasByConcurso($concurso_id) {
    $db = getDB();
    
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT * FROM escolas WHERE concurso_id = ? AND status = 'ativo' ORDER BY nome");
            $stmt->execute([$concurso_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao buscar escolas: " . $e->getMessage());
        }
    }
    
    // Fallback para CSV
    return getEscolasFromCSV($concurso_id);
}

// Função para obter salas por escola
function getSalasByEscola($escola_id) {
    $db = getDB();
    
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT * FROM salas WHERE escola_id = ? AND status = 'ativo' ORDER BY nome");
            $stmt->execute([$escola_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao buscar salas: " . $e->getMessage());
        }
    }
    
    // Fallback para CSV
    return getSalasFromCSV($escola_id);
}

// Função para autenticar usuário
function autenticarUsuario($email, $senha) {
    $db = getDB();
    
    if ($db) {
        try {
            $stmt = $db->prepare("
                SELECT u.*, t.nome as tipo_nome, t.permissoes 
                FROM usuarios u 
                JOIN tipos_usuario t ON u.tipo_usuario_id = t.id 
                WHERE u.email = ? AND u.status = 'ativo'
            ");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                // Atualizar último login
                $stmt = $db->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
                $stmt->execute([$usuario['id']]);
                
                // Armazenar dados na sessão
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['user_name'] = $usuario['nome'];
                $_SESSION['user_email'] = $usuario['email'];
                $_SESSION['user_type'] = $usuario['tipo_usuario_id'];
                $_SESSION['user_permissions'] = json_decode($usuario['permissoes'], true);
                
                return true;
            }
        } catch (Exception $e) {
            error_log("Erro ao autenticar usuário: " . $e->getMessage());
        }
    }
    
    return false;
}

// Função para verificar permissões
function temPermissao($permissao) {
    if (!isset($_SESSION['user_permissions'])) {
        return false;
    }
    
    return isset($_SESSION['user_permissions'][$permissao]) && $_SESSION['user_permissions'][$permissao];
}

// Função para log de atividades
function logActivity($message, $level = 'INFO') {
    $log_file = LOG_PATH . '/system.log';
    $timestamp = date('Y-m-d H:i:s');
    $user_id = $_SESSION['user_id'] ?? 'sistema';
    $log_entry = "[$timestamp] [$level] [User:$user_id] $message" . PHP_EOL;
    
    // Criar diretório de logs se não existir
    if (!is_dir(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    
    // Verificar se o arquivo de log existe e tem permissões corretas
    if (!file_exists($log_file)) {
        touch($log_file);
        chmod($log_file, 0644);
    }
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Funções de fallback para CSV (mantidas para compatibilidade)
function getConcursosFromCSV() {
    $csv_file = 'data/concursos.csv';
    if (!file_exists($csv_file)) {
        return [];
    }
    
    $concursos = [];
    $handle = fopen($csv_file, 'r');
    $header = fgetcsv($handle);
    
    while (($data = fgetcsv($handle)) !== false) {
        $concurso = array_combine($header, $data);
        $concursos[] = $concurso;
    }
    
    fclose($handle);
    return $concursos;
}

function getConcursosAtivosFromCSV() {
    $concursos = getConcursosFromCSV();
    return array_filter($concursos, function($concurso) {
        return $concurso['status'] === 'ativo';
    });
}

function getFiscaisFromCSV() {
    $csv_file = 'data/fiscais.csv';
    if (!file_exists($csv_file)) {
        return [];
    }
    
    $fiscais = [];
    $handle = fopen($csv_file, 'r');
    $header = fgetcsv($handle);
    
    while (($data = fgetcsv($handle)) !== false) {
        $fiscal = array_combine($header, $data);
        $fiscais[] = $fiscal;
    }
    
    fclose($handle);
    return $fiscais;
}

function getEscolasFromCSV($concurso_id) {
    $csv_file = 'data/escolas.csv';
    if (!file_exists($csv_file)) {
        return [];
    }
    
    $escolas = [];
    $handle = fopen($csv_file, 'r');
    $header = fgetcsv($handle);
    
    while (($data = fgetcsv($handle)) !== false) {
        $escola = array_combine($header, $data);
        if ($escola['concurso_id'] == $concurso_id && $escola['status'] === 'ativo') {
            $escolas[] = $escola;
        }
    }
    
    fclose($handle);
    return $escolas;
}

function getSalasFromCSV($escola_id) {
    $csv_file = 'data/salas.csv';
    if (!file_exists($csv_file)) {
        return [];
    }
    
    $salas = [];
    $handle = fopen($csv_file, 'r');
    $header = fgetcsv($handle);
    
    while (($data = fgetcsv($handle)) !== false) {
        $sala = array_combine($header, $data);
        if ($sala['escola_id'] == $escola_id && $sala['status'] === 'ativo') {
            $salas[] = $sala;
        }
    }
    
    fclose($handle);
    return $salas;
}

function getConfiguracoesFromCSV() {
    $csv_file = 'data/configuracoes.csv';
    if (!file_exists($csv_file)) {
        return [
            'site_name' => 'Sistema de Cadastro de Fiscais - IDH',
            'admin_email' => 'admin@idh.com',
            'whatsapp_number' => '+5511999999999',
            'max_fiscais_por_concurso' => '100'
        ];
    }
    
    $configuracoes = [];
    $handle = fopen($csv_file, 'r');
    $header = fgetcsv($handle);
    
    while (($data = fgetcsv($handle)) !== false) {
        $config = array_combine($header, $data);
        $configuracoes[$config['chave']] = $config['valor'];
    }
    
    fclose($handle);
    return $configuracoes;
}

// Funções de segurança
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Funções de mensagens
function setMessage($message, $type = 'info') {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
}

function getMessage() {
    $message = $_SESSION['message'] ?? null;
    unset($_SESSION['message']);
    return $message;
}

// Funções de redirecionamento
function redirect($url) {
    header("Location: $url");
    exit;
}

// Funções de autenticação
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_permissions']['admin']) && $_SESSION['user_permissions']['admin'];
}

function isColaborador() {
    return isset($_SESSION['user_permissions']['alocacoes']) && $_SESSION['user_permissions']['alocacoes'];
}

function isCoordenador() {
    return isset($_SESSION['user_permissions']['presenca']) && $_SESSION['user_permissions']['presenca'];
}

function temPermissaoUsuarios() {
    return isset($_SESSION['user_permissions']['usuarios']) && $_SESSION['user_permissions']['usuarios'];
}

function temPermissaoTiposUsuario() {
    return isset($_SESSION['user_permissions']['tipos_usuario']) && $_SESSION['user_permissions']['tipos_usuario'];
}

function temPermissaoAlocacoes() {
    return isset($_SESSION['user_permissions']['alocacoes']) && $_SESSION['user_permissions']['alocacoes'];
}

function temPermissaoPresenca() {
    return isset($_SESSION['user_permissions']['presenca']) && $_SESSION['user_permissions']['presenca'];
}

function temPermissaoPagamentos() {
    return isset($_SESSION['user_permissions']['pagamentos']) && $_SESSION['user_permissions']['pagamentos'];
}

// Função para sanitizar entrada
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Função para verificar se o sistema está funcionando
function checkSystemStatus() {
    $status = [
        'mysql_available' => extension_loaded('pdo_mysql'),
        'database_connected' => false,
        'logs_writable' => is_writable(LOG_PATH),
        'data_writable' => is_writable('data'),
        'php_version' => PHP_VERSION,
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time')
    ];
    
    // Testar conexão com MySQL
    try {
        $db = getDB();
        $status['database_connected'] = $db !== null;
    } catch (Exception $e) {
        $status['database_connected'] = false;
    }
    
    return $status;
}

// Funções de formatação
function formatCPF($cpf) {
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) === 11) {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }
    return $cpf;
}

function formatPhone($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) === 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
    }
    return $phone;
}
?> 
