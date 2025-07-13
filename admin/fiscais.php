<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();
$fiscais = [];

try {
    $stmt = $db->query("
        SELECT f.id, f.nome, f.email, f.celular, f.whatsapp, f.cpf, f.data_nascimento, 
               f.status, f.created_at, f.observacoes, f.concurso_id,
               c.titulo as concurso_titulo,
               TIMESTAMPDIFF(YEAR, f.data_nascimento, CURDATE()) as idade
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        ORDER BY f.created_at DESC
    ");
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar fiscais: ' . $e->getMessage(), 'ERROR');
}

$pageTitle = 'Gerenciar Fiscais';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-users me-2"></i>
                Gerenciar Fiscais
            </h1>
            <div>
                <button onclick="exportData('csv')" class="btn btn-success">
                    <i class="fas fa-download me-2"></i>
                    Exportar CSV
                </button>
                <button onclick="exportData('excel')" class="btn btn-info">
                    <i class="fas fa-file-excel me-2"></i>
                    Exportar Excel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>
                    Filtros
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label for="statusFilter" class="form-label">Status</label>
                        <select class="form-select" id="statusFilter">
                            <option value="">Todos</option>
                            <option value="pendente">Pendente</option>
                            <option value="aprovado">Aprovado</option>
                            <option value="reprovado">Reprovado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="concursoFilter" class="form-label">Concurso</label>
                        <select class="form-select" id="concursoFilter">
                            <option value="">Todos</option>
                            <?php
                            $concursos = $db->query("SELECT id, titulo FROM concursos WHERE status = 'ativo'")->fetchAll();
                            foreach ($concursos as $concurso) {
                                echo "<option value='{$concurso['id']}'>{$concurso['titulo']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="searchFilter" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="searchFilter" placeholder="Nome, email ou CPF">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button onclick="limparFiltros()" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>
                                Limpar Filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Fiscais -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Lista de Fiscais (<?= count($fiscais) ?>)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="fiscaisTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Celular</th>
                                <th>CPF</th>
                                <th>Idade</th>
                                <th>Concurso</th>
                                <th>Status</th>
                                <th>Data Cadastro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fiscais as $fiscal): ?>
                            <tr>
                                <td><?= $fiscal['id'] ?></td>
                                <td><?= htmlspecialchars($fiscal['nome']) ?></td>
                                <td><?= htmlspecialchars($fiscal['email']) ?></td>
                                <td><?= formatPhone($fiscal['celular']) ?></td>
                                <td><?= formatCPF($fiscal['cpf']) ?></td>
                                <td><?= $fiscal['idade'] ?> anos</td>
                                <td><?= htmlspecialchars($fiscal['concurso_titulo'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge bg-<?= getStatusColor($fiscal['status']) ?>">
                                        <?= ucfirst($fiscal['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($fiscal['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="verDetalhes(<?= $fiscal['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-warning" 
                                                onclick="editarFiscal(<?= $fiscal['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-success" 
                                                onclick="alocarFiscal(<?= $fiscal['id'] ?>)">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-info" 
                                                onclick="abrirWhatsApp('<?= $fiscal['whatsapp'] ?? $fiscal['celular'] ?>', '<?= htmlspecialchars($fiscal['nome']) ?>')"
                                                title="Abrir WhatsApp">
                                            <i class="fab fa-whatsapp"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deleteFiscal(<?= $fiscal['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalhes -->
<div class="modal fade" id="detalhesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user me-2"></i>
                    Detalhes do Fiscal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalhesContent">
                <!-- Conteúdo será carregado via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" onclick="editarFiscal()">Editar</button>
                <button type="button" class="btn btn-success" onclick="alocarFiscal()">Alocar</button>
            </div>
        </div>
    </div>
</div>

<script>
let table;

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    table = $('#fiscaisTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[0, 'desc']],
        columnDefs: [
            { targets: [9], orderable: false, searchable: false }
        ]
    });
    
    // Filtros
    $('#statusFilter').on('change', function() {
        table.column(7).search($(this).val()).draw();
    });
    
    $('#concursoFilter').on('change', function() {
        table.column(6).search($(this).val()).draw();
    });
    
    $('#searchFilter').on('keyup', function() {
        table.search($(this).val()).draw();
    });
});

function limparFiltros() {
    $('#statusFilter').val('');
    $('#concursoFilter').val('');
    $('#searchFilter').val('');
    table.search('').columns().search('').draw();
}

function verDetalhes(id) {
    showLoading();
    
    fetch(`get_fiscal.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.error) {
                showMessage(data.error, 'error');
                return;
            }
            
            // Gerar HTML com os dados
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h5>Dados Pessoais</h5>
                        <p><strong>Nome:</strong> ${data.fiscal.nome}</p>
                        <p><strong>Email:</strong> ${data.fiscal.email}</p>
                        <p><strong>CPF:</strong> ${formatCPF(data.fiscal.cpf)}</p>
                        <p><strong>Celular:</strong> ${formatPhone(data.fiscal.celular)}</p>
                        <p><strong>WhatsApp:</strong> ${data.fiscal.whatsapp ? formatPhone(data.fiscal.whatsapp) : 'Não informado'}</p>
                        <p><strong>Data de Nascimento:</strong> ${new Date(data.fiscal.data_nascimento).toLocaleDateString('pt-BR')}</p>
                        <p><strong>Gênero:</strong> ${data.fiscal.genero === 'M' ? 'Masculino' : 'Feminino'}</p>
                    </div>
                    <div class="col-md-6">
                        <h5>Informações do Concurso</h5>
                        <p><strong>Concurso:</strong> ${data.fiscal.concurso_nome || 'Não informado'}</p>
                        <p><strong>Status:</strong> <span class="badge bg-${getStatusColor(data.fiscal.status)}">${data.fiscal.status}</span></p>
                        <p><strong>Status do Contato:</strong> ${data.fiscal.status_contato || 'Não informado'}</p>
                        <p><strong>Melhor Horário:</strong> ${data.fiscal.melhor_horario || 'Não informado'}</p>
                        <p><strong>Aceite dos Termos:</strong> ${data.fiscal.aceite_termos ? 'Sim' : 'Não'}</p>
                        <p><strong>Data de Cadastro:</strong> ${new Date(data.fiscal.created_at).toLocaleDateString('pt-BR')}</p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <h5>Endereço</h5>
                        <p>${data.fiscal.endereco || 'Não informado'}</p>
                    </div>
                </div>
                
                ${data.fiscal.observacoes ? `
                <div class="row mt-3">
                    <div class="col-12">
                        <h5>Observações</h5>
                        <p>${data.fiscal.observacoes}</p>
                    </div>
                </div>
                ` : ''}
                
                ${data.alocacoes.length > 0 ? `
                <div class="row mt-3">
                    <div class="col-12">
                        <h5>Alocações</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Escola</th>
                                        <th>Sala</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.alocacoes.map(alocacao => `
                                        <tr>
                                            <td>${alocacao.escola_nome || 'N/A'}</td>
                                            <td>${alocacao.sala_nome || 'N/A'}</td>
                                            <td>${new Date(alocacao.data_alocacao).toLocaleDateString('pt-BR')}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                ` : ''}
                
                ${data.presencas.length > 0 ? `
                <div class="row mt-3">
                    <div class="col-12">
                        <h5>Presença</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Concurso</th>
                                        <th>Data</th>
                                        <th>Presente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.presencas.map(presenca => `
                                        <tr>
                                            <td>${presenca.concurso_nome || 'N/A'}</td>
                                            <td>${new Date(presenca.data).toLocaleDateString('pt-BR')}</td>
                                            <td><span class="badge bg-${presenca.presente ? 'success' : 'danger'}">${presenca.presente ? 'Sim' : 'Não'}</span></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                ` : ''}
                
                ${data.pagamentos.length > 0 ? `
                <div class="row mt-3">
                    <div class="col-12">
                        <h5>Pagamentos</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Concurso</th>
                                        <th>Data</th>
                                        <th>Valor</th>
                                        <th>Pago</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.pagamentos.map(pagamento => `
                                        <tr>
                                            <td>${pagamento.concurso_nome || 'N/A'}</td>
                                            <td>${new Date(pagamento.data_pagamento).toLocaleDateString('pt-BR')}</td>
                                            <td>R$ ${parseFloat(pagamento.valor).toFixed(2).replace('.', ',')}</td>
                                            <td><span class="badge bg-${pagamento.pago ? 'success' : 'warning'}">${pagamento.pago ? 'Sim' : 'Não'}</span></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                ` : ''}
            `;
            
            document.getElementById('detalhesContent').innerHTML = html;
            document.getElementById('detalhesModal').setAttribute('data-fiscal-id', id);
            new bootstrap.Modal(document.getElementById('detalhesModal')).show();
        })
        .catch(error => {
            hideLoading();
            console.error('Erro:', error);
            showMessage('Erro ao carregar detalhes', 'error');
        });
}

function editarFiscal(id) {
    if (id) {
        window.location.href = `editar_fiscal.php?id=${id}`;
    } else {
        // Pegar ID do modal
        const modal = document.getElementById('detalhesModal');
        const id = modal.getAttribute('data-fiscal-id');
        if (id) {
            window.location.href = `editar_fiscal.php?id=${id}`;
        }
    }
}

function alocarFiscal(id) {
    if (id) {
        window.location.href = `alocar_fiscal.php?id=${id}`;
    } else {
        // Pegar ID do modal
        const modal = document.getElementById('detalhesModal');
        const id = modal.getAttribute('data-fiscal-id');
        if (id) {
            window.location.href = `alocar_fiscal.php?id=${id}`;
        }
    }
}

function changeStatus(id, status) {
    confirmAction(`Tem certeza que deseja alterar o status para "${status}"?`, function() {
        showLoading();
        
        fetch('change_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id, status: status })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showMessage('Status alterado com sucesso!');
                setTimeout(() => location.reload(), 1500);
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showMessage('Erro ao alterar status', 'error');
        });
    });
}

function exportData(format) {
    // Versão simplificada - download direto
    const url = `export_direto.php?format=${format}`;
    console.log('Exportando:', url);
    window.open(url, '_blank');
}

// Funções auxiliares
function formatPhone(phone) {
    phone = phone.replace(/\D/g, '');
    if (phone.length === 11) {
        return phone.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    }
    return phone;
}

function formatCPF(cpf) {
    cpf = cpf.replace(/\D/g, '');
    return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

function abrirWhatsApp(telefone, nome) {
    // Limpar o telefone (remover caracteres especiais)
    let numero = telefone.replace(/\D/g, '');
    
    // Adicionar código do país se não tiver
    if (numero.length === 11 && numero.startsWith('0')) {
        numero = '55' + numero.substring(1);
    } else if (numero.length === 11) {
        numero = '55' + numero;
    } else if (numero.length === 10) {
        numero = '5511' + numero;
    }
    
    // Criar mensagem padrão
    const mensagem = `Olá ${nome}! Sou do Instituto Dignidade Humana (IDH) e gostaria de falar sobre o concurso.`;
    
    // URL do WhatsApp
    const url = `https://wa.me/${numero}?text=${encodeURIComponent(mensagem)}`;
    
    // Abrir em nova aba
    window.open(url, '_blank');
}

function getStatusColor(status) {
    switch (status) {
        case 'aprovado': return 'success';
        case 'pendente': return 'warning';
        case 'reprovado': return 'danger';
        case 'cancelado': return 'secondary';
        default: return 'secondary';
    }
}
</script>

<?php 
// Funções auxiliares
function getStatusColor($status) {
    switch ($status) {
        case 'aprovado': return 'success';
        case 'pendente': return 'warning';
        case 'reprovado': return 'danger';
        case 'cancelado': return 'secondary';
        default: return 'secondary';
    }
}

include '../includes/footer.php'; 
?> 