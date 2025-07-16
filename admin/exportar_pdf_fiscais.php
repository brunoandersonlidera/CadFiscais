<?php
// Prevenir qualquer saída antes do PDF
ob_start();

require_once '../config.php';
require_once '../includes/pdf_base.php';

// Verificar se é admin
if (!isAdmin()) {
    ob_end_clean();
    redirect('../login.php');
}

$db = getDB();

// Parâmetros
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : 0;

// Buscar dados do concurso
$concurso = null;
if ($concurso_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM concursos WHERE id = ?");
        $stmt->execute([$concurso_id]);
        $concurso = $stmt->fetch();
    } catch (Exception $e) {
        logActivity('Erro ao buscar concurso: ' . $e->getMessage(), 'ERROR');
    }
} else {
    // Se não especificado, buscar o concurso ativo mais recente
    try {
        $stmt = $db->query("SELECT * FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC LIMIT 1");
        $concurso = $stmt->fetch();
    } catch (Exception $e) {
        logActivity('Erro ao buscar concurso ativo: ' . $e->getMessage(), 'ERROR');
    }
}

// Buscar fiscais
try {
    $sql = "SELECT f.nome, f.cpf, f.status, f.created_at
            FROM fiscais f";
    
    if ($concurso_id) {
        $sql .= " WHERE f.concurso_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$concurso_id]);
    } else {
        $sql .= " ORDER BY f.nome";
        $stmt = $db->prepare($sql);
        $stmt->execute();
    }
    
    $fiscais = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar fiscais: ' . $e->getMessage(), 'ERROR');
    $fiscais = [];
}

