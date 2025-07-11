<?php
require_once '../config.php';

echo "<h1>🔧 Criar Tabela de Alocações</h1>";

$db = getDB();
if (!$db) {
    echo "❌ Erro na conexão com banco<br>";
    exit;
}

try {
    // Verificar se a tabela já existe
    $stmt = $db->query("SHOW TABLES LIKE 'alocacoes_fiscais'");
    $tabela_existe = $stmt->rowCount() > 0;
    
    if ($tabela_existe) {
        echo "✅ Tabela alocacoes_fiscais já existe<br>";
    } else {
        echo "📋 Criando tabela alocacoes_fiscais...<br>";
        
        $sql = "
        CREATE TABLE alocacoes_fiscais (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fiscal_id INT NOT NULL,
            escola_id INT NOT NULL,
            sala_id INT NOT NULL,
            tipo_alocacao ENUM('sala', 'corredor', 'entrada', 'banheiro', 'outro') DEFAULT 'sala',
            observacoes TEXT NULL,
            data_alocacao DATE NOT NULL,
            horario_alocacao TIME NOT NULL,
            status ENUM('ativo', 'inativo', 'cancelado') DEFAULT 'ativo',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_fiscal_id (fiscal_id),
            INDEX idx_escola_id (escola_id),
            INDEX idx_sala_id (sala_id),
            INDEX idx_data_horario (data_alocacao, horario_alocacao),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->exec($sql);
        echo "✅ Tabela alocacoes_fiscais criada com sucesso!<br>";
    }
    
    // Verificar se a tabela escolas existe
    $stmt = $db->query("SHOW TABLES LIKE 'escolas'");
    $escolas_existe = $stmt->rowCount() > 0;
    
    if (!$escolas_existe) {
        echo "📋 Criando tabela escolas...<br>";
        
        $sql = "
        CREATE TABLE escolas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            endereco TEXT NOT NULL,
            telefone VARCHAR(20) NULL,
            email VARCHAR(255) NULL,
            responsavel VARCHAR(255) NULL,
            capacidade_total INT NULL,
            status ENUM('ativo', 'inativo') DEFAULT 'ativo',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->exec($sql);
        echo "✅ Tabela escolas criada com sucesso!<br>";
        
        // Inserir algumas escolas de exemplo
        $escolas_exemplo = [
            ['nome' => 'Escola Municipal João da Silva', 'endereco' => 'Rua das Flores, 123 - Centro', 'telefone' => '(11) 3333-3333'],
            ['nome' => 'Escola Estadual Maria Santos', 'endereco' => 'Av. Principal, 456 - Bairro Novo', 'telefone' => '(11) 4444-4444'],
            ['nome' => 'Colégio Particular São José', 'endereco' => 'Rua da Paz, 789 - Jardim', 'telefone' => '(11) 5555-5555']
        ];
        
        $stmt = $db->prepare("
            INSERT INTO escolas (nome, endereco, telefone, email, responsavel, capacidade_total) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($escolas_exemplo as $escola) {
            $stmt->execute([
                $escola['nome'],
                $escola['endereco'],
                $escola['telefone'],
                'contato@' . strtolower(str_replace(' ', '', $escola['nome'])) . '.com',
                'Diretor Responsável',
                500
            ]);
        }
        
        echo "✅ Escolas de exemplo inseridas<br>";
    } else {
        echo "✅ Tabela escolas já existe<br>";
    }
    
    // Verificar se a tabela salas existe
    $stmt = $db->query("SHOW TABLES LIKE 'salas'");
    $salas_existe = $stmt->rowCount() > 0;
    
    if (!$salas_existe) {
        echo "📋 Criando tabela salas...<br>";
        
        $sql = "
        CREATE TABLE salas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            escola_id INT NOT NULL,
            nome VARCHAR(255) NOT NULL,
            tipo ENUM('sala_aula', 'laboratorio', 'auditorio', 'biblioteca', 'outro') DEFAULT 'sala_aula',
            capacidade INT NULL,
            andar VARCHAR(10) NULL,
            bloco VARCHAR(50) NULL,
            status ENUM('ativo', 'inativo') DEFAULT 'ativo',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_escola_id (escola_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->exec($sql);
        echo "✅ Tabela salas criada com sucesso!<br>";
        
        // Inserir salas de exemplo
        $escolas = $db->query("SELECT id FROM escolas WHERE status = 'ativo'")->fetchAll();
        
        if (!empty($escolas)) {
            $stmt = $db->prepare("
                INSERT INTO salas (escola_id, nome, tipo, capacidade, andar, bloco) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($escolas as $escola) {
                $escola_id = $escola['id'];
                
                // Salas para cada escola
                $salas_exemplo = [
                    ['nome' => 'Sala 101', 'tipo' => 'sala_aula', 'capacidade' => 30, 'andar' => '1º', 'bloco' => 'A'],
                    ['nome' => 'Sala 102', 'tipo' => 'sala_aula', 'capacidade' => 30, 'andar' => '1º', 'bloco' => 'A'],
                    ['nome' => 'Sala 201', 'tipo' => 'sala_aula', 'capacidade' => 35, 'andar' => '2º', 'bloco' => 'A'],
                    ['nome' => 'Sala 202', 'tipo' => 'sala_aula', 'capacidade' => 35, 'andar' => '2º', 'bloco' => 'A'],
                    ['nome' => 'Laboratório de Informática', 'tipo' => 'laboratorio', 'capacidade' => 25, 'andar' => '1º', 'bloco' => 'B'],
                    ['nome' => 'Auditório Principal', 'tipo' => 'auditorio', 'capacidade' => 100, 'andar' => '1º', 'bloco' => 'C'],
                    ['nome' => 'Biblioteca', 'tipo' => 'biblioteca', 'capacidade' => 50, 'andar' => '1º', 'bloco' => 'B']
                ];
                
                foreach ($salas_exemplo as $sala) {
                    $stmt->execute([
                        $escola_id,
                        $sala['nome'],
                        $sala['tipo'],
                        $sala['capacidade'],
                        $sala['andar'],
                        $sala['bloco']
                    ]);
                }
            }
            
            echo "✅ Salas de exemplo inseridas<br>";
        }
    } else {
        echo "✅ Tabela salas já existe<br>";
    }
    
    // Verificar estrutura final
    echo "<h2>📊 Estrutura das Tabelas</h2>";
    
    $tabelas = ['alocacoes_fiscais', 'escolas', 'salas'];
    
    foreach ($tabelas as $tabela) {
        echo "<h3>Tabela: $tabela</h3>";
        $stmt = $db->query("DESCRIBE $tabela");
        $colunas = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>Coluna</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th>";
        echo "</tr>";
        
        foreach ($colunas as $coluna) {
            echo "<tr>";
            echo "<td>" . $coluna['Field'] . "</td>";
            echo "<td>" . $coluna['Type'] . "</td>";
            echo "<td>" . $coluna['Null'] . "</td>";
            echo "<td>" . $coluna['Key'] . "</td>";
            echo "<td>" . ($coluna['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . ($coluna['Extra'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>✅ Configuração Concluída!</h2>";
    echo "<p>As tabelas necessárias para alocação de fiscais foram criadas com sucesso.</p>";
    
    echo "<h3>🔧 Próximos Passos</h3>";
    echo "<a href='fiscais.php' class='btn btn-primary'>Gerenciar Fiscais</a> ";
    echo "<a href='escolas.php' class='btn btn-success'>Gerenciar Escolas</a> ";
    echo "<a href='salas.php' class='btn btn-info'>Gerenciar Salas</a>";
    
} catch (Exception $e) {
    echo "❌ Erro na criação das tabelas: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?> 