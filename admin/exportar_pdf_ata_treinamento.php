<?php
// Limpar qualquer saída anterior e iniciar buffer
ob_clean();
ob_start();

require_once '../config.php';
require_once '../includes/pdf_base.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();

// Parâmetros de filtro
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : null;
$data_treinamento = isset($_GET['data_treinamento']) ? $_GET['data_treinamento'] : date('Y-m-d');
$horario_treinamento = isset($_GET['horario_treinamento']) ? $_GET['horario_treinamento'] : '';

// Buscar fiscais presentes no treinamento
try {
    $sql = "
        SELECT DISTINCT f.nome, c.titulo as concurso_titulo, c.numero_concurso, c.ano_concurso, c.orgao, c.cidade, c.estado,
               COALESCE(e.nome, 'N/A') as escola_nome, COALESCE(af.sala_id, 'N/A') as sala,
               p.status as status_presenca, p.data_presenca
        FROM fiscais f 
        INNER JOIN presenca p ON f.id = p.fiscal_id 
        INNER JOIN concursos c ON f.concurso_id = c.id
        LEFT JOIN alocacoes_fiscais af ON f.id = af.fiscal_id AND af.status = 'ativo'
        LEFT JOIN escolas e ON af.escola_id = e.id
        WHERE p.tipo_presenca = 'treinamento' AND p.status = 'presente'
    ";
    $params = [];
    
    if ($concurso_id) {
        $sql .= " AND f.concurso_id = ?";
        $params[] = $concurso_id;
    }
    
    $sql .= " ORDER BY f.nome";
    
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
$instituto_nome = getConfig('instituto_nome', 'Instituto Dignidade Humana');
$instituto_logo = __DIR__ . '/../logos/instituto.png';
$instituto_info = getConfig('info_institucional', 'Instituto Dignidade Humana\nEndereço: ...\nContato: ...');
$pdf->setInstitutoData($instituto_nome, $instituto_logo, $instituto_info);

// Configurar margens
$pdf->SetMargins(15, 35, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Configurar quebras de página automáticas
$pdf->SetAutoPageBreak(TRUE, 35);

$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);

// Título principal
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 5, 'ATA DO TREINAMENTO PARA FISCAIS DE CONCURSOS', 0, 1, 'C');
$pdf->Ln(1);

// Informações do treinamento
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 16, 'Data: ' . date('d/m/Y', strtotime($data_treinamento)), 0, 1);
$pdf->Cell(0, 8, 'Horário: ' . ($horario_treinamento ?: 'A definir'), 0, 1);
if (!empty($fiscais)) {
    $concurso_info = $fiscais[0]['concurso_titulo'];
    $pdf->Cell(0, 8, 'Concurso: ' . $concurso_info, 0, 1);
}
$pdf->Cell(0, 8, 'Total de Participantes: ' . count($fiscais), 0, 1);
$pdf->Ln(8);

// Objetivos do Treinamento
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'Objetivos do Treinamento', 0, 1);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(5, 6, '•', 0, 0);
$pdf->Cell(0, 6, 'Capacitar fiscais para atuação no concurso', 0, 1);
$pdf->Cell(5, 6, '•', 0, 0);
$pdf->Cell(0, 6, 'Esclarecer procedimentos e normas', 0, 1);
$pdf->Cell(5, 6, '•', 0, 0);
$pdf->Cell(0, 6, 'Treinar uso de equipamentos', 0, 1);
$pdf->Cell(5, 6, '•', 0, 0);
$pdf->Cell(0, 6, 'Simular situações de prova', 0, 1);
$pdf->Cell(5, 6, '•', 0, 0);
$pdf->Cell(0, 6, 'Tirar dúvidas dos participantes', 0, 1);
$pdf->Ln(5);

// Material Distribuído
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'Material Distribuído', 0, 1);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(5, 6, '•', 0, 0);
$pdf->Cell(0, 6, 'Manual do Fiscal', 0, 1);
$pdf->Cell(5, 6, '•', 0, 0);
$pdf->Cell(0, 6, 'Lista de Procedimentos', 0, 1);
$pdf->Cell(5, 6, '•', 0, 0);
$pdf->Cell(0, 6, 'Credencial de Identificação', 0, 1);
$pdf->Cell(5, 6, '•', 0, 0);
$pdf->Cell(0, 6, 'Material de Escritório', 0, 1);
$pdf->Cell(5, 6, '•', 0, 0);
$pdf->Cell(0, 6, 'Certificado de Participação', 0, 1);
$pdf->Ln(8);

