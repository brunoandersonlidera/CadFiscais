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
$escola_id = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : 0;

// Buscar dados do concurso (opcional)
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

// Buscar alocações com nomes
try {
    $sql = "
        SELECT a.id, f.nome as fiscal_nome, e.nome as escola_nome, e.endereco as escola_endereco, e.responsavel as escola_responsavel, s.nome as sala_nome,
               a.tipo_alocacao, a.observacoes, a.data_alocacao, a.horario_alocacao, a.status,
               f.concurso_id
        FROM alocacoes_fiscais a
        LEFT JOIN fiscais f ON a.fiscal_id = f.id
        LEFT JOIN escolas e ON a.escola_id = e.id
        LEFT JOIN salas s ON a.sala_id = s.id
        WHERE 1=1
    ";
    $params = [];
    if ($concurso_id) {
        $sql .= " AND f.concurso_id = ?";
        $params[] = $concurso_id;
    }
    if ($escola_id) {
        $sql .= " AND a.escola_id = ?";
        $params[] = $escola_id;
    }
    $sql .= " ORDER BY a.data_alocacao DESC, e.nome, s.nome";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $alocacoes = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar alocações: ' . $e->getMessage(), 'ERROR');
    $alocacoes = [];
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
$pdf->SetTitle('Relatório de Alocações');

// Configurar margens
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 35);
$pdf->SetFont('helvetica', '', 10);
$pdf->AddPage();
$pdf->SetY(30);

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
$pdf->Cell(0, 10, 'RELATÓRIO DE ALOCAÇÕES', 0, 1, 'C');
$pdf->Ln(5);

// Cabeçalho da tabela
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(32, 8, 'Fiscal', 1, 0, 'C', true);
$pdf->Cell(49, 8, 'Escola', 1, 0, 'C', true);
$pdf->Cell(45, 8, 'Endereço Escola', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Coordenador', 1, 0, 'C', true);
$pdf->Cell(28, 8, 'Sala', 1, 1, 'C', true);

// Dados das alocações
$pdf->SetFont('helvetica', '', 8);
$pdf->SetFillColor(255, 255, 255);
foreach ($alocacoes as $alocacao) {
    if ($pdf->GetY() > 250) {
        $pdf->AddPage();
        $pdf->SetY(35);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(32, 8, 'Fiscal', 1, 0, 'C', true);
        $pdf->Cell(49, 8, 'Escola', 1, 0, 'C', true);
        $pdf->Cell(45, 8, 'Endereço Escola', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Coordenador', 1, 0, 'C', true);
        $pdf->Cell(28, 8, 'Sala', 1, 1, 'C', true);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetFillColor(255, 255, 255);
    }

    // Textos das células
    $fiscal = $alocacao['fiscal_nome'] ? $alocacao['fiscal_nome'] : 'N/A';
    $escola = $alocacao['escola_nome'] ? $alocacao['escola_nome'] : 'N/A';
    $endereco = $alocacao['escola_endereco'] ? $alocacao['escola_endereco'] : 'N/A';
    $coordenador = $alocacao['escola_responsavel'] ? $alocacao['escola_responsavel'] : 'N/A';
    $sala = $alocacao['sala_nome'] ? $alocacao['sala_nome'] : 'N/A';

    // Larguras das colunas
    $w = [32, 49, 45, 30, 28];

    // Calcular altura de cada célula
    $h_fiscal = $pdf->getStringHeight($w[0], $fiscal);
    $h_escola = $pdf->getStringHeight($w[1], $escola);
    $h_endereco = $pdf->getStringHeight($w[2], $endereco);
    $h_coordenador = $pdf->getStringHeight($w[3], $coordenador);
    $h_sala = $pdf->getStringHeight($w[4], $sala);
    $h = max($h_fiscal, $h_escola, $h_endereco, $h_coordenador, $h_sala);

    // Fiscal
    $pdf->MultiCell($w[0], $h, $fiscal, 1, 'L', false, 0, '', '', true, 0, false, true, $h, 'M');
    // Escola
    $pdf->MultiCell($w[1], $h, $escola, 1, 'L', false, 0, '', '', true, 0, false, true, $h, 'M');
    // Endereço
    $pdf->MultiCell($w[2], $h, $endereco, 1, 'L', false, 0, '', '', true, 0, false, true, $h, 'M');
    // Coordenador
    $pdf->MultiCell($w[3], $h, $coordenador, 1, 'L', false, 0, '', '', true, 0, false, true, $h, 'M');
    // Sala (última coluna, avança linha)
    $pdf->MultiCell($w[4], $h, $sala, 1, 'L', false, 1, '', '', true, 0, false, true, $h, 'M');
}

// Estatísticas
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 8, 'ESTATÍSTICAS:', 0, 1);

$total_alocacoes = count($alocacoes);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Total de Alocações: ' . $total_alocacoes, 0, 1);

// Data e hora do relatório
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 6, 'Relatório gerado em: ' . date('d/m/Y H:i:s'), 0, 1);

// Saída do PDF
ob_end_clean();
$pdf->Output('relatorio_alocacoes_' . date('Y-m-d_H-i-s') . '.pdf', 'I'); 