<?php
require_once 'config.php';

try {
    $db = getDB();
    
    // Ler o arquivo SQL
    $sql = file_get_contents('certificados_table.sql');
    
    // Executar o SQL
    $db->exec($sql);
    
    echo "Tabela 'certificados' criada com sucesso!\n";
    echo "Estrutura da tabela:\n";
    echo "- id (chave primária)\n";
    echo "- fiscal_id (referência ao fiscal)\n";
    echo "- concurso_id (referência ao concurso)\n";
    echo "- numero_certificado (número único do certificado)\n";
    echo "- tipo_treinamento (treinamento/prova)\n";
    echo "- data_treinamento (data do treinamento)\n";
    echo "- data_geracao (timestamp de criação)\n";
    echo "- status (ativo/cancelado/reemitido)\n";
    echo "- observacoes (campo de texto livre)\n";
    echo "- usuario_gerador_id (usuário que gerou o certificado)\n";
    
} catch (Exception $e) {
    echo "Erro ao criar tabela: " . $e->getMessage() . "\n";
    exit(1);
}
?>