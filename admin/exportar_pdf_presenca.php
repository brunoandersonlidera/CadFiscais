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
$data_prova = isset($_GET['data_prova']) ? $_GET['data_prova'] : date('Y-m-d');

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
    $sql = "
        SELECT f.*, c.titulo as concurso_titulo, c.data_prova,
               a.escola_id, a.sala_id, a.data_alocacao, a.horario_alocacao,
               e.nome as escola_nome, s.nome as sala_nome
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        LEFT JOIN alocacoes_fiscais a ON f.id = a.fiscal_id AND a.status = 'ativo'
        LEFT JOIN escolas e ON a.escola_id = e.id
        LEFT JOIN salas s ON a.sala_id = s.id
        WHERE f.status = 'aprovado'
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
    
    $sql .= " ORDER BY e.nome, s.nome, f.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
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
$pdf->SetTitle('Lista de Presença');

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
$pdf->Cell(0, 10, 'LISTA DE PRESENÇA - DIA DA PROVA', 0, 1, 'C');
$pdf->Ln(5);

// Informações do concurso
if ($concurso) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Concurso: ' . $concurso['titulo'], 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Data da Prova: ' . date('d/m/Y', strtotime($data_prova)), 0, 1);
    $pdf->Cell(0, 6, 'Total de Fiscais: ' . count($fiscais), 0, 1);
    $pdf->Ln(5);
}

// Agrupar fiscais por escola
$escolas_agrupadas = [];
foreach ($fiscais as $fiscal) {
    $escola_nome = $fiscal['escola_nome'] ?? 'Não Alocado';
    $escolas_agrupadas[$escola_nome][] = $fiscal;
}

// Gerar lista por escola
foreach ($escolas_agrupadas as $escola_nome => $fiscais_escola) {
    // Verificar se precisa de nova página
    if ($pdf->GetY() > 200) {
        $pdf->AddPage();
    }
    
    // Título da escola
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Escola: ' . $escola_nome, 0, 1);
    $pdf->Ln(3);
    
    // Cabeçalho da tabela
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(10, 8, '#', 1, 0, 'C', true);
    $pdf->Cell(60, 8, 'Nome', 1, 0, 'L', true);
    $pdf->Cell(30, 8, 'CPF', 1, 0, 'L', true);
    $pdf->Cell(25, 8, 'Sala', 1, 0, 'L', true);
    $pdf->Cell(20, 8, 'Horário', 1, 0, 'L', true);
    $pdf->Cell(45, 8, 'Assinatura', 1, 1, 'C', true);
    
    // Dados dos fiscais
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(255, 255, 255);
    
    foreach ($fiscais_escola as $index => $fiscal) {
        // Verificar se precisa de nova página
        if ($pdf->GetY() > 250) {
            $pdf->AddPage();
            $pdf->SetY(35); // Posicionar mais próximo do cabeçalho
            // Reimprimir cabeçalho
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(10, 8, '#', 1, 0, 'C', true);
            $pdf->Cell(60, 8, 'Nome', 1, 0, 'L', true);
            $pdf->Cell(30, 8, 'CPF', 1, 0, 'L', true);
            $pdf->Cell(25, 8, 'Sala', 1, 0, 'L', true);
            $pdf->Cell(20, 8, 'Horário', 1, 0, 'L', true);
            $pdf->Cell(45, 8, 'Assinatura', 1, 1, 'C', true);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetFillColor(255, 255, 255);
        }
        
        $pdf->Cell(10, 6, $index + 1, 1, 0, 'C');
        $pdf->Cell(60, 6, substr($fiscal['nome'], 0, 30), 1, 0, 'L');
        $pdf->Cell(30, 6, $fiscal['cpf'], 1, 0, 'L');
        $pdf->Cell(25, 6, substr($fiscal['sala_nome'] ?? 'N/A', 0, 12), 1, 0, 'L');
        $pdf->Cell(20, 6, $fiscal['horario_alocacao'] ?? 'N/A', 1, 0, 'L');
        $pdf->Cell(45, 6, '', 1, 1, 'C'); // Espaço para assinatura
    }
    
    $pdf->Ln(5);
}

// Instruções
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 8, 'INSTRUÇÕES:', 0, 1);

$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 6, '• Marque a presença de cada fiscal na coluna "Assinatura"', 0, 1);
$pdf->Cell(0, 6, '• Confirme se o fiscal está na sala correta', 0, 1);
$pdf->Cell(0, 6, '• Verifique se chegou no horário estabelecido', 0, 1);
$pdf->Cell(0, 6, '• Anote observações se necessário', 0, 1);

// Data e hora do relatório
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 6, 'Relatório gerado em: ' . date('d/m/Y H:i:s'), 0, 1);

// Limpar qualquer saída anterior
ob_end_clean();

// Configurar headers para download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="lista_presenca_' . date('Y-m-d_H-i-s') . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Saída do PDF
$pdf->Output('lista_presenca_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
exit;
?> 