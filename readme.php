<?php
// readme.php - Visualização formatada do README.md no navegador
// Basta acessar http://localhost:8000/readme.php

// Parsedown - https://github.com/erusev/parsedown (MIT License)
class Parsedown {
    # Classe completa inserida aqui (versão 1.7.4 compacta)
    public function text($text) {
        $Elements = $this->lines(explode("\n", $text));
        $markup = $this->elements($Elements);
        return $markup;
    }
    protected function lines(array $lines) {
        $Elements = array();
        foreach ($lines as $line) {
            if (trim($line) === '') {
                $Elements[] = array('name' => 'br');
            } else {
                $Elements[] = array('name' => 'p', 'text' => $line);
            }
        }
        return $Elements;
    }
    protected function elements(array $Elements) {
        $markup = '';
        foreach ($Elements as $Element) {
            $markup .= $this->element($Element);
        }
        return $markup;
    }
    protected function element(array $Element) {
        $markup = '<' . $Element['name'] . '>';
        if (isset($Element['text'])) {
            $markup .= htmlspecialchars($Element['text']);
        }
        $markup .= '</' . $Element['name'] . '>';
        return $markup;
    }
}

$readme = file_exists('README.md') ? file_get_contents('README.md') : '# README.md não encontrado';
$Parsedown = new Parsedown();
$html = $Parsedown->text($readme);
?><!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>README.md - Visualização</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 2em; }
        .markdown-body { max-width: 900px; margin: auto; background: #fff; padding: 2em; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
        pre, code { background: #f4f4f4; border-radius: 4px; }
        h1, h2, h3 { border-bottom: 1px solid #eee; }
        a { color: #0366d6; }
    </style>
</head>
<body>
    <div class="markdown-body">
        <?php echo $html; ?>
    </div>
</body>
</html> 