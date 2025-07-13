<?php
require_once 'config.php';

// Função para testar validação de CPF
function testarCPF($cpf) {
    $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf_limpo) !== 11) {
        return false;
    }
    
    if (preg_match('/^(\d)\1+$/', $cpf_limpo)) {
        return false;
    }
    
    // Calcula primeiro dígito verificador
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += intval($cpf_limpo[$i]) * (10 - $i);
    }
    $remainder = ($sum * 10) % 11;
    if ($remainder == 10 || $remainder == 11) {
        $remainder = 0;
    }
    if ($remainder != intval($cpf_limpo[9])) {
        return false;
    }
    
    // Calcula segundo dígito verificador
    $sum = 0;
    for ($i = 0; $i < 10; $i++) {
        $sum += intval($cpf_limpo[$i]) * (11 - $i);
    }
    $remainder = ($sum * 10) % 11;
    if ($remainder == 10 || $remainder == 11) {
        $remainder = 0;
    }
    if ($remainder != intval($cpf_limpo[10])) {
        return false;
    }
    
    return true;
}

// Função para testar validação de celular brasileiro
function testarCelular($celular) {
    $celular_limpo = preg_replace('/[^0-9]/', '', $celular);
    
    if (strlen($celular_limpo) < 10 || strlen($celular_limpo) > 11) {
        return false;
    }
    
    $ddd = substr($celular_limpo, 0, 2);
    $number = substr($celular_limpo, 2);
    
    $valid_ddds = [
        11, 12, 13, 14, 15, 16, 17, 18, 19, 21, 22, 24, 27, 28, 31, 32, 33, 34, 35, 37, 38, 41, 42, 43, 44, 45, 46, 47, 48, 49, 51, 53, 54, 55, 61, 62, 63, 64, 65, 66, 67, 68, 69, 71, 73, 74, 75, 77, 79, 81, 82, 83, 84, 85, 86, 87, 88, 89, 91, 92, 93, 94, 95, 96, 97, 98, 99
    ];
    
    if (!in_array($ddd, $valid_ddds)) {
        return false;
    }
    
    if (strlen($number) == 9 && substr($number, 0, 1) != '9') {
        return false;
    }
    
    return true;
}

// Função para testar validação de email
function testarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Função para testar validação de data
function testarData($data) {
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data)) {
        $data_parts = explode('/', $data);
        $data_formatada = $data_parts[2] . '-' . $data_parts[1] . '-' . $data_parts[0];
    } else {
        $data_formatada = $data;
    }
    
    $data_obj = DateTime::createFromFormat('Y-m-d', $data_formatada);
    if (!$data_obj) {
        return false;
    }
    
    $hoje = new DateTime();
    $idade = $hoje->diff($data_obj)->y;
    
    return $idade >= 18;
}

// Testes
$testes = [
    'CPF' => [
        '111.444.777-35' => true,  // CPF válido
        '111.111.111-11' => false, // CPF inválido (todos iguais)
        '123.456.789-01' => false, // CPF inválido
        '000.000.000-00' => false, // CPF inválido
        '123.456.789-10' => false, // CPF inválido
    ],
    'Celular' => [
        '(11) 99999-9999' => true,  // Celular válido
        '(11) 88888-8888' => false, // Celular inválido (não começa com 9)
        '(11) 9999-9999' => false,  // Celular inválido (muito curto)
        '(99) 99999-9999' => false, // DDD inválido
        '(11) 99999-99999' => false, // Celular inválido (muito longo)
    ],
    'Email' => [
        'teste@email.com' => true,
        'teste@email' => false,
        '@email.com' => false,
        'teste.email.com' => false,
        'teste@.com' => false,
    ],
    'Data' => [
        '01/01/1990' => true,   // Data válida (maior de 18)
        '01/01/2010' => false,  // Data inválida (menor de 18)
        '32/13/1990' => false,  // Data inválida
        '00/00/1990' => false,  // Data inválida
        '01/01/2005' => false,  // Data inválida (menor de 18)
    ]
];

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Validações - Cadastro de Fiscais</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-vial"></i> 
                            Teste de Validações - Cadastro de Fiscais
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Teste das Validações Implementadas</h5>
                            <p>Este arquivo testa todas as validações implementadas no sistema de cadastro de fiscais.</p>
                        </div>

                        <?php foreach ($testes as $tipo => $valores): ?>
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="mb-0">Teste de <?= $tipo ?></h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Valor Testado</th>
                                                    <th>Resultado Esperado</th>
                                                    <th>Resultado Obtido</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $funcao_teste = '';
                                                switch ($tipo) {
                                                    case 'CPF':
                                                        $funcao_teste = 'testarCPF';
                                                        break;
                                                    case 'Celular':
                                                        $funcao_teste = 'testarCelular';
                                                        break;
                                                    case 'Email':
                                                        $funcao_teste = 'testarEmail';
                                                        break;
                                                    case 'Data':
                                                        $funcao_teste = 'testarData';
                                                        break;
                                                }
                                                
                                                foreach ($valores as $valor => $esperado):
                                                    $resultado = $funcao_teste($valor);
                                                    $status = $resultado === $esperado ? 'success' : 'danger';
                                                    $icone = $resultado === $esperado ? 'check' : 'times';
                                                ?>
                                                <tr>
                                                    <td><code><?= htmlspecialchars($valor) ?></code></td>
                                                    <td>
                                                        <span class="badge bg-<?= $esperado ? 'success' : 'danger' ?>">
                                                            <?= $esperado ? 'Válido' : 'Inválido' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?= $resultado ? 'success' : 'danger' ?>">
                                                            <?= $resultado ? 'Válido' : 'Inválido' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <i class="fas fa-<?= $icone ?> text-<?= $status ?>"></i>
                                                        <?= $resultado === $esperado ? 'Passou' : 'Falhou' ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Teste de Máscaras JavaScript</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="teste_cpf">Teste CPF:</label>
                                            <input type="text" class="form-control" id="teste_cpf" 
                                                   placeholder="000.000.000-00" maxlength="14">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="teste_celular">Teste Celular:</label>
                                            <input type="tel" class="form-control" id="teste_celular" 
                                                   placeholder="(99) 99999-9999" maxlength="15">
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="teste_data">Teste Data:</label>
                                            <input type="text" class="form-control" id="teste_data" 
                                                   placeholder="dd/mm/aaaa" maxlength="10">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="teste_email">Teste Email:</label>
                                            <input type="email" class="form-control" id="teste_email" 
                                                   placeholder="teste@email.com">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <a href="cadastro.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Voltar ao Cadastro
                            </a>
                            <a href="admin/" class="btn btn-secondary">
                                <i class="fas fa-cog"></i> Área Administrativa
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Máscara para CPF
        const cpfInput = document.getElementById('teste_cpf');
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
        const celularInput = document.getElementById('teste_celular');
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

        // Máscara para data
        const dataInput = document.getElementById('teste_data');
        if (dataInput) {
            dataInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.replace(/(\d{2})(\d)/, '$1/$2');
                }
                if (value.length >= 5) {
                    value = value.replace(/(\d{2})(\d{2})(\d)/, '$1/$2/$3');
                }
                e.target.value = value;
            });
        }
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 