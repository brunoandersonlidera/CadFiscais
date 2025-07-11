<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$pageTitle = 'Guia de Acesso - Listas e Relatórios';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-map me-2"></i>
                Guia de Acesso - Listas e Relatórios
            </h1>
        </div>
    </div>
</div>

<!-- Menu Principal -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Menu Principal - Acesso Rápido
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-users me-2"></i>
                            Gestão de Fiscais
                        </h6>
                        <div class="list-group mb-4">
                            <a href="fiscais.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-list me-2"></i>
                                <strong>Lista de Fiscais</strong>
                                <small class="text-muted d-block">Visualizar e editar todos os fiscais cadastrados</small>
                            </a>
                            <a href="editar_fiscal.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-edit me-2"></i>
                                <strong>Editar Fiscais</strong>
                                <small class="text-muted d-block">Editar dados de fiscais específicos</small>
                            </a>
                            <a href="relatorio_fiscais.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-chart-bar me-2"></i>
                                <strong>Relatório Geral de Fiscais</strong>
                                <small class="text-muted d-block">Relatório completo com filtros e exportação</small>
                            </a>
                            <a href="relatorio_fiscais_aprovados.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Relatório de Fiscais Aprovados</strong>
                                <small class="text-muted d-block">Apenas fiscais aprovados com resumo por escola</small>
                            </a>
                            <a href="relatorio_fiscais_horario.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-clock me-2"></i>
                                <strong>Relatório por Horário</strong>
                                <small class="text-muted d-block">Fiscais agrupados por horário preferencial</small>
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-clipboard-check me-2"></i>
                            Controle de Presença
                        </h6>
                        <div class="list-group mb-4">
                            <a href="lista_presenca.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-list me-2"></i>
                                <strong>Lista de Presença - Prova</strong>
                                <small class="text-muted d-block">Lista para o dia da prova com filtros</small>
                            </a>
                            <a href="lista_presenca_treinamento.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-graduation-cap me-2"></i>
                                <strong>Lista de Presença - Treinamento</strong>
                                <small class="text-muted d-block">Lista para treinamentos com agrupamento por escola</small>
                            </a>
                            <a href="relatorio_comparecimento.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-clipboard-check me-2"></i>
                                <strong>Relatório de Comparecimento</strong>
                                <small class="text-muted d-block">Controle de presença/ausência com estatísticas</small>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Alocações e Locais
                        </h6>
                        <div class="list-group mb-4">
                            <a href="alocacoes.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                <strong>Alocações de Fiscais</strong>
                                <small class="text-muted d-block">Alocar fiscais em escolas e salas</small>
                            </a>
                            <a href="relatorio_alocacoes.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-chart-bar me-2"></i>
                                <strong>Relatório de Alocações</strong>
                                <small class="text-muted d-block">Relatório completo de alocações com filtros</small>
                            </a>
                            <a href="escolas.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-school me-2"></i>
                                <strong>Gerenciar Escolas</strong>
                                <small class="text-muted d-block">Cadastrar e gerenciar escolas</small>
                            </a>
                            <a href="salas.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-door-open me-2"></i>
                                <strong>Gerenciar Salas</strong>
                                <small class="text-muted d-block">Cadastrar e gerenciar salas das escolas</small>
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-money-bill-wave me-2"></i>
                            Controle Financeiro
                        </h6>
                        <div class="list-group mb-4">
                            <a href="lista_pagamentos.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-list me-2"></i>
                                <strong>Lista de Pagamentos</strong>
                                <small class="text-muted d-block">Visualizar e gerenciar pagamentos</small>
                            </a>
                            <a href="planilha_pagamentos.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-table me-2"></i>
                                <strong>Planilha de Pagamentos</strong>
                                <small class="text-muted d-block">Planilha completa com filtros e exportação</small>
                            </a>
                            <a href="recibo_pagamento.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-receipt me-2"></i>
                                <strong>Recibo de Pagamento</strong>
                                <small class="text-muted d-block">Gerar recibos para impressão</small>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-file-alt me-2"></i>
                            Documentos e Atas
                        </h6>
                        <div class="list-group mb-4">
                            <a href="ata_treinamento.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-file-alt me-2"></i>
                                <strong>Ata de Treinamento</strong>
                                <small class="text-muted d-block">Gerar ata formal de treinamento</small>
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-cog me-2"></i>
                            Configurações
                        </h6>
                        <div class="list-group mb-4">
                            <a href="usuarios.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-users-cog me-2"></i>
                                <strong>Gerenciar Usuários</strong>
                                <small class="text-muted d-block">Cadastrar e gerenciar usuários do sistema</small>
                            </a>
                            <a href="concursos.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-trophy me-2"></i>
                                <strong>Gerenciar Concursos</strong>
                                <small class="text-muted d-block">Configurar concursos e datas</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- URLs Diretas -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-link me-2"></i>
                    URLs Diretas para Acesso Rápido
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6 class="text-primary">Fiscais</h6>
                        <ul class="list-unstyled">
                            <li><code>admin/fiscais.php</code> - Lista de fiscais</li>
                            <li><code>admin/relatorio_fiscais.php</code> - Relatório geral</li>
                            <li><code>admin/relatorio_fiscais_aprovados.php</code> - Fiscais aprovados</li>
                            <li><code>admin/relatorio_fiscais_horario.php</code> - Por horário</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-primary">Presença</h6>
                        <ul class="list-unstyled">
                            <li><code>admin/lista_presenca.php</code> - Lista para prova</li>
                            <li><code>admin/lista_presenca_treinamento.php</code> - Lista para treinamento</li>
                            <li><code>admin/relatorio_comparecimento.php</code> - Relatório de comparecimento</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-primary">Alocações</h6>
                        <ul class="list-unstyled">
                            <li><code>admin/alocacoes.php</code> - Alocar fiscais</li>
                            <li><code>admin/relatorio_alocacoes.php</code> - Relatório de alocações</li>
                            <li><code>admin/escolas.php</code> - Gerenciar escolas</li>
                            <li><code>admin/salas.php</code> - Gerenciar salas</li>
                        </ul>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-4">
                        <h6 class="text-primary">Pagamentos</h6>
                        <ul class="list-unstyled">
                            <li><code>admin/lista_pagamentos.php</code> - Lista de pagamentos</li>
                            <li><code>admin/planilha_pagamentos.php</code> - Planilha completa</li>
                            <li><code>admin/recibo_pagamento.php</code> - Recibos</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-primary">Documentos</h6>
                        <ul class="list-unstyled">
                            <li><code>admin/ata_treinamento.php</code> - Ata de treinamento</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-primary">Configurações</h6>
                        <ul class="list-unstyled">
                            <li><code>admin/usuarios.php</code> - Gerenciar usuários</li>
                            <li><code>admin/concursos.php</code> - Gerenciar concursos</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fluxo de Trabalho -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-route me-2"></i>
                    Fluxo de Trabalho Recomendado
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-success">1. Configuração Inicial</h6>
                        <ol>
                            <li>Cadastrar concursos em <code>admin/concursos.php</code></li>
                            <li>Cadastrar escolas em <code>admin/escolas.php</code></li>
                            <li>Cadastrar salas em <code>admin/salas.php</code></li>
                            <li>Cadastrar usuários em <code>admin/usuarios.php</code></li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success">2. Gestão de Fiscais</h6>
                        <ol>
                            <li>Visualizar fiscais em <code>admin/fiscais.php</code></li>
                            <li>Editar fiscais em <code>admin/editar_fiscal.php</code></li>
                            <li>Gerar relatórios em <code>admin/relatorio_fiscais.php</code></li>
                        </ol>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6 class="text-success">3. Alocações</h6>
                        <ol>
                            <li>Alocar fiscais em <code>admin/alocacoes.php</code></li>
                            <li>Visualizar alocações em <code>admin/relatorio_alocacoes.php</code></li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success">4. Controle de Presença</h6>
                        <ol>
                            <li>Lista de presença em <code>admin/lista_presenca.php</code></li>
                            <li>Relatório de comparecimento em <code>admin/relatorio_comparecimento.php</code></li>
                        </ol>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6 class="text-success">5. Controle Financeiro</h6>
                        <ol>
                            <li>Lista de pagamentos em <code>admin/lista_pagamentos.php</code></li>
                            <li>Planilha completa em <code>admin/planilha_pagamentos.php</code></li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success">6. Documentação</h6>
                        <ol>
                            <li>Ata de treinamento em <code>admin/ata_treinamento.php</code></li>
                            <li>Recibos em <code>admin/recibo_pagamento.php</code></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dicas de Uso -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">
                    <i class="fas fa-lightbulb me-2"></i>
                    Dicas de Uso
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-warning">Funcionalidades Principais</h6>
                        <ul>
                            <li><strong>Filtros:</strong> Use os filtros para encontrar dados específicos</li>
                            <li><strong>Exportação:</strong> Todos os relatórios podem ser exportados em PDF/Excel</li>
                            <li><strong>Impressão:</strong> Use Ctrl+P para imprimir listas</li>
                            <li><strong>Busca:</strong> Use a busca das tabelas para encontrar registros</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-warning">Atalhos Úteis</h6>
                        <ul>
                            <li><strong>Fiscais:</strong> Acesse diretamente via menu lateral</li>
                            <li><strong>Relatórios:</strong> Use os filtros de data e concurso</li>
                            <li><strong>Alocações:</strong> Selecione escola e sala antes de alocar</li>
                            <li><strong>Pagamentos:</strong> Marque como pago após confirmação</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 