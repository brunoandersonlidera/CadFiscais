<?php
require_once 'config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

// Redirecionar para a página de seleção de tipo de presença
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Presença - IDH</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .mobile-optimized {
            max-width: 100%;
            padding: 10px;
        }
        .header {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        .presenca-option {
            border: 2px solid #ddd;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            background: #fff;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .presenca-option:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,123,255,0.15);
            text-decoration: none;
            color: inherit;
        }
        .presenca-option.treinamento {
            border-left: 5px solid #007bff;
        }
        .presenca-option.prova {
            border-left: 5px solid #dc3545;
        }
        .icon-large {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .btn-voltar {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .btn-voltar:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container mobile-optimized">
        <div class="header">
            <h1>📱 Controle de Presença</h1>
            <p>Sistema IDH - Escolha o tipo de presença</p>
        </div>

        <div class="row">
            <div class="col-md-6">
                <a href="presenca_treinamento.php" class="presenca-option treinamento">
                    <div class="text-center">
                        <div class="icon-large">📚</div>
                        <h3>Presença no Treinamento</h3>
                        <p class="text-muted">
                            Controle de presença dos fiscais durante o treinamento prévio à aplicação da prova.
                        </p>
                        <div class="mt-3">
                            <span class="badge bg-primary">Treinamento</span>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-6">
                <a href="presenca_prova.php" class="presenca-option prova">
                    <div class="text-center">
                        <div class="icon-large">📝</div>
                        <h3>Presença na Prova</h3>
                        <p class="text-muted">
                            Controle de presença dos fiscais no dia da aplicação da prova.
                        </p>
                        <div class="mt-3">
                            <span class="badge bg-danger">Prova</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        
        <div class="text-center">
            <a href="index.php" class="btn-voltar">
                <i class="fas fa-arrow-left me-2"></i>
                Voltar ao Início
            </a>
        </div>
    </div>
</body>
</html> 