<?php
// Limpar qualquer saída anterior
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

require_once '../config.php';
require_once '../TCPDF/tcpdf.php';

if (!isLoggedIn() || !temPermissaoPagamentos()) {
    redirect('../login.php');
}

// Função auxiliar para converter número para extenso
function numeroParaExtenso($numero) {
    $unidades = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];
    $dezenas = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
    $especiais = ['dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];
    $centenas = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];
    
    if ($numero == 0) return 'zero';
    if ($numero == 100) return 'cem';
    
    $extenso = '';
    
    // Centenas
    if ($numero >= 100) {
        $centena = (int) ($numero / 100);
        if ($centena > 0) {
            $extenso .= $centenas[$centena];
            if ($numero % 100 > 0) $extenso .= ' e ';
        }
        $numero = $numero % 100;
    }
    
    // Dezenas e unidades
    if ($numero >= 20) {
        $dezena = (int) ($numero / 10);
        $extenso .= $dezenas[$dezena];
        if ($numero % 10 > 0) {
            $extenso .= ' e ' . $unidades[$numero % 10];
        }
    } elseif ($numero >= 10) {
        $extenso .= $especiais[$numero - 10];
    } elseif ($numero > 0) {
        $extenso .= $unidades[$numero];
    }
    
    return $extenso;
}

function valorPorExtenso($valor) {
    $inteiro = (int) $valor;
    $centavos = round(($valor - $inteiro) * 100);
    
    // Montar o valor por extenso
    $extenso = '';
    
    if ($inteiro > 0) {
        $extenso .= numeroParaExtenso($inteiro);
        $extenso .= $inteiro == 1 ? ' real' : ' reais';
    }
    
    if ($centavos > 0) {
        if ($inteiro > 0) $extenso .= ' e ';
        $extenso .= numeroParaExtenso($centavos);
        $extenso .= $centavos == 1 ? ' centavo' : ' centavos';
    }
    
    return $extenso;
}

// Função para converter data por extenso
function dataPorExtenso($data, $cidade, $estado) {
    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
    ];
    
    $data_obj = new DateTime($data);
    $dia = (int) $data_obj->format('d');
    $mes = (int) $data_obj->format('m');
    $ano = (int) $data_obj->format('Y');
    
    $mes_extenso = $meses[$mes];
    
    return "$cidade - $estado, $dia de $mes_extenso de $ano.";
}

// Verificar se foi selecionado um concurso
$concurso_id = $_GET['concurso_id'] ?? null;

if (!$concurso_id) {
    // Se não foi selecionado, mostrar formulário de seleção
    $db = getDB();
    $concursos = $db->query("SELECT id, titulo, numero_concurso, orgao, data_prova, cidade, estado FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    include '../includes/header.php';
    ?>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-file-pdf me-2"></i>Gerar Recibos PDF</h4>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="mb-3">
                                <label for="concurso_id" class="form-label">Selecione o Concurso:</label>
                                <select class="form-select" id="concurso_id" name="concurso_id" required>
                                    <option value="">Escolha um concurso...</option>
                                    <?php foreach ($concursos as $concurso): ?>
                                        <option value="<?= $concurso['id'] ?>">
                                            <?= htmlspecialchars($concurso['titulo']) ?> 
                                            (<?= date('d/m/Y', strtotime($concurso['data_prova'])) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-download me-2"></i>Gerar Recibos PDF
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    include '../includes/footer.php';
    exit;
}

// Buscar dados do concurso selecionado
$db = getDB();
$stmt = $db->prepare("SELECT * FROM concursos WHERE id = ? AND status = 'ativo'");
$stmt->execute([$concurso_id]);
$concurso = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$concurso) {
    redirect('lista_pagamentos.php');
}

// Buscar fiscais aprovados do concurso selecionado
$sql = "
    SELECT f.nome, f.cpf, f.genero, c.valor_pagamento, e.nome as escola_nome, c.titulo as concurso_titulo,
           c.data_prova, c.cidade, c.estado
    FROM fiscais f
    LEFT JOIN concursos c ON f.concurso_id = c.id
    LEFT JOIN alocacoes_fiscais af ON f.id = af.fiscal_id AND af.status = 'ativo'
    LEFT JOIN escolas e ON af.escola_id = e.id
    WHERE f.status = 'aprovado' AND f.concurso_id = ? ORDER BY f.nome";
$stmt = $db->prepare($sql);
$stmt->execute([$concurso_id]);
$fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);

$instituto_nome = getConfig('instituto_nome', 'Instituto Dignidade Humana');
$instituto_logo = __DIR__ . '/../logos/instituto.png';
$instituto_info = getConfig('info_institucional', 'Instituto Dignidade Humana - Endereço: ... - Contato: ...');

