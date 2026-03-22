# Task 9.0: Polimento, README e checklist de release

## Informações Gerais

**Status**: ✅ Concluída
**Prioridade**: Média
**Dependências**: Task 8.0
**Assignee**: Não atribuído

---

## Objetivo

Consolidar a biblioteca para uso interno: documentar no `README.md` como criar instâncias isoladas, carregar `config/pzpdf.php`, configurar cache e executar testes serial/paralelo; aplicar checklist de prefixo `PzPdf` em tipos e arquivos públicos; revisar dependências efetivas no `composer.json`; e garantir que os comandos documentados funcionem como gate de release interna.

---

## Contexto

O PRD pede documentação mínima de uso (RF-05, seção 7.3) e critérios de sucesso de onboarding. A Tech Spec §10.1 lista `README.md` como previsto e §11.1 cita polimento final.

**Referências**:
- PRD: `tasks/prd-pzpdf-wrapper-fpdf/prd.md` — RF-05, RNF-01, seção 12.2
- Tech Spec: `tasks/prd-pzpdf-wrapper-fpdf/techspec.md` — Seções 7.1, 10.1, 11.1

---

## Escopo

### O que ESTÁ no escopo:
- ✅ `README.md` na raiz do repo `fpdf` com: requisitos PHP/extensões, instalação Composer, exemplo `PzPdfFactory::make()`, notas de isolamento e concorrência (`PZPDF_CACHE_DIR`, `TEST_TOKEN`), execução de testes (`phpunit`, `paratest`)
- ✅ Checklist automatizável ou script simples que valide que todo arquivo PHP público em `src/` declara classes prefixadas `PzPdf` (excluindo namespaces e arquivos puramente internos se estiverem em pasta documentada como internal)
- ✅ Revisão final de `composer.json` (dependências realmente usadas)
- ✅ Ajustes cosméticos de PHPDoc faltantes **apenas** onde necessário para completar o checklist de release

### O que NÃO está no escopo:
- ❌ Implementar novas features além da paridade
- ❌ Publicação open-source obrigatória
- ❌ Alterações no `api`

---

## Subtarefas

### 1. README
**Descrição**: Escrever seções claras para desenvolvedor júnior: instalação, primeiro PDF, config, testes paralelos, limitações.
**Arquivos afetados**:
- `README.md`

---

### 2. Checklist de prefixo e superfície pública
**Descrição**: Executar busca por violações de RF-04; corrigir renomeações pontuais se encontradas.
**Arquivos afetados**:
- Possíveis renomeações em `src/` se alguma classe pública estiver fora do padrão

---

### 3. Scripts Composer
**Descrição**: Adicionar `test:parallel` ou documentar comando equivalente no README + `composer.json` scripts.
**Arquivos afetados**:
- `composer.json`

---

### 4. Testes Unitários
**Descrição**: Se o checklist for implementado como script PHP em `bin/` ou teste dedicado, cobrir com teste unitário mínimo.

**Arquivos de teste**:
- `tests/Unit/Tooling/PzPdfNamingConventionTest.php` (opcional, se implementado verificador em PHP)

**Casos de teste obrigatórios**:
1. **Nenhuma classe pública em `src/` fora da convenção**
   - **Cenário**: Reflexão ou parse de arquivos em `src/` (limitado ao que for viável)
   - **Expectativa**: Falha se arquivo público não seguir prefixo

**Cobertura mínima**: N/A para README; se houver script verificador, ≥ 70% do script

---

### 5. Testes de Integração
**Descrição**: Gate final documentado = suíte completa.

**Arquivos de teste**:
- Reutiliza toda a suíte da Task 8.0; opcionalmente `tests/Integration/Release/FullSuiteDocumentationTest.php` que apenas documenta comandos (evitar testes vazios — preferir validação real via CI)

**Cenários de teste obrigatórios**:
1. **Comandos README executáveis**
   - **Fluxo**: Seguir seção “Validação” do README em ambiente limpo
   - **Expectativa**: Comandos coincidem com scripts `composer.json` e funcionam

**Nota**: Este cenário pode ser validação manual checklist no PR + CI, sem novo teste automatizado se redundante.

---

## Critérios de Aceitação

- [x] Todos os arquivos criados/modificados conforme especificado
- [x] Código segue **PSR-12**
- [x] Tipagem PHP sem uso de `mixed` em novos artefatos desta task
- [x] Todos os testes unitários passando
- [x] Todos os testes de integração passando
- [ ] `README.md` revisado por par técnico
- [ ] Code review aprovado
- [x] Checklist RF-04 concluído sem pendências não justificadas
- [x] Sem erros de lint/type-check

---

## Entregáveis

**Arquivos Criados**:
- [x] `README.md`
- [x] `bin/check-pzpdf-prefix.php` e testes em `tests/Unit/Tooling/`

**Arquivos Modificados**:
- [x] `composer.json` (script `check:prefix`)
- [x] `tests/Integration/Service/PzPdfCacheTokenIsolationTest.php` (restauração de env após Paratest)

**Documentação**:
- [x] README como documentação principal de onboarding

---

## Guia de Implementação

### Passo 1: Escrever README com exemplos reais
Copiar trechos que passam nos testes de integração existentes.

### Passo 2: Rodar suíte completa
```bash
composer test
./vendor/bin/paratest -c phpunit.xml.dist
```

### Passo 3: Checklist de prefixo
```bash
# Exemplo: ripgrep manual
rg '^class (?!PzPdf)' src/
```

---

## Validação

### Checklist de Validação Manual:
- [ ] README menciona limitações de cache compartilhado entre workers
- [ ] README não referencia caminhos absolutos pessoais
- [ ] Scripts `composer test` e paratest documentados estão sincronizados

### Comandos de Validação:
```bash
composer install
composer test
./vendor/bin/paratest -c phpunit.xml.dist
```

---

## Riscos e Mitigações

| Risco | Impacto | Probabilidade | Mitigação |
|-------|---------|---------------|-----------|
| README desatualizar rápido | Médio | Média | Exemplos copiados de testes automatizados |
| Checklist manual esquecido | Médio | Média | Transformar em teste ou job de CI |

---

## Notas Adicionais

- Esta task fecha o PRD RF-10 “baseline de release interna” em conjunto com a suíte verde da Task 8.0.
- Não expandir escopo para benchmarks; se executados, apenas mencionar resultados como referência opcional.

---

## Histórico

| Data | Autor | Mudança |
|------|-------|---------|
| 2026-03-22 | Planejamento | Criação da tarefa |
| 2026-03-22 | Implementação | README, `check:prefix`, scanner RF-04, correção teste de token sob Paratest |
