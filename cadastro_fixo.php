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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Fiscais - Sistema IDH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .termos-fixos {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            position: relative;
        }
        .termos-fixos h6 {
            color: #856404;
            margin-bottom: 15px;
        }
        .termos-fixos .conteudo {
            max-height: 200px;
            overflow-y: auto;
            font-size: 0.9em;
            line-height: 1.5;
        }
        .checkbox-termos {
            margin-top: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .btn-enviar {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            padding: 12px 30px;
            font-size: 1.1em;
            font-weight: bold;
        }
        .btn-enviar:hover {
            background: linear-gradient(45deg, #0056b3, #004085);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
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
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="data_nascimento"><i class="fas fa-calendar"></i> Data de Nascimento *</label>
                                            <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" required>
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
                                            <small class="form-text text-muted">Marque se deseja receber contato via WhatsApp</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="observacoes"><i class="fas fa-comment"></i> Observações</label>
                                    <textarea class="form-control" id="observacoes" name="observacoes" rows="3" 
                                              placeholder="Informações adicionais, restrições, etc."></textarea>
                                </div>

                                <!-- TERMOS DE ACEITE - FIXOS E SEMPRE VISÍVEIS -->
                                <div class="termos-fixos">
                                    <h6><i class="fas fa-exclamation-triangle"></i> Termos de Aceite</h6>
                                    <div class="conteudo">
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
                                    <div class="checkbox-termos">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="aceite_termos" name="aceite_termos" required>
                                            <label class="form-check-label" for="aceite_termos">
                                                <strong>Li e aceito os termos acima *</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group text-center">
                                    <button type="submit" class="btn btn-enviar btn-lg">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Formulário carregado - Versão FIXA');
        
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

        // Controle do checkbox WhatsApp
        const usaWhatsappCheckbox = document.getElementById('usa_whatsapp');
        const whatsappField = document.getElementById('whatsapp');
        
        if (usaWhatsappCheckbox && whatsappField) {
            usaWhatsappCheckbox.addEventListener('change', function() {
                whatsappField.disabled = !this.checked;
                if (!this.checked) {
                    whatsappField.value = '';
                }
            });
        }

        // Validação de idade
        const dataNascimentoInput = document.getElementById('data_nascimento');
        if (dataNascimentoInput) {
            dataNascimentoInput.addEventListener('change', function() {
                const dataNascimento = new Date(this.value);
                const hoje = new Date();
                const idade = hoje.getFullYear() - dataNascimento.getFullYear();
                const mes = hoje.getMonth() - dataNascimento.getMonth();
                
                if (mes < 0 || (mes === 0 && hoje.getDate() < dataNascimento.getDate())) {
                    idade--;
                }
                
                if (idade < 18) {
                    alert('Você deve ter pelo menos 18 anos para se cadastrar.');
                    this.value = '';
                }
            });
        }

        // Validação do formulário - VERSÃO SIMPLIFICADA
        const form = document.getElementById('formCadastro');
        if (form) {
            form.addEventListener('submit', function(e) {
                console.log('Formulário sendo enviado...');
                
                // Verificar aceite dos termos PRIMEIRO
                const aceiteTermos = document.getElementById('aceite_termos');
                if (!aceiteTermos || !aceiteTermos.checked) {
                    e.preventDefault();
                    alert('Você deve aceitar os termos para continuar.');
                    aceiteTermos.focus();
                    return false;
                }
                
                // Verificar campos obrigatórios básicos
                const camposObrigatorios = ['nome', 'email', 'ddi', 'celular', 'genero', 'cpf', 'data_nascimento', 'endereco'];
                let camposVazios = [];
                
                camposObrigatorios.forEach(function(campo) {
                    const elemento = document.getElementById(campo);
                    if (elemento && !elemento.value.trim()) {
                        const label = elemento.previousElementSibling?.textContent || campo;
                        camposVazios.push(label);
                    }
                });
                
                if (camposVazios.length > 0) {
                    e.preventDefault();
                    alert('Por favor, preencha todos os campos obrigatórios:\n\n' + camposVazios.join('\n'));
                    return false;
                }
                
                // Validar celular brasileiro se DDI for +55
                const ddi = document.getElementById('ddi');
                const celular = document.getElementById('celular');
                
                if (ddi && celular && ddi.value === '+55') {
                    const celularLimpo = celular.value.replace(/\D/g, '');
                    if (celularLimpo.length < 10 || celularLimpo.length > 11) {
                        e.preventDefault();
                        alert('Por favor, insira um número de celular válido.');
                        celular.focus();
                        return false;
                    }
                }
                
                console.log('Formulário válido, enviando...');
                // Se chegou até aqui, o formulário é válido e será enviado
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
</body>
</html> 