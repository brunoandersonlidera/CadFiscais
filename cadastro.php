<?php
require_once 'config.php';
require_once 'ddi.php';

// Verificar se o cadastro está aberto
$cadastro_aberto = getConfig('cadastro_aberto');
if ($cadastro_aberto != '1') {
    header('Location: index.php?msg=cadastro_fechado');
    exit;
}

// Obter concursos ativos
$concursos = getConcursosAtivos();

// Se não há concursos, redirecionar
if (empty($concursos)) {
    header('Location: index.php?msg=sem_concursos');
    exit;
}

// Se há apenas um concurso, selecionar automaticamente
$concurso_selecionado = isset($_GET['concurso']) ? (int)$_GET['concurso'] : null;
if (!$concurso_selecionado && count($concursos) == 1) {
    $concurso_selecionado = $concursos[0]['id'];
}

// Obter dados do concurso selecionado
$concurso = null;
if ($concurso_selecionado) {
    $concurso = getConcurso($concurso_selecionado);
    if (!$concurso) {
        header('Location: index.php?msg=concurso_inexistente');
        exit;
    }
}

// Obter lista de DDI
$ddi_list = getDDIList();
$ddi_padrao = getConfig('ddi_padrao');

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-user-plus"></i> 
                        Cadastro de Fiscais
                    </h4>
                </div>
                <div class="card-body">
                    <?php if (count($concursos) > 1 && !$concurso_selecionado): ?>
                        <!-- Seleção de concurso -->
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Selecione um Concurso</h5>
                            <p>Escolha o concurso para o qual deseja se cadastrar como fiscal:</p>
                        </div>
                        
                        <div class="row">
                            <?php foreach ($concursos as $c): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title"><?= htmlspecialchars($c['titulo']) ?></h6>
                                            <p class="card-text">
                                                <strong>Órgão:</strong> <?= htmlspecialchars($c['orgao']) ?><br>
                                                <strong>Data:</strong> <?= date('d/m/Y', strtotime($c['data_prova'])) ?><br>
                                                <strong>Horário:</strong> <?= $c['horario_inicio'] ?> às <?= $c['horario_fim'] ?><br>
                                                <strong>Valor:</strong> R$ <?= number_format($c['valor_pagamento'], 2, ',', '.') ?><br>
                                                <strong>Vagas:</strong> <?= $c['vagas_disponiveis'] ?> disponíveis
                                            </p>
                                            <a href="?concurso=<?= $c['id'] ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-arrow-right"></i> Selecionar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <!-- Formulário de cadastro -->
                        <?php if ($concurso): ?>
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> Concurso Selecionado</h5>
                                <p><strong><?= htmlspecialchars($concurso['titulo']) ?></strong></p>
                                <p>
                                    <strong>Data:</strong> <?= date('d/m/Y', strtotime($concurso['data_prova'])) ?> | 
                                    <strong>Horário:</strong> <?= $concurso['horario_inicio'] ?> às <?= $concurso['horario_fim'] ?> | 
                                    <strong>Valor:</strong> R$ <?= number_format($concurso['valor_pagamento'], 2, ',', '.') ?>
                                </p>
                                <a href="cadastro.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Trocar Concurso
                                </a>
                            </div>
                        <?php endif; ?>

                        <form action="processar_cadastro.php" method="POST" id="formCadastro">
                            <input type="hidden" name="concurso_id" value="<?= $concurso_selecionado ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nome"><i class="fas fa-user"></i> Nome Completo *</label>
                                        <input type="text" class="form-control" id="nome" name="nome" required 
                                               placeholder="Digite seu nome completo">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email"><i class="fas fa-envelope"></i> E-mail *</label>
                                        <input type="email" class="form-control" id="email" name="email" required 
                                               placeholder="seu@email.com">
                                        <div class="invalid-feedback" id="email-error"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="ddi"><i class="fas fa-globe"></i> DDI *</label>
                                        <select class="form-control" id="ddi" name="ddi" required>
                                            <?php foreach ($ddi_list as $ddi => $pais): ?>
                                                <option value="<?= $ddi ?>" <?= ($ddi == $ddi_padrao) ? 'selected' : '' ?>>
                                                    <?= $ddi ?> (<?= $pais ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="celular"><i class="fas fa-mobile-alt"></i> Celular *</label>
                                        <input type="tel" class="form-control" id="celular" name="celular" required 
                                               placeholder="(99) 99999-9999" maxlength="15">
                                        <div class="invalid-feedback" id="celular-error"></div>
                                        <small class="form-text text-muted">Formato: (99) 99999-9999</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="genero"><i class="fas fa-venus-mars"></i> Gênero *</label>
                                        <select class="form-control" id="genero" name="genero" required>
                                            <option value="">Selecione</option>
                                            <option value="M">Masculino</option>
                                            <option value="F">Feminino</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="cpf"><i class="fas fa-id-card"></i> CPF *</label>
                                        <input type="text" class="form-control" id="cpf" name="cpf" required 
                                               placeholder="000.000.000-00" maxlength="14">
                                        <div class="invalid-feedback" id="cpf-error"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="data_nascimento"><i class="fas fa-calendar"></i> Data de Nascimento *</label>
                                        <input type="text" class="form-control" id="data_nascimento" name="data_nascimento" required 
                                               placeholder="dd/mm/aaaa" maxlength="10">
                                        <div class="invalid-feedback" id="data_nascimento-error"></div>
                                        <small class="form-text text-muted">Idade mínima: 18 anos</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="endereco"><i class="fas fa-map-marker-alt"></i> Endereço Completo *</label>
                                <textarea class="form-control" id="endereco" name="endereco" rows="2" required 
                                          placeholder="Rua, número, bairro, cidade, estado, CEP"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="melhor_horario"><i class="fas fa-clock"></i> Melhor Horário para Contato</label>
                                        <select class="form-control" id="melhor_horario" name="melhor_horario">
                                            <option value="">Selecione</option>
                                            <option value="manha">Manhã (8h às 12h)</option>
                                            <option value="tarde">Tarde (12h às 18h)</option>
                                            <option value="noite">Noite (18h às 22h)</option>
                                            <option value="qualquer">Qualquer horário</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="whatsapp"><i class="fab fa-whatsapp"></i> WhatsApp</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text">
                                                    <input type="checkbox" id="usa_whatsapp" name="usa_whatsapp" value="1">
                                                </div>
                                            </div>
                                            <input type="tel" class="form-control" id="whatsapp" name="whatsapp" 
                                                   placeholder="(99) 99999-9999" maxlength="15" disabled>
                                        </div>
                                        <div class="invalid-feedback" id="whatsapp-error"></div>
                                        <small class="form-text text-muted">Marque se deseja receber contato via WhatsApp</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="observacoes"><i class="fas fa-comment"></i> Observações</label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="3" 
                                          placeholder="Informações adicionais, restrições, etc."></textarea>
                            </div>

                            <!-- Termos de Aceite - VERSÃO CORRIGIDA (Sempre visível) -->
                            <div class="form-group">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Termos de Aceite</h6>
                                    </div>
                                    <div class="card-body" style="max-height: 300px; overflow-y: auto; font-size: 0.9em;">
                                        <strong>Termos de Aceite para Participação como Fiscal</strong><br><br>
                                        
                                        Ao se cadastrar como fiscal para este concurso, você concorda com os seguintes termos:<br><br>
                                        
                                        <strong>1. Responsabilidades:</strong><br>
                                        • Comparecer no local e horário determinados<br>
                                        • Manter sigilo sobre o conteúdo da prova<br>
                                        • Zelar pela integridade do processo seletivo<br>
                                        • Seguir todas as orientações fornecidas<br><br>
                                        
                                        <strong>2. Condições:</strong><br>
                                        • Ser pontual e assíduo<br>
                                        • Não utilizar dispositivos eletrônicos durante a aplicação<br>
                                        • Manter postura profissional durante todo o processo<br>
                                        • Reportar qualquer irregularidade observada<br><br>
                                        
                                        <strong>3. Pagamento:</strong><br>
                                        • O pagamento será realizado conforme condições estabelecidas<br>
                                        • Documentação necessária deverá ser apresentada<br>
                                        • Prazo para pagamento será comunicado posteriormente<br><br>
                                        
                                        <strong>4. Aceite:</strong><br>
                                        • Ao marcar a caixa abaixo, você confirma que leu e aceita todos os termos<br>
                                        • Seus dados serão utilizados apenas para fins do concurso<br>
                                        • Você será contatado para confirmação de participação
                                    </div>
                                </div>
                                <div class="form-check mt-3">
                                    <input type="checkbox" class="form-check-input" id="aceite_termos" name="aceite_termos" required>
                                    <label class="form-check-label" for="aceite_termos">
                                        <strong>Li e aceito os termos acima *</strong>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-lg btn-block">
                                    <i class="fas fa-paper-plane"></i> Enviar Cadastro
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Formulário carregado');
    
    // Função para validar CPF
    function validateCPF(cpf) {
        cpf = cpf.replace(/\D/g, '');
        
        if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) {
            return false;
        }
        
        let sum = 0;
        for (let i = 0; i < 9; i++) {
            sum += parseInt(cpf.charAt(i)) * (10 - i);
        }
        let remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.charAt(9))) return false;
        
        sum = 0;
        for (let i = 0; i < 10; i++) {
            sum += parseInt(cpf.charAt(i)) * (11 - i);
        }
        remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.charAt(10))) return false;
        
        return true;
    }

    // Função para validar email
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Função para validar celular brasileiro
    function validateBrazilianPhone(phone) {
        const phoneClean = phone.replace(/\D/g, '');
        if (phoneClean.length < 10 || phoneClean.length > 11) {
            return false;
        }
        
        // DDDs válidos no Brasil
        const validDDDs = [11,12,13,14,15,16,17,18,19,21,22,24,27,28,31,32,33,34,35,37,38,41,42,43,44,45,46,47,48,49,51,53,54,55,61,62,63,64,65,66,67,68,69,71,73,74,75,77,79,81,82,83,84,85,86,87,88,89,91,92,93,94,95,96,97,98,99];
        
        const ddd = parseInt(phoneClean.substring(0, 2));
        if (!validDDDs.includes(ddd)) {
            return false;
        }
        
        // Para celular, deve começar com 9
        if (phoneClean.length === 11 && phoneClean.charAt(2) !== '9') {
            return false;
        }
        
        return true;
    }

    // Máscara para CPF
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
            
            // Validar CPF em tempo real
            if (value.length === 14) {
                if (!validateCPF(value)) {
                    e.target.classList.add('is-invalid');
                    document.getElementById('cpf-error').textContent = 'CPF inválido';
                } else {
                    e.target.classList.remove('is-invalid');
                    e.target.classList.add('is-valid');
                    document.getElementById('cpf-error').textContent = '';
                }
            } else {
                e.target.classList.remove('is-invalid', 'is-valid');
                document.getElementById('cpf-error').textContent = '';
            }
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
            
            // Validar celular em tempo real
            if (value.length === 15) {
                const ddi = document.getElementById('ddi').value;
                if (ddi === '+55' && !validateBrazilianPhone(value)) {
                    e.target.classList.add('is-invalid');
                    document.getElementById('celular-error').textContent = 'Número de celular inválido';
                } else {
                    e.target.classList.remove('is-invalid');
                    e.target.classList.add('is-valid');
                    document.getElementById('celular-error').textContent = '';
                }
            } else {
                e.target.classList.remove('is-invalid', 'is-valid');
                document.getElementById('celular-error').textContent = '';
            }
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
            
            // Validar WhatsApp em tempo real
            if (value.length === 15) {
                const ddi = document.getElementById('ddi').value;
                if (ddi === '+55' && !validateBrazilianPhone(value)) {
                    e.target.classList.add('is-invalid');
                    document.getElementById('whatsapp-error').textContent = 'Número de WhatsApp inválido';
                } else {
                    e.target.classList.remove('is-invalid');
                    e.target.classList.add('is-valid');
                    document.getElementById('whatsapp-error').textContent = '';
                }
            } else {
                e.target.classList.remove('is-invalid', 'is-valid');
                document.getElementById('whatsapp-error').textContent = '';
            }
        });
    }

    // Máscara para data de nascimento
    const dataNascimentoInput = document.getElementById('data_nascimento');
    if (dataNascimentoInput) {
        dataNascimentoInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.replace(/(\d{2})(\d)/, '$1/$2');
            }
            if (value.length >= 5) {
                value = value.replace(/(\d{2})(\d{2})(\d)/, '$1/$2/$3');
            }
            e.target.value = value;
            
            // Validar data em tempo real
            if (value.length === 10) {
                const [dia, mes, ano] = value.split('/');
                const data = new Date(ano, mes - 1, dia);
                const hoje = new Date();
                const idade = hoje.getFullYear() - data.getFullYear();
                const mesAtual = hoje.getMonth() - data.getMonth();
                
                if (mesAtual < 0 || (mesAtual === 0 && hoje.getDate() < data.getDate())) {
                    idade--;
                }
                
                if (idade < 18) {
                    e.target.classList.add('is-invalid');
                    document.getElementById('data_nascimento-error').textContent = 'Você deve ter pelo menos 18 anos';
                } else if (data.getDate() != dia || data.getMonth() != mes - 1 || data.getFullYear() != ano) {
                    e.target.classList.add('is-invalid');
                    document.getElementById('data_nascimento-error').textContent = 'Data inválida';
                } else {
                    e.target.classList.remove('is-invalid');
                    e.target.classList.add('is-valid');
                    document.getElementById('data_nascimento-error').textContent = '';
                }
            } else {
                e.target.classList.remove('is-invalid', 'is-valid');
                document.getElementById('data_nascimento-error').textContent = '';
            }
        });
    }

    // Validar email em tempo real
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('blur', function(e) {
            if (e.target.value && !validateEmail(e.target.value)) {
                e.target.classList.add('is-invalid');
                document.getElementById('email-error').textContent = 'E-mail inválido';
            } else {
                e.target.classList.remove('is-invalid');
                e.target.classList.add('is-valid');
                document.getElementById('email-error').textContent = '';
            }
        });
    }

    // Controle do checkbox WhatsApp
    const usaWhatsappCheckbox = document.getElementById('usa_whatsapp');
    const whatsappField = document.getElementById('whatsapp');
    
    if (usaWhatsappCheckbox && whatsappField) {
        usaWhatsappCheckbox.addEventListener('change', function() {
            whatsappField.disabled = !this.checked;
            if (!this.checked) {
                whatsappField.value = '';
                whatsappField.classList.remove('is-invalid', 'is-valid');
            }
        });
    }

    // Preencher WhatsApp automaticamente se for igual ao celular
    const celularField = document.getElementById('celular');
    if (celularField && whatsappField) {
        celularField.addEventListener('blur', function() {
            if (usaWhatsappCheckbox.checked && !whatsappField.value) {
                whatsappField.value = this.value;
                // Disparar evento de input para aplicar máscara
                whatsappField.dispatchEvent(new Event('input'));
            }
        });
    }

    // Verificar CPF duplicado via AJAX
    const cpfField = document.getElementById('cpf');
    const concursoId = document.querySelector('input[name="concurso_id"]').value;
    
    if (cpfField) {
        cpfField.addEventListener('blur', function() {
            const cpf = this.value.replace(/\D/g, '');
            if (cpf.length === 11 && validateCPF(this.value)) {
                // Verificar se CPF já existe
                fetch('verificar_cpf.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `cpf=${cpf}&concurso_id=${concursoId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        this.classList.add('is-invalid');
                        document.getElementById('cpf-error').textContent = 'CPF já cadastrado neste concurso';
                    }
                })
                .catch(error => {
                    console.error('Erro ao verificar CPF:', error);
                });
            }
        });
    }

    // Validação do formulário
    const form = document.getElementById('formCadastro');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('Formulário sendo enviado...');
            
            // Verificar se todos os campos obrigatórios estão preenchidos
            const camposObrigatorios = [
                'nome', 'email', 'ddi', 'celular', 'genero', 
                'cpf', 'data_nascimento', 'endereco', 'aceite_termos'
            ];
            
            let camposVazios = [];
            let camposInvalidos = [];
            
            camposObrigatorios.forEach(function(campo) {
                const elemento = document.getElementById(campo);
                if (elemento) {
                    if (campo === 'aceite_termos') {
                        if (!elemento.checked) {
                            camposVazios.push('Aceite dos termos');
                        }
                    } else if (!elemento.value.trim()) {
                        camposVazios.push(elemento.previousElementSibling?.textContent || campo);
                    } else if (elemento.classList.contains('is-invalid')) {
                        camposInvalidos.push(elemento.previousElementSibling?.textContent || campo);
                    }
                }
            });
            
            if (camposVazios.length > 0) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios:\n\n' + camposVazios.join('\n'));
                return false;
            }
            
            if (camposInvalidos.length > 0) {
                e.preventDefault();
                alert('Por favor, corrija os seguintes campos:\n\n' + camposInvalidos.join('\n'));
                return false;
            }
            
            // Validar gênero
            const genero = document.getElementById('genero');
            if (genero && !genero.value) {
                e.preventDefault();
                alert('Por favor, selecione seu gênero.');
                return false;
            }
            
            // Validar celular brasileiro se DDI for +55
            const ddi = document.getElementById('ddi');
            const celular = document.getElementById('celular');
            
            if (ddi && celular && ddi.value === '+55') {
                if (!validateBrazilianPhone(celular.value)) {
                    e.preventDefault();
                    alert('Por favor, insira um número de celular válido.');
                    return false;
                }
            }
            
            // Validar aceite dos termos
            const aceiteTermos = document.getElementById('aceite_termos');
            if (aceiteTermos && !aceiteTermos.checked) {
                e.preventDefault();
                alert('Você deve aceitar os termos para continuar.');
                return false;
            }
            
            console.log('Formulário válido, enviando...');
        });
    }
    
    // Debug: verificar se os elementos existem
    console.log('Elementos do formulário:');
    console.log('Form:', document.getElementById('formCadastro'));
    console.log('Aceite termos:', document.getElementById('aceite_termos'));
    console.log('Nome:', document.getElementById('nome'));
    console.log('Email:', document.getElementById('email'));
});
</script>

<?php include 'includes/footer.php'; ?> 