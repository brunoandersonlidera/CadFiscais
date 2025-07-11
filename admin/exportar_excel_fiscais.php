<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();

// Parâmetros
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : 0;

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
    $sql = "SELECT f.*, 
                   CASE WHEN af.id IS NOT NULL THEN 'Alocado' ELSE 'Não Alocado' END as status_alocacao,
                   e.nome as escola_nome,
                   s.nome as sala_nome
            FROM fiscais f
            LEFT JOIN alocacoes_fiscais af ON f.id = af.fiscal_id AND af.concurso_id = ?
            LEFT JOIN salas s ON af.sala_id = s.id
            LEFT JOIN escolas e ON s.escola_id = e.id";
    
    if ($concurso_id) {
        $sql .= " WHERE f.concurso_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$concurso_id, $concurso_id]);
    } else {
        $sql .= " ORDER BY f.nome";
        $stmt = $db->prepare($sql);
        $stmt->execute([null]);
    }
    
    $fiscais = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar fiscais: ' . $e->getMessage(), 'ERROR');
    $fiscais = [];
}

// Configurar cabeçalhos para download do Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="relatorio_fiscais_' . date('Y-m-d_H-i-s') . '.xls"');
header('Cache-Control: max-age=0');

// Início do arquivo Excel
?>
<html>
<head>
    <meta charset="UTF-8">
    <title>Relatório de Fiscais</title>
</head>
<body>
    <table border="1">
        <tr>
            <th colspan="6" style="background-color: #4CAF50; color: white; font-size: 16px; text-align: center;">
                RELATÓRIO DE FISCAIS
            </th>
        </tr>
        <?php if ($concurso): ?>
        <tr>
            <th colspan="6" style="background-color: #f0f0f0; text-align: left;">
                Concurso: <?= htmlspecialchars($concurso['titulo']) ?> | 
                Data da Prova: <?= date('d/m/Y', strtotime($concurso['data_prova'])) ?>
            </th>
        </tr>
        <?php endif; ?>
        <tr>
            <th style="background-color: #2196F3; color: white;">Nome</th>
            <th style="background-color: #2196F3; color: white;">CPF</th>
            <th style="background-color: #2196F3; color: white;">Telefone</th>
            <th style="background-color: #2196F3; color: white;">Status</th>
            <th style="background-color: #2196F3; color: white;">Alocação</th>
            <th style="background-color: #2196F3; color: white;">Escola</th>
        </tr>
        <?php foreach ($fiscais as $fiscal): ?>
        <tr>
            <td><?= htmlspecialchars($fiscal['nome']) ?></td>
            <td><?= htmlspecialchars($fiscal['cpf']) ?></td>
            <td><?= htmlspecialchars($fiscal['telefone']) ?></td>
            <td><?= ucfirst($fiscal['status']) ?></td>
            <td><?= $fiscal['status_alocacao'] ?></td>
            <td><?= htmlspecialchars($fiscal['escola_nome'] ?? '-') ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <th colspan="6" style="background-color: #f0f0f0; text-align: left;">
                ESTATÍSTICAS
            </th>
        </tr>
        <tr>
            <td colspan="2">Total de Fiscais: <?= count($fiscais) ?></td>
            <td colspan="2">Fiscais Aprovados: <?= count(array_filter($fiscais, function($f) { return $f['status'] == 'aprovado'; })) ?></td>
            <td colspan="2">Fiscais Alocados: <?= count(array_filter($fiscais, function($f) { return $f['status_alocacao'] == 'Alocado'; })) ?></td>
        </tr>
        <tr>
            <th colspan="6" style="background-color: #f0f0f0; text-align: left;">
                Relatório gerado em: <?= date('d/m/Y H:i:s') ?>
            </th>
        </tr>
    </table>
</body>
</html> 