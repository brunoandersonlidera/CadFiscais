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
$escola_id = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : 0;
$data_treinamento = isset($_GET['data_treinamento']) ? $_GET['data_treinamento'] : date('Y-m-d');
$horario_treinamento = isset($_GET['horario_treinamento']) ? $_GET['horario_treinamento'] : '';

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

// Buscar fiscais
try {
    $sql = "
        SELECT f.*, c.titulo as concurso_titulo, c.data_prova,
               a.escola_id, a.sala_id, a.data_alocacao, a.horario_alocacao,
               e.nome as escola_nome, s.nome as sala_nome
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        LEFT JOIN alocacoes_fiscais a ON f.id = a.fiscal_id AND a.status = 'ativo' AND a.tipo_alocacao = 'treinamento'
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
    
    if ($horario_treinamento) {
        $sql .= " AND a.horario_alocacao = ?";
        $params[] = $horario_treinamento;
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
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 15, 'ATA DE TREINAMENTO', 0, false, 'C', 0, '', 0, false, 'M', 'M');
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
$pdf->SetTitle('Ata de Treinamento');

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

// Título do documento
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'ATA DE TREINAMENTO', 0, 1, 'C');
$pdf->Ln(5);

// Informações do treinamento
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'INFORMAÇÕES DO TREINAMENTO', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Data: ' . date('d/m/Y', strtotime($data_treinamento)), 0, 1);
$pdf->Cell(0, 6, 'Horário: ' . ($horario_treinamento ?: 'A definir'), 0, 1);

if ($concurso) {
    $pdf->Cell(0, 6, 'Concurso: ' . $concurso['titulo'], 0, 1);
    $pdf->Cell(0, 6, 'Data da Prova: ' . date('d/m/Y', strtotime($concurso['data_prova'])), 0, 1);
}

$pdf->Cell(0, 6, 'Total de Participantes: ' . count($fiscais), 0, 1);
$pdf->Ln(5);

// Objetivos do treinamento
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'OBJETIVOS DO TREINAMENTO', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, '• Orientar os fiscais sobre os procedimentos do concurso', 0, 1);
$pdf->Cell(0, 6, '• Esclarecer dúvidas sobre o funcionamento', 0, 1);
$pdf->Cell(0, 6, '• Distribuir materiais necessários', 0, 1);
$pdf->Cell(0, 6, '• Estabelecer contato com a equipe', 0, 1);
$pdf->Ln(5);

// Conteúdo abordado
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'CONTEÚDO ABORDADO', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, '• Procedimentos gerais de fiscalização', 0, 1);
$pdf->Cell(0, 6, '• Normas específicas do concurso', 0, 1);
$pdf->Cell(0, 6, '• Como lidar com irregularidades', 0, 1);
$pdf->Cell(0, 6, '• Preenchimento de documentos', 0, 1);
$pdf->Cell(0, 6, '• Procedimentos de emergência', 0, 1);
$pdf->Ln(5);

// Lista de participantes
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'LISTA DE PARTICIPANTES', 0, 1);

// Cabeçalho da tabela
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(10, 8, '#', 1, 0, 'C', true);
$pdf->Cell(60, 8, 'Nome', 1, 0, 'L', true);
$pdf->Cell(30, 8, 'CPF', 1, 0, 'L', true);
$pdf->Cell(25, 8, 'Escola', 1, 0, 'L', true);
$pdf->Cell(25, 8, 'Sala', 1, 0, 'L', true);
$pdf->Cell(35, 8, 'Assinatura', 1, 1, 'C', true);

// Dados dos fiscais
$pdf->SetFont('helvetica', '', 8);
$pdf->SetFillColor(255, 255, 255);

foreach ($fiscais as $index => $fiscal) {
    // Verificar se precisa de nova página
    if ($pdf->GetY() > 250) {
        $pdf->AddPage();
        // Reimprimir cabeçalho
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(10, 8, '#', 1, 0, 'C', true);
        $pdf->Cell(60, 8, 'Nome', 1, 0, 'L', true);
        $pdf->Cell(30, 8, 'CPF', 1, 0, 'L', true);
        $pdf->Cell(25, 8, 'Escola', 1, 0, 'L', true);
        $pdf->Cell(25, 8, 'Sala', 1, 0, 'L', true);
        $pdf->Cell(35, 8, 'Assinatura', 1, 1, 'C', true);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetFillColor(255, 255, 255);
    }
    
    $pdf->Cell(10, 6, $index + 1, 1, 0, 'C');
    $pdf->Cell(60, 6, substr($fiscal['nome'], 0, 30), 1, 0, 'L');
    $pdf->Cell(30, 6, $fiscal['cpf'], 1, 0, 'L');
    $pdf->Cell(25, 6, substr($fiscal['escola_nome'] ?? 'N/A', 0, 12), 1, 0, 'L');
    $pdf->Cell(25, 6, substr($fiscal['sala_nome'] ?? 'N/A', 0, 12), 1, 0, 'L');
    $pdf->Cell(35, 6, '', 1, 1, 'C'); // Espaço para assinatura
}

$pdf->Ln(10);

// Observações
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'OBSERVAÇÕES', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, '_________________________________________________________________', 0, 1);
$pdf->Cell(0, 6, '_________________________________________________________________', 0, 1);
$pdf->Cell(0, 6, '_________________________________________________________________', 0, 1);
$pdf->Ln(5);

// Conclusões
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'CONCLUSÕES', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, '_________________________________________________________________', 0, 1);
$pdf->Cell(0, 6, '_________________________________________________________________', 0, 1);
$pdf->Cell(0, 6, '_________________________________________________________________', 0, 1);
$pdf->Ln(10);

// Assinaturas
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(95, 8, 'Responsável pelo Treinamento', 0, 0, 'C');
$pdf->Cell(95, 8, 'Coordenador', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(95, 20, '', 0, 0, 'C'); // Espaço para assinatura
$pdf->Cell(95, 20, '', 0, 1, 'C'); // Espaço para assinatura

// Data e hora do relatório
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 6, 'Ata gerada em: ' . date('d/m/Y H:i:s'), 0, 1);

// Limpar qualquer saída anterior
ob_end_clean();

// Configurar headers para download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="ata_treinamento_' . date('Y-m-d_H-i-s') . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Saída do PDF
$pdf->Output('ata_treinamento_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
exit;
?> 