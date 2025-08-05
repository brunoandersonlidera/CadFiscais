# Nova Página de Alocar Fiscais

## Problema Identificado

O menu "Locais -> Alocar Fiscais" estava chamando a mesma página do menu "Fiscais -> Listar Fiscais" (`admin/fiscais.php`), não oferecendo uma interface específica para alocação.

## Solução Implementada

Criada uma nova página `admin/alocar_fiscais.php` específica para alocação de fiscais, com foco em:

### Funcionalidades Principais

1. **Filtros Específicos para Alocação**
   - Por concurso
   - Por status de alocação (alocado/não alocado)

2. **Estatísticas Visuais**
   - Total de fiscais
   - Fiscais alocados
   - Fiscais não alocados
   - Contagem de mulheres

3. **Informações de Alocação**
   - Escola e sala onde está alocado
   - Data e horário da alocação
   - Tipo de alocação (sala, corredor, entrada, etc.)
   - Status visual com badges coloridos

4. **Botão WhatsApp**
   - Contato direto com o fiscal
   - Mensagem pré-formatada

5. **Ações Específicas**
   - Alocar fiscal (para não alocados)
   - Re-alocar fiscal (para já alocados)
   - Ver detalhes completos
   - Editar fiscal

### Estrutura da Tabela

| Coluna | Descrição |
|--------|-----------|
| ID | Identificador do fiscal |
| Nome | Nome, idade, gênero |
| Contato | Telefone, email, botão WhatsApp |
| Concurso | Concurso ao qual está vinculado |
| Status Alocação | Badge visual (alocado/não alocado) |
| Escola/Sala | Informações de alocação (se aplicável) |
| Ações | Botões para alocar, ver detalhes, editar |

### Diferenças das Páginas

| Página | URL | Função |
|--------|-----|--------|
| Listar Fiscais | `admin/fiscais.php` | Lista todos os fiscais com opções gerais |
| Alocar Fiscais | `admin/alocar_fiscais.php` | Foco em alocação, apenas fiscais aprovados |
| Alocar Fiscal | `admin/alocar_fiscal.php` | Formulário para alocar fiscal específico |

## Arquivos Modificados

### 1. `admin/alocar_fiscais.php` (NOVO)
- Página específica para alocação
- Consulta SQL otimizada para alocações
- Interface focada em alocação

### 2. `includes/header.php`
- Atualizado menu "Locais -> Alocar Fiscais"
- Agora aponta para `admin/alocar_fiscais.php`

## Consulta SQL Principal

```sql
SELECT 
    f.*,
    c.titulo as concurso_titulo,
    TIMESTAMPDIFF(YEAR, f.data_nascimento, CURDATE()) as idade,
    a.escola_id,
    a.sala_id,
    a.data_alocacao,
    a.horario_alocacao,
    a.tipo_alocacao,
    a.observacoes as observacoes_alocacao,
    e.nome as escola_nome,
    s.nome as sala_nome,
    CASE 
        WHEN a.id IS NOT NULL THEN 'alocado'
        ELSE 'nao_alocado'
    END as status_alocacao
FROM fiscais f
LEFT JOIN concursos c ON f.concurso_id = c.id
LEFT JOIN alocacoes_fiscais a ON f.id = a.fiscal_id AND a.status = 'ativo'
LEFT JOIN escolas e ON a.escola_id = e.id
LEFT JOIN salas s ON a.sala_id = s.id
WHERE f.status = 'aprovado'
```

## Funcionalidades Especiais

### Botão WhatsApp
```javascript
<a href="https://wa.me/55<?= preg_replace('/\D/', '', $fiscal['celular']) ?>?text=Olá <?= urlencode($fiscal['nome']) ?>! Sou do IDH e gostaria de falar sobre o concurso." 
   target="_blank" class="btn btn-sm btn-success">
    <i class="fab fa-whatsapp"></i> WhatsApp
</a>
```

### Status Visual
- **Alocado**: Badge verde com ícone de check
- **Não Alocado**: Badge amarelo com ícone de relógio

### Filtros Dinâmicos
- Por concurso (dropdown)
- Por status de alocação (alocado/não alocado)

## Teste da Implementação

Para testar a nova funcionalidade:

1. Acesse `http://localhost:8000/admin/alocar_fiscais.php`
2. Verifique os filtros funcionando
3. Teste o botão WhatsApp
4. Verifique as estatísticas
5. Teste as ações de alocar/re-alocar

## Data da Implementação

Implementação realizada em: <?= date('d/m/Y H:i:s') ?>

## Observações

- A página foca apenas em fiscais com status 'aprovado'
- Interface otimizada para o processo de alocação
- Mantém compatibilidade com a página individual de alocação
- WhatsApp integrado para contato direto
- Estatísticas em tempo real 