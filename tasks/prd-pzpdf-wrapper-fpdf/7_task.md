# Task 7.0: Configuração portável e PzPdfTemplateWriter

## Informações Gerais

**Status**: ✅ Concluída
**Prioridade**: Alta
**Dependências**: Task 4.0, Task 5.0, Task 6.0
**Assignee**: Não atribuído

---

## Objetivo

Entregar o arquivo `config/pzpdf.php` com a **mesma árvore de chaves** do `api/config/pzpdf.php`, porém com defaults portáveis (sem depender de `env()`, `storage_path()` ou `public_path()` do Laravel), a classe imutável `PzPdfConfiguration` para acesso tipado/seguro aos valores, e a classe abstrata `PzPdfTemplateWriter` no namespace `Puzl\PzPdf\Templates`, permitindo subclasses testáveis que inicializam `PzPdf` com header/footer corporativo usando **injeção de configuração**.

---

## Contexto

A Tech Spec §1.2 e §2.3 exigem template desacoplado do Laravel e config portável. O PRD requer que desenvolvedores fora do Laravel consigam usar a biblioteca (RF-07, RF-09). Carbon é dependência para datas localizadas como na referência.

**Referências**:
- PRD: `tasks/prd-pzpdf-wrapper-fpdf/prd.md` — RF-07, RF-09, seção 8.3
- Tech Spec: `tasks/prd-pzpdf-wrapper-fpdf/techspec.md` — Seções 1.2, 2.1, 3.1, 10.1, 13.3

---

## Escopo

### O que ESTÁ no escopo:
- ✅ Arquivo `config/pzpdf.php` retornando array com estrutura espelhada; defaults usando `getenv()`, paths relativos ao pacote ou placeholders documentados
- ✅ `PzPdfConfiguration` `final readonly` com `fromArray()` e `get(string $dotPath, mixed $default = null)` conforme exemplo da Tech Spec **§3.1**
- ✅ `PzPdfTemplateWriter` abstrato portado da referência, adaptado para receber `PzPdfConfiguration` (ou array) no construtor / factory estática `fromConfigFile()`
- ✅ Uso de `nesbot/carbon` onde a referência formatar datas no template
- ✅ Métodos equivalentes a `initialize()`, cores base, etc.

### O que NÃO está no escopo:
- ❌ Service Provider Laravel ou alterações no `api`
- ❌ Porte completo dos testes da referência (Task 8.0)
- ❌ UI ou rotas HTTP

---

## Subtarefas

### 1. config/pzpdf.php
**Descrição**: Copiar chaves do arquivo da referência e substituir chamadas Laravel por valores portáveis; documentar no README (Task 9.0) como o app injeta paths ricos.
**Arquivos afetados**:
- `config/pzpdf.php`

---

### 2. PzPdfConfiguration
**Descrição**: Implementar VO de config com acesso por dot-notation; validação leve de tipos esperados nas chaves críticas.
**Arquivos afetados**:
- `src/Config/PzPdfConfiguration.php`

**Implementação**:
- O método `get` pode aceitar `mixed` como valor default e retorno conforme Tech Spec; **exceção** ao critério global “sem mixed” apenas nesta classe, documentada no PR

---

### 3. PzPdfTemplateWriter
**Descrição**: Portar métodos abstratos/concretos da referência; remover chamadas a `config()`; injetar dependências.
**Arquivos afetados**:
- `src/Templates/PzPdfTemplateWriter.php`

---

### 4. Integração com PzPdf
**Descrição**: Garantir que `initialize()` produz instância `PzPdf` com header/footer e margens da config.
**Arquivos afetados**:
- `src/PzPdf.php` (se precisar de hooks)
- `src/Templates/PzPdfTemplateWriter.php`

---

### 5. Testes Unitários
**Descrição**: Validar `PzPdfConfiguration` e regras puras do template (sem PDF quando possível).

**Arquivos de teste**:
- `tests/Unit/Config/PzPdfConfigurationTest.php`
- `tests/Unit/Templates/PzPdfTemplateWriterTest.php` (com classe concreta de stub em `tests/Fixture`)

**Casos de teste obrigatórios**:
1. **Dot path retorna valor aninhado**
   - **Cenário**: Array com `['a' => ['b' => 1]]`, chave `a.b`
   - **Expectativa**: `1`

