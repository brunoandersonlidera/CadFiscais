<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$pageTitle = 'Teste de Formulário';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1>🧪 Teste de Formulário</h1>
        <p>Este teste verifica se o problema está no JavaScript ou no PHP.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Teste 1: Formulário Simples (Sem JavaScript)</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="salvar_alocacao_debug.php">
                    <input type="hidden" name="fiscal_id" value="6">
                    <input type="hidden" name="escola_id" value="1">
                    <input type="hidden" name="sala_id" value="1">
                    <input type="hidden" name="tipo_alocacao" value="sala">
                    <input type="hidden" name="observacoes_alocacao" value="Teste simples">
                    <input type="hidden" name="data_alocacao" value="<?= date('Y-m-d') ?>">
                    <input type="hidden" name="horario_alocacao" value="08:00">
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Testar Formulário Simples
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Teste 2: Formulário com JavaScript</h5>
            </div>
            <div class="card-body">
                <form id="testeForm" method="POST" action="salvar_alocacao_debug.php">
                    <input type="hidden" name="fiscal_id" value="6">
                    <input type="hidden" name="escola_id" value="1">
                    <input type="hidden" name="sala_id" value="1">
                    <input type="hidden" name="tipo_alocacao" value="sala">
                    <input type="hidden" name="observacoes_alocacao" value="Teste com JavaScript">
                    <input type="hidden" name="data_alocacao" value="<?= date('Y-m-d') ?>">
                    <input type="hidden" name="horario_alocacao" value="08:00">
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>
                        Testar com JavaScript
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Teste 3: Console JavaScript</h5>
            </div>
            <div class="card-body">
                <button type="button" class="btn btn-info" onclick="testarJavaScript()">
                    <i class="fas fa-code me-2"></i>
                    Testar JavaScript no Console
                </button>
                
                <div id="resultado" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<script>
// Teste de JavaScript
function testarJavaScript() {
    console.log('=== Teste de JavaScript ===');
    
    // Verificar se jQuery está carregado
    if (typeof $ !== 'undefined') {
        console.log('✅ jQuery está carregado');
    } else {
        console.log('❌ jQuery não está carregado');
    }
    
    // Verificar se as funções existem
    if (typeof showMessage === 'function') {
        console.log('✅ showMessage existe');
        showMessage('Teste de mensagem', 'success');
    } else {
        console.log('❌ showMessage não existe');
    }
    
    if (typeof showLoading === 'function') {
        console.log('✅ showLoading existe');
    } else {
        console.log('❌ showLoading não existe');
    }
    
    if (typeof hideLoading === 'function') {
        console.log('✅ hideLoading existe');
    } else {
        console.log('❌ hideLoading não existe');
    }
    
    // Testar fetch
    console.log('Testando fetch...');
    fetch('salvar_alocacao_debug.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'fiscal_id=6&escola_id=1&sala_id=1&tipo_alocacao=sala&observacoes_alocacao=Teste+fetch&data_alocacao=<?= date('Y-m-d') ?>&horario_alocacao=08:00'
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(data => {
        console.log('Response data:', data);
        document.getElementById('resultado').innerHTML = '<div class="alert alert-info">Resposta do servidor recebida. Verifique o console.</div>';
    })
    .catch(error => {
        console.error('Fetch error:', error);
        document.getElementById('resultado').innerHTML = '<div class="alert alert-danger">Erro no fetch: ' + error.message + '</div>';
    });
}

// Teste do formulário com JavaScript
document.getElementById('testeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('Formulário submetido via JavaScript');
    
    // Simular envio
    const formData = new FormData(this);
    
    fetch('salvar_alocacao_debug.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        console.log('Resposta do servidor:', data);
        document.getElementById('resultado').innerHTML = '<div class="alert alert-success">Formulário enviado com sucesso!</div>';
    })
    .catch(error => {
        console.error('Erro:', error);
        document.getElementById('resultado').innerHTML = '<div class="alert alert-danger">Erro: ' + error.message + '</div>';
    });
});

// Verificar quando a página carrega
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== Página carregada ===');
    console.log('URL atual:', window.location.href);
    console.log('User Agent:', navigator.userAgent);
    
    // Verificar se há erros no console
    window.addEventListener('error', function(e) {
        console.error('Erro JavaScript:', e.error);
    });
});
</script>

<?php include '../includes/footer.php'; ?> 