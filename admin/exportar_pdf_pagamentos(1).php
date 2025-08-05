<?php
// Limpar qualquer saída anterior
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

require_once '../config.php';
require_once '../includes/pdf_base.php';

if (!isLoggedIn() || !temPermissaoPresenca()) {
    redirect('../login.php');
}

$db = getDB();
$fiscais = [];

// Filtros
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : null;
$escola_id = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : null;

try {
    $sql = "
        SELECT 
            f.id,
            f.nome,
            f.cpf,
            c.valor_pagamento,
            e.nome as escola_nome,
            p.status as status_pagamento,
            p.data_pagamento
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        LEFT JOIN alocacoes_fiscais af ON f.id = af.fiscal_id AND af.status = 'ativo'
        LEFT JOIN escolas e ON af.escola_id = e.id
        LEFT JOIN pagamentos p ON f.id = p.fiscal_id
        WHERE f.status = 'aprovado'";
    $params = [];
    if ($concurso_id) {
        $sql .= " AND f.concurso_id = ?";
        $params[] = $concurso_id;
    }
    if ($escola_id) {
        $sql .= " AND af.escola_id = ?";
        $params[] = $escola_id;
    }
    $sql .= " ORDER BY e.nome, f.nome";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar fiscais para relatório de pagamentos: ' . $e->getMessage(), 'ERROR');
}

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

$instituto_nome = getConfig('instituto_nome', 'Instituto Dignidade Humana');
$instituto_logo = __DIR__ . '/../logos/instituto.png';
$instituto_info = getConfig('info_institucional', 'Instituto Dignidade Humana\nEndereço: ...\nContato: ...');

