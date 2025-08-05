<?php
require_once 'config.php';

// A função formatCPF já está definida em config.php

$numero_certificado_input = $_POST['numero_certificado'] ?? $_GET['numero_certificado'] ?? '';
$certificado_valido = false;
$dados_certificado = null;
$error = '';

if (!empty($numero_certificado_input)) {
    // Validação apenas pelo código do certificado
    try {
        $db = getDB();
        
        // Buscar certificado diretamente na tabela certificados
        $sql = "
            SELECT cert.*, f.nome, f.cpf, c.titulo, c.numero_concurso, c.orgao, c.cidade, c.estado, 
                   cert.data_treinamento, c.data_prova, c.ano_concurso
            FROM certificados cert
            INNER JOIN fiscais f ON cert.fiscal_id = f.id
            INNER JOIN concursos c ON cert.concurso_id = c.id
            WHERE cert.numero_certificado = ? AND cert.status = 'ativo' AND cert.tipo_treinamento = 'treinamento'
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([strtoupper($numero_certificado_input)]);
        $certificado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($certificado) {
            $certificado_valido = true;
            $dados_certificado = $certificado;
        } else {
            $error = 'Certificado não encontrado ou inválido.';
        }
        
    } catch (Exception $e) {
        $error = 'Erro ao validar certificado: ' . $e->getMessage();
    }
} elseif (empty($numero_certificado_input)) {
    // Página inicial - mostrar formulário
    $error = '';
} else {
    $error = 'Código do certificado não informado.';
}

