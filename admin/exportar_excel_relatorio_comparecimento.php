<?php
require_once '../config.php';

// Verificar se tem permissão para relatórios
if (!isLoggedIn() || !temPermissaoPresenca()) {
    redirect('../login.php');
}

$db = getDB();
$relatorio = [];

// Filtros
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : null;
$escola_id = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : null;
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

try {
    $sql = "
        SELECT 
            f.id,
            f.nome,
            f.cpf,
            f.celular,
            c.titulo as concurso_titulo,
            e.nome as escola_nome,
            s.nome as sala_nome,
            a.data_alocacao,
            a.horario_alocacao,
            a.tipo_alocacao,
            pt.status as presente_treinamento,
            pt.observacoes as obs_treinamento,
            pp.status as presente_prova,
            pp.observacoes as obs_prova
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        LEFT JOIN alocacoes_fiscais a ON f.id = a.fiscal_id AND a.status = 'ativo'
        LEFT JOIN escolas e ON a.escola_id = e.id
        LEFT JOIN salas s ON a.sala_id = s.id
        LEFT JOIN presenca pt ON f.id = pt.fiscal_id AND pt.concurso_id = f.concurso_id AND pt.tipo_presenca = 'treinamento'
        LEFT JOIN presenca pp ON f.id = pp.fiscal_id AND pp.concurso_id = f.concurso_id AND pp.tipo_presenca = 'prova'
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
    $relatorio = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar dados para relatório: ' . $e->getMessage(), 'ERROR');
}

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

// Configurar cabeçalhos para download Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="relatorio_comparecimento.xls"');
header('Cache-Control: max-age=0');

// Estatísticas
$total_fiscais = count($relatorio);
$presentes_treinamento = count(array_filter($relatorio, function($r) { return $r['presente_treinamento'] == 'presente'; }));
$presentes_prova = count(array_filter($relatorio, function($r) { return $r['presente_prova'] == 'presente'; }));
$presentes_ambos = count(array_filter($relatorio, function($r) { return $r['presente_treinamento'] == 'presente' && $r['presente_prova'] == 'presente'; }));

?>
<html>
<head>
    <meta charset="UTF-8">
    <title>Relatório de Comparecimento</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .header { background-color: #4CAF50; color: white; text-align: center; }
        .stats { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <table>
        <tr class="header">
            <th colspan="8">RELATÓRIO DE COMPARECIMENTO</th>
        </tr>
        <?php if ($concurso): ?>
        <tr class="header">
            <th colspan="8"><?= htmlspecialchars($concurso['orgao'] . ' - ' . $concurso['cidade'] . ' - ' . $concurso['estado']) ?></th>
        </tr>
        <tr class="header">
            <th colspan="8"><?= htmlspecialchars($concurso['titulo'] . ' - ' . $concurso['numero_concurso'] . '/' . $concurso['ano_concurso']) ?></th>
        </tr>
        <?php endif; ?>
        
        <tr class="stats">
            <th colspan="2">Total de Fiscais</th>
            <td colspan="2"><?= $total_fiscais ?></td>
            <th colspan="2">Presentes Treinamento</th>
            <td colspan="2"><?= $presentes_treinamento ?></td>
        </tr>
        <tr class="stats">
            <th colspan="2">Presentes Prova</th>
            <td colspan="2"><?= $presentes_prova ?></td>
            <th colspan="2">Presentes Ambos</th>
            <td colspan="2"><?= $presentes_ambos ?></td>
        </tr>
        
        <tr>
            <th>Nome</th>
            <th>CPF</th>
            <th>Concurso</th>
            <th>Escola</th>
            <th>Sala</th>
            <th>Data Alocação</th>
            <th>Treinamento</th>
            <th>Prova</th>
        </tr>
        
        <?php foreach ($relatorio as $fiscal): ?>
        <tr>
            <td><?= htmlspecialchars($fiscal['nome']) ?></td>
            <td><?= formatCPF($fiscal['cpf']) ?></td>
            <td><?= htmlspecialchars($fiscal['concurso_titulo']) ?></td>
            <td><?= htmlspecialchars($fiscal['escola_nome'] ?? 'Não alocado') ?></td>
            <td><?= htmlspecialchars($fiscal['sala_nome'] ?? 'Não alocado') ?></td>
            <td><?= $fiscal['data_alocacao'] ? date('d/m/Y', strtotime($fiscal['data_alocacao'])) : 'N/A' ?></td>
            <td>
                <?php 
                $treinamento = $fiscal['presente_treinamento'] ?? '';
                if ($treinamento == 'presente') {
                    echo 'Presente';
                } elseif ($treinamento == 'ausente') {
                    echo 'Ausente';
                } elseif ($treinamento == 'justificado') {
                    echo 'Justificado';
                } else {
                    echo 'Não registrado';
                }
                ?>
            </td>
            <td>
                <?php 
                $prova = $fiscal['presente_prova'] ?? '';
                if ($prova == 'presente') {
                    echo 'Presente';
                } elseif ($prova == 'ausente') {
                    echo 'Ausente';
                } elseif ($prova == 'justificado') {
                    echo 'Justificado';
                } else {
                    echo 'Não registrado';
                }
                ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html> 