2. **Dot path inexistente retorna default**
   - **Cenário**: Chave ausente com default tipado
   - **Expectativa**: Default retornado

3. **Template stub aplica cores base**
   - **Cenário**: Subclasse de teste chama `initBaseColors()` equivalente
   - **Expectativa**: Estado interno ou spy em `PzPdf` consistente com config

**Cobertura mínima**: ≥ 85% de `PzPdfConfiguration.php`; ≥ 60% de `PzPdfTemplateWriter.php` (lógica abstrata parcialmente coberta via stub)

---

### 6. Testes de Integração
**Descrição**: Gerar PDF via subclasse real de template usando config carregada de `config/pzpdf.php` ou array mínimo.

**Arquivos de teste**:
- `tests/Integration/Templates/PzPdfTemplateWriterSmokeTest.php`

**Cenários de teste obrigatórios**:
1. **Smoke a partir do arquivo de config do pacote**
   - **Fluxo**: `require config/pzpdf.php` → `PzPdfConfiguration::fromArray` → template stub → saída PDF
   - **Expectativa**: PDF válido mínimo com header/footer (texto esperado presente no stream)

2. **Carbon formata data conforme locale/config**
   - **Fluxo**: Config definindo locale; gerar rodapé/cabeçalho com data
   - **Expectativa**: String de data não vazia e formato estável para o locale fixado no teste

---

## Critérios de Aceitação

- [x] Todos os arquivos criados/modificados conforme especificado
- [x] **PSR-12** e tipagem estrita onde aplicável
- [x] Tipagem PHP sem uso de `mixed` **exceto** em `PzPdfConfiguration::get()` conforme Tech Spec (documentar no PR)
- [x] Todos os testes unitários passando
- [x] Todos os testes de integração passando
- [x] Cobertura mínima conforme acima
- [ ] Code review aprovado
- [x] PHPDoc em `PzPdfTemplateWriter` e `PzPdfConfiguration`
- [x] Sem erros de lint/type-check

---

## Entregáveis

**Arquivos Criados**:
- [x] `config/pzpdf.php`
- [x] `src/Config/PzPdfConfiguration.php`
- [x] `src/Templates/PzPdfTemplateWriter.php`
- [x] `tests/Unit/Config/PzPdfConfigurationTest.php`
- [x] `tests/Unit/Templates/...`
- [x] `tests/Integration/Templates/...`
- [x] Stubs de template em `tests/Fixture/...` se necessário

**Arquivos Modificados**:
- [x] `src/PzPdf.php` ou serviços se precisar de integração (não necessário)

**Documentação**:
- [x] Docblocks explicando injeção de config fora do Laravel

---

## Guia de Implementação

### Passo 1: Copiar chaves do config da referência
Comparar arquivo a arquivo com `/home/ljlim/code/puzl/api/config/pzpdf.php`.

### Passo 2: Implementar `PzPdfConfiguration`
Dot-notation iterativa ou biblioteca leve — preferir implementação simples sem nova dependência.

### Passo 3: Portar template writer
Substituir helpers globais por propriedades injetadas.

```bash
composer test
```

---

## Validação

### Checklist de Validação Manual:
- [ ] Nenhuma chamada a `config()` ou `storage_path()` no código de produção do pacote
- [ ] `fromConfigFile()` ou equivalente localiza `config/pzpdf.php` relativo ao pacote

### Comandos de Validação:
```bash
./vendor/bin/phpunit tests/Unit/Config tests/Unit/Templates
./vendor/bin/phpunit tests/Integration/Templates
```

---

## Riscos e Mitigações

| Risco | Impacto | Probabilidade | Mitigação |
|-------|---------|---------------|-----------|
| Chaves divergirem da referência | Alto | Média | Checklist de chaves comparado automaticamente (teste array_key_exists em conjunto mínimo) |
| Carbon dependente de timezone do sistema | Médio | Média | Fixar timezone nos testes de integração |

---

## Notas Adicionais

- Esta task consolida dependências das tasks 5.0 e 6.0 para que o template possa exercitar tabelas e TTF como na referência.
- Se `PzPdfConfiguration::get` usar `mixed`, registrar isso explicitamente no critério de aceite do PR para evitar conflito com regra geral do template de tarefas.

---

## Histórico

| Data | Autor | Mudança |
|------|-------|---------|
| 2026-03-22 | Planejamento | Criação da tarefa |
