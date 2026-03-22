# Tasks - Wrapper PzPdf (FPDF) no repositório fpdf

## Visão Geral

**PRD**: `tasks/prd-pzpdf-wrapper-fpdf/prd.md`
**Tech Spec**: `tasks/prd-pzpdf-wrapper-fpdf/techspec.md`

**Resumo**: Implementar biblioteca PHP 8.2 autônoma na raiz do repositório `fpdf`, com fachada fluente `PzPdf*` acoplada diretamente ao `fawno/fpdf`, paridade funcional e de testes com a referência somente leitura em `api`, prefixo `PzPdf` em tipos e arquivos públicos, isolamento entre instâncias para uso concorrente, e suíte portada (31 arquivos) complementada por testes de paralelismo e Paratest.

---

## Lista de Tarefas

### Legenda
- ✅ Concluída
- 🚧 Em Progresso
- ⏳ Aguardando
- 📦 Pronta para Iniciar

---

### Task 1.0: Bootstrap do pacote e pipeline de testes
**Status**: ✅
**Dependências**: Nenhuma
**Descrição**: Configurar Composer, autoload PSR-4, PHPUnit 10, Paratest, artefatos de projeto e um teste fumaça que comprove execução local e em CI.

**Entregáveis**:
- [x] `composer.json` com dependências alinhadas à Tech Spec
- [x] `phpunit.xml.dist`, `.gitignore` e script `composer test`
- [x] Testes unitários (autoload/namespaces)
- [x] Testes de integração (smoke de suíte)

**Arquivo**: `tasks/prd-pzpdf-wrapper-fpdf/1_task.md`

---

### Task 2.0: Domínio base público (enums, VOs e exceções)
**Status**: ✅
**Dependências**: Task 1.0
**Descrição**: Implementar enums, value objects e exceções públicas prefixadas `PzPdf*`, sem expor `AdapterType` nem contratos de motor alternativo.

**Entregáveis**:
- [x] Enums e VOs em `Puzl\PzPdf\Enum` e `Puzl\PzPdf\ValueObject`
- [x] Exceções `PzPdfException` e correlatas
- [x] Testes unitários
- [x] Testes de integração (composição mínima)

**Arquivo**: `tasks/prd-pzpdf-wrapper-fpdf/2_task.md`

---

### Task 3.0: Motor interno FPDF (bridge e engine final)
**Status**: ✅
**Dependências**: Task 2.0
**Descrição**: Portar lógica do adapter atual para classes internas `final` (bridge/engine), incluindo UTF-8, desenho e suporte a alpha/gradiente quando exigido pela paridade.

**Entregáveis**:
- [x] Bridge/engine internos acoplados ao `fawno/fpdf`
- [x] Traits ou módulos internos para alpha/gradiente, se necessário
- [x] Testes unitários
- [x] Testes de integração (PDF mínimo válido)

**Arquivo**: `tasks/prd-pzpdf-wrapper-fpdf/3_task.md`

---

### Task 4.0: PzPdfFactory e fachada PzPdf (MVP de documento)
**Status**: ✅
**Dependências**: Task 3.0
**Descrição**: Expor `PzPdfFactory::make()` e fachada `PzPdf` com API fluente para documento, página, texto, imagens e modos de saída equivalentes aos usados pelos testes da referência, sem `getAdapter()` público.

**Entregáveis**:
- [x] `PzPdfFactory` e `PzPdf` públicos
- [x] Fluxo mínimo completo de geração até bytes/arquivo
- [x] Testes unitários
- [x] Testes de integração (end-to-end leve)

**Arquivo**: `tasks/prd-pzpdf-wrapper-fpdf/4_task.md`

---

### Task 5.0: Componentes de layout (tabela, lista e writers)
**Status**: ✅
**Dependências**: Task 4.0
**Descrição**: Implementar builders e writers de tabela/lista com paridade comportamental da referência.

**Entregáveis**:
- [x] `PzPdfTableBuilder`, `PzPdfListBuilder`, `PzPdfTableWriter`, `PzPdfTableColumn`
- [x] Testes unitários
- [x] Testes de integração (PDFs com tabelas e listas)

**Arquivo**: `tasks/prd-pzpdf-wrapper-fpdf/5_task.md`

---

### Task 6.0: Serviços TTF, cache de fontes e resolução de imagem
**Status**: ✅
**Dependências**: Task 4.0
**Descrição**: Implementar resolução TTF, configuração de cache e resolução de tipo de imagem, com isolamento adequado para testes paralelos (`PZPDF_CACHE_DIR`, `TEST_TOKEN`).

**Entregáveis**:
- [x] `PzPdfTtfFontResolver`, `PzPdfTtfFontCacheConfig`, `PzPdfFpdfImageTypeResolver` (ou equivalentes prefixados)
- [x] Testes unitários
- [x] Testes de integração (PDF com TTF e imagens)

**Arquivo**: `tasks/prd-pzpdf-wrapper-fpdf/6_task.md`

