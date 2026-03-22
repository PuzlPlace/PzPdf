# Task 1.0: Bootstrap do pacote e pipeline de testes

## Informações Gerais

**Status**: ✅ Concluída
**Prioridade**: Alta
**Dependências**: Nenhuma
**Assignee**: Não atribuído

---

## Objetivo

Disponibilizar a fundação do repositório `fpdf` como pacote PHP autônomo com autoload PSR-4, dependências declaradas conforme a Tech Spec e um pipeline de testes executável (`phpunit` e preparação para `paratest`), permitindo que tarefas seguintes adicionem código de produção com feedback imediato de CI local.

---

## Contexto

Sem Composer, autoload e configuração de testes, nenhum código `PzPdf*` pode ser carregado nem validado de forma repetível. Esta tarefa entrega o “esqueleto” exigido pelo PRD (RF-09, RNF-02) e pelo plano da Tech Spec (fase 1).

**Referências**:
- PRD: `tasks/prd-pzpdf-wrapper-fpdf/prd.md` — Seções 1.3, 8.3, 9.1, RF-09
- Tech Spec: `tasks/prd-pzpdf-wrapper-fpdf/techspec.md` — Seções 1.3, 10.1, 11.1

---

## Escopo

### O que ESTÁ no escopo:
- ✅ Arquivo `composer.json` com PHP `^8.2`, autoload PSR-4 `Puzl\PzPdf\` → `src/`, e `autoload-dev` para `tests/`
- ✅ Dependências de produção: `fawno/fpdf`, `kaareln/php-svg-path-data`, `nesbot/carbon` (conforme alinhamento com a referência no momento da implementação)
- ✅ Dependências de desenvolvimento: `phpunit/phpunit` (^10), `brianium/paratest`
- ✅ `phpunit.xml.dist`, `.gitignore` adequado ao projeto PHP
- ✅ Script `composer test` (ou equivalente documentado) executando a suíte PHPUnit
- ✅ Diretórios vazios ou com `.gitkeep` onde necessário (`src/`, `tests/`)
- ✅ Teste fumaça que comprove autoload e execução da suíte

### O que NÃO está no escopo:
- ❌ Implementação da fachada `PzPdf`, engine ou componentes de negócio
- ❌ Porte dos 31 arquivos de teste da referência
- ❌ Alterações em `/home/ljlim/code/puzl/api/`

---

## Subtarefas

### 1. Composer e autoload
**Descrição**: Criar `composer.json` com nome de pacote acordado pelo time (ex.: `puzl/pzpdf`), `require` e `require-dev` alinhados à Tech Spec, e mapeamento PSR-4.
**Arquivos afetados**:
- `composer.json`

**Implementação**:
- Definir `autoload.psr-4` para `Puzl\\PzPdf\\` → `src/`
- Definir `autoload-dev.psr-4` para o namespace de testes do pacote (ex.: `Puzl\\PzPdf\\Tests\\` → `tests/`)

---

### 2. PHPUnit e Paratest
**Descrição**: Adicionar `phpunit.xml.dist` com bootstrap `vendor/autoload.php`, diretório de testes e metadados compatíveis com PHPUnit 10; documentar comando `vendor/bin/paratest` no guia de validação (uso pleno na Task 8.0).
**Arquivos afetados**:
- `phpunit.xml.dist`

**Implementação**:
- Configurar `testsuite` para `tests/`
- Habilitar `colors="true"` e fail rápido se desejado pelo time

---

### 3. Higiene do repositório
**Descrição**: Adicionar `.gitignore` para `vendor/`, cache PHPUnit, artefatos de IDE e arquivos temporários de PDF gerados em desenvolvimento.
**Arquivos afetados**:
- `.gitignore`

---

### 4. Testes Unitários
**Descrição**: Garantir que classes de teste mínimas validem autoload e namespaces.

**Arquivos de teste**:
- `tests/Unit/Bootstrap/ComposerAutoloadTest.php` (nome ajustável)

**Casos de teste obrigatórios**:
1. **Autoload PSR-4 resolve namespace de produção**
   - **Cenário**: Uma classe stub `Puzl\PzPdf\Internal\BootstrapProbe` (ou classe real mínima) existe em `src/`
   - **Expectativa**: `class_exists` retorna verdadeiro sem `require` manual

2. **Namespace de testes carrega sem conflito**
   - **Cenário**: Classe de teste no namespace `Puzl\PzPdf\Tests\...`
   - **Expectativa**: PHPUnit descobre e executa o teste

**Cobertura mínima**: 100% do código da classe stub de bootstrap (se existir); demais arquivos desta task são configuração (sem cobertura de linha obrigatória).

---

### 5. Testes de Integração
**Descrição**: Provar que o comando de testes roda de ponta a ponta no ambiente limpo após `composer install`.

**Arquivos de teste**:
- `tests/Integration/Bootstrap/TestSuiteSmokeTest.php` (nome ajustável)

**Cenários de teste obrigatórios**:
1. **Execução da suíte mínima**
   - **Fluxo**: `composer install` → executar `vendor/bin/phpunit` (ou `composer test`)
   - **Expectativa**: Processo termina com código 0 e pelo menos um teste executado

2. **Binário Paratest presente**
   - **Fluxo**: Verificar existência de `vendor/bin/paratest` após install (assert em teste ou script documentado; preferir teste que falhe se o binário sumir)
   - **Expectativa**: Paratest instalado como dev dependency

---

## Critérios de Aceitação

- [x] Todos os arquivos criados/modificados conforme especificado
- [x] Código segue os padrões definidos em `.cursor/rules/` (quando existirem); caso contrário, **PSR-12**
- [x] Tipagem PHP sem uso de `mixed` no código de produção desta task
- [x] Todos os testes unitários passando
- [x] Todos os testes de integração passando
- [x] Cobertura de testes >= 100% apenas onde houver código PHP de produção introduzido nesta task (ex.: stub) — exercício por testes de unidade (sem extensão de cobertura no ambiente atual)
- [ ] Code review aprovado
- [x] Documentação inline (PHPDoc) na classe stub pública mínima, se houver
- [x] Sem erros de lint/type-check adotados pelo repositório

---

## Entregáveis

**Arquivos Criados**:
- [x] `composer.json`
- [x] `phpunit.xml.dist`
- [x] `.gitignore`
- [x] `tests/Unit/...` (testes unitários)
- [x] `tests/Integration/...` (testes de integração)
- [x] `src/...` (classe mínima opcional para prova de autoload)

**Arquivos Modificados**:
- [ ] Nenhum pré-existente obrigatório (repo pode estar vazio)

**Documentação**:
- [ ] Comentários no `composer.json` apenas se o time usar `extra` documentado (opcional)

---

## Guia de Implementação

### Passo 1: Inicializar Composer
```bash
cd /home/ljlim/code/puzl/fpdf
composer init # ou criar composer.json manualmente conforme spec
composer require fawno/fpdf kaareln/php-svg-path-data nesbot/carbon
composer require --dev phpunit/phpunit:^10 brianium/paratest
```

### Passo 2: Configurar PHPUnit
Criar `phpunit.xml.dist` apontando para `tests/` e `vendor/autoload.php`.

### Passo 3: Adicionar script de teste
No `composer.json`:
```json
"scripts": {
  "test": "phpunit -c phpunit.xml.dist"
}
```

---

## Validação

### Checklist de Validação Manual:
- [x] `composer install` completa sem erros
- [x] `composer test` retorna sucesso
- [x] `vendor/bin/paratest -c phpunit.xml.dist` executa (pode ser zero testes apenas nesta task se ainda não houver suíte grande; após adicionar testes, deve passar)

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
| Versões de pacotes divergirem da referência `api` | Médio | Média | Travar versões compatíveis com a referência no momento do porte (Task 8.0) |
| PHPUnit 10 exigir extensões PHP ausentes no CI | Médio | Baixa | Documentar extensões mínimas (`mbstring`, etc.) no README na Task 9.0 |

---

## Notas Adicionais

- A classe stub em `src/` pode ser removida ou fundida com código real na Task 2.0 ou 3.0, desde que os testes de autoload sejam atualizados para uma classe real.
- Não adicionar `README` completo nesta task; o README mínimo fica na Task 9.0.

---

## Histórico

| Data | Autor | Mudança |
|------|-------|---------|
| 2026-03-22 | Planejamento | Criação da tarefa |