// Obter número do certificado e definir tipo de validação
$numero_certificado = '';
$validacao_por_numero = false;
if ($certificado_valido) {
    $numero_certificado = $dados_certificado['numero_certificado'];
    $validacao_por_numero = !empty($numero_certificado_input); // True se validado por código
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validação de Certificado - IDH</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .validation-container {
            max-width: 800px;
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
        .validation-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }
        .status-valid {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }
        .status-invalid {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }
        .status-icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .certificate-details {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
            flex: 1;
        }
        .detail-value {
            flex: 2;
            text-align: right;
            color: #212529;
        }
        .certificate-number {
            background: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            font-family: monospace;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .btn-download {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,123,255,0.4);
            color: white;
            text-decoration: none;
        }
        .validation-info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #2196f3;
        }
        .validation-info h3 {
            color: #1976d2;
            margin-bottom: 15px;
        }
        @media (max-width: 768px) {
            .detail-row {
                flex-direction: column;
                align-items: flex-start;
            }
            .detail-value {
                text-align: left;
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="validation-container">
        <!-- Menu de Navegação -->
        <nav style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 15px;">
                <a href="index.php" style="color: #007bff; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background-color 0.3s;" onmouseover="this.style.backgroundColor='#e7f3ff'" onmouseout="this.style.backgroundColor='transparent'">
                    <i class="fas fa-home"></i> Início
                </a>
                <span style="color: #6c757d;">|</span>
                <a href="certificado_treinamento.php" style="color: #007bff; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background-color 0.3s;" onmouseover="this.style.backgroundColor='#e7f3ff'" onmouseout="this.style.backgroundColor='transparent'">
                    <i class="fas fa-certificate"></i> Gerar Certificado
                </a>
                <span style="color: #6c757d;">|</span>
                <a href="validar_certificado.php" style="color: #17a2b8; text-decoration: none; padding: 8px 15px; border-radius: 5px; background-color: #e6f7ff; font-weight: bold;">
                    <i class="fas fa-check-circle"></i> Validar Certificado
                </a>
            </div>
        </nav>
        
        <div class="header">
            <h1>🔍 Validação de Certificado</h1>
            <p>Instituto Dignidade Humana</p>
        </div>

        <div class="validation-card">
            <?php if (empty($numero_certificado_input)): ?>
                <!-- Formulário de validação manual -->
                <div style="text-align: center; margin-bottom: 30px;">
                    <h2 style="color: #007bff; margin-bottom: 20px;">🔍 Validar Certificado</h2>
                    <p style="color: #6c757d; margin-bottom: 30px;">Insira o código do certificado para validar</p>
                </div>
                
                <form method="POST" action="" style="max-width: 500px; margin: 0 auto;">
                    <div style="margin-bottom: 30px;">
                        <label for="numero_certificado" style="display: block; font-weight: 600; margin-bottom: 8px; color: #495057;">Código do Certificado:</label>
                        <input type="text" id="numero_certificado" name="numero_certificado" required 
                               style="width: 100%; padding: 15px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 1.2rem; font-family: monospace; letter-spacing: 2px; text-transform: uppercase; text-align: center; transition: border-color 0.3s;"
                               placeholder="Ex: 70C32B9F" maxlength="8" />
                        <small style="color: #6c757d; margin-top: 5px; display: block;">Digite o código de 8 caracteres presente no certificado</small>
                    </div>
                    
                    <div style="text-align: center;">
                        <button type="submit" class="btn-download" style="margin-top: 0; padding: 15px 30px; font-size: 1.1rem;">
                            🔍 Validar Certificado
                        </button>
                    </div>
                </form>
                
                <div class="validation-info" style="margin-top: 30px;">
                    <h3>ℹ️ Como validar seu certificado</h3>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li><strong>Pelo QR Code:</strong> Escaneie o código QR presente no certificado</li>
                        <li><strong>Manualmente:</strong> Insira apenas o código do certificado</li>
                        <li>O código do certificado está localizado no canto inferior direito do documento</li>
                        <li>Ambos os métodos garantem a autenticidade do certificado</li>
                    </ul>
                </div>
                
            <?php elseif ($certificado_valido): ?>
                <div class="status-valid">
                    <div class="status-icon">✅</div>
                    <h2>Certificado Válido</h2>
                    <p>Este certificado é autêntico e foi emitido pelo Instituto Dignidade Humana</p>
                    <?php if ($validacao_por_numero): ?>
                        <p style="font-size: 0.9rem; opacity: 0.9;">✓ Validado por código do certificado</p>
                    <?php else: ?>
                        <p style="font-size: 0.9rem; opacity: 0.9;">✓ Validado por QR Code</p>
                    <?php endif; ?>
                </div>
                
                <div class="certificate-details">
                    <h3 style="color: #007bff; margin-bottom: 20px;">📋 Detalhes do Certificado</h3>
                    
                    <div class="detail-row">
                        <span class="detail-label">Número do Certificado:</span>
                        <span class="detail-value">
                            <span class="certificate-number"><?= $numero_certificado ?></span>
                        </span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Participante:</span>
                        <span class="detail-value"><strong><?= htmlspecialchars($dados_certificado['nome']) ?></strong></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">CPF:</span>
                        <span class="detail-value"><?= formatCPF($dados_certificado['cpf']) ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Local:</span>
                        <span class="detail-value"><?= htmlspecialchars($dados_certificado['cidade'] . '/' . $dados_certificado['estado']) ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Data do Treinamento:</span>
                        <span class="detail-value"><?php 
                            $data_treinamento = $dados_certificado['data_treinamento'] ?? null;
                            if ($data_treinamento && $data_treinamento !== '0000-00-00' && strtotime($data_treinamento)) {
                                echo date('d/m/Y', strtotime($data_treinamento));
                            } else {
                                echo 'Data não informada';
                            }
                        ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Carga Horária:</span>
                        <span class="detail-value">4 horas</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Data de Validação:</span>
                        <span class="detail-value"><?= date('d/m/Y H:i:s') ?></span>
                    </div>
                </div>
                
            <?php else: ?>
                <div class="status-invalid">
                    <div class="status-icon">❌</div>
                    <h2>Certificado Inválido</h2>
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="certificado_treinamento.php" class="btn-download">
                        🔍 Verificar Outro Certificado
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="validation-info">
                <h3>ℹ️ Sobre a Validação</h3>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>Esta validação confirma a autenticidade do certificado</li>
                    <li>O QR Code contém informações criptografadas únicas</li>
                    <li>Certificados válidos podem ser baixados a qualquer momento</li>
                    <li>Em caso de dúvidas, entre em contato com o Instituto Dignidade Humana</li>
                </ul>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" style="color: #6c757d; text-decoration: none;">
                🏠 Voltar ao Início
            </a>
        </div>
    </div>
</body>
</html>