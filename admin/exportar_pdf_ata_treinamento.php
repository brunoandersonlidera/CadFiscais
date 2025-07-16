<?php
require_once '../config.php';
require_once '../includes/pdf_base.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();

// Parâmetros de filtro
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : null;
$escola_id = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : null;
$data_treinamento = isset($_GET['data_treinamento']) ? $_GET['data_treinamento'] : date('Y-m-d');
$horario_treinamento = isset($_GET['horario_treinamento']) ? $_GET['horario_treinamento'] : '';

// Buscar fiscais com alocações de treinamento
try {
    $sql = "
        SELECT f.*, c.titulo as concurso_titulo, c.data_prova,
               TIMESTAMPDIFF(YEAR, f.data_nascimento, CURDATE()) as idade,
               a.escola_id, a.sala_id, a.data_alocacao, a.horario_alocacao,
               e.nome as escola_nome, s.nome as sala_nome
        FROM alocacoes_fiscais a
        INNER JOIN fiscais f ON a.fiscal_id = f.id
        INNER JOIN concursos c ON f.concurso_id = c.id
        INNER JOIN escolas e ON a.escola_id = e.id
        INNER JOIN salas s ON a.sala_id = s.id
        WHERE f.status = 'aprovado' 
        AND a.status = 'ativo' 
        AND a.tipo_alocacao = 'treinamento'
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
    
    if ($horario_treinamento) {
        $sql .= " AND a.horario_alocacao = ?";
        $params[] = $horario_treinamento;
    }
    
    $sql .= " ORDER BY e.nome, s.nome, f.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar fiscais para PDF: ' . $e->getMessage(), 'ERROR');
    $fiscais = [];
}

// Configurar PDF
$pdf = new PDFInstituto('P', 'mm', 'A4');
$pdf->SetCreator('Sistema de Fiscais');
$pdf->SetAuthor('Instituto Dignidade Humana');
$pdf->SetTitle('Ata de Treinamento');
$pdf->SetSubject('Ata de Treinamento - Fiscais');

// Configurar dados do instituto
$pdf->setInstitutoData(
    'Instituto Dignidade Humana',
    '../logos/instituto.png',
    'Sistema de Gerenciamento de Fiscais'
);

$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);

// Título
$pdf->Cell(0, 10, 'ATA DE TREINAMENTO', 0, 1, 'C');
$pdf->Ln(5);

// Informações do treinamento
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 8, 'Data: ' . date('d/m/Y', strtotime($data_treinamento)), 0, 1);
$pdf->Cell(0, 8, 'Horário: ' . ($horario_treinamento ?: 'A definir'), 0, 1);
if (!empty($fiscais)) {
    $pdf->Cell(0, 8, 'Concurso: ' . $fiscais[0]['concurso_titulo'], 0, 1);
}
$pdf->Cell(0, 8, 'Total de Participantes: ' . count($fiscais), 0, 1);
$pdf->Ln(5);

// Cabeçalho da tabela
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(15, 8, '#', 1, 0, 'C', true);
$pdf->Cell(80, 8, 'Nome', 1, 0, 'C', true);
$pdf->Cell(50, 8, 'Escola', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Sala', 1, 0, 'C', true);
$pdf->Cell(15, 8, 'Ass.', 1, 1, 'C', true);

// Dados dos fiscais
$pdf->SetFont('helvetica', '', 9);
$pdf->SetFillColor(255, 255, 255);

foreach ($fiscais as $index => $fiscal) {
    if ($pdf->GetY() > 250) {
        $pdf->AddPage();
        $pdf->SetY(35);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(15, 8, '#', 1, 0, 'C', true);
        $pdf->Cell(80, 8, 'Nome', 1, 0, 'C', true);
        $pdf->Cell(50, 8, 'Escola', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Sala', 1, 0, 'C', true);
        $pdf->Cell(15, 8, 'Ass.', 1, 1, 'C', true);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetFillColor(255, 255, 255);
    }
    
    $pdf->Cell(15, 8, ($index + 1), 1, 0, 'C');
    $pdf->Cell(80, 8, $fiscal['nome'], 1, 0, 'L');
    $pdf->Cell(50, 8, $fiscal['escola_nome'], 1, 0, 'L');
    $pdf->Cell(30, 8, $fiscal['sala_nome'], 1, 0, 'C');
    $pdf->Cell(15, 8, '', 1, 1, 'C');
}

$pdf->Ln(10);

// Seção de observações
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Observações:', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 6, '________________________________________________________________________________', 0, 'L');
$pdf->MultiCell(0, 6, '________________________________________________________________________________', 0, 'L');
$pdf->MultiCell(0, 6, '________________________________________________________________________________', 0, 'L');

$pdf->Ln(10);

// Seção de conclusões
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Conclusões:', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 6, '________________________________________________________________________________', 0, 'L');
$pdf->MultiCell(0, 6, '________________________________________________________________________________', 0, 'L');
$pdf->MultiCell(0, 6, '________________________________________________________________________________', 0, 'L');

$pdf->Ln(15);

// Assinaturas
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(95, 8, 'Responsável pelo Treinamento', 0, 0, 'C');
$pdf->Cell(95, 8, 'Coordenador', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(95, 20, '________________________', 0, 0, 'C');
$pdf->Cell(95, 20, '________________________', 0, 1, 'C');

// Gerar PDF
$pdf->Output('ata_treinamento_' . date('Y-m-d_H-i-s') . '.pdf', 'I');
?> 