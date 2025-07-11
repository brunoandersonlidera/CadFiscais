<!DOCTYPE html>
<html>
<head>
    <title>Teste Edição Escola</title>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <h1>Teste de Edição de Escola</h1>
    <button onclick="testarEdicao()">Testar Edição</button>
    <div id="resultado"></div>

    <script>
    function showMessage(message, type = 'success') {
        Swal.fire({
            title: type === 'success' ? 'Sucesso!' : 'Atenção!',
            text: message,
            icon: type,
            timer: type === 'success' ? 3000 : undefined,
            timerProgressBar: type === 'success'
        });
    }

    function testarEdicao() {
        console.log('Iniciando teste de edição');
        
        $.ajax({
            url: 'buscar_escola.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                escola_id: 1
            }),
            success: function(data) {
                console.log('Sucesso:', data);
                $('#resultado').html('<pre>' + JSON.stringify(data, null, 2) + '</pre>');
                
                if (data.success) {
                    showMessage('Escola encontrada com sucesso!', 'success');
                } else {
                    showMessage('Erro: ' + data.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                $('#resultado').html('Erro: ' + error + '<br>Status: ' + status + '<br>Response: ' + xhr.responseText);
                showMessage('Erro na requisição: ' + error, 'error');
            }
        });
    }
    </script>
</body>
</html> 