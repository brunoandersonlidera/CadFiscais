<?php
// Prevenir qualquer saída antes do PDF
ob_start();

require_once '../config.php';
require_once '../TCPDF/tcpdf.php';

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
}

// Se não especificou concurso, buscar o primeiro concurso ativo
if (!$concurso) {
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
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 15, 'RELATÓRIO DE FISCAIS', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln();
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Criar nova instância do PDF
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurar informações do documento
$pdf->SetCreator('Sistema CadFiscais');
$pdf->SetAuthor('IDH');
$pdf->SetTitle('Relatório de Fiscais');

// Configurar margens
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Configurar quebras de página automáticas
$pdf->SetAutoPageBreak(TRUE, 25);

// Configurar fonte
$pdf->SetFont('helvetica', '', 10);

// Adicionar página
$pdf->AddPage();

// Título do relatório
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'RELATÓRIO DE FISCAIS', 0, 1, 'C');
$pdf->Ln(5);

// Termo de aceite do concurso
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'TERMO DE ACEITE DOS FISCAIS', 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('helvetica', '', 10);
if ($concurso && !empty($concurso['termos_aceite'])) {
    $termo = $concurso['termos_aceite'];
} else {
    $termo = "Declaro, para os devidos fins, que estou ciente e de acordo com as normas e responsabilidades atribuídas à função de fiscal, conforme estabelecido pelo Instituto Dignidade Humana e pelo edital do concurso.";
}
$pdf->MultiCell(0, 6, $termo, 0, 'J');
$pdf->Ln(10);

// Informações do concurso
if ($concurso) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Concurso: ' . $concurso['titulo'], 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Data da Prova: ' . date('d/m/Y', strtotime($concurso['data_prova'])), 0, 1);
    $pdf->Ln(5);
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
$pdf->Ln(10);

// Linhas para assinaturas
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(60, 8, '_____________________________', 0, 0, 'C');
$pdf->Cell(60, 8, '_____________________________', 0, 0, 'C');
$pdf->Cell(60, 8, '_____________________________', 0, 1, 'C');

$pdf->Cell(60, 6, 'Assinatura 1', 0, 0, 'C');
$pdf->Cell(60, 6, 'Assinatura 2', 0, 0, 'C');
$pdf->Cell(60, 6, 'Assinatura 3', 0, 1, 'C');

// Estatísticas
$pdf->Ln(15);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 8, 'ESTATÍSTICAS:', 0, 1);

$total_fiscais = count($fiscais);
$aprovados = count(array_filter($fiscais, function($f) { return $f['status'] == 'aprovado'; }));

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Total de Fiscais: ' . $total_fiscais, 0, 1);
$pdf->Cell(0, 6, 'Fiscais Aprovados: ' . $aprovados, 0, 1);

// Data e hora do relatório
$pdf->Ln(10);
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