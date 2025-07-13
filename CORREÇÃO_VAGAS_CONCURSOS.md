# Correção do Problema de Contagem de Vagas nos Concursos

## Problema Identificado

Na página `http://localhost:8000/admin/concursos.php`, a coluna 'Vagas' estava mostrando 0 para todas as quantidades informadas, mesmo tendo fiscais cadastrados e aprovados.

## Causa Raiz

A consulta SQL estava usando `f.status = 'ativo'` em vez de `f.status = 'aprovado'` para contar os fiscais cadastrados.

## Arquivos Corrigidos

### 1. `admin/concursos.php`
**Linha 58:** Alterado o filtro na consulta SQL
```sql
-- ANTES:
LEFT JOIN fiscais f ON c.id = f.concurso_id AND f.status = 'ativo'

-- DEPOIS:
LEFT JOIN fiscais f ON c.id = f.concurso_id AND f.status = 'aprovado'
```

### 2. `index.php`
**Linha 36:** Corrigido a contagem de fiscais na página inicial
```sql
-- ANTES:
SELECT COUNT(*) as total FROM fiscais WHERE status = 'ativo'

-- DEPOIS:
SELECT COUNT(*) as total FROM fiscais WHERE status = 'aprovado'
```

### 3. `teste_presenca.php`
**Linha 109:** Corrigido a contagem de fiscais no arquivo de teste
```sql
-- ANTES:
SELECT COUNT(*) as total FROM fiscais WHERE status = 'ativo'

-- DEPOIS:
SELECT COUNT(*) as total FROM fiscais WHERE status = 'aprovado'
```

## Resultado Esperado

Após as correções, a coluna 'Vagas' na página de concursos deve mostrar:
- O número correto de fiscais aprovados para cada concurso
- O formato: `X/Y` onde X é o número de fiscais aprovados e Y é o total de vagas disponíveis
- A barra de progresso deve refletir corretamente o percentual de ocupação

## Status dos Fiscais no Sistema

- **`pendente`**: Fiscal cadastrado mas ainda não aprovado
- **`aprovado`**: Fiscal aprovado e disponível para alocação
- **`reprovado`**: Fiscal reprovado
- **`cancelado`**: Fiscal cancelado

## Teste da Correção

Para testar se a correção funcionou:

1. Acesse `http://localhost:8000/admin/concursos.php`
2. Verifique se a coluna 'Vagas' agora mostra os números corretos
3. Execute o arquivo de teste: `http://localhost:8000/teste_concursos_vagas.php`

## Data da Correção

Correção aplicada em: <?= date('d/m/Y H:i:s') ?>

## Observações

- A consulta que verifica se há fiscais cadastrados antes de excluir um concurso foi mantida sem filtro de status, pois deve contar todos os fiscais (independente do status) para evitar a exclusão de concursos que possuem fiscais cadastrados.
- Outras consultas que usam `af.status = 'ativo'` estão corretas, pois se referem ao status das alocações, não ao status dos fiscais. 