---

### Task 7.0: Configuração portável e PzPdfTemplateWriter
**Status**: ✅
**Dependências**: Task 4.0, Task 5.0, Task 6.0
**Descrição**: Entregar `config/pzpdf.php` portátil, `PzPdfConfiguration` e `PzPdfTemplateWriter` desacoplado do Laravel, com injeção de configuração.

**Entregáveis**:
- [x] `config/pzpdf.php` e `PzPdfConfiguration`
- [x] `PzPdfTemplateWriter` abstrato em `Puzl\PzPdf\Templates`
- [x] Testes unitários
- [x] Testes de integração (template com config injetada)

**Arquivo**: `tasks/prd-pzpdf-wrapper-fpdf/7_task.md`

---

### Task 8.0: Porte da suíte de testes, Paratest e concorrência
**Status**: ✅
**Dependências**: Task 7.0
**Descrição**: Portar os 31 arquivos de teste da referência, reescrever dependências de adapter, habilitar Paratest e adicionar testes de geração simultânea com validação de integridade.

**Entregáveis**:
- [x] Árvore `tests/Unit/...` espelhando a referência, ajustada ao pacote
- [x] Configuração e documentação de execução com `paratest`
- [x] Testes unitários (porte + novos onde necessário)
- [x] Testes de integração (porte + concorrência)

**Arquivo**: `tasks/prd-pzpdf-wrapper-fpdf/8_task.md`

---

### Task 9.0: Polimento, README e checklist de release
**Status**: ✅
**Dependências**: Task 8.0
**Descrição**: Documentar uso mínimo, limitações de cache/concorrência, comandos de validação e checklist de prefixo `PzPdf` para baseline de release interna.

**Entregáveis**:
- [x] `README.md` com exemplos de factory, config e paralelismo
- [x] Checklist de nomenclatura e dependências efetivas
- [x] Testes unitários (regressão pontual, se aplicável)
- [x] Testes de integração (gate `phpunit` + `paratest` documentado)

**Arquivo**: `tasks/prd-pzpdf-wrapper-fpdf/9_task.md`

---

## Sequenciamento e Dependências

```
Task 1.0 (Bootstrap)
  ↓
Task 2.0 (Enums/VOs/Exceptions)
  ↓
Task 3.0 (Engine/Bridge interno)
  ↓
Task 4.0 (Factory + PzPdf)
  ├─→ Task 5.0 (Componentes)     ─┐
  └─→ Task 6.0 (Serviços)         ─┘  paralelas após 4.0
            ↓
         Task 7.0 (Config + Template)
            ↓
         Task 8.0 (Porte + concorrência)
            ↓
         Task 9.0 (README + checklist)
```

---

## Cronograma Sugerido

| Task | Descrição | Dependências | Pode Iniciar |
|------|-----------|--------------|--------------|
| 1.0 | Bootstrap do pacote e pipeline de testes | — | Imediato |
| 2.0 | Domínio base público | 1.0 | Após 1.0 |
| 3.0 | Motor interno FPDF | 2.0 | Após 2.0 |
| 4.0 | Factory e fachada PzPdf | 3.0 | Após 3.0 |
| 5.0 | Componentes de layout | 4.0 | Após 4.0 (paralela a 6.0) |
| 6.0 | Serviços TTF e imagem | 4.0 | Após 4.0 (paralela a 5.0) |
| 7.0 | Config e template | 4.0, 5.0, 6.0 | Após 5.0 e 6.0 |
| 8.0 | Porte da suíte e concorrência | 7.0 | Após 7.0 |
| 9.0 | Polimento e README | 8.0 | Após 8.0 |

---

## Notas Importantes

- Nenhum arquivo sob `/home/ljlim/code/puzl/api/` deve ser modificado por este trabalho; a referência é somente leitura para paridade e porte de testes.
- A superfície pública não deve expor padrão adapter nem interfaces cujo propósito seja trocar o motor de PDF; lógica equivalente permanece em classes internas `final`.
- Todos os tipos e arquivos PHP **públicos** da biblioteca devem usar o prefixo `PzPdf` (RF-04).
- Execução paralela de testes deve usar `brianium/paratest` e diretórios de cache isolados por `TEST_TOKEN` / `UNIQUE_TEST_TOKEN` quando aplicável (Tech Spec §7.1, §8.1).

---

## Checklist de Conclusão da Funcionalidade

- [ ] Todas as tarefas individuais concluídas
- [ ] Testes unitários passando (cobertura equivalente ou superior à referência, conforme PRD)
- [ ] Testes de integração passando
- [ ] Code review aprovado
- [ ] Documentação atualizada (`README` e docblocks da entrada pública)

---

## Histórico de Versões

| Versão | Data | Autor | Descrição |
|--------|------|-------|-----------|
| 1.0 | 2026-03-22 | Planejamento | Lista inicial de tarefas após aprovação do escopo em alto nível |