$pdf = new PDFInstituto('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setInstitutoData($instituto_nome, $instituto_logo, $instituto_info);
$pdf->AddPage();
$pdf->Ln(15);

// Informações do concurso
if ($concurso) {
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->Cell(0, 8, ($concurso['orgao'] ?? '') . ' - ' . ($concurso['cidade'] ?? '') . ' - ' . ($concurso['estado'] ?? ''), 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, ($concurso['titulo'] ?? '') . ' - ' . ($concurso['numero_concurso'] ?? '') . ' / ' . ($concurso['ano_concurso'] ?? ''), 0, 1, 'C');
    $pdf->Ln(8);
}

// Título do relatório
$pdf->SetFont('helvetica', 'B', 15);
$pdf->Cell(0, 10, 'RELATÓRIO DE PAGAMENTOS', 0, 1, 'C');
$pdf->Ln(5);

// Estatísticas
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 8, 'ESTATÍSTICAS:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);
$total_fiscais = count($fiscais);
$pagamentos_realizados = count(array_filter($fiscais, function($f) { return $f['status_pagamento'] === 'pago'; }));
$aguardando_pagamento = count(array_filter($fiscais, function($f) { return $f['status_pagamento'] !== 'pago'; }));
$pdf->Cell(60, 6, 'Total de Fiscais:', 0, 0, 'L');
$pdf->Cell(30, 6, $total_fiscais, 0, 1, 'L');
$pdf->Cell(60, 6, 'Pagamentos Realizados:', 0, 0, 'L');
$pdf->Cell(30, 6, $pagamentos_realizados, 0, 1, 'L');
$pdf->Cell(60, 6, 'Aguardando Pagamento:', 0, 0, 'L');
$pdf->Cell(30, 6, $aguardando_pagamento, 0, 1, 'L');
$pdf->Ln(8);

// Cabeçalho da tabela
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(50, 7, 'Nome', 1, 0, 'L', true);
$pdf->Cell(25, 7, 'CPF', 1, 0, 'L', true);
$pdf->Cell(40, 7, 'Escola', 1, 0, 'L', true);
$pdf->Cell(20, 7, 'Valor', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'Pagamento', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Data Pagamento', 1, 1, 'C', true);

// Função para redesenhar o cabeçalho da tabela
function pdf_cabecalho_tabela_pagamentos($pdf) {
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(50, 7, 'Nome', 1, 0, 'L', true);
    $pdf->Cell(25, 7, 'CPF', 1, 0, 'L', true);
    $pdf->Cell(40, 7, 'Escola', 1, 0, 'L', true);
    $pdf->Cell(20, 7, 'Valor', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Pagamento', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Data Pagamento', 1, 1, 'C', true);
    // Voltar para fonte normal após o cabeçalho
    $pdf->SetFont('helvetica', '', 7);
}

// Dados
$pdf->SetFont('helvetica', '', 7);
foreach ($fiscais as $fiscal) {
    // Verificar espaço restante na página
    $linha_altura = 6;
    // Calcular altura necessária para nome e escola
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $nome_height = $pdf->getStringHeight(50, $fiscal['nome']);
    $escola_height = $pdf->getStringHeight(40, $fiscal['escola_nome'] ?? 'Não alocado');
    $max_height = max($linha_altura, $nome_height, $escola_height);
    $espaco_restante = $pdf->getPageHeight() - $pdf->GetY() - $pdf->getBreakMargin();
    if ($espaco_restante < ($max_height + 10)) {
        $pdf->AddPage();
        $pdf->Ln(10);
        pdf_cabecalho_tabela_pagamentos($pdf);
    }
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    // Nome
    $pdf->MultiCell(50, $max_height, $fiscal['nome'], 1, 'L', false, 0);
    // CPF
    $pdf->SetXY($x + 50, $y);
    $pdf->Cell(25, $max_height, formatCPF($fiscal['cpf']), 1, 0, 'L');
    // Escola
    $pdf->SetXY($x + 75, $y);
    $pdf->MultiCell(40, $max_height, $fiscal['escola_nome'] ?? 'Não alocado', 1, 'L', false, 0);
    // Valor
    $pdf->SetXY($x + 115, $y);
    $pdf->Cell(20, $max_height, 'R$ ' . number_format($fiscal['valor_pagamento'], 2, ',', '.'), 1, 0, 'C');
    // Pagamento
    $pagamento = $fiscal['status_pagamento'] === 'pago' ? 'Pago' : 'Pendente';
    $pdf->SetXY($x + 135, $y);
    $pdf->Cell(25, $max_height, $pagamento, 1, 0, 'C');
    // Data Pagamento
    $pdf->SetXY($x + 160, $y);
    $pdf->Cell(30, $max_height, $fiscal['data_pagamento'] ? date('d/m/Y', strtotime($fiscal['data_pagamento'])) : '-', 1, 1, 'C');
}

$pdf->Ln(8);

// Resumo financeiro
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 8, 'RESUMO FINANCEIRO:', 0, 1, 'L');
$total_devido = array_sum(array_column($fiscais, 'valor_pagamento'));
$total_pago = array_sum(array_column(array_filter($fiscais, function($f) { return $f['status_pagamento'] === 'pago'; }), 'valor_pagamento'));
$total_pendente = $total_devido - $total_pago;
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(60, 6, 'Total Devido:', 0, 0, 'L');
$pdf->Cell(30, 6, 'R$ ' . number_format($total_devido, 2, ',', '.'), 0, 1, 'L');
$pdf->Cell(60, 6, 'Total Pago:', 0, 0, 'L');
$pdf->Cell(30, 6, 'R$ ' . number_format($total_pago, 2, ',', '.'), 0, 1, 'L');
$pdf->Cell(60, 6, 'Total Pendente:', 0, 0, 'L');
$pdf->Cell(30, 6, 'R$ ' . number_format($total_pendente, 2, ',', '.'), 0, 1, 'L');

$pdf->Ln(8);


// Data e hora do relatório
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 6, 'Relatório gerado em: ' . date('d/m/Y H:i:s'), 0, 1, 'L');

// Limpar buffer e enviar PDF
ob_end_clean();
$pdf->Output('relatorio_pagamentos.pdf', 'D');
exit; 