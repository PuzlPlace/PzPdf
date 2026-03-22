# Task 3.0: Motor interno FPDF (bridge e engine final)

## Informações Gerais

**Status**: ✅ Concluída
**Prioridade**: Alta
**Dependências**: Task 2.0
**Assignee**: Não atribuído

---

## Objetivo

Implementar o acoplamento direto ao `fawno/fpdf` por meio de classes internas `final` (bridge/engine), portando o comportamento hoje concentrado no adapter da referência, incluindo tratamento de UTF-8, operações vetoriais/caminhos com `kaareln/php-svg-path-data` quando necessário, e suporte a alpha/gradiente, **sem** expor adapter na API pública.

---

## Contexto

Esta task materializa RF-02 e RF-03: motor fixo FPDF com organização interna legível. A fachada `PzPdf` (Task 4.0) delegará para o engine sem interfaces públicas de troca de motor (Tech Spec §2.3).

**Referências**:
- PRD: `tasks/prd-pzpdf-wrapper-fpdf/prd.md` — RF-02, RF-03, RNF-01
- Tech Spec: `tasks/prd-pzpdf-wrapper-fpdf/techspec.md` — Seções 2.1, 2.2, 2.3, 10.1

---

## Escopo

### O que ESTÁ no escopo:
- ✅ Classe(s) interna(s) `final` para bridge com FPDF (ex.: `PzPdfFpdfBridge`) no namespace interno (ex.: `Puzl\PzPdf\Internal` ou `Engine`)
- ✅ Engine `final` (ex.: `PzPdfFpdfEngine`) orquestrando chamadas ao FPDF
- ✅ Conversão de texto UTF-8 para encoding suportado pelo FPDF, equivalente à referência
- ✅ Suporte a alpha e gradiente via traits internos ou métodos privados, se presentes na referência
- ✅ Uso de `kaareln/php-svg-path-data` para dados de caminho quando a referência depender disso

### O que NÃO está no escopo:
- ❌ Expor `PdfAdapterInterface` ou métodos públicos `getAdapter()`
- ❌ `PzPdfFactory` e API fluente completa (Task 4.0)
- ❌ Componentes de tabela/lista (Task 5.0)

---

## Subtarefas

### 1. Estrutura de pastas internas
**Descrição**: Criar diretório `src/Internal` ou `src/Engine` para código não destinado ao consumidor direto; garantir que o `composer.json` ainda autoload apenas `src/` (público + interno sob o mesmo prefixo é aceitável, desde que classes internas não sejam documentadas como API estável — documentar no README na Task 9.0).

**Arquivos afetados**:
- `src/Internal/...` ou `src/Engine/...`

---

### 2. Bridge FPDF
**Descrição**: Encapsular herança/composição com `Fpdf` da biblioteca `fawno/fpdf`, expondo apenas métodos necessários ao engine.

**Arquivos afetados**:
- `src/Internal/PzPdfFpdfBridge.php` (nome ajustável mantendo coerência com a spec)

**Implementação**:
- Evitar fugir do padrão da referência para minimizar regressões visuais

---

### 3. Engine
**Descrição**: Implementar operações de baixo nível: página, margens, cores, texto, imagem, linhas, paths, alpha, gradiente.

**Arquivos afetados**:
- `src/Internal/PzPdfFpdfEngine.php`
- `src/Internal/Trait/PzPdfAlphaSupport.php` (se extraído)
- `src/Internal/Trait/PzPdfGradientSupport.php` (se extraído)

---

### 4. Testes Unitários
**Descrição**: Testar unidades puras (parsing de path, normalização de cor, validações) com doubles quando necessário.

**Arquivos de teste**:
- `tests/Unit/Internal/PzPdfFpdfEngineTest.php` (parcial, com mocks)
- `tests/Unit/Internal/PathParsingTest.php` (exemplo)

**Casos de teste obrigatórios**:
1. **Validação de parâmetro inválido no engine**
   - **Cenário**: Chamada interna com dimensão negativa
   - **Expectativa**: Exceção `PzPdf*` ou `InvalidArgumentException` consistente com a referência

