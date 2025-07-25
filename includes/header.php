<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Sistema de Fiscais' ?> - IDH</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- IMask -->
    <script src="https://unpkg.com/imask"></script>
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --info-color: #0dcaf0;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }
        
        .stats-card {
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-card .icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        
        .card-header {
            border-bottom: 1px solid #e9ecef;
            background-color: #fff;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .btn {
            border-radius: 8px;
            font-weight: 500;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        
        .table {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .progress {
            border-radius: 10px;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .modal-header {
            border-bottom: 1px solid #e9ecef;
            border-radius: 15px 15px 0 0;
        }
        
        .footer {
            background-color: #343a40;
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        
        .footer a {
            color: #adb5bd;
            text-decoration: none;
        }
        
        .footer a:hover {
            color: white;
        }
        
        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 1rem;
            }
            
            .navbar-brand img {
                height: 30px;
            }
        }
        /* Suporte visual para dropdown-submenu */
        .dropdown-submenu {
            position: relative;
        }
        .dropdown-submenu > .dropdown-menu {
            top: 0;
            left: 100%;
            margin-top: -1px;
            margin-left: 0;
            border-radius: 0.5rem;
            min-width: 220px;
        }
        .dropdown-submenu > a:after {
            content: "\f105";
            float: right;
            border: none;
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
        }
        .dropdown-submenu .dropdown-menu {
            display: none;
        }
        .dropdown-submenu.show > .dropdown-menu {
            display: block;
        }
    </style>
</head>
<body>
    <?php
    // Detectar se estamos na pasta admin ou gerador_dados
    $currentPath = $_SERVER['REQUEST_URI'] ?? '';
    $isAdmin = strpos($currentPath, '/admin/') !== false;
    $isGerador = strpos($currentPath, '/gerador_dados/') !== false;
    
    // Definir basePath baseado na localização atual
    if ($isAdmin) {
        $basePath = '../';
    } elseif ($isGerador) {
        $basePath = '../';
    } else {
        $basePath = '';
    }
    
    // Debug (remover em produção)
    // echo "<!-- Debug: currentPath=$currentPath, isAdmin=" . ($isAdmin ? 'true' : 'false') . ", isGerador=" . ($isGerador ? 'true' : 'false') . ", basePath=$basePath -->";
    ?>
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= isLoggedIn() ? ($isAdmin ? 'dashboard.php' : 'admin/dashboard.php') : 'index.php' ?>">
                <img src="<?= $basePath ?>logos/instituto.png" alt="IDH" class="me-2">
                <span>Instituto Dignidade Humana</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isLoggedIn()): ?>
                    <!-- Menu Administrativo -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $isAdmin ? 'dashboard.php' : 'admin/dashboard.php' ?>">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-clipboard-list me-1"></i>Concursos
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'concursos.php' : 'admin/concursos.php' ?>">
                                <i class="fas fa-list me-2"></i>Gerenciar Concursos
                            </a></li>
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'novo_concurso.php' : 'admin/novo_concurso.php' ?>">
                                <i class="fas fa-plus me-2"></i>Novo Concurso
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'escolas.php' : 'admin/escolas.php' ?>">
                                <i class="fas fa-school me-2"></i>Escolas dos Concursos
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users me-1"></i>Fiscais
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'fiscais.php' : 'admin/fiscais.php' ?>">
                                <i class="fas fa-list me-2"></i>Listar Fiscais
                            </a></li>
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'editar_fiscal.php' : 'admin/editar_fiscal.php' ?>">
                                <i class="fas fa-edit me-2"></i>Editar Fiscais
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li class="dropdown-submenu">
                                <a class="dropdown-item dropdown-toggle" href="#">
                                    <i class="fas fa-clipboard-check me-2"></i>Presença
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?= $basePath ?>presenca_prova.php">
                                        <i class="fas fa-file-alt me-2"></i>Presença na Prova
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?= $basePath ?>presenca_treinamento.php">
                                        <i class="fas fa-graduation-cap me-2"></i>Presença no Treinamento
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?= $isAdmin ? 'lista_presenca.php' : 'admin/lista_presenca.php' ?>">
                                        <i class="fas fa-list me-2"></i>Lista de Presença - Prova
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?= $isAdmin ? 'lista_presenca_treinamento.php' : 'admin/lista_presenca_treinamento.php' ?>">
                                        <i class="fas fa-graduation-cap me-2"></i>Lista de Presença - Treinamento
                                    </a></li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-bar me-1"></i>Relatórios
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'relatorios.php' : 'admin/relatorios.php' ?>">
                                <i class="fas fa-chart-bar me-2"></i>Relatórios Gerais
                            </a></li>
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'relatorio_fiscais.php' : 'admin/relatorio_fiscais.php' ?>">
                                <i class="fas fa-list me-2"></i>Exportar Fiscais (PDF/Excel)
                            </a></li>
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'exportar_pdf_fiscais.php' : 'admin/exportar_pdf_fiscais.php' ?>">
                                <i class="fas fa-file-pdf me-2"></i> Relação de Fiscais Inscritos
                            </a></li>
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'exportar_pdf_fiscais_aprovados.php' : 'admin/exportar_pdf_fiscais_aprovados.php' ?>">
                                <i class="fas fa-file-pdf me-2"></i> Relação de Fiscais Aprovados
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-school me-1"></i>Locais
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'escolas.php' : 'admin/escolas.php' ?>">
                                <i class="fas fa-school me-2"></i>Gerenciar Escolas
                            </a></li>
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'salas.php' : 'admin/salas.php' ?>">
                                <i class="fas fa-door-open me-2"></i>Gerenciar Salas
                            </a></li>
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'alocar_fiscais.php' : 'admin/alocar_fiscais.php' ?>">
                                <i class="fas fa-map-marker-alt me-2"></i>Alocar Fiscais
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-money-bill-wave me-1"></i>Financeiro
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'lista_pagamentos.php' : 'admin/lista_pagamentos.php' ?>">
                                <i class="fas fa-list me-2"></i>Lista de Pagamentos
                            </a></li>
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'planilha_pagamentos.php' : 'admin/planilha_pagamentos.php' ?>">
                                <i class="fas fa-table me-2"></i>Planilha de Pagamentos
                            </a></li>
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'recibo_pagamento.php' : 'admin/recibo_pagamento.php' ?>">
                                <i class="fas fa-receipt me-2"></i>Recibos
                            </a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <!-- Menu Público -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePath ?>index.php">
                            <i class="fas fa-home me-1"></i>Início
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePath ?>cadastro.php">
                            <i class="fas fa-user-plus me-1"></i>Cadastro
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'configuracoes.php' : 'admin/configuracoes.php' ?>">
                                <i class="fas fa-cog me-2"></i>Configurações
                            </a></li>
                            <?php if (isAdmin()): ?>
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'usuarios.php' : 'admin/usuarios.php' ?>">
                                <i class="fas fa-users-cog me-2"></i>Gerenciar Usuários
                            </a></li>
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'tipos_usuario.php' : 'admin/tipos_usuario.php' ?>">
                                <i class="fas fa-user-tag me-2"></i>Tipos de Usuário
                            </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= $isAdmin ? 'logout.php' : 'admin/logout.php' ?>">
                                <i class="fas fa-sign-out-alt me-2"></i>Sair
                            </a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePath ?>login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Loading Overlay -->
    <div id="loading" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background: rgba(0,0,0,0.5); z-index: 9999;">
        <div class="d-flex justify-content-center align-items-center h-100">
            <div class="text-center text-white">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <div class="mt-2">Carregando...</div>
            </div>
        </div>
    </div>

    <!-- Container Principal -->
    <div class="container-fluid py-4">
        <?php 
        $message = getMessage();
        if ($message): 
        ?>
        <div class="alert alert-<?= $message['type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $message['type'] === 'success' ? 'check-circle' : ($message['type'] === 'error' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
            <?= htmlspecialchars($message['text']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?> 