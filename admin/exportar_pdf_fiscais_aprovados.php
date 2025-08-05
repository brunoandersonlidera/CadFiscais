<?php
// Iniciar buffer de saída
ob_start();

// Desativar exibição de erros para evitar saída indesejada
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once '../config.php';
require_once '../includes/pdf_base.php';

// Verificar se é admin
if (!isAdmin()) {
    ob_end_clean();
    redirect('../login.php');
    exit;
}

// Mapeamento de siglas de estados para nomes completos
$estados_brasileiros = [
    'AC' => 'do Acre',
    'AL' => 'de Alagoas',
    'AP' => 'do Amapá',
    'AM' => 'da Amazonas',
    'BA' => 'da Bahia',
    'CE' => 'do Ceará',
    'DF' => 'do Distrito Federal',
    'ES' => 'do Espírito Santo',
    'GO' => 'de Goiás',
    'MA' => 'do Maranhão',
    'MT' => 'de Mato Grosso',
    'MS' => 'do Mato Grosso do Sul',
    'MG' => 'de Minas Gerais',
    'PA' => 'do Pará',
    'PB' => 'da Paraíba',
    'PR' => 'do Paraná',
    'PE' => 'de Pernambuco',
    'PI' => 'do Piauí',
    'RJ' => 'do Rio de Janeiro',
    'RN' => 'do Rio Grande do Norte',
    'RS' => 'do Rio Grande do Sul',
    'RO' => 'de Rondônia',
    'RR' => 'de Roraima',
    'SC' => 'de Santa Catarina',
    'SP' => 'de São Paulo',
    'SE' => 'do Sergipe',
    'TO' => 'do Tocantins'
];

$db = getDB();
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : null;
$escola_id = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : null;

if (!$concurso_id) {
    ob_end_clean();
    die('Concurso não especificado.');
}

// Buscar dados do concurso
try {
    $stmt = $db->prepare("SELECT * FROM concursos WHERE id = ? AND status = 'ativo'");
    $stmt->execute([$concurso_id]);
    $concurso = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$concurso) {
        ob_end_clean();
        die('Concurso inválido ou inativo.');
    }
} catch (Exception $e) {
    error_log('Erro ao buscar concurso: ' . $e->getMessage());
    ob_end_clean();
    die('Erro ao consultar o banco de dados.');
}

// Buscar fiscais com os filtros aplicados
$fiscais = [];
try {
    $sql = "
        SELECT f.nome, f.cpf, f.created_at,
               CASE WHEN a.id IS NOT NULL THEN 'Alocado' ELSE 'Não Alocado' END as status_alocacao,
               e.nome as escola_nome,
               s.nome as sala_nome,
               a.escola_id
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        LEFT JOIN alocacoes_fiscais a ON f.id = a.fiscal_id AND a.status = 'ativo'
        LEFT JOIN escolas e ON a.escola_id = e.id AND e.concurso_id = c.id
        LEFT JOIN salas s ON a.sala_id = s.id
        WHERE f.status = 'aprovado' AND c.status = 'ativo' AND f.concurso_id = ?
    ";
    $params = [$concurso_id];
    
    if ($escola_id) {
        $sql .= " AND a.escola_id = ?";
        $params[] = $escola_id;
    }
    
    $sql .= " ORDER BY f.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log para depuração
    error_log("Fiscais encontrados para PDF: " . count($fiscais) . " para concurso_id=$concurso_id" . ($escola_id ? ", escola_id=$escola_id" : ""));
    error_log("Fiscais com escola_nome não nulo: " . count(array_filter($fiscais, function($f) { return !empty($f['escola_nome']); })));
    foreach ($fiscais as $fiscal) {
        error_log("Fiscal: {$fiscal['nome']}, escola_id: " . ($fiscal['escola_id'] ?? 'NULL') . ", escola_nome: " . ($fiscal['escola_nome'] ?? 'NULL'));
    }
} catch (Exception $e) {
    error_log('Erro ao buscar fiscais aprovados: ' . $e->getMessage());
    ob_end_clean();
    die('Erro ao consultar o banco de dados.');
}

// Inicializar PDF
$instituto_nome = getConfig('instituto_nome', 'Instituto Dignidade Humana');
$instituto_logo = __DIR__ . '/../logos/instituto.png';
$instituto_info = getConfig('info_institucional', 'Instituto Dignidade Humana\nEndereço: ...\nContato: ...');

