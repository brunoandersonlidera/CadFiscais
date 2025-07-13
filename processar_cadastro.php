<?php
require_once 'config.php';
require_once 'ddi.php';

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastro.php');
    exit;
}

// Verificar se o cadastro está aberto
$cadastro_aberto = getConfig('cadastro_aberto');
if ($cadastro_aberto != '1') {
    header('Location: index.php?msg=cadastro_fechado');
    exit;
}

try {
    // Validar dados obrigatórios
    $campos_obrigatorios = [
        'concurso_id', 'nome', 'email', 'ddi', 'celular', 'genero', 
        'cpf', 'data_nascimento', 'endereco'
    ];
    
    foreach ($campos_obrigatorios as $campo) {
        if (!isset($_POST[$campo]) || empty(trim($_POST[$campo]))) {
            throw new Exception("Campo obrigatório não preenchido: $campo");
        }
    }
    
    // Obter e validar dados
    $concurso_id = (int)$_POST['concurso_id'];
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $ddi = trim($_POST['ddi']);
    $celular = trim($_POST['celular']);
    $genero = trim($_POST['genero']);
    $cpf = trim($_POST['cpf']);
    $data_nascimento = trim($_POST['data_nascimento']);
    $endereco = trim($_POST['endereco']);
    $melhor_horario = isset($_POST['melhor_horario']) ? trim($_POST['melhor_horario']) : '';
    $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : '';
    $whatsapp = '';
    $usa_whatsapp = isset($_POST['usa_whatsapp']) && $_POST['usa_whatsapp'] == '1';
    
    if ($usa_whatsapp && isset($_POST['whatsapp']) && !empty(trim($_POST['whatsapp']))) {
        $whatsapp = trim($_POST['whatsapp']);
    }
    
    // Validar aceite dos termos
    $aceite_termos = isset($_POST['aceite_termos']) && $_POST['aceite_termos'] == 'on';
    if (!$aceite_termos) {
        throw new Exception("Você deve aceitar os termos para continuar");
    }
    
    // Validar DDI
    if (!validateDDI($ddi)) {
        throw new Exception("DDI inválido");
    }
    
    // Validar gênero
    if (!in_array($genero, ['M', 'F'])) {
        throw new Exception("Gênero inválido");
    }
    
    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("E-mail inválido");
    }
    
    // Validar CPF
    $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf_limpo) !== 11) {
        throw new Exception("CPF inválido");
    }
    
    // Validar CPF usando algoritmo oficial
    if (!validateCPF($cpf_limpo)) {
        throw new Exception("CPF inválido");
    }
    
    // Validar data de nascimento e idade
    // Converter formato dd/mm/aaaa para aaaa-mm-dd se necessário
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data_nascimento)) {
        $data_parts = explode('/', $data_nascimento);
        $data_nascimento = $data_parts[2] . '-' . $data_parts[1] . '-' . $data_parts[0];
    }
    
    $data_nasc = new DateTime($data_nascimento);
    $hoje = new DateTime();
    $idade = $hoje->diff($data_nasc)->y;
    
    $idade_minima = (int)getConfig('idade_minima', '18');
    if ($idade < $idade_minima) {
        throw new Exception("Você deve ter pelo menos $idade_minima anos para se cadastrar");
    }
    
    // Validar celular brasileiro se DDI for +55
    if ($ddi === '+55') {
        $celular_limpo = preg_replace('/[^0-9]/', '', $celular);
        if (!validateBrazilianPhone($celular_limpo)) {
            throw new Exception("Número de celular brasileiro inválido");
        }
    }
    
    // Validar WhatsApp se fornecido
    if ($usa_whatsapp && !empty($whatsapp)) {
        $whatsapp_limpo = preg_replace('/[^0-9]/', '', $whatsapp);
        if ($ddi === '+55' && !validateBrazilianPhone($whatsapp_limpo)) {
            throw new Exception("Número de WhatsApp brasileiro inválido");
        }
    }
    
    // Verificar se o concurso existe e está ativo
    $concurso = getConcurso($concurso_id);
    if (!$concurso || $concurso['status'] !== 'ativo') {
        throw new Exception("Concurso não encontrado ou inativo");
    }
    
    // Verificar se há vagas disponíveis
    $fiscais_cadastrados = countFiscaisByConcurso($concurso_id);
    if ($fiscais_cadastrados >= $concurso['vagas_disponiveis']) {
        throw new Exception("Não há mais vagas disponíveis para este concurso");
    }
    
    // Verificar se CPF já está cadastrado neste concurso
    if (fiscalExists($cpf_limpo, $concurso_id)) {
        throw new Exception("CPF já cadastrado neste concurso");
    }
    
    // Verificar se email já está cadastrado neste concurso
    if (fiscalEmailExists($email, $concurso_id)) {
        throw new Exception("E-mail já cadastrado neste concurso");
    }
    
    // Preparar dados para inserção
    $dados_fiscal = [
        'concurso_id' => $concurso_id,
        'nome' => $nome,
        'email' => $email,
        'ddi' => $ddi,
        'celular' => $celular,
        'whatsapp' => $whatsapp,
        'cpf' => $cpf_limpo,
        'data_nascimento' => $data_nascimento,
        'genero' => $genero,
        'endereco' => $endereco,
        'melhor_horario' => $melhor_horario,
        'observacoes' => $observacoes,
        'status' => 'pendente',
        'status_contato' => 'nao_contatado',
        'aceite_termos' => $aceite_termos ? 1 : 0,
        'data_aceite_termos' => $aceite_termos ? date('Y-m-d H:i:s') : null,
        'ip_cadastro' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ];
    
    // Inserir fiscal
    $fiscal_id = insertFiscal($dados_fiscal);
    
    if (!$fiscal_id) {
        throw new Exception("Erro ao salvar cadastro");
    }
    
    // Log da atividade
    logActivity("Novo fiscal cadastrado: $nome (CPF: $cpf_limpo) - Concurso ID: $concurso_id", 'INFO');
    
    // Redirecionar para página de sucesso
    header("Location: sucesso.php?id=$fiscal_id");
    exit;
    
} catch (Exception $e) {
    logActivity('Erro no cadastro: ' . $e->getMessage(), 'ERROR');
    
    // Redirecionar com erro
    $erro = urlencode($e->getMessage());
    $concurso_id = isset($_POST['concurso_id']) ? (int)$_POST['concurso_id'] : '';
    
    if ($concurso_id) {
        header("Location: cadastro.php?concurso=$concurso_id&erro=$erro");
    } else {
        header("Location: cadastro.php?erro=$erro");
    }
    exit;
}

