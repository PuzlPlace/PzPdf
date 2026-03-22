# Task 5.0: Componentes de layout (tabela, lista e writers)

## Informações Gerais

**Status**: ✅ Concluída
**Prioridade**: Alta
**Dependências**: Task 4.0
**Assignee**: Não atribuído

---

## Objetivo

Entregar os componentes públicos de construção de tabelas e listas (`PzPdfTableBuilder`, `PzPdfListBuilder`, `PzPdfTableWriter`, `PzPdfTableColumn`) com paridade funcional com a referência, permitindo montar blocos repetitivos de relatórios via API fluente e integração direta com a fachada `PzPdf`.

---

## Contexto

O PRD lista tabelas/listas como parte da paridade (RF-01, RF-06). A Tech Spec nomeia explicitamente esses componentes na superfície pública. Esta task pode executar em **paralelo** com a Task 6.0 após a Task 4.0.

**Referências**:
- PRD: `tasks/prd-pzpdf-wrapper-fpdf/prd.md` — RF-01, RF-06, caso de uso 5.1 (fluxo alternativo)
- Tech Spec: `tasks/prd-pzpdf-wrapper-fpdf/techspec.md` — Seções 1.2, 10.1

---

## Escopo

### O que ESTÁ no escopo:
- ✅ `PzPdfTableColumn` com validação de largura/alinhamento/estilo coerente com a referência
- ✅ `PzPdfTableBuilder` para definir colunas, cabeçalhos e linhas
- ✅ `PzPdfTableWriter` para renderizar a tabela em `PzPdf` com quebras de página corretas quando aplicável
- ✅ `PzPdfListBuilder` para listas numeradas/marcadores conforme referência
- ✅ Prefixo `PzPdf` e arquivos PSR-4 alinhados aos nomes das classes

### O que NÃO está no escopo:
- ❌ Resolução TTF e cache (Task 6.0) — usar fonte core até integração
- ❌ Template corporativo (Task 7.0)
- ❌ Porte completo dos testes da referência (Task 8.0)

---

## Subtarefas

### 1. PzPdfTableColumn
**Descrição**: Portar propriedades e métodos da coluna; validar somas de larguras ou comportamento flex conforme referência.
**Arquivos afetados**:
- `src/Component/PzPdfTableColumn.php`

---

### 2. PzPdfTableBuilder e PzPdfTableWriter
**Descrição**: Implementar encadeamento fluente e escrita no `PzPdf`; suportar multi-página.
**Arquivos afetados**:
- `src/Component/PzPdfTableBuilder.php`
- `src/Component/PzPdfTableWriter.php`

---

### 3. PzPdfListBuilder
**Descrição**: Implementar listas com recuo, numeração e quebras conforme referência.
**Arquivos afetados**:
- `src/Component/PzPdfListBuilder.php`

---

### 4. Testes Unitários
**Descrição**: Cobrir cálculos de layout, validações e formatação sem renderizar PDF quando possível.

**Arquivos de teste**:
- `tests/Unit/Component/PzPdfTableColumnTest.php`
- `tests/Unit/Component/PzPdfTableBuilderTest.php`
- `tests/Unit/Component/PzPdfTableWriterTest.php`
- `tests/Unit/Component/PzPdfListBuilderTest.php`

**Casos de teste obrigatórios**:
1. **Coluna rejeita largura inválida**
   - **Cenário**: Largura zero ou negativa
   - **Expectativa**: Exceção documentada

2. **Writer calcula altura de linha mínima**
   - **Cenário**: Linha com texto que precisa wrap
   - **Expectativa**: Altura >= altura de fonte conforme regra da referência

**Cobertura mínima**: ≥ 80% dos arquivos em `src/Component/` introduzidos nesta task

---

### 5. Testes de Integração
**Descrição**: Renderizar PDFs com tabelas e listas reais.

**Arquivos de teste**:
- `tests/Integration/Component/PzPdfTableIntegrationTest.php`
- `tests/Integration/Component/PzPdfListIntegrationTest.php`

**Cenários de teste obrigatórios**:
1. **Tabela com múltiplas linhas e bordas**
   - **Fluxo**: `PzPdfFactory::make()` → builder com 3 colunas → 10 linhas → writer
   - **Expectativa**: PDF gerado; asserts estáveis (presença de texto das células em stream ou contagem de operações via camada test dupla se necessário)

2. **Lista com vários itens e subníveis (se suportado)**
   - **Fluxo**: Montar lista conforme API da referência
   - **Expectativa**: PDF sem exceção; conteúdo verificável

---

## Critérios de Aceitação

- [x] Todos os arquivos criados/modificados conforme especificado
- [x] **PSR-12** e `declare(strict_types=1);`
- [x] Tipagem PHP sem uso de `mixed` nas APIs públicas dos componentes
- [x] Todos os testes unitários passando
- [x] Todos os testes de integração passando
- [x] Cobertura >= 80% dos componentes novos
- [ ] Code review aprovado
- [x] PHPDoc nos métodos públicos dos builders/writers
- [x] Sem erros de lint/type-check

---

## Entregáveis

**Arquivos Criados**:
- [x] `src/Component/PzPdfTableColumn.php`
- [x] `src/Component/PzPdfTableBuilder.php`
- [x] `src/Component/PzPdfTableWriter.php`
- [x] `src/Component/PzPdfListBuilder.php`
- [x] Testes em `tests/Unit/Component/` e `tests/Integration/Component/`

**Arquivos Modificados**:
- [x] `src/PzPdf.php` (apenas se precisar expor métodos helper para componentes)

**Documentação**:
- [ ] Exemplos mínimos em docblocks dos builders

---

## Guia de Implementação

### Passo 1: Portar `TableColumn` e testes primeiro
Base estável para larguras.

### Passo 2: Builder → Writer
Garantir que writer usa apenas API pública de `PzPdf`.

### Passo 3: Lista
Alinhar recuo e numeração com a referência.

```bash
./vendor/bin/phpunit tests/Unit/Component tests/Integration/Component
```

---

## Validação

### Checklist de Validação Manual:
- [ ] Quebra de página não corrompe linhas seguintes (cenário com muitas linhas)
- [ ] Nenhum estado estático mutável compartilhado entre duas instâncias de builder

### Comandos de Validação:
```bash
composer test
```

---

## Riscos e Mitigações

| Risco | Impacto | Probabilidade | Mitigação |
|-------|---------|---------------|-----------|
| Cálculo de altura divergir na quebra de página | Alto | Média | Teste de integração com muitas linhas; comparar com saída da referência na Task 8.0 |
| Performance em tabelas grandes | Médio | Média | Evitar alocações desnecessárias; benchmark opcional alinhado ao PRD RNF-03 |

---

## Notas Adicionais

- Executar em paralelo com Task 6.0 após Task 4.0; integrar ambas antes da Task 7.0 se o template depender de fontes/tabelas.

---

## Histórico

| Data | Autor | Mudança |
|------|-------|---------|
| 2026-03-22 | Planejamento | Criação da tarefa |
