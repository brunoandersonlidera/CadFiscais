<?php
require_once 'config.php';

$message = '';
$error = '';
$certificado_gerado = false;

// Processar solicitação de certificado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerar_certificado'])) {
    $cpf = sanitizeInput($_POST['cpf'] ?? '');
    
    if (empty($cpf)) {
        $error = 'Por favor, informe seu CPF.';
    } else {
        // Remover formatação do CPF
        $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf_limpo) !== 11) {
            $error = 'CPF deve conter 11 dígitos.';
        } else {
            try {
                $db = getDB();
                
                // Buscar fiscal pelo CPF que participou de treinamento
                $sql = "
                    SELECT DISTINCT f.*, c.titulo, c.numero_concurso, c.orgao, c.cidade, c.estado, 
                           p.data_presenca, c.data_prova
                    FROM fiscais f
                    INNER JOIN presenca p ON f.id = p.fiscal_id
                    INNER JOIN concursos c ON f.concurso_id = c.id
                    WHERE f.cpf = ? AND p.tipo_presenca = 'treinamento' AND p.status = 'presente'
                    ORDER BY p.data_presenca DESC
                    LIMIT 1
                ";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([$cpf_limpo]);
                $fiscal = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($fiscal) {
                    // Redirecionar para geração do PDF
                    $certificado_gerado = true;
                    $url_certificado = 'gerar_certificado_pdf.php?cpf=' . urlencode($cpf_limpo);
                } else {
                    $error = 'Não foi encontrado registro de participação em treinamento para este CPF.';
                }
                
            } catch (Exception $e) {
                $error = 'Erro ao buscar dados: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado de Treinamento - IDH</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .certificate-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 30px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,123,255,0.3);
        }
        .header h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        .form-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,123,255,0.4);
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .certificate-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .certificate-info h3 {
            color: #007bff;
            margin-bottom: 15px;
        }
        .certificate-info ul {
            list-style: none;
            padding: 0;
        }
        .certificate-info li {
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .certificate-info li:last-child {
            border-bottom: none;
        }
        .btn-download {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
            margin-top: 20px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40,167,69,0.4);
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <!-- Menu de Navegação -->
        <nav style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 15px;">
                <a href="index.php" style="color: #007bff; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background-color 0.3s;" onmouseover="this.style.backgroundColor='#e7f3ff'" onmouseout="this.style.backgroundColor='transparent'">
                    <i class="fas fa-home"></i> Início
                </a>
                <span style="color: #6c757d;">|</span>
                <a href="certificado_treinamento.php" style="color: #28a745; text-decoration: none; padding: 8px 15px; border-radius: 5px; background-color: #e8f5e8; font-weight: bold;">
                    <i class="fas fa-certificate"></i> Gerar Certificado
                </a>
                <span style="color: #6c757d;">|</span>
                <a href="validar_certificado.php" style="color: #007bff; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background-color 0.3s;" onmouseover="this.style.backgroundColor='#e7f3ff'" onmouseout="this.style.backgroundColor='transparent'">
                    <i class="fas fa-check-circle"></i> Validar Certificado
                </a>
            </div>
        </nav>
        
        <div class="header">
            <h1>🎓 Certificado de Treinamento</h1>
            <p>Instituto Dignidade Humana</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if (!$certificado_gerado): ?>
        <div class="form-card">
            <h2 style="text-align: center; margin-bottom: 25px; color: #333;">Gerar Certificado</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label for="cpf">CPF do Participante:</label>
                    <input type="text" id="cpf" name="cpf" class="form-control" 
                           placeholder="000.000.000-00" maxlength="14" 
                           value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>" required>
                </div>
                
                <button type="submit" name="gerar_certificado" class="btn-primary">
                    🔍 Buscar e Gerar Certificado
                </button>
            </form>
            
            <div class="certificate-info">
                <h3>ℹ️ Informações Importantes</h3>
                <ul>
                    <li><strong>Quem pode gerar:</strong> Apenas fiscais que participaram do treinamento</li>
                    <li><strong>Validação:</strong> Cada certificado possui QR Code para validação</li>
                    <li><strong>Formato:</strong> PDF para download e impressão</li>
                    <li><strong>Dados necessários:</strong> Apenas o CPF do participante</li>
                </ul>
            </div>
        </div>
        <?php else: ?>
        <div class="form-card">
            <h2 style="text-align: center; margin-bottom: 25px; color: #28a745;">✅ Certificado Encontrado!</h2>
            
            <div class="alert alert-success">
                <strong>Sucesso!</strong> Seus dados foram encontrados e o certificado está pronto para download.
            </div>
            
            <a href="<?= $url_certificado ?>" class="btn-download" target="_blank">
                📄 Baixar Certificado PDF
            </a>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="certificado_treinamento.php" style="color: #007bff; text-decoration: none;">
                    ← Gerar outro certificado
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" style="color: #6c757d; text-decoration: none;">
                🏠 Voltar ao Início
            </a>
        </div>
    </div>

    <script>
    // Máscara para CPF
    document.getElementById('cpf').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = value;
    });
    </script>
</body>
</html>