// Conteúdo Programático
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'Conteúdo Programático', 0, 1);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(5, 6, '1.', 0, 0);
$pdf->Cell(0, 6, 'Apresentação do regulamento do concurso e suas especificidades', 0, 1);
$pdf->Cell(5, 6, '2.', 0, 0);
$pdf->Cell(0, 6, 'Procedimentos de identificação e controle de candidatos', 0, 1);
$pdf->Cell(5, 6, '3.', 0, 0);
$pdf->Cell(0, 6, 'Técnicas de fiscalização e supervisão durante a prova', 0, 1);
$pdf->Cell(5, 6, '4.', 0, 0);
$pdf->Cell(0, 6, 'Protocolo de segurança e confidencialidade', 0, 1);
$pdf->Cell(5, 6, '5.', 0, 0);
$pdf->Cell(0, 6, 'Gestão de situações excepcionais e emergências', 0, 1);
$pdf->Cell(5, 6, '6.', 0, 0);
$pdf->Cell(0, 6, 'Preenchimento correto de documentos e relatórios', 0, 1);
$pdf->Ln(8);

// Lista de Participantes
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'Fiscais que Participaram do Treinamento', 0, 1);
$pdf->Ln(3);

$pdf->SetFont('helvetica', '', 11);
$pdf->MultiCell(0, 6, 'Os seguintes fiscais fizeram o curso para fiscais e estão aptos para atuação no concurso:', 0, 'J');
$pdf->Ln(5);

// Criar lista de nomes separados por vírgula
$nomes_fiscais = array();
foreach ($fiscais as $fiscal) {
    $nomes_fiscais[] = $fiscal['nome'];
}
$lista_nomes = implode(', ', $nomes_fiscais);

$pdf->SetFont('helvetica', '', 10);

// Imprimir lista de nomes (o TCPDF gerenciará automaticamente as quebras de página)
$pdf->MultiCell(0, 6, $lista_nomes, 0, 'J');

$pdf->Ln(10);

// Metodologia Aplicada
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'Metodologia Aplicada', 0, 1);
$pdf->SetFont('helvetica', '', 11);
$pdf->MultiCell(0, 6, 'O treinamento foi conduzido através de metodologia teórico-prática, com apresentação expositiva dos conteúdos, demonstrações práticas dos procedimentos e simulações de situações reais que podem ocorrer durante a aplicação do concurso. Foram utilizados recursos audiovisuais e materiais didáticos específicos para garantir a efetiva compreensão dos participantes.', 0, 'J');
$pdf->Ln(5);

// Avaliação e Certificação
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'Avaliação e Certificação', 0, 1);
$pdf->SetFont('helvetica', '', 11);
$pdf->MultiCell(0, 6, 'Todos os participantes foram avaliados quanto ao aproveitamento do conteúdo ministrado através de exercícios práticos e questionamentos durante o treinamento. Os fiscais que demonstraram pleno domínio dos procedimentos e normas apresentadas foram considerados aptos para atuação no concurso e receberão certificado de participação.', 0, 'J');
$pdf->Ln(5);

// Compromissos Assumidos
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'Compromissos Assumidos', 0, 1);
$pdf->SetFont('helvetica', '', 11);
$pdf->MultiCell(0, 6, 'Os participantes assumiram o compromisso de aplicar rigorosamente todos os procedimentos apresentados, manter sigilo absoluto sobre o conteúdo das provas, agir com imparcialidade e ética profissional, e comunicar imediatamente à coordenação qualquer irregularidade observada durante a aplicação do concurso.', 0, 'J');
$pdf->Ln(8);

// Seção de conclusões
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Conclusões:', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 6, 'O treinamento foi realizado com êxito, atingindo seus objetivos de capacitação.', 0, 'L');
$pdf->MultiCell(0, 6, 'Todos os participantes demonstraram aptidão para exercer a função de fiscal.', 0, 'L');
$pdf->MultiCell(0, 6, '________________________________________________________________________________', 0, 'L');

$pdf->Ln(15);

// Assinaturas
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(95, 8, 'Responsável pelo Treinamento', 0, 0, 'C');
$pdf->Cell(95, 8, 'Coordenador', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(95, 20, '_______________________________', 0, 0, 'C');
$pdf->Cell(95, 20, '_______________________________', 0, 1, 'C');

// Limpar buffer antes de gerar PDF
ob_end_clean();

// Gerar PDF
$pdf->Output('ata_treinamento_' . date('Y-m-d_H-i-s') . '.pdf', 'I');