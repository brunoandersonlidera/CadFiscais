# Sistema de Cadastro de Fiscais - IDH

## 📋 Descrição

O **Sistema de Cadastro de Fiscais** é uma aplicação web desenvolvida em PHP para gerenciar o cadastro e controle de fiscais para concursos públicos do Instituto Dignidade Humana (IDH). O sistema permite o cadastro online de fiscais, controle administrativo, alocação em escolas e salas, controle de presença e pagamentos.

## 🏗️ Arquitetura do Sistema

### Tecnologias Utilizadas
- **Backend**: PHP 7.4+
- **Banco de Dados**: MySQL/MariaDB (com fallback para CSV)
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **PDF**: TCPDF para geração de relatórios
- **Hospedagem**: Hostinger

### Estrutura de Diretórios
```
CadFiscais/
├── admin/                 # Painel administrativo
├── includes/             # Arquivos de inclusão (header/footer)
├── logos/               # Logos institucionais
├── logs/                # Arquivos de log do sistema
├── TCPDF/               # Biblioteca para geração de PDFs
├── config.php           # Configurações principais
├── index.php            # Página inicial
├── cadastro.php         # Formulário de cadastro público
└── processar_cadastro.php # Processamento do cadastro
```

## 🎯 Funcionalidades Principais

### Área Pública
- **Cadastro de Fiscais**: Formulário online para inscrição
- **Visualização de Concursos**: Lista de concursos ativos
- **Controle de Vagas**: Sistema automático de controle de vagas disponíveis
- **Validação de Dados**: Validação de CPF, email, telefone, idade mínima

### Área Administrativa
- **Dashboard**: Visão geral com estatísticas
- **Gestão de Concursos**: CRUD completo de concursos
- **Gestão de Fiscais**: Listagem, edição e controle de status
- **Gestão de Escolas**: Cadastro e controle de escolas
- **Gestão de Salas**: Controle de salas por escola
- **Alocação de Fiscais**: Sistema de alocação em escolas/salas
- **Controle de Presença**: Registro de presença dos fiscais
- **Gestão de Pagamentos**: Controle de pagamentos aos fiscais
- **Relatórios**: Geração de relatórios em PDF
- **Configurações**: Controle de configurações do sistema

## 🗄️ Estrutura do Banco de Dados

### Tabelas Principais
- **usuarios**: Usuários do sistema administrativo
- **tipos_usuario**: Tipos de usuário (admin, colaborador)
- **concursos**: Informações dos concursos
- **escolas**: Escolas onde ocorrem as provas
- **salas**: Salas das escolas
- **fiscais**: Cadastro dos fiscais
- **pagamentos**: Controle de pagamentos
- **presenca**: Registro de presença
- **configuracoes**: Configurações do sistema

## 🚀 Instalação

### Pré-requisitos
- PHP 7.4 ou superior
- MySQL/MariaDB
- Extensões PHP: PDO, PDO_MySQL, mbstring
- Servidor web (Apache/Nginx)

### Passos de Instalação

1. **Upload dos Arquivos**
   ```bash
   # Faça upload de todos os arquivos para o servidor
   ```

2. **Configuração do Banco**
   - Edite o arquivo `config.php`
   - Configure as credenciais do banco de dados
   - Execute o script de instalação: `instalar_sistema.php`

3. **Configurações Iniciais**
   - Acesse o painel administrativo: `/admin/`
   - Faça login com as credenciais padrão
   - Configure os parâmetros do sistema

4. **Permissões de Arquivo**
   ```bash
   chmod 755 logs/
   chmod 644 config.php
   ```

## 📖 Manual de Uso

### Para Administradores

#### 1. Gestão de Concursos
- Acesse: `admin/concursos.php`
- Crie novos concursos com todas as informações necessárias
- Configure vagas disponíveis e valor do pagamento
- Ative/desative concursos conforme necessário

#### 2. Gestão de Fiscais
- Acesse: `admin/fiscais.php`
- Visualize todos os fiscais cadastrados
- Edite informações quando necessário
- Controle status (pendente, ativo, validado, confirmado, rejeitado)

#### 3. Gestão de Escolas e Salas
- **Escolas**: `admin/escolas.php`
- **Salas**: `admin/salas.php`
- Cadastre escolas onde ocorrerão as provas
- Configure salas com capacidade adequada

#### 4. Alocação de Fiscais
- Acesse: `admin/alocar_fiscal.php`
- Aloque fiscais em escolas e salas específicas
- Controle a distribuição de fiscais por local

#### 5. Controle de Presença
- Acesse: `admin/lista_presenca.php`
- Registre presença dos fiscais no dia da prova
- Controle horários de entrada e saída

#### 6. Gestão de Pagamentos
- Acesse: `admin/lista_pagamentos.php`
- Registre pagamentos realizados
- Gere recibos e relatórios

#### 7. Relatórios
- **Relatório Geral**: `admin/relatorios.php`
- **Relatório de Alocações**: `admin/relatorio_alocacoes.php`
- **Relatório de Comparecimento**: `admin/relatorio_comparecimento.php`
- **Relatório de Fiscais**: `admin/relatorio_fiscais.php`

