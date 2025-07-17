# Sistema de Cadastro de Fiscais - IDH

## 📋 Descrição

O **Sistema de Cadastro de Fiscais** é uma aplicação web em PHP para gerenciar fiscais de concursos públicos do Instituto Dignidade Humana (IDH). Permite cadastro, alocação, controle de presença, pagamentos e geração de relatórios.

## 🏗️ Estrutura do Projeto

```
CadFiscais/
├── admin/                 # Painel administrativo
├── includes/              # Arquivos de inclusão (header/footer)
├── logos/                 # Logos institucionais
├── logs/                  # Arquivos de log do sistema
├── TCPDF/                 # Biblioteca para geração de PDFs
├── database/              # Scripts SQL, manual e dados de teste
├── config.php             # Configuração do sistema (NÃO versionado)
├── config.example.php     # Exemplo de configuração
├── index.php              # Página inicial
├── instalar/              # Instalador web do sistema
└── ...
```

## 🚀 Instalação

1. **Clone o repositório:**
   ```sh
   git clone ...
   ```

2. **Configuração do banco de dados:**
   - Acesse a pasta `/database` para encontrar o script `cadfiscais.sql` e o manual.
   - Você pode criar o banco manualmente ou usar o instalador web.

3. **Configuração do sistema:**
   - Copie `config.example.php` para `config.php` e preencha os dados de conexão.
   - NÃO remova as funções utilitárias do arquivo.

4. **Instalação automática (recomendado):**
   - Acesse `http://localhost:8000/instalar/index.php` no navegador.
   - Preencha os dados de conexão do banco.
   - Escolha se deseja importar dados de teste.
   - O instalador criará as tabelas e configurará o sistema.

5. **Acesso ao sistema:**
   - Usuário padrão: `admin@idh.com`
   - Senha padrão: `admin123`

## 🧪 Dados de Teste
- Os scripts de dados fictícios estão em `/database/dados_teste`.
- Você pode importar esses dados pelo instalador ou manualmente.

## 📖 Visualizar README formatado localmente
Se quiser visualizar este README.md formatado no navegador local, acesse:
```
http://localhost:8000/readme.php
```

## 📝 Observações
- O arquivo `config.php` **NÃO** deve ser versionado.
- O instalador web facilita a configuração inicial.
- Para dúvidas, consulte o manual em `/database/README.md` ou abra uma issue.

---

> Desenvolvido por Instituto Dignidade Humana (IDH) e colaboradores. 