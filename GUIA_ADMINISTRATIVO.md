# 📋 Guia Administrativo Completo - Sistema de Fiscais

## 🎯 **Funcionalidades Administrativas**

### 👥 **1. Gerenciamento de Usuários**
**URL**: `https://lideratecnologia.com.br/concurso/CadFiscais/admin/usuarios.php`

**Funcionalidades**:
- ✅ Listar todos os usuários do sistema
- ✅ Filtrar por tipo (admin/colaborador) e status
- ✅ Adicionar novos usuários
- ✅ Editar usuários existentes
- ✅ Excluir usuários
- ✅ Ver detalhes e histórico de login

**Tipos de Usuário**:
- **Administrador**: Acesso total ao sistema
- **Colaborador**: Acesso para gerar relatórios

---

### 🏫 **2. Gerenciamento de Escolas**
**URL**: `https://lideratecnologia.com.br/concurso/CadFiscais/admin/escolas.php`

**Funcionalidades**:
- ✅ Cadastrar novas escolas
- ✅ Editar informações das escolas
- ✅ Filtrar por concurso e status
- ✅ Ver estatísticas (total de salas, alocações)
- ✅ Gerenciar salas de cada escola
- ✅ Excluir escolas

**Informações das Escolas**:
- Nome da escola
- Endereço completo
- Telefone e email
- Responsável
- Capacidade total
- Status (ativo/inativo)

---

### 🚪 **3. Gerenciamento de Salas**
**URL**: `https://lideratecnologia.com.br/concurso/CadFiscais/admin/salas.php`

**Funcionalidades**:
- ✅ Cadastrar novas salas
- ✅ Editar informações das salas
- ✅ Filtrar por escola, tipo e status
- ✅ Ver alocações de cada sala
- ✅ Excluir salas

**Tipos de Sala**:
- **Sala de Aula**: Salas tradicionais
- **Laboratório**: Laboratórios de informática, ciências, etc.
- **Auditório**: Salas grandes para apresentações
- **Biblioteca**: Espaços de estudo
- **Outro**: Outros tipos de ambiente

**Informações das Salas**:
- Nome da sala
- Tipo de sala
- Capacidade
- Andar
- Bloco
- Status (ativo/inativo)

---

### 👤 **4. Gerenciamento de Fiscais**
**URL**: `https://lideratecnologia.com.br/concurso/CadFiscais/admin/fiscais.php`

**Funcionalidades**:
- ✅ Listar todos os fiscais cadastrados
- ✅ Filtrar por status, concurso e busca
- ✅ Ver detalhes completos de cada fiscal
- ✅ Editar informações dos fiscais
- ✅ Alocar fiscais em escolas/salas
- ✅ Excluir fiscais
- ✅ Exportar dados em CSV/Excel

**Status dos Fiscais**:
- **Pendente**: Aguardando aprovação
- **Aprovado**: Aprovado para alocação
- **Reprovado**: Não aprovado
- **Cancelado**: Cadastro cancelado

---

### 📍 **5. Sistema de Alocação**
**URL**: `https://lideratecnologia.com.br/concurso/CadFiscais/admin/alocar_fiscal.php?id=X`

**Funcionalidades**:
- ✅ Alocar fiscais em escolas específicas
- ✅ Selecionar salas por escola
- ✅ Definir tipo de alocação (sala, corredor, entrada, etc.)
- ✅ Definir data e horário da alocação
- ✅ Adicionar observações
- ✅ Ver histórico de alocações
- ✅ Editar e remover alocações

**Tipos de Alocação**:
- **Sala**: Sala de aula
- **Corredor**: Vigilância de corredor
- **Entrada**: Controle de entrada/saída
- **Banheiro**: Vigilância de banheiros
- **Outro**: Outros locais

---

### 📊 **6. Relatórios**
**URL**: `https://lideratecnologia.com.br/concurso/CadFiscais/admin/relatorios.php`

#### **6.1 Relatórios de Fiscais**
- **Lista Completa**: Todos os fiscais cadastrados
- **Fiscais Aprovados**: Apenas fiscais aprovados
- **Alocações**: Detalhamento de alocações
- **Por Horário**: Agrupamento por melhor horário