### Para Fiscais (Área Pública)

#### 1. Cadastro
- Acesse a página inicial do sistema
- Selecione o concurso desejado
- Preencha o formulário com dados pessoais
- Aceite os termos de uso
- Aguarde confirmação

#### 2. Acompanhamento
- O fiscal receberá informações por email
- Poderá acompanhar status do cadastro
- Receberá instruções sobre local e horário

## ⚙️ Configurações do Sistema

### Configurações Principais
- **cadastro_aberto**: Controla se o cadastro está aberto (1/0)
- **idade_minima**: Idade mínima para cadastro (padrão: 18)
- **ddi_padrao**: DDI padrão para telefones (+55)
- **valor_pagamento_padrao**: Valor padrão do pagamento

### Configurações de Segurança
- Sessões seguras com cookies httponly
- Validação de CSRF tokens
- Sanitização de inputs
- Logs de atividades

## 🔧 Manutenção

### Logs do Sistema
- Arquivo: `logs/system.log`
- Contém todas as atividades do sistema
- Monitoramento de erros e ações dos usuários

### Backup
- Backup automático antes de alterações estruturais
- Arquivos de backup salvos com timestamp
- Recomenda-se backup regular do banco de dados

### Monitoramento
- Verificar logs regularmente
- Monitorar espaço em disco
- Verificar performance do banco de dados

## 🚨 Arquivos Não Utilizados (Para Limpeza)

### Arquivos de Teste e Debug
- `teste_cadastro.php` - Teste de cadastro
- `teste_final_cadastro.php` - Teste final
- `debug_cadastro.php` - Debug do cadastro
- `corrigir_cadastro.php` - Correção de cadastro
- `corrigir_tabela_fiscais.php` - Correção de tabela
- `atualizar_tabela_fiscais.php` - Atualização de tabela
- `remover_restricoes_fiscais.php` - Remoção de restrições
- `verificar_concursos.php` - Verificação de concursos
- `verificar_escolas.php` - Verificação de escolas
- `verificar_problema_julianday.php` - Verificação de problema

### Arquivos de Teste no Admin
- `admin/teste_auth.php` - Teste de autenticação
- `admin/teste_editar_escola.php` - Teste de edição
- `admin/teste_showmessage.php` - Teste de mensagens
- `admin/teste_simples_escola.php` - Teste simples
- `admin/debug_console.php` - Console de debug
- `admin/debug_editar_escola.php` - Debug de edição

### Arquivos Temporários
- `cadastro_fixo.php` - Cadastro fixo (obsoleto)
- `cadastro_simples.php` - Cadastro simples (obsoleto)
- `ddi.php` - Lista de DDI (pode ser integrado)

## 📊 Estatísticas do Sistema

### Funcionalidades Implementadas
- ✅ Cadastro público de fiscais
- ✅ Painel administrativo completo
- ✅ Gestão de concursos
- ✅ Controle de escolas e salas
- ✅ Sistema de alocação
- ✅ Controle de presença
- ✅ Gestão de pagamentos
- ✅ Relatórios em PDF
- ✅ Sistema de logs
- ✅ Validações de segurança
- ✅ Sistema de certificados de treinamento
- ✅ Validação de certificados por QR Code

### Tecnologias Utilizadas
- ✅ PHP 7.4+
- ✅ MySQL/MariaDB
- ✅ Bootstrap 5
- ✅ TCPDF
- ✅ PDO para banco de dados
- ✅ Sistema de sessões seguras

## 🤝 Suporte

### Contato
- **Instituto Dignidade Humana (IDH)**
- Email: contato@idh.org.br
- Telefone: (XX) XXXX-XXXX

### Documentação Técnica
- Arquivo de configuração: `config.php`
- Logs do sistema: `logs/system.log`
- Estrutura do banco: Definida em `config.php`

### Problemas Comuns
1. **Erro de conexão com banco**: Verificar credenciais em `config.php`
2. **Permissões de arquivo**: Verificar permissões de escrita em `logs/`
3. **Cadastro não funciona**: Verificar se `cadastro_aberto = '1'`
4. **Relatórios não geram**: Verificar biblioteca TCPDF

## 📝 Changelog

### Versão 1.1.0 (2025-01-XX)
- ✅ Sistema de certificados de treinamento implementado
- ✅ Validação de certificados por código simplificada
- ✅ QR Code simplificado para validação
- ✅ Correção do cabeçalho para exibir nome do usuário
- ✅ Remoção de campos desnecessários na validação
- ✅ Melhorias na interface do usuário

### Versão 1.0.0 (2025-01-XX)
- ✅ Sistema inicial implementado
- ✅ Cadastro público funcionando
- ✅ Painel administrativo completo
- ✅ Relatórios em PDF
- ✅ Sistema de logs
- ✅ Validações de segurança

---

**Desenvolvido para o Instituto Dignidade Humana (IDH)**
*Sistema de Cadastro de Fiscais - Versão 1.1.0*