$pdf = new PDFInstituto('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setInstitutoData($instituto_nome, $instituto_logo, $instituto_info);

// Configurar informações do documento
$pdf->SetCreator('Sistema CadFiscais');
$pdf->SetAuthor('IDH');
$pdf->SetTitle('Relatório de Fiscais Aprovados');

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
$pdf->SetY(30); // Posicionar abaixo do cabeçalho

// Informações do concurso centralizadas
if ($concurso) {
    // Estado e Órgão
    $estado_nome = isset($estados_brasileiros[$concurso['estado']]) ? $estados_brasileiros[$concurso['estado']] : $concurso['estado'];
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 8, 'Estado ' . htmlspecialchars($estado_nome), 0, 1, 'C');
    $pdf->Cell(0, 8, htmlspecialchars($concurso['orgao'] . ' de ' . $concurso['cidade']), 0, 1, 'C');
    
    // Descrição do concurso (formato do dropdown)
    $concurso_descricao = htmlspecialchars($concurso['titulo']) . ' ' . 
                          htmlspecialchars($concurso['numero_concurso']) . '/' . 
                          htmlspecialchars($concurso['ano_concurso']) . ' da ' . 
                          htmlspecialchars($concurso['orgao']) . ' de ' . 
                          htmlspecialchars($concurso['cidade']) . '/' . 
                          htmlspecialchars($concurso['estado']);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->MultiCell(0, 8, $concurso_descricao, 0, 'C');
    $pdf->Ln(8);
}

// Título do relatório
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'RELATÓRIO DE FISCAIS APROVADOS', 0, 1, 'C');
$pdf->Ln(5);

// Informações do concurso
if ($concurso) {
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Data da Prova: ' . date('d/m/Y', strtotime($concurso['data_prova'])), 0, 1);
    $pdf->Ln(5);
}

// Cabeçalho da tabela
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(60, 8, 'Nome', 1, 0, 'L', true);
$pdf->Cell(80, 8, 'Escola', 1, 0, 'L', true);
$pdf->Cell(40, 8, 'Sala', 1, 1, 'L', true);

// Dados dos fiscais
$pdf->SetFont('helvetica', '', 8);
$pdf->SetFillColor(255, 255, 255);

foreach ($fiscais as $fiscal) {
    // Calcular altura necessária para a linha
    $nome = $fiscal['nome'] ?? '';
    $escola = $fiscal['escola_nome'] ?? '-';
    $sala = $fiscal['sala_nome'] ?? '-';
    
    // Estimar número de linhas para cada célula usando getNumLines
    $pdf->SetFont('helvetica', '', 8);
    $line_height = 5; // Altura base por linha (reduzida para melhor ajuste)
    $nome_lines = $pdf->getNumLines($nome, 60);
    $escola_lines = $pdf->getNumLines($escola, 80);
    $sala_lines = $pdf->getNumLines($sala, 40);
    $max_lines = max(1, $nome_lines, $escola_lines, $sala_lines);
    $row_height = $line_height * $max_lines;
    
    // Verificar se precisa de nova página
    if ($pdf->GetY() + $row_height > 250) {
        $pdf->AddPage();
        $pdf->SetY(35); // Posicionar mais próximo do cabeçalho
        // Reimprimir cabeçalho
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(60, 8, 'Nome', 1, 0, 'L', true);
        $pdf->Cell(80, 8, 'Escola', 1, 0, 'L', true);
        $pdf->Cell(40, 8, 'Sala', 1, 1, 'L', true);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetFillColor(255, 255, 255);
    }
    
    // Renderizar linha com MultiCell, mantendo alinhamento
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->MultiCell(60, $row_height, $nome, 1, 'L', true, 0);
    $pdf->SetXY($x + 60, $y);
    $pdf->MultiCell(80, $row_height, $escola, 1, 'L', true, 0);
    $pdf->SetXY($x + 140, $y);
    $pdf->MultiCell(40, $row_height, $sala, 1, 'L', true, 0);
    $pdf->SetXY($x, $y + $row_height); // Avançar para a próxima linha
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

// Limpar buffer de saída
ob_end_clean();

// Configurar headers para download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="relatorio_fiscais_aprovados_' . date('Y-m-d_H-i-s') . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Saída do PDF
$pdf->Output('relatorio_fiscais_aprovados_' . date('Y-m-d_H-i-s') . '.pdf', 'I');
exit;
?>