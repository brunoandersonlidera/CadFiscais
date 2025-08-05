<?php
// Limpar qualquer saída anterior
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

require_once 'config.php';
require_once 'includes/pdf_base.php';
require_once 'TCPDF/tcpdf_barcodes_2d.php';

// Verificar se CPF foi fornecido
$cpf = $_GET['cpf'] ?? '';
if (empty($cpf)) {
    die('CPF não fornecido.');
}

// Remover formatação do CPF
$cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);

if (strlen($cpf_limpo) !== 11) {
    die('CPF inválido.');
}

try {
    $db = getDB();
    
    // Buscar dados do fiscal e treinamento
    $sql = "
        SELECT DISTINCT f.*, c.titulo, c.numero_concurso, c.orgao, c.cidade, c.estado, 
               p.data_presenca, c.data_prova, c.ano_concurso, c.data_treinamento
        FROM fiscais f
        INNER JOIN presenca p ON f.id = p.fiscal_id
        INNER JOIN concursos c ON f.concurso_id = c.id
        WHERE f.cpf = ? AND p.tipo_presenca = 'treinamento' AND p.status = 'presente'
        ORDER BY p.data_presenca DESC
        LIMIT 1
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$cpf_limpo]);
    $fiscal = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$fiscal) {
        die('Não foi encontrado registro de participação em treinamento para este CPF.');
    }
    
    // Verificar se já existe certificado para este fiscal e treinamento
    $check_cert_sql = "
        SELECT numero_certificado, data_geracao 
        FROM certificados 
        WHERE fiscal_id = ? AND concurso_id = ? AND tipo_treinamento = 'treinamento' AND status = 'ativo'
    ";
    
    $check_stmt = $db->prepare($check_cert_sql);
    $check_stmt->execute([$fiscal['id'], $fiscal['concurso_id']]);
    $certificado_existente = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Se já existe certificado, usar o número existente
    if ($certificado_existente) {
        $numero_certificado = $certificado_existente['numero_certificado'];
    } else {
        // Gerar novo número de certificado
        $numero_certificado = strtoupper(substr(md5($cpf_limpo . $fiscal['data_presenca'] . $fiscal['concurso_id'] . time()), 0, 8));
    }
    
    // Criar PDF
    $pdf = new PDFInstituto('L', 'mm', 'A4'); // Paisagem para certificado
    $pdf->SetCreator('Sistema IDH');
    $pdf->SetAuthor('Instituto Dignidade Humana');
    $pdf->SetTitle('Certificado de Treinamento - ' . $fiscal['nome']);
    $pdf->SetSubject('Certificado de Participação em Treinamento');
    $pdf->SetKeywords('certificado, treinamento, fiscal');
    
    // Configurar dados do instituto
    $logo_path = 'logos/instituto.png';
    if (!file_exists($logo_path)) {
        $logo_path = 'logos/_instituto.png';
    }
    
    // Configurar informações institucionais detalhadas (mesmo padrão do relatório)
    $instituto_info = getConfig('info_institucional', 'Instituto Dignidade Humana\nEndereço: ...\nContato: ...');
    
    // Se não houver configuração específica, usar dados padrão mais detalhados
    if ($instituto_info === 'Instituto Dignidade Humana\nEndereço: ...\nContato: ...') {
        $instituto_info = getConfig('instituto_nome', 'Instituto Dignidade Humana') . "\n" . 
                         getConfig('instituto_endereco', 'Endereço do Instituto') . "\n" . 
                         "Tel: " . getConfig('instituto_telefone', '(00) 0000-0000') . " | Email: " . getConfig('instituto_email', 'contato@instituto.com');
    }
    
    $pdf->setInstitutoData(
        getConfig('instituto_nome', 'Instituto Dignidade Humana'),
        $logo_path,
        $instituto_info
    );
    
    // Configurar margens
    $pdf->SetMargins(20, 50, 20);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(15);
    $pdf->SetAutoPageBreak(TRUE, 30);
    
    // Adicionar página
    $pdf->AddPage();
    
    // Título principal do certificado
    $pdf->SetFont('helvetica', 'B', 28);
    $pdf->SetTextColor(0, 123, 255);
    $pdf->Ln(1);
    $pdf->Cell(0, 10, 'CERTIFICADO', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 10, 'DE PARTICIPAÇÃO EM TREINAMENTO', 0, 1, 'C');
    
    $pdf->Ln(15);
    
    // Texto do certificado
    $pdf->SetFont('helvetica', '', 14);
    $pdf->SetTextColor(0, 0, 0);
    
    $texto_certificado = "Certificamos que";
    $pdf->Cell(0, 8, $texto_certificado, 0, 1, 'C');
    
    $pdf->Ln(5);
    
    // Nome do fiscal em destaque
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetTextColor(0, 123, 255);
    $pdf->Cell(0, 12, strtoupper($fiscal['nome']), 0, 1, 'C');
    
    $pdf->Ln(5);
    
    // CPF
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 6, 'CPF: ' . formatCPF($fiscal['cpf']), 0, 1, 'C');
    
    $pdf->Ln(10);
    
    // Texto principal
    $pdf->SetFont('helvetica', '', 14);
    $pdf->SetTextColor(0, 0, 0);
    
    $texto_principal = "Participou do Treinamento para Fiscal de Provas em Concursos Públicos e Seletivos";
    $pdf->Cell(0, 8, $texto_principal, 0, 1, 'C');
    
    $pdf->Ln(5);
    
    // Nome do instituto
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(0, 123, 255);
    $instituto_texto = getConfig('instituto_nome', 'Promovido pelo Instituto Dignidade Humana');
    $pdf->Cell(0, 10, $instituto_texto, 0, 1, 'C');
    
    $pdf->Ln(20);
    
    // Data do treinamento
    $data_treinamento_real = !empty($fiscal['data_treinamento']) && $fiscal['data_treinamento'] !== '0000-00-00' 
        ? $fiscal['data_treinamento'] 
        : $fiscal['data_presenca'];
    $data_treinamento = date('d/m/Y', strtotime($data_treinamento_real));
    $texto_data = "Treinamento realizado em " . $data_treinamento . ".";
    $pdf->Cell(0, 8, $texto_data, 0, 1, 'C');
    
    $pdf->Ln(15);
    
    // Carga horária (padrão)
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 6, 'Carga horária: 4 horas', 0, 1, 'C');
    
    $pdf->Ln(15);
    
    // Data de emissão
    $data_emissao = date('d/m/Y');
    $cidade_emissao = $fiscal['cidade'] . ' - ' . $fiscal['estado'];
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 6, $cidade_emissao . ', ' . $data_emissao, 0, 1, 'C');
    
    $pdf->Ln(10);
    
    // Assinatura digital
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 6, getConfig('instituto_nome', 'Instituto Dignidade Humana'), 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Documento gerado digitalmente', 0, 1, 'C');
    
    // Gerar QR Code com link de validação - centralizado
    $validation_url = getConfig('site_url', 'https://fiscais.lidera.srv.br') . "/validar_certificado.php?numero_certificado=" . urlencode($numero_certificado);
    $qr_size = 25;
    $qr_x = ($pdf->getPageWidth() - $qr_size) / 2; // Centralizar horizontalmente
    $qr_y = $pdf->getPageHeight() - $qr_size - 45;
    $pdf->write2DBarcode($validation_url, 'QRCODE,H', $qr_x, $qr_y, $qr_size, $qr_size, array(), 'T,R');
        
    // Número do certificado - centralizado abaixo do QR code (usando variável já definida)
    $pdf->SetXY(0, $qr_y + $qr_size + 2);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(0, 4, 'Certificado Nº: ' . $numero_certificado, 0, 1, 'C');
    
    // URL de validação - centralizada
    $pdf->SetXY(0, $qr_y + $qr_size + 6);
    $pdf->SetFont('helvetica', '', 7);
    $pdf->Cell(0, 3, 'Validar em: ' . getConfig('site_url', 'https://fiscais.lidera.srv.br') . '/validar_certificado.php', 0, 1, 'C');
    
    // Salvar certificado no banco de dados (apenas se não existir)
    if (!$certificado_existente) {
        $data_treinamento_db = !empty($fiscal['data_treinamento']) && $fiscal['data_treinamento'] !== '0000-00-00' 
            ? $fiscal['data_treinamento'] 
            : ($fiscal['data_presenca'] !== '0000-00-00' ? $fiscal['data_presenca'] : date('Y-m-d'));
        
        $insert_cert_sql = "
            INSERT INTO certificados (fiscal_id, concurso_id, numero_certificado, tipo_treinamento, data_treinamento, data_geracao, status)
            VALUES (?, ?, ?, 'treinamento', ?, NOW(), 'ativo')
        ";
        
        $insert_stmt = $db->prepare($insert_cert_sql);
        $insert_stmt->execute([$fiscal['id'], $fiscal['concurso_id'], $numero_certificado, $data_treinamento_db]);
    }
    
    // Limpar buffer e enviar PDF
    ob_end_clean();
    
    $filename = 'Certificado_Treinamento_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $fiscal['nome']) . '.pdf';
    $pdf->Output($filename, 'I'); // D = Download
    
} catch (Exception $e) {
    ob_end_clean();
    die('Erro ao gerar certificado: ' . $e->getMessage());
}
?>