<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$fiscal_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDB();

if (!$fiscal_id) {
    showMessage('ID do fiscal não fornecido', 'error');
    redirect('fiscais.php');
}

// Buscar dados do fiscal
try {
    $stmt = $db->prepare("
        SELECT f.*, c.titulo as concurso_titulo
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        WHERE f.id = ?
    ");
    $stmt->execute([$fiscal_id]);
    $fiscal = $stmt->fetch();
    
    if (!$fiscal) {
        showMessage('Fiscal não encontrado', 'error');
        redirect('fiscais.php');
    }
} catch (Exception $e) {
    logActivity('Erro ao buscar fiscal: ' . $e->getMessage(), 'ERROR');
    showMessage('Erro ao buscar fiscal', 'error');
    redirect('fiscais.php');
}

// Buscar concursos ativos
$concursos = [];
try {
    $stmt = $db->query("SELECT id, titulo FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC");
    $concursos = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar concursos: ' . $e->getMessage(), 'ERROR');
}

$pageTitle = 'Editar Fiscal';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-edit me-2"></i>
                Editar Fiscal
            </h1>
            <a href="fiscais.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Voltar
            </a>
        </div>
    </div>
</div>

<!-- Formulário de Edição -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>
                    Dados do Fiscal
                </h5>
            </div>
            <div class="card-body">
                <form action="salvar_edicao_fiscal.php" method="POST" id="formEditarFiscal">
                    <input type="hidden" name="fiscal_id" value="<?= $fiscal_id ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       value="<?= htmlspecialchars($fiscal['nome']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($fiscal['email']) ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="ddi" class="form-label">DDI *</label>
                                <select class="form-select" id="ddi" name="ddi" required>
                                    <option value="+55" <?= $fiscal['ddi'] == '+55' ? 'selected' : '' ?>>+55 (Brasil)</option>
                                    <option value="+1" <?= $fiscal['ddi'] == '+1' ? 'selected' : '' ?>>+1 (Estados Unidos/Canadá)</option>
                                    <option value="+33" <?= $fiscal['ddi'] == '+33' ? 'selected' : '' ?>>+33 (França)</option>
                                    <option value="+44" <?= $fiscal['ddi'] == '+44' ? 'selected' : '' ?>>+44 (Reino Unido)</option>
                                    <option value="+49" <?= $fiscal['ddi'] == '+49' ? 'selected' : '' ?>>+49 (Alemanha)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="celular" class="form-label">Celular *</label>
                                <input type="tel" class="form-control" id="celular" name="celular" 
                                       value="<?= htmlspecialchars($fiscal['celular']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="genero" class="form-label">Gênero *</label>
                                <select class="form-select" id="genero" name="genero" required>
                                    <option value="">Selecione</option>
                                    <option value="M" <?= $fiscal['genero'] == 'M' ? 'selected' : '' ?>>Masculino</option>
                                    <option value="F" <?= $fiscal['genero'] == 'F' ? 'selected' : '' ?>>Feminino</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cpf" class="form-label">CPF *</label>
                                <input type="text" class="form-control" id="cpf" name="cpf" 
                                       value="<?= htmlspecialchars($fiscal['cpf']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="data_nascimento" class="form-label">Data de Nascimento *</label>
                                <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" 
                                       value="<?= $fiscal['data_nascimento'] ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="endereco" class="form-label">Endereço Completo *</label>
                        <textarea class="form-control" id="endereco" name="endereco" rows="2" required><?= htmlspecialchars($fiscal['endereco']) ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="melhor_horario" class="form-label">Melhor Horário para Contato</label>
                                <select class="form-select" id="melhor_horario" name="melhor_horario">
                                    <option value="">Selecione</option>
                                    <option value="manha" <?= $fiscal['melhor_horario'] == 'manha' ? 'selected' : '' ?>>Manhã (8h às 12h)</option>
                                    <option value="tarde" <?= $fiscal['melhor_horario'] == 'tarde' ? 'selected' : '' ?>>Tarde (12h às 18h)</option>
                                    <option value="noite" <?= $fiscal['melhor_horario'] == 'noite' ? 'selected' : '' ?>>Noite (18h às 22h)</option>
                                    <option value="qualquer" <?= $fiscal['melhor_horario'] == 'qualquer' ? 'selected' : '' ?>>Qualquer horário</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="whatsapp" class="form-label">WhatsApp</label>
                                <input type="tel" class="form-control" id="whatsapp" name="whatsapp" 
                                       value="<?= htmlspecialchars($fiscal['whatsapp'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?= htmlspecialchars($fiscal['observacoes'] ?? '') ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="concurso_id" class="form-label">Concurso</label>
                                <select class="form-select" id="concurso_id" name="concurso_id" required>
                                    <option value="">Selecione um concurso</option>
                                    <?php foreach ($concursos as $concurso): ?>
                                    <option value="<?= $concurso['id'] ?>" <?= $fiscal['concurso_id'] == $concurso['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($concurso['titulo']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="pendente" <?= $fiscal['status'] == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                                    <option value="aprovado" <?= $fiscal['status'] == 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                                    <option value="reprovado" <?= $fiscal['status'] == 'reprovado' ? 'selected' : '' ?>>Reprovado</option>
                                    <option value="cancelado" <?= $fiscal['status'] == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status_contato" class="form-label">Status do Contato</label>
                                <select class="form-select" id="status_contato" name="status_contato">
                                    <option value="nao_contatado" <?= $fiscal['status_contato'] == 'nao_contatado' ? 'selected' : '' ?>>Não Contatado</option>
                                    <option value="contatado" <?= $fiscal['status_contato'] == 'contatado' ? 'selected' : '' ?>>Contatado</option>
                                    <option value="confirmado" <?= $fiscal['status_contato'] == 'confirmado' ? 'selected' : '' ?>>Confirmado</option>
                                    <option value="desistiu" <?= $fiscal['status_contato'] == 'desistiu' ? 'selected' : '' ?>>Desistiu</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="aceite_termos" class="form-label">Aceite dos Termos</label>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="aceite_termos" name="aceite_termos" value="1" 
                                           <?= $fiscal['aceite_termos'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="aceite_termos">
                                        Fiscal aceitou os termos
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" class="btn btn-secondary" onclick="history.back()">
                            <i class="fas fa-times me-2"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Informações do Cadastro -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Informações do Cadastro
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Data de Cadastro:</strong> <?= date('d/m/Y H:i', strtotime($fiscal['created_at'])) ?></p>
                        <p><strong>Data de Aceite dos Termos:</strong> 
                            <?= $fiscal['data_aceite_termos'] ? date('d/m/Y H:i', strtotime($fiscal['data_aceite_termos'])) : 'Não aceitou' ?>
                        </p>
                        <p><strong>IP do Cadastro:</strong> <?= htmlspecialchars($fiscal['ip_cadastro'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Concurso:</strong> <?= htmlspecialchars($fiscal['concurso_titulo']) ?></p>
                        <p><strong>Idade:</strong> 
                            <?php 
                            $idade = date_diff(date_create($fiscal['data_nascimento']), date_create('today'))->y;
                            echo $idade . ' anos';
                            ?>
                        </p>
                        <p><strong>Última Atualização:</strong> <?= date('d/m/Y H:i', strtotime($fiscal['updated_at'])) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Máscara para CPF
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        });
    }

    // Máscara para celular
    const celularInput = document.getElementById('celular');
    if (celularInput) {
        celularInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 10) {
                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            } else {
                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
            }
            e.target.value = value;
        });
    }

    // Máscara para WhatsApp
    const whatsappInput = document.getElementById('whatsapp');
    if (whatsappInput) {
        whatsappInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 10) {
                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            } else {
                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
            }
            e.target.value = value;
        });
    }

    // Validação do formulário
    const form = document.getElementById('formEditarFiscal');
    if (form) {
        form.addEventListener('submit', function(e) {
            const camposObrigatorios = ['nome', 'email', 'ddi', 'celular', 'genero', 'cpf', 'data_nascimento', 'endereco', 'concurso_id', 'status'];
            
            let camposVazios = [];
            
            camposObrigatorios.forEach(function(campo) {
                const elemento = document.getElementById(campo);
                if (elemento && !elemento.value.trim()) {
                    camposVazios.push(elemento.previousElementSibling?.textContent || campo);
                }
            });
            
            if (camposVazios.length > 0) {
                e.preventDefault();
                showMessage('Por favor, preencha todos os campos obrigatórios:\n\n' + camposVazios.join('\n'), 'error');
                return false;
            }
            
            showLoading();
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?> 