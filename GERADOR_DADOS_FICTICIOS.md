# Gerador de Dados Fictícios e Alocação Automática

## Resumo das Implementações

Foram criadas funcionalidades completas para geração de dados fictícios para testes e alocação automática de fiscais, facilitando o desenvolvimento e testes do sistema.

## 1. Gerador de Dados Fictícios

### Localização
- **Pasta:** `gerador_dados/`
- **Acesso:** Menu Configurações → Gerador de Dados Fictícios

### Funcionalidades Implementadas

#### 1.1 Página Principal (`gerador_dados/index.php`)
- **Interface intuitiva** com estatísticas em tempo real
- **Geração individual** de cada tipo de dado
- **Geração completa** de todos os dados de uma vez
- **Links úteis** para navegação rápida
- **Avisos de segurança** sobre dados fictícios

#### 1.2 Gerador de Concurso (`gerar_concurso.php`)
- **Concurso fictício:** "Concurso Público Municipal 2025 - TESTE"
- **Dados completos:** título, órgão, data, horário, valor, vagas
- **Verificação de duplicação:** evita criar múltiplos concursos de teste
- **Suporte a banco de dados e CSV**

#### 1.3 Gerador de Escolas (`gerar_escolas.php`)
- **5 escolas municipais fictícias** com nomes realistas
- **Dados completos:** nome, endereço, telefone, responsável, capacidade
- **Verificação de dependência:** requer concurso de teste existente
- **Prevenção de duplicação:** evita criar escolas duplicadas

#### 1.4 Gerador de Salas (`gerar_salas.php`)
- **25 salas no total** (5 por escola)
- **Tipos de locais:** 3 salas + 1 corredor + 1 portaria por escola
- **Dados específicos:** nome, tipo, capacidade, observações
- **Verificação de dependência:** requer escolas existentes

#### 1.5 Gerador de Fiscais (`gerar_fiscais.php`)
- **50 fiscais no total** (2 por sala/corredor/portaria)
- **Status aprovado:** todos os fiscais gerados já estão aprovados
- **Dados realistas:** nomes, emails, CPFs, telefones, endereços
- **Distribuição balanceada:** 25 homens e 25 mulheres
- **Dados completos:** todas as informações necessárias preenchidas

### Estrutura de Dados Gerados

```
1 Concurso de Teste
├── 5 Escolas Municipais
    ├── Escola Municipal Professor João Silva
    ├── Escola Municipal Dona Ana Costa
    ├── Escola Municipal São José
    ├── Escola Municipal Santa Maria
    └── Escola Municipal Dom Pedro
        ├── 3 Salas de Aula (101, 102, 103)
        ├── 1 Corredor Principal
        └── 1 Portaria
            └── 2 Fiscais por local (50 fiscais total)
```

## 2. Alocação Automática de Fiscais

### Localização
- **Página principal:** `admin/alocacao_automatica.php`
- **Acesso:** Página "Alocar Fiscais" → Botão "Alocação Automática"

### Funcionalidades Implementadas

#### 2.1 Interface de Alocação Automática
- **Seleção de concurso** com dropdown
- **Estatísticas em tempo real** via AJAX
- **Explicação detalhada** do funcionamento
- **Feedback visual** de sucesso/erro

#### 2.2 Lógica de Alocação
- **Busca fiscais aprovados** não alocados
- **Distribui 2 fiscais por sala** automaticamente
- **Respeita capacidade** máxima de cada sala
- **Aloca em ordem alfabética** por nome
- **Suporte a banco de dados e CSV**

#### 2.3 Estatísticas em Tempo Real
- **Fiscais disponíveis** para alocação
- **Salas disponíveis** para receber fiscais
- **Atualização via AJAX** ao selecionar concurso

### Como Funciona a Alocação Automática

1. **Seleciona concurso** na interface
2. **Busca fiscais aprovados** que ainda não foram alocados
3. **Obtém salas disponíveis** do concurso selecionado
4. **Distribui fiscais:** 2 por sala em ordem alfabética
5. **Cria alocações** no sistema
6. **Retorna feedback** com número de alocações realizadas

## 3. Integração com Sistema Existente

