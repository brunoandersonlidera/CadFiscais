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

// Buscar fiscais aprovados
try {
    $sql = "SELECT f.nome, f.created_at,
                   CASE WHEN af.id IS NOT NULL THEN 'Alocado' ELSE 'Não Alocado' END as status_alocacao,
                   e.nome as escola_nome,
                   s.nome as sala_nome
            FROM fiscais f
            LEFT JOIN alocacoes_fiscais af ON f.id = af.fiscal_id AND af.status = 'ativo'
            LEFT JOIN salas s ON af.sala_id = s.id
            LEFT JOIN escolas e ON s.escola_id = e.id
            WHERE f.status = 'aprovado'";
    
    if ($concurso_id) {
        $sql .= " AND f.concurso_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$concurso_id]);
    } else {
        $sql .= " ORDER BY f.nome";
        $stmt = $db->prepare($sql);
        $stmt->execute();
    }
    
    $fiscais = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar fiscais aprovados: ' . $e->getMessage(), 'ERROR');
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
$pdf->SetTitle('Relatório de Fiscais Aprovados - Mural');

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
$pdf->SetY(40); // Posicionar bem abaixo do cabeçalho

// Informações do concurso centralizadas
if ($concurso) {
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->Cell(0, 8, $concurso['orgao'] . ' - ' . $concurso['cidade'] . ' - ' . $concurso['estado'], 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, $concurso['titulo'] . ' - ' . $concurso['numero_concurso'] . '/' . $concurso['ano_concurso'], 0, 1, 'C');
    $pdf->Ln(8);
}

// Título do relatório
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'RELATÓRIO DE FISCAIS APROVADOS - MURAL', 0, 1, 'C');
$pdf->Ln(5);

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
$pdf->Cell(60, 8, 'Nome', 1, 0, 'L', true);
$pdf->Cell(30, 8, 'Alocação', 1, 0, 'L', true);
$pdf->Cell(50, 8, 'Escola', 1, 0, 'L', true);
$pdf->Cell(30, 8, 'Sala', 1, 1, 'L', true);

// Dados dos fiscais
$pdf->SetFont('helvetica', '', 8);
$pdf->SetFillColor(255, 255, 255);

foreach ($fiscais as $fiscal) {
    // Verificar se precisa de nova página
    if ($pdf->GetY() > 250) {
        $pdf->AddPage();
        $pdf->SetY(35); // Posicionar mais próximo do cabeçalho
        // Reimprimir cabeçalho
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(60, 8, 'Nome', 1, 0, 'L', true);
        $pdf->Cell(30, 8, 'Alocação', 1, 0, 'L', true);
        $pdf->Cell(50, 8, 'Escola', 1, 0, 'L', true);
        $pdf->Cell(30, 8, 'Sala', 1, 1, 'L', true);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetFillColor(255, 255, 255);
    }
    $pdf->Cell(60, 6, substr($fiscal['nome'], 0, 35), 1, 0, 'L');
    $pdf->Cell(30, 6, $fiscal['status_alocacao'], 1, 0, 'L');
    $pdf->Cell(50, 6, substr($fiscal['escola_nome'] ?? '-', 0, 22), 1, 0, 'L');
    $pdf->Cell(30, 6, substr($fiscal['sala_nome'] ?? '-', 0, 12), 1, 1, 'L');
}

// Estatísticas
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 8, 'ESTATÍSTICAS:', 0, 1);

$total_fiscais = count($fiscais);
$alocados = count(array_filter($fiscais, function($f) { return $f['status_alocacao'] == 'Alocado'; }));

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Total de Fiscais Aprovados: ' . $total_fiscais, 0, 1);
$pdf->Cell(0, 6, 'Fiscais Alocados: ' . $alocados, 0, 1);
$pdf->Cell(0, 6, 'Fiscais Não Alocados: ' . ($total_fiscais - $alocados), 0, 1);

// Data e hora do relatório
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 6, 'Relatório gerado em: ' . date('d/m/Y H:i:s'), 0, 1);

// Limpar qualquer saída anterior
ob_end_clean();

// Configurar headers para download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="relatorio_fiscais_aprovados_mural_' . date('Y-m-d_H-i-s') . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Saída do PDF
$pdf->Output('relatorio_fiscais_aprovados_mural_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
exit;
?> 