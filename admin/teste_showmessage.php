<!DOCTYPE html>
<html>
<head>
    <title>Teste ShowMessage</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <h1>Teste de ShowMessage</h1>
    <button onclick="testarSucesso()">Testar Sucesso</button>
    <button onclick="testarErro()">Testar Erro</button>

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

    function testarSucesso() {
        showMessage('Teste de sucesso!', 'success');
    }

    function testarErro() {
        showMessage('Teste de erro!', 'error');
    }
    </script>
</body>
</html> 