#### **6.2 Relatórios de Presença**
- **Lista de Presença - Dia da Prova**: Para controle no dia
- **Lista de Presença - Treinamento**: Para treinamento
- **Ata de Reunião - Treinamento**: Modelo de ata
- **Relatório de Comparecimento**: Estatísticas

#### **6.3 Relatórios de Pagamentos**
- **Lista de Pagamentos**: Todos os fiscais com valores
- **Recibos de Pagamento**: Modelo de recibo
- **Resumo Financeiro**: Valores pagos e pendentes
- **Planilha de Pagamentos**: Exportação Excel

#### **6.4 Relatórios por Escola**
- **Fiscais Alocados**: Por escola específica
- **Lista de Presença**: Por escola
- **Salas e Capacidades**: Por escola
- **Relatório Completo**: Tudo da escola

#### **6.5 Relatórios por Concurso**
- **Fiscais Cadastrados**: Por concurso
- **Alocações**: Por concurso
- **Presença**: Por concurso
- **Pagamentos**: Por concurso
- **Relatório Completo**: Tudo do concurso

---

## 🔗 **Links Diretos das Funcionalidades**

### **Gerenciamento**
- **Usuários**: `/admin/usuarios.php`
- **Escolas**: `/admin/escolas.php`
- **Salas**: `/admin/salas.php`
- **Fiscais**: `/admin/fiscais.php`
- **Alocações**: `/admin/alocar_fiscal.php`

### **Relatórios**
- **Central de Relatórios**: `/admin/relatorios.php`
- **Fiscais**: `/admin/relatorio_fiscais.php`
- **Presença**: `/admin/lista_presenca.php`
- **Pagamentos**: `/admin/lista_pagamentos.php`
- **Por Escola**: `/admin/relatorio_escola.php`
- **Por Concurso**: `/admin/relatorio_concurso.php`

---

## 📋 **Checklist de Configuração**

### **Antes de Usar o Sistema**
- [ ] Cadastrar usuários administradores
- [ ] Configurar concursos ativos
- [ ] Cadastrar escolas participantes
- [ ] Cadastrar salas das escolas
- [ ] Abrir cadastro de fiscais
- [ ] Configurar termos de aceite

### **Durante o Uso**
- [ ] Monitorar cadastros de fiscais
- [ ] Aprovar fiscais adequados
- [ ] Alocar fiscais em escolas/salas
- [ ] Gerar listas de presença
- [ ] Controlar pagamentos
- [ ] Gerar relatórios necessários

---

## 🎯 **Fluxo de Trabalho Recomendado**

### **1. Preparação**
1. **Cadastrar Escolas**: Adicionar todas as escolas participantes
2. **Cadastrar Salas**: Adicionar salas de cada escola
3. **Configurar Concurso**: Definir datas, horários e valores
4. **Abrir Cadastro**: Permitir cadastro de fiscais

### **2. Cadastro e Aprovação**
1. **Monitorar Cadastros**: Verificar fiscais que se cadastram
2. **Aprovar Fiscais**: Aprovar fiscais adequados
3. **Alocar Fiscais**: Distribuir fiscais pelas escolas/salas

### **3. Controle de Presença**
1. **Gerar Lista de Treinamento**: Para reunião de treinamento
2. **Gerar Lista de Presença**: Para o dia da prova
3. **Controlar Comparecimento**: Marcar presenças

### **4. Pagamentos**
1. **Gerar Lista de Pagamentos**: Com valores e dados
2. **Emitir Recibos**: Para cada fiscal
3. **Controlar Pagamentos**: Marcar pagamentos realizados

### **5. Relatórios Finais**
1. **Relatório de Comparecimento**: Estatísticas finais
2. **Relatório Financeiro**: Resumo dos pagamentos
3. **Relatório Completo**: Tudo do concurso

---

## 📞 **Suporte**

Para dúvidas ou problemas:
- **Email**: suporte@idh.com
- **Telefone**: (11) 3333-3333
- **Horário**: Segunda a Sexta, 8h às 18h

---

**✅ Sistema Completo e Funcional!**

**Status**: ✅ **100% OPERACIONAL**
**Versão**: 2.0 - Com todas as funcionalidades
**Data**: 10/07/2025 