// Funções auxiliares
function fiscalExists($cpf, $concurso_id) {
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM fiscais WHERE cpf = ? AND concurso_id = ?");
        $stmt->execute([$cpf, $concurso_id]);
        return $stmt->fetchColumn() > 0;
    } else {
        // Fallback para CSV
        $fiscais = getFiscaisFromCSV();
        foreach ($fiscais as $fiscal) {
            if ($fiscal['cpf'] === $cpf && $fiscal['concurso_id'] == $concurso_id) {
                return true;
            }
        }
        return false;
    }
}

function fiscalEmailExists($email, $concurso_id) {
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM fiscais WHERE email = ? AND concurso_id = ?");
        $stmt->execute([$email, $concurso_id]);
        return $stmt->fetchColumn() > 0;
    } else {
        // Fallback para CSV
        $fiscais = getFiscaisFromCSV();
        foreach ($fiscais as $fiscal) {
            if ($fiscal['email'] === $email && $fiscal['concurso_id'] == $concurso_id) {
                return true;
            }
        }
        return false;
    }
}

function insertFiscal($dados) {
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("
            INSERT INTO fiscais (
                concurso_id, nome, email, ddi, celular, whatsapp, cpf, 
                data_nascimento, genero, endereco, melhor_horario, observacoes, 
                status, status_contato, aceite_termos, data_aceite_termos, 
                ip_cadastro, user_agent, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        $stmt->execute([
            $dados['concurso_id'], $dados['nome'], $dados['email'], 
            $dados['ddi'], $dados['celular'], $dados['whatsapp'], $dados['cpf'],
            $dados['data_nascimento'], $dados['genero'], $dados['endereco'],
            $dados['melhor_horario'], $dados['observacoes'], $dados['status'],
            $dados['status_contato'], $dados['aceite_termos'], $dados['data_aceite_termos'],
            $dados['ip_cadastro'], $dados['user_agent']
        ]);
        
        return $db->lastInsertId();
    } else {
        // Fallback para CSV
        return insertFiscalCSV($dados);
    }
}

// Função para validar CPF
function validateCPF($cpf) {
    // Remove caracteres não numéricos
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    // Verifica se tem 11 dígitos
    if (strlen($cpf) !== 11) {
        return false;
    }
    
    // Verifica se todos os dígitos são iguais
    if (preg_match('/^(\d)\1+$/', $cpf)) {
        return false;
    }
    
    // Calcula primeiro dígito verificador
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += intval($cpf[$i]) * (10 - $i);
    }
    $remainder = ($sum * 10) % 11;
    if ($remainder == 10 || $remainder == 11) {
        $remainder = 0;
    }
    if ($remainder != intval($cpf[9])) {
        return false;
    }
    
    // Calcula segundo dígito verificador
    $sum = 0;
    for ($i = 0; $i < 10; $i++) {
        $sum += intval($cpf[$i]) * (11 - $i);
    }
    $remainder = ($sum * 10) % 11;
    if ($remainder == 10 || $remainder == 11) {
        $remainder = 0;
    }
    if ($remainder != intval($cpf[10])) {
        return false;
    }
    
    return true;
}

function insertFiscalCSV($dados) {
    $csv_file = 'data/fiscais.csv';
    
    // Criar arquivo se não existir
    if (!file_exists($csv_file)) {
        $header = "id,concurso_id,nome,email,ddi,celular,whatsapp,cpf,data_nascimento,genero,endereco,melhor_horario,observacoes,status,status_contato,created_at\n";
        file_put_contents($csv_file, $header);
    }
    
    // Gerar ID único
    $fiscais = getFiscaisFromCSV();
    $novo_id = 1;
    if (!empty($fiscais)) {
        $novo_id = max(array_column($fiscais, 'id')) + 1;
    }
    
    // Preparar linha para CSV
    $linha = [
        $novo_id,
        $dados['concurso_id'],
        $dados['nome'],
        $dados['email'],
        $dados['ddi'],
        $dados['celular'],
        $dados['whatsapp'],
        $dados['cpf'],
        $dados['data_nascimento'],
        $dados['genero'],
        $dados['endereco'],
        $dados['melhor_horario'],
        $dados['observacoes'],
        $dados['status'],
        $dados['status_contato'],
        date('Y-m-d H:i:s')
    ];
    
    // Adicionar ao CSV
    $handle = fopen($csv_file, 'a');
    fputcsv($handle, $linha);
    fclose($handle);
    
    return $novo_id;
}
?> 