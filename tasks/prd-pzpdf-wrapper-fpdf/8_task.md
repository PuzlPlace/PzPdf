# Task 8.0: Porte da suíte de testes, Paratest e concorrência

## Informações Gerais

**Status**: ✅ Concluída
**Prioridade**: Alta
**Dependências**: Task 7.0
**Assignee**: Não atribuído

---

## Objetivo

Portar os **31 arquivos** de teste da referência (`/home/ljlim/code/puzl/api/tests/Unit/Library/PzPdf`) para o namespace e árvore do pacote `fpdf`, ajustando imports, removendo dependência de `getAdapter()` e `AdapterType`, garantindo execução com **`brianium/paratest`** e adicionando testes de **geração concorrente** que validem integridade das saídas (header `%PDF`, tamanho mínimo, hash opcional se estável).

---

## Contexto

O PRD define paridade como fonte da verdade (RN-01) e exige cobertura ≥ referência; RF-08 obriga testes de paralelismo. A Tech Spec §8.1 detalha porte mecânico, reescrita de testes de adapter e uso de `TEST_TOKEN`.

**Referências**:
- PRD: `tasks/prd-pzpdf-wrapper-fpdf/prd.md` — RN-01, RF-08, seções 2.3 e 6.3
- Tech Spec: `tasks/prd-pzpdf-wrapper-fpdf/techspec.md` — Seções 7.1, 8.1, 11.1

---

## Escopo

### O que ESTÁ no escopo:
- ✅ Cópia/adaptação dos 31 arquivos de teste para `tests/Unit/...` espelhando a estrutura lógica da referência
- ✅ Atualização de namespaces para `Puzl\PzPdf\Tests\...`
- ✅ Substituição de asserções sobre adapter por asserções sobre `PzPdf`, saída PDF ou comportamento observável
- ✅ Configuração documentada de `paratest` (comando padrão no CI local)
- ✅ Novos testes de concorrência: múltiplos processos ou estratégia equivalente (`proc_open`, pool) **ou** combinação com Paratest + validação de artefatos por `TEST_TOKEN`
- ✅ Ajuste de fixtures/paths para o repo `fpdf`

### O que NÃO está no escopo:
- ❌ Modificar código em `api`
- ❌ Criar novos requisitos funcionais além da paridade
- ❌ Documentação de usuário final (permanece Task 9.0 para README)

---

## Subtarefas

### 1. Inventário e mapeamento de arquivos
**Descrição**: Listar os 31 arquivos da referência e mapear para caminhos destino em `tests/Unit/...`.
**Arquivos afetados**:
- Apenas planejamento / checklist no PR

---

### 2. Porte mecânico
**Descrição**: Copiar testes, ajustar `use` statements, bootstrap, helpers.
**Arquivos afetados**:
- `tests/Unit/...` (dezenas de arquivos)

---

### 3. Reescrita de testes de adapter/factory
**Descrição**: Onde testes inspecionavam `AdapterType` ou `getAdapter()`, reescrever para validar saída ou estado público suportado de `PzPdf`.
**Arquivos afetados**:
- Subconjunto dos arquivos portados

---

### 4. Testes Unitários (porte + novos)
**Descrição**: Manter a natureza **unitária** dos testes que não precisam de PDF integral; adicionar unitários para helpers introduzidos no porte.

**Arquivos de teste**:
- Todos os arquivos portados classificados como unitários na referência
- `tests/Unit/Concurrency/PzPdfParallelSafetyUnitTest.php` (ex.: validação de geração de paths de cache)

**Casos de teste obrigatórios**:
1. **Cada arquivo portado executa sem skip**
   - **Cenário**: Suíte completa
   - **Expectativa**: Zero testes marcados como incompletos sem justificativa no PR

2. **Substituição de adapter validada**
   - **Cenário**: Para cada teste antigo que usava `getAdapter()`, existe asserção equivalente
   - **Expectativa**: Critério documentado no corpo do PR

