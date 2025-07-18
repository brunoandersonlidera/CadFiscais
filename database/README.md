# Manual de Instalação do Banco de Dados

Esta pasta contém:
- `cadfiscais.sql`: Script SQL para criar todas as tabelas do sistema.
- `dados_teste/`: Scripts e arquivos para importar dados fictícios (opcional).

## Como usar

1. **Criação do banco de dados:**
   - Abra o phpMyAdmin, DBeaver ou outro cliente MySQL/MariaDB.
   - Crie um banco de dados vazio (ex: `cadfiscais`).
   - Importe o arquivo `cadfiscais.sql` para criar as tabelas.

2. **Importar dados de teste (opcional):**
   - Os scripts em `dados_teste/` podem ser executados após a criação das tabelas para popular o sistema com dados fictícios.
   - Você também pode optar por importar esses dados durante a instalação via instalador web.

3. **Configuração do sistema:**
   - Edite o arquivo `config.php` ou use o instalador web para informar os dados de conexão.

## Observações
- O script SQL cobre toda a estrutura do sistema, incluindo usuários, concursos, escolas, fiscais, salas, pagamentos, etc.
- Os dados de teste são úteis para explorar o sistema sem dados reais.
- Para dúvidas, consulte o README principal do projeto ou o instalador web. 