<?php
require_once __DIR__ . '/../TCPDF/tcpdf.php';

class PDFInstituto extends TCPDF {
    protected $instituto_nome;
    protected $instituto_logo;
    protected $instituto_info;
    protected $cor_primaria = [33, 150, 243]; // Azul moderno
    protected $cor_secundaria = [240, 240, 240]; // Cinza claro

    public function setInstitutoData($nome, $logo, $info) {
        $this->instituto_nome = $nome;
        $this->instituto_logo = $logo;
        $this->instituto_info = $info;
    }

    // Cabeçalho elegante
    public function Header() {
        // Remover fundo colorido - deixar branco
        $this->SetFillColor(255, 255, 255);
        $this->Rect(0, 0, $this->getPageWidth(), 30, 'F');
        
        // Calcular posições para logo e texto lado a lado
        $logo_width = 30; // Tamanho da logo
        $text = "Instituto Dignidade Humana";
        $this->SetFont('helvetica', 'B', 16);
        $text_width = $this->GetStringWidth($text);
        
        // Calcular posição central total (logo + espaçamento + texto)
        $total_width = $logo_width + 15 + $text_width; // 10px de espaçamento
        $start_x = ($this->getPageWidth() - $total_width) / 2;
        
        // Posicionar logo
        if ($this->instituto_logo && file_exists($this->instituto_logo)) {
            $this->Image($this->instituto_logo, $start_x, 8, $logo_width, '', '', '', 'T', false, 300);
        }
        
        // Nome do Instituto ao lado da logo (não em cima)
        $this->SetFont('helvetica', 'B', 18);
        $this->SetTextColor(0, 0, 0); // Texto preto
        $this->SetY(10); // Mesma altura da logo
        $this->SetX($start_x + $logo_width + 10); // Posicionar após a logo + espaçamento
        $this->Cell($text_width, 8, $text, 0, 0, 'L', false, '', 0, false, 'T', 'T');
        
        // Linha decorativa sutil
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, 25, $this->getPageWidth() - 10, 25);
        
        $this->SetY(35); // Posição para o conteúdo
    }

    // Rodapé elegante
    public function Footer() {
        $this->SetY(-28);
        // Linha decorativa
        $this->SetDrawColor($this->cor_primaria[0], $this->cor_primaria[1], $this->cor_primaria[2]);
        $this->SetLineWidth(0.7);
        $this->Line(15, $this->GetY(), $this->getPageWidth() - 15, $this->GetY());
        $this->SetY(-24);
        // Informações institucionais
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(80,80,80);
        $this->MultiCell(0, 10, $this->instituto_info, 0, 'C');
        // Número da página
        $this->SetY(-12);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(120,120,120);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }
} 