// Criar PDF
$instituto_nome = getConfig('instituto_nome', 'Instituto Dignidade Humana');
$instituto_logo = __DIR__ . '/../logos/instituto.png';
$instituto_info = getConfig('info_institucional', 'Instituto Dignidade Humana\nEndereço: ...\nContato: ...');
$pdf = new PDFInstituto('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setInstitutoData($instituto_nome, $instituto_logo, $instituto_info);

// Configurar informações do documento
$pdf->SetCreator('Sistema CadFiscais');
$pdf->SetAuthor('IDH');
$pdf->SetTitle('Relatório de Fiscais');

// Configurar margens
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Configurar quebras de página automáticas com margem maior
$pdf->SetAutoPageBreak(TRUE, 35);

// Configurar fonte
$pdf->SetFont('helvetica', '', 10);

// Adicionar página
$pdf->AddPage();
$pdf->SetY(30); // Posicionar bem abaixo do cabeçalho

// Informações do concurso centralizadas
if ($concurso) {
    $pdf->SetFont('helvetica', 'B', 15);
    $pdf->Cell(0, 8, ' Estado De Mato Grosso ', 0, 1, 'C');
    $pdf->Cell(0, 8, $concurso['orgao'] . ' - ' . $concurso['cidade'] . ' - ' . $concurso['estado'], 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 15);
    $pdf->Cell(0, 7, $concurso['titulo'] . ' - ' . $concurso['numero_concurso'] . '/' . $concurso['ano_concurso'], 0, 1, 'C');
    $pdf->Ln(8);
}

// Título do relatório
$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell(0, 10, 'RELATÓRIO DE FISCAIS INSCRITOS', 0, 1, 'C');
$pdf->Ln(5);

// Termo de compromisso
$pdf->SetFont('helvetica', '', 10);
if ($concurso && !empty($concurso['termos_aceite'])) {
    $termo = $concurso['termos_aceite'];
} else {
    $termo = "Declaro, para os devidos fins, que estou ciente e de acordo com as normas e responsabilidades atribuídas à função de fiscal, conforme estabelecido pelo Instituto Dignidade Humana e pelo edital do concurso.";
}

// Impressão robusta: termo parágrafo a parágrafo, checando espaço antes de cada um
$paragrafos = preg_split('/\r\n|\r|\n/', $termo);
foreach ($paragrafos as $paragrafo) {
    if (trim($paragrafo) === '') continue;
    // Estimar altura do parágrafo (6 pontos por linha, 80 caracteres por linha)
    $linhas = ceil(strlen($paragrafo) / 80);
    $altura = $linhas * 6 + 2; // 2 extra de espaçamento
    $espaco_restante = $pdf->getPageHeight() - $pdf->GetY() - 35; // 35 = margem inferior segura
    if ($altura > $espaco_restante) {
        $pdf->AddPage();
        $pdf->SetY(40); // Posicionar bem abaixo do cabeçalho
    }
    $pdf->MultiCell(0, 6, $paragrafo, 0, 'L');
    $pdf->Ln(2);
}
$pdf->Ln(8); // Espaço extra após o termo

// Verificar se há espaço suficiente para a tabela antes de começar
// Se estiver muito próximo do cabeçalho, descer a posição
if ($pdf->GetY() < 60) {
    $pdf->SetY(60); // Posicionar bem abaixo do cabeçalho
}

// Cabeçalho da tabela
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(80, 8, 'Nome', 1, 0, 'L', true);
$pdf->Cell(35, 8, 'CPF', 1, 0, 'L', true);
$pdf->Cell(25, 8, 'Status', 1, 0, 'L', true);
$pdf->Cell(35, 8, 'Data Cadastro', 1, 1, 'L', true);

// Dados dos fiscais
$pdf->SetFont('helvetica', '', 8);
$pdf->SetFillColor(255, 255, 255);

foreach ($fiscais as $fiscal) {
    // Verificar se precisa de nova página
    if ($pdf->GetY() > 250) {
        $pdf->AddPage();
        $pdf->SetY(35); // Posicionar mais próximo do cabeçalho
        // Verificar se há espaço suficiente para a tabela
        if ($pdf->GetY() < 45) {
            $pdf->SetY(45); // Posicionar abaixo do cabeçalho
        }
        // Reimprimir cabeçalho
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(80, 8, 'Nome', 1, 0, 'L', true);
        $pdf->Cell(35, 8, 'CPF', 1, 0, 'L', true);
        $pdf->Cell(25, 8, 'Status', 1, 0, 'L', true);
        $pdf->Cell(35, 8, 'Data Cadastro', 1, 1, 'L', true);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetFillColor(255, 255, 255);
    }
    
    $pdf->Cell(80, 6, substr($fiscal['nome'], 0, 35), 1, 0, 'L');
    $pdf->Cell(35, 6, $fiscal['cpf'], 1, 0, 'L');
    $pdf->Cell(25, 6, ucfirst($fiscal['status']), 1, 0, 'L');
    $pdf->Cell(35, 6, date('d/m/Y', strtotime($fiscal['created_at'])), 1, 1, 'L');
}

// Espaço para assinaturas
$pdf->Ln(20);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 8, 'ASSINATURAS:', 0, 1, 'C');
$pdf->Ln(5);

// Linhas para assinaturas
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(60, 8, '_____________________________', 0, 0, 'C');
$pdf->Cell(60, 8, '_____________________________', 0, 0, 'C');
$pdf->Cell(60, 8, '_____________________________', 0, 1, 'C');


// Estatísticas
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 8, 'ESTATÍSTICAS:', 0, 1);

$total_fiscais = count($fiscais);
$aprovados = count(array_filter($fiscais, function($f) { return $f['status'] == 'aprovado'; }));

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Total de Fiscais: ' . $total_fiscais, 0, 1);
$pdf->Cell(0, 6, 'Fiscais Aprovados: ' . $aprovados, 0, 1);

// Data e hora do relatório
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 6, 'Relatório gerado em: ' . date('d/m/Y H:i:s'), 0, 1);

// Limpar qualquer saída anterior
ob_end_clean();

// Configurar headers para download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="relatorio_fiscais_' . date('Y-m-d_H-i-s') . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Saída do PDF
$pdf->Output('relatorio_fiscais_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
exit;
?> 