2. **Parser de path SVG aceita caso da referência**
   - **Cenário**: String de path igual a um caso usado nos testes portados
   - **Expectativa**: Estrutura intermediária esperada (pontos/comandos) sem lançar exceção

**Cobertura mínima**: ≥ 70% dos arquivos internos introduzidos (parte depende de integração com FPDF real)

---

### 5. Testes de Integração
**Descrição**: Gerar PDF real via engine ou fachada mínima temporária (pode ser classe `InternalEnginePdfSmoke` em `tests/` apenas para esta task, removida na Task 4.0).

**Arquivos de teste**:
- `tests/Integration/Engine/PzPdfEngineGeneratesValidPdfTest.php`

**Cenários de teste obrigatórios**:
1. **PDF mínimo com header válido**
   - **Fluxo**: Instanciar engine → adicionar página → escrever texto curto → obter string binária
   - **Expectativa**: Bytes começam com `%PDF` e contêm `%%EOF` aproximadamente como PDF válido simples

2. **Imagem ou retângulo com alpha (se aplicável)**
   - **Fluxo**: Reproduzir cenário mínimo equivalente à referência
   - **Expectativa**: Geração completa sem exceção e tamanho mínimo de arquivo > N bytes definido no teste

---

## Critérios de Aceitação

- [x] Todos os arquivos criados/modificados conforme especificado
- [x] Código segue **PSR-12** e `declare(strict_types=1);`
- [x] Tipagem PHP sem uso de `mixed` no código novo, salvo interseção inevitável com FPDF documentada em nota de PR
- [x] Todos os testes unitários passando
- [x] Todos os testes de integração passando
- [x] Cobertura de testes >= 70% dos módulos internos desta task (complementada na Task 8.0; requer driver pcov/xdebug no ambiente)
- [ ] Code review aprovado
- [x] PHPDoc nas classes internas `final` (mínimo: responsabilidade e limites)
- [x] Sem erros de lint/type-check

---

## Entregáveis

**Arquivos Criados**:
- [x] `src/Internal/PzPdfFpdfBridge.php` (ou caminho acordado)
- [x] `src/Internal/PzPdfFpdfEngine.php`
- [x] Traits internos opcionais
- [x] `tests/Unit/Internal/...`
- [x] `tests/Integration/Engine/...`

**Arquivos Modificados**:
- [x] `phpunit.xml.dist` (cobertura inclui `src/Internal`)

**Documentação**:
- [x] Comentários sobre limites de thread-safety do FPDF se relevante

---

## Guia de Implementação

### Passo 1: Copiar fluxo da referência
Ler implementação atual do adapter em `api` e transcrever para classes internas `final`.

### Passo 2: Implementar bridge primeiro
Garantir que herança/composição com FPDF funciona com PHPUnit.

### Passo 3: Cobrir com testes de integração
Priorizar asserts estáveis (header, presença de strings), não snapshot visual frágil.

```bash
composer test
```

---

## Validação

### Checklist de Validação Manual:
- [x] Nenhuma classe pública com sufixo `Adapter` na API destinada ao consumidor
- [x] `fawno/fpdf` é a única implementação de motor usada pelo engine

### Comandos de Validação:
```bash
./vendor/bin/phpunit tests/Unit/Internal
./vendor/bin/phpunit tests/Integration/Engine
```

---

## Riscos e Mitigações

| Risco | Impacto | Probabilidade | Mitigação |
|-------|---------|---------------|-----------|
| Diferenças de renderização entre ambientes | Médio | Média | Evitar asserts de pixel; usar estrutura PDF e tamanhos mínimos |
| Complexidade do alpha no FPDF | Alto | Média | Portar código da referência literalmente e validar com testes portados (Task 8.0) |

---

## Notas Adicionais

- Se for necessário expor algum método “público” ao namespace `Puzl\PzPdf` para testes, preferir `@internal` e documentar que não faz parte da API estável.
- Remover qualquer helper temporário de teste ao concluir Task 4.0 se a fachada substituir o caminho.

---

## Histórico

| Data | Autor | Mudança |
|------|-------|---------|
| 2026-03-22 | Planejamento | Criação da tarefa |
