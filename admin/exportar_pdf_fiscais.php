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

// Parâmetros
$concurso_id = isset($_GET['concurso_id']) && $_GET['concurso_id'] !== '' ? (int)$_GET['concurso_id'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$genero = isset($_GET['genero']) ? $_GET['genero'] : '';

// Buscar dados do concurso
$concurso = null;
if ($concurso_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM concursos WHERE id = ?");
        $stmt->execute([$concurso_id]);
        $concurso = $stmt->fetch();
    } catch (Exception $e) {
        logActivity('Erro ao buscar concurso: ' . $e->getMessage(), 'ERROR');
        ob_end_clean();
        die('Erro ao consultar o banco de dados.');
    }
}

// Buscar fiscais com filtros
$fiscais = [];
if ($concurso_id) {
    try {
        $sql = "
            SELECT f.nome, f.cpf, f.status, f.created_at
            FROM fiscais f
            WHERE f.concurso_id = ?
        ";
        $params = [$concurso_id];
        
        if ($status) {
            $sql .= " AND f.status = ?";
            $params[] = $status;
        }
        
        if ($genero) {
            $sql .= " AND f.genero = ?";
            $params[] = $genero;
        }
        
        $sql .= " ORDER BY f.nome";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Log para depuração
        error_log("Fiscais encontrados para PDF: " . count($fiscais) . " para concurso_id=" . ($concurso_id ?? 'NULL') . ", status=$status, genero=$genero");
    } catch (Exception $e) {
        logActivity('Erro ao buscar fiscais: ' . $e->getMessage(), 'ERROR');
        ob_end_clean();
        die('Erro ao consultar o banco de dados.');
    }
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

// Configurar quebras de página automáticas
$pdf->SetAutoPageBreak(TRUE, 35);

// Configurar fonte
$pdf->SetFont('helvetica', '', 10);

// Adicionar página
$pdf->AddPage();
$pdf->SetY(30); // Posicionar abaixo do cabeçalho

// Informações do concurso centralizadas
if ($concurso) {
    $estado_nome = isset($estados_brasileiros[$concurso['estado']]) ? $estados_brasileiros[$concurso['estado']] : $concurso['estado'];
    $pdf->SetFont('helvetica', 'B', 15);
    $pdf->Cell(0, 8, 'Estado ' . htmlspecialchars($estado_nome), 0, 1, 'C');
    $pdf->Cell(0, 8, htmlspecialchars($concurso['orgao'] . ' - ' . $concurso['cidade'] . ' - ' . $concurso['estado']), 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, htmlspecialchars($concurso['titulo'] . ' - ' . $concurso['numero_concurso'] . '/' . $concurso['ano_concurso']), 0, 1, 'C');
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

// Impressão robusta: termo parágrafo a parágrafo
$paragrafos = preg_split('/\r\n|\r|\n/', $termo);
foreach ($paragrafos as $paragrafo) {
    if (trim($paragrafo) === '') continue;
    $linhas = $pdf->getNumLines($paragrafo, 180);
    $altura = $linhas * 6 + 2;
    $espaco_restante = $pdf->getPageHeight() - $pdf->GetY() - 35;
    if ($altura > $espaco_restante) {
        $pdf->AddPage();
        $pdf->SetY(40);
    }
    $pdf->MultiCell(0, 6, $paragrafo, 0, 'L');
    $pdf->Ln(2);
}
$pdf->Ln(8);

// Ajustar posição inicial da tabela
if ($pdf->GetY() < 60) {
    $pdf->SetY(60);
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

if (empty($fiscais)) {
    $pdf->MultiCell(0, 8, 'Nenhum fiscal encontrado para os filtros selecionados.', 0, 'C');
} else {
    foreach ($fiscais as $fiscal) {
        // Calcular altura necessária para a linha
        $nome = $fiscal['nome'] ?? '';
        $cpf = $fiscal['cpf'] ?? '';
        $status_fiscal = ucfirst($fiscal['status'] ?? '');
        $data_cadastro = date('d/m/Y', strtotime($fiscal['created_at']));
        
        $line_height = 6;
        $nome_lines = $pdf->getNumLines($nome, 80);
        $cpf_lines = $pdf->getNumLines($cpf, 35);
        $status_lines = $pdf->getNumLines($status_fiscal, 25);
        $data_lines = $pdf->getNumLines($data_cadastro, 35);
        $max_lines = max(1, $nome_lines, $cpf_lines, $status_lines, $data_lines);
        $row_height = $line_height * $max_lines;
        
        // Verificar se precisa de nova página
        if ($pdf->GetY() + $row_height > 250) {
            $pdf->AddPage();
            $pdf->SetY(35);
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
        
        // Renderizar linha com MultiCell
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->MultiCell(80, $row_height, $nome, 1, 'L', true, 0);
        $pdf->SetXY($x + 80, $y);
        $pdf->MultiCell(35, $row_height, $cpf, 1, 'L', true, 0);
        $pdf->SetXY($x + 115, $y);
        $pdf->MultiCell(25, $row_height, $status_fiscal, 1, 'L', true, 0);
        $pdf->SetXY($x + 140, $y);
        $pdf->MultiCell(35, $row_height, $data_cadastro, 1, 'L', true, 0);
        $pdf->SetXY($x, $y + $row_height);
    }
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

// Configurar headers para visualização inline
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="relatorio_fiscais_' . date('Y-m-d_H-i-s') . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Saída do PDF
$pdf->Output('relatorio_fiscais_' . date('Y-m-d_H-i-s') . '.pdf', 'I');
exit;
?>