// Classe personalizada com rodapé customizado
class PDFRecibos extends TCPDF
{
    protected $instituto_info;
    protected $cor_primaria = [33, 150, 33]; // Azul moderno

    public function setInstitutoInfo($info)
    {
        $this->instituto_info = $info;
    }

    public function Footer()
    {
        $this->SetY(-28);
        // Linha decorativa
        $this->SetDrawColor($this->cor_primaria[0], $this->cor_primaria[1], $this->cor_primaria[2]);
        $this->SetLineWidth(0.7);
        $this->Line(15, $this->GetY(), $this->getPageWidth() - 15, $this->GetY());
        $this->SetY(-24);
        // Informações institucionais
        $this->SetFont('helvetica', 8);
        $this->SetTextColor(80, 80, 80);
        $this->MultiCell(0, 10, $this->instituto_info, 0, 'C');
        // Número da página
        $this->SetY(-12);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(120, 120, 20);
        $this->Cell(0, 0, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}

// Criar PDF usando nossa classe personalizada
$pdf = new PDFRecibos('P', 'mm', 'A4', 'UTF-8', false);
$pdf->SetCreator('Sistema CadFiscais');
$pdf->SetAuthor('IDH');
$pdf->SetTitle('Recibos de Pagamento');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->setInstitutoInfo($instituto_info);
$pdf->SetMargins(15, 10, 15);
$pdf->SetAutoPageBreak(false);
$pdf->SetFont('helvetica', '', 10);

foreach ($fiscais as $fiscal) {
    $pdf->AddPage();
    
    // === PRIMEIRA METADE (VIA DO FISCAL) ===
    $pdf->SetY(10);
    
    // Cabeçalho da primeira metade
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 8, $instituto_nome, 0, 1, 'C');
    $pdf->Image($instituto_logo, 15, 10, 20, 0, '', '', '', false, 300, '', false, false, 0);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Ln(8);
    
    // Título do recibo
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'Recibo de Pagamento de Fiscal', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Corpo do recibo
    $pdf->SetFont('helvetica', '', 12);
    $termo_portador = ($fiscal['genero'] === 'F') ? 'portadora' : 'portador';
    $ano_concurso = '';
    if (!empty($fiscal['data_prova'])) {
        $ano_concurso = date('Y', strtotime($fiscal['data_prova']));
    }
    $numero_concurso = $concurso['numero_concurso']; // Se houver campo especifico, troque aqui
    $orgao = $concurso['orgao'] ?? '';
    $cidade = $fiscal['cidade'] ?? '';
    $estado = $fiscal['estado'] ?? '';
    $texto = 'Eu, <b>' . htmlspecialchars($fiscal['nome']) . '</b>, ' . $termo_portador . ' do CPF de número <b>' . htmlspecialchars(formatCPF($fiscal['cpf'])) . '</b>, declaro que recebi do Instituto Dignidade Humana a importância de <b>R$ ' . number_format($fiscal['valor_pagamento'], 2, ',', '.') . '</b> (' . ucfirst(valorPorExtenso($fiscal['valor_pagamento'])) . ') referente à minha atuação como fiscal no Concurso Público <b>' . $numero_concurso . '/' . $ano_concurso . '</b> da <b>' . htmlspecialchars($orgao) . '</b> do município de <b>' . htmlspecialchars($cidade) . ' - ' . htmlspecialchars($estado) . '</b> na <b>' . htmlspecialchars($fiscal['escola_nome'] ?? 'Não alocado') . '</b>.';
    $pdf->writeHTMLCell(0, 0, '', '', $texto, 0, 1, false, true, 'J');
    $pdf->Ln(4);
    
    // Data por extenso
    $data_extenso = dataPorExtenso($fiscal['data_prova'], $fiscal['cidade'], $fiscal['estado']);
    $pdf->Cell(0, 6, $data_extenso, 0, 1, 'L');
    $pdf->Ln(18);
    
    // Assinaturas
    $pdf->Cell(80, 8, 'Assinatura do Fiscal', 'T', 0, 'C');
    $pdf->Cell(20, 8, '', 0, 0, 'C');
    $pdf->Cell(80, 8, 'Assinatura do Coordenador', 'T', 1, 'C');
    $pdf->Ln(12);
    
    // Identificação da via
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Via do Fiscal', 0, 1, 'R');
    
    // Rodapé da primeira metade
    $pdf->SetY(130);
    $pdf->SetDrawColor(33, 150, 33); // Azul moderno
    $pdf->SetLineWidth(0.7);
    $pdf->Line(15, $pdf->GetY(), $pdf->getPageWidth() - 15, $pdf->GetY());
    $pdf->SetY($pdf->GetY() + 4);
    $pdf->SetFont('helvetica', 8);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->MultiCell(0, 10, $instituto_info, 0, 'C');
    
    // Resetar configurações
    $pdf->SetDrawColor(0, 0, 0); // Preto
    $pdf->SetLineWidth(0.2); // Espessura padrão
    $pdf->SetTextColor(0, 0, 0); // Texto preto
    
    // Linha de corte tracejada com tesourinha
    $pdf->SetLineStyle(['width' => 0.5, 'dash' => '2,2']);
    $pdf->Line(15, 148.5, 195, 148.5);
    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0]); // Resetar para linha contínua
    $pdf->SetFont('dejavusans', '', 12); // Fonte compatível com Unicode
    $pdf->Text(12, 146.5, '✂');
    $pdf->SetFont('helvetica', '', 12); // Volta para fonte padrão
    
    // === SEGUNDA METADE (VIA DO IDH) ===
    $pdf->SetY(155);
    
    // Cabeçalho da segunda metade
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 8, $instituto_nome, 0, 1, 'C');
    $pdf->Image($instituto_logo, 15, 155, 20, 0, '', '', '', false, 300, '', false, false, 0);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Ln(8);
    
    // Título do recibo
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'Recibo de Pagamento de Fiscal', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Corpo do recibo (segunda via)
    $pdf->SetFont('helvetica', '', 12);
    $termo_portador = ($fiscal['genero'] === 'F') ? 'portadora' : 'portador';
    $ano_concurso = '';
    if (!empty($fiscal['data_prova'])) {
        $ano_concurso = date('Y', strtotime($fiscal['data_prova']));
    }
    $numero_concurso = $concurso['numero_concurso']; // Se houver campo especifico, troque aqui
    $orgao = $concurso['orgao'] ?? '';
    $cidade = $fiscal['cidade'] ?? '';
    $estado = $fiscal['estado'] ?? '';
    $texto = 'Eu, <b>' . htmlspecialchars($fiscal['nome']) . '</b>, ' . $termo_portador . ' do CPF de número <b>' . htmlspecialchars(formatCPF($fiscal['cpf'])) . '</b>, declaro que recebi do Instituto Dignidade Humana a importância de <b>R$ ' . number_format($fiscal['valor_pagamento'], 2, ',', '.') . '</b> (' . ucfirst(valorPorExtenso($fiscal['valor_pagamento'])) . ') referente à minha atuação como fiscal no Concurso Público <b>' . $numero_concurso . '/' . $ano_concurso . '</b> da <b>' . htmlspecialchars($orgao) . '</b> do município de <b>' . htmlspecialchars($cidade) . ' - ' . htmlspecialchars($estado) . '</b> na <b>' . htmlspecialchars($fiscal['escola_nome'] ?? 'Não alocado') . '</b>.';
    $pdf->writeHTMLCell(0, 0, '', '', $texto, 0, 1, false, true, 'J');
    $pdf->Ln(4);
    
    // Data por extenso
    $data_extenso = dataPorExtenso($fiscal['data_prova'], $fiscal['cidade'], $fiscal['estado']);
    $pdf->Cell(0, 6, $data_extenso, 0, 1, 'L');
    $pdf->Ln(18);
    
    // Assinaturas
    $pdf->Cell(80, 8, 'Assinatura do Fiscal', 'T', 0, 'C');
    $pdf->Cell(20, 8, '', 0, 0, 'C');
    $pdf->Cell(80, 8, 'Assinatura do Coordenador', 'T', 1, 'C');
    $pdf->Ln(12);
    
    // Identificação da via
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Via do IDH', 0, 1, 'R');
    
    // Rodapé da segunda metade
    $pdf->SetY(275);
    $pdf->SetDrawColor(33, 150, 33); // Azul moderno
    $pdf->SetLineWidth(0.7);
    $pdf->Line(15, $pdf->GetY(), $pdf->getPageWidth() - 15, $pdf->GetY());
    $pdf->SetY($pdf->GetY() + 4);
    $pdf->SetFont('helvetica', 8);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->MultiCell(0, 10, $instituto_info, 0, 'C');
    
    // Resetar configurações para a próxima página
    $pdf->SetDrawColor(0, 0, 0); // Preto
    $pdf->SetLineWidth(0.2); // Espessura padrão
    $pdf->SetTextColor(0, 0, 0); // Texto preto
}

// Limpar buffer e enviar PDF
ob_end_clean();
$pdf->Output('recibos_fiscais.pdf', 'I');
exit; 