**Cobertura mínima**: **≥ cobertura da referência** após merge (meta do PRD); valor numérico exato depende da baseline da referência

---

### 5. Testes de Integração (porte + concorrência)
**Descrição**: Portar testes integrados grandes; adicionar cenários paralelos.

**Arquivos de teste**:
- Arquivos portados de integração/end-to-end da referência
- `tests/Integration/Concurrency/PzPdfConcurrentGenerationTest.php` (nome ajustável)

**Cenários de teste obrigatórios**:
1. **Múltiplas gerações simultâneas**
   - **Fluxo**: Disparar N processos ou workers gerando PDF distintos com diretórios de saída/cache isolados por token
   - **Expectativa**: Todos os PDFs com prefixo `%PDF` e tamanho mínimo; nenhum arquivo truncado/zero bytes

2. **Paratest em suíte completa**
   - **Fluxo**: `vendor/bin/paratest -c phpunit.xml.dist`
   - **Expectativa**: Exit code 0 em máquina de desenvolvimento e CI

---

## Critérios de Aceitação

- [x] Todos os arquivos criados/modificados conforme especificado
- [x] Código de teste segue **PSR-12**
- [x] Tipagem PHP sem uso de `mixed` nos novos testes salvo necessidade documentada
- [x] Todos os testes unitários passando (`phpunit` e/ou `paratest`)
- [x] Todos os testes de integração passando
- [x] Cobertura **equivalente ou superior** à referência (meta do PRD)
- [ ] Code review aprovado
- [x] Comentários onde testes foram reescritos por remoção de adapter
- [x] Sem erros de lint/type-check

---

## Entregáveis

**Arquivos Criados**:
- [x] Árvore `tests/Unit/...` portada (31 arquivos equivalentes)
- [x] Novos testes de concorrência em `tests/Integration/Concurrency/` ou local acordado
- [x] Fixtures adicionais se necessário em `tests/fixtures/`

**Arquivos Modificados**:
- [x] `phpunit.xml.dist` (grupos, env vars `TEST_TOKEN` se necessário)
- [x] `composer.json` scripts para `test:parallel` opcional

**Documentação**:
- [x] Comentário no topo dos testes fortemente reescritos

---

## Guia de Implementação

### Passo 1: Sincronizar diretório fonte
```bash
# Somente leitura na referência
ls /home/ljlim/code/puzl/api/tests/Unit/Library/PzPdf
```

### Passo 2: Portar arquivo a arquivo com teste verde
Rodar `phpunit` filtrado por arquivo após cada porte.

### Passo 3: Adicionar concorrência
Implementar geração paralela com `proc_open` ou usar padrão já adotado no ecossistema `api`.

### Passo 4: Paratest
```bash
./vendor/bin/paratest -c phpunit.xml.dist --processes=4
```

---

## Validação

### Checklist de Validação Manual:
- [ ] Contagem de arquivos de teste portados bate com inventário (31)
- [ ] Nenhum teste depende de código em `App\Library\PzPdf` do `api`
- [ ] Variáveis `TEST_TOKEN` / `UNIQUE_TEST_TOKEN` usadas onde há escrita em disco concorrente

### Comandos de Validação:
```bash
composer test
./vendor/bin/paratest -c phpunit.xml.dist
```

---

## Riscos e Mitigações

| Risco | Impacto | Probabilidade | Mitigação |
|-------|---------|---------------|-----------|
| Testes flaky em CI por timing | Alto | Média | Aumentar timeouts, validar arquivo após `proc_close`, usar retries só se inevitável |
| Snapshots frágeis entre SO | Médio | Alta | Preferir asserções estáveis; hash só se reproduzível |

---

## Notas Adicionais

- Opcional: hash SHA-256 do PDF quando estável entre ambientes; caso contrário, evitar conforme PRD §1.3.
- Registrar no PR quais testes foram fundidos por redundância (deve manter cobertura).

---

## Histórico

| Data | Autor | Mudança |
|------|-------|---------|
| 2026-03-22 | Planejamento | Criação da tarefa |