### 3.1 Menu de Configurações
- **Adicionada seção** "Gerador de Dados Fictícios"
- **Acesso direto** via botão na página de configurações
- **Avisos de segurança** sobre dados de teste

### 3.2 Página de Alocar Fiscais
- **Botão "Alocação Automática"** adicionado no cabeçalho
- **Integração visual** com design existente
- **Navegação intuitiva** entre funcionalidades

### 3.3 Suporte a Banco de Dados e CSV
- **Compatibilidade total** com ambos os sistemas
- **Fallback automático** para CSV quando necessário
- **Funções unificadas** para ambos os casos

## 4. Arquivos Criados/Modificados

### 4.1 Novos Arquivos
1. **`gerador_dados/index.php`** - Página principal do gerador
2. **`gerador_dados/gerar_concurso.php`** - Gerador de concurso
3. **`gerador_dados/gerar_escolas.php`** - Gerador de escolas
4. **`gerador_dados/gerar_salas.php`** - Gerador de salas
5. **`gerador_dados/gerar_fiscais.php`** - Gerador de fiscais
6. **`admin/alocacao_automatica.php`** - Página de alocação automática
7. **`admin/get_estatisticas_alocacao.php`** - API para estatísticas

### 4.2 Arquivos Modificados
1. **`admin/configuracoes.php`** - Adicionada seção do gerador
2. **`admin/alocar_fiscais.php`** - Adicionado botão de alocação automática

## 5. Benefícios das Implementações

### 5.1 Para Desenvolvimento
- **Testes rápidos** com dados realistas
- **Ambiente consistente** para desenvolvimento
- **Validação de funcionalidades** com dados completos
- **Demonstração do sistema** com dados fictícios

### 5.2 Para Usuários
- **Alocação eficiente** de fiscais
- **Redução de trabalho manual** na distribuição
- **Interface intuitiva** para operações complexas
- **Feedback imediato** sobre operações

### 5.3 Para Administração
- **Controle total** sobre dados de teste
- **Geração sob demanda** de dados necessários
- **Limpeza fácil** de dados de teste
- **Logs detalhados** de todas as operações

## 6. Como Usar

### 6.1 Gerar Dados Fictícios
1. Acesse **Configurações** → **Gerador de Dados Fictícios**
2. Escolha **"Gerar Todos os Dados"** para criação completa
3. Ou use **geração individual** para criar dados específicos
4. Verifique os dados criados nas páginas correspondentes

### 6.2 Usar Alocação Automática
1. Acesse **Locais** → **Alocar Fiscais**
2. Clique em **"Alocação Automática"**
3. Selecione o **concurso** desejado
4. Clique em **"Executar Alocação Automática"**
5. Verifique as **alocações criadas**

## 7. Segurança e Validações

### 7.1 Proteções Implementadas
- **Verificação de permissões** de administrador
- **Validação de dados** antes da criação
- **Prevenção de duplicação** de dados de teste
- **Logs detalhados** de todas as operações

### 7.2 Avisos de Segurança
- **Alertas visuais** sobre dados fictícios
- **Confirmações** antes de operações críticas
- **Documentação clara** sobre uso dos dados
- **Separação** entre dados reais e de teste

## 8. Compatibilidade

- **Navegadores:** Todos os navegadores modernos
- **Sistemas:** Banco de dados MySQL e sistema CSV
- **Dispositivos:** Desktop, tablet e mobile
- **Versões:** Compatível com sistema existente

## 9. Manutenção

### 9.1 Limpeza de Dados de Teste
- **Identificação fácil** por palavras-chave "TESTE"
- **Remoção seletiva** por tipo de dado
- **Backup automático** antes de operações críticas

### 9.2 Logs e Monitoramento
- **Logs detalhados** de todas as operações
- **Rastreamento** de dados criados
- **Estatísticas** de uso das funcionalidades

## 10. Próximos Passos

### 10.1 Melhorias Futuras
- **Mais tipos de dados** fictícios
- **Configuração personalizada** de dados gerados
- **Exportação** de dados de teste
- **Templates** de dados para diferentes cenários

### 10.2 Integrações
- **API REST** para geração programática
- **Interface de linha de comando** para automação
- **Integração** com sistemas de CI/CD
- **Relatórios** de uso das funcionalidades 