# Task 4.0: PzPdfFactory e fachada PzPdf (MVP de documento)

## Informações Gerais

**Status**: ✅ Concluída
**Prioridade**: Alta
**Dependências**: Task 3.0
**Assignee**: Não atribuído

---

## Objetivo

Expor à aplicação consumidora a entrada oficial da biblioteca: `PzPdfFactory::make()` criando **nova instância isolada** por chamada e a fachada fluente `PzPdf` com operações de documento equivalentes às da referência (páginas, texto, imagens, desenho básico, permissões/metadados de exibição se houver), além dos modos de obtenção de bytes necessários aos testes portados — **sem** `getAdapter()` público e sem `AdapterType`.

---

## Contexto

Esta task fecha o fluxo principal do PRD (caso de uso 5.1) e habilita RF-05, RF-07 e parte de RF-01. A Tech Spec define remoção de `getAdapter()` e substituição por asserções sobre `PzPdf` ou saída (§2.3, §8.1).

**Referências**:
- PRD: `tasks/prd-pzpdf-wrapper-fpdf/prd.md` — RF-01, RF-05, RF-07, RN-04
- Tech Spec: `tasks/prd-pzpdf-wrapper-fpdf/techspec.md` — Seções 2.1, 2.2, 2.3, 4.1

---

## Escopo

### O que ESTÁ no escopo:
- ✅ `PzPdfFactory` com método estático `make()` (ou equivalente) retornando `PzPdf` novo
- ✅ Classe `PzPdf` pública com métodos fluentes alinhados à referência para o núcleo do documento
- ✅ Delegação ao engine interno da Task 3.0, sem vazar tipos internos na assinatura pública
- ✅ Modos de saída equivalentes aos usados na referência (string, arquivo, `Output` com flags — conforme testes)
- ✅ Configuração de header/footer/callbacks se presente na referência

### O que NÃO está no escopo:
- ❌ Builders de tabela/lista completos (Task 5.0)
- ❌ Serviços TTF e cache (Task 6.0)
- ❌ `PzPdfTemplateWriter` (Task 7.0)

---

## Subtarefas

### 1. PzPdfFactory
**Descrição**: Garantir que cada chamada `make()` produz engine FPDF novo e estado não compartilhado entre instâncias.
**Arquivos afetados**:
- `src/PzPdfFactory.php`

**Implementação**:
- Proibir singletons mutáveis no factory

---

### 2. Fachada PzPdf — núcleo
**Descrição**: Portar métodos da referência em grupos: documento, página, margens, fonte core (não-TTF se separado), texto, imagem, linhas/retângulos, estado gráfico.
**Arquivos afetados**:
- `src/PzPdf.php`

---

### 3. Saída e permissões
**Descrição**: Implementar `Output`/equivalentes e opções de permissão/zoom/layout se a referência expuser.
**Arquivos afetados**:
- `src/PzPdf.php`
- Possivelmente enums já criados na Task 2.0

---

### 4. Testes Unitários
**Descrição**: Validar factory, isolamento superficial de estado e regras puras da fachada (sem arquivo real quando mockável).

**Arquivos de teste**:
- `tests/Unit/PzPdfFactoryTest.php`
- `tests/Unit/PzPdfFacadeTest.php` (nome ajustável)

**Casos de teste obrigatórios**:
1. **Duas instâncias não compartilham o mesmo objeto engine**
   - **Cenário**: `make()` duas vezes; comparar identidade do objeto interno via reflexão **apenas em teste** ou via comportamento (ex.: primeira instância não altera página da segunda após operações)
   - **Expectativa**: Isolamento observável

2. **Fluent interface retorna a mesma instância onde aplicável**
   - **Cenário**: Encadear 2–3 métodos fluentes
   - **Expectativa**: Mesma instância de `PzPdf`

**Cobertura mínima**: ≥ 75% de `PzPdfFactory.php` e ≥ 60% de `PzPdf.php` nesta task (aumento na Task 8.0 com porte)

---

### 5. Testes de Integração
**Descrição**: Gerar PDFs reais cobrindo o MVP.

**Arquivos de teste**:
- `tests/Integration/PzPdf/PzPdfGenerationTest.php`

**Cenários de teste obrigatórios**:
1. **Fluxo fábrica → texto → string binária**
   - **Fluxo**: `PzPdfFactory::make()` → adicionar página → escrever texto → obter string
   - **Expectativa**: PDF válido mínimo (header `%PDF`)

2. **Escrita em arquivo temporário**
   - **Fluxo**: Gerar PDF em `sys_get_temp_dir()` com nome único
   - **Expectativa**: Arquivo existe, tamanho > 0, legível

---

## Critérios de Aceitação

- [x] Todos os arquivos criados/modificados conforme especificado
- [x] Código segue **PSR-12** e tipagem estrita
- [x] Tipagem PHP sem uso de `mixed` na API pública nova
- [x] Todos os testes unitários passando
- [x] Todos os testes de integração passando
- [x] Cobertura mínima atingida para factory/fachada conforme acima (verificar localmente com extensão de cobertura)
- [ ] Code review aprovado
- [x] PHPDoc nos métodos públicos da fachada
- [x] Sem erros de lint/type-check

---

## Entregáveis

**Arquivos Criados**:
- [x] `src/PzPdfFactory.php`
- [x] `src/PzPdf.php`
- [x] `tests/Unit/PzPdfFactoryTest.php`
- [x] `tests/Unit/PzPdfFacadeTest.php`
- [x] `tests/Integration/PzPdf/PzPdfGenerationTest.php`

**Arquivos Modificados**:
- [x] Possíveis ajustes em `src/Internal/...` para métodos necessários à fachada (nenhum necessário — engine Task 3.0 já cobria a fachada)

**Documentação**:
- [x] Docblocks na factory explicando isolamento por requisição/job

---

## Guia de Implementação

### Passo 1: Implementar factory e fachada mínima
Compor engine interno criado no construtor de `PzPdf`.

### Passo 2: Portar métodos em lotes
Sincronizar ordem com testes que serão portados na Task 8.0.

### Passo 3: Rodar testes
```bash
composer test
```

---

## Validação

### Checklist de Validação Manual:
- [ ] Não existe método público `getAdapter()`
- [ ] Não existe tipo público `AdapterType`

### Comandos de Validação:
```bash
./vendor/bin/phpunit tests/Unit/PzPdfFactoryTest.php tests/Unit/PzPdfFacadeTest.php
./vendor/bin/phpunit tests/Integration/PzPdf
```

---

## Riscos e Mitigações

| Risco | Impacto | Probabilidade | Mitigação |
|-------|---------|---------------|-----------|
| API fluente divergir da referência | Alto | Média | Checklist cruzado com assinaturas na referência antes do porte de testes |
| Estado de página compartilhado acidentalmente | Alto | Baixa | Testes de isolamento + revisão de propriedades estáticas |

---

## Notas Adicionais

- Métodos que dependem de TTF podem permanecer como stubs que lançam `PzPdfUnsupportedOperationException` até a Task 6.0 **somente se** os testes da Task 4.0 não os exercitarem; caso contrário, completar na Task 6.0 e manter esta task focada no MVP sem TTF.

---

## Histórico

| Data | Autor | Mudança |
|------|-------|---------|
| 2026-03-22 | Planejamento | Criação da tarefa |
