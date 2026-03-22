# Task 2.0: Domínio base público (enums, VOs e exceções)

## Informações Gerais

**Status**: ✅ Concluída
**Prioridade**: Alta
**Dependências**: Task 1.0
**Assignee**: Não atribuído

---

## Objetivo

Entregar os tipos públicos fundamentais da biblioteca (enums, value objects e exceções) com prefixo `PzPdf`, espelhando a referência em `api` onde aplicável, **sem** expor `AdapterType` nem interfaces de motor PDF, de forma que o motor interno e a fachada possam depender de tipos estáveis e testáveis.

---

## Contexto

A referência em `api` define enums e VOs usados em toda a API fluente. Esta task materializa o “vocabulário” do domínio no pacote `fpdf`, atendendo RF-01, RF-03, RF-04 e a seção 3.3 da Tech Spec.

**Referências**:
- PRD: `tasks/prd-pzpdf-wrapper-fpdf/prd.md` — RF-01, RF-03, RF-04, RN-02
- Tech Spec: `tasks/prd-pzpdf-wrapper-fpdf/techspec.md` — Seções 3.3, 2.3 (remoção de `AdapterType` público)

---

## Escopo

### O que ESTÁ no escopo:
- ✅ Enums públicos em `Puzl\PzPdf\Enum` com prefixo `PzPdf` (ex.: tamanho de página, orientação, modo de saída, alinhamento, estilo de fonte, permissões, etc.), **exceto** `AdapterType` como tipo público
- ✅ Value objects públicos em `Puzl\PzPdf\ValueObject` (ex.: cores RGB/CMYK, dimensões, gradient stops, fonte TTF resolvida — conforme paridade)
- ✅ Exceções `PzPdfException`, `PzPdfUnsupportedOperationException` (ou nomes prefixados equivalentes) com mensagens acionáveis alinhadas à referência onde testes dependerem de texto
- ✅ Nomes de arquivo PHP refletindo o nome da classe principal (PSR-4)

### O que NÃO está no escopo:
- ❌ Implementação do engine FPDF ou da fachada `PzPdf`
- ❌ `PzPdfFactory` completa (Task 4.0)
- ❌ Porte da suíte de testes da referência (Task 8.0)

---

## Subtarefas

### 1. Mapeamento referência → pacote
**Descrição**: Listar enums/VOs/exceções da referência e definir namespace destino `Puzl\PzPdf\...`, registrando renomeações apenas quando necessário para o prefixo `PzPdf`.
**Arquivos afetados**:
- Documentação interna da task / comentário no PR (opcional)

---

### 2. Implementação de enums
**Descrição**: Portar enums com backed values idênticos onde a serialização ou comparação for relevante; manter métodos auxiliares (`tryFrom`, labels) se existirem na referência.
**Arquivos afetados**:
- `src/Enum/PzPdf*.php`

**Implementação**:
- Usar enums nativos PHP 8.2
- Garantir que nenhum enum público viole RF-04 (prefixo `PzPdf`)

---

### 3. Implementação de value objects
**Descrição**: Portar VOs como classes `readonly` quando possível; validar invariantes (dimensões > 0, intervalos de cor, etc.).
**Arquivos afetados**:
- `src/ValueObject/PzPdf*.php`

---

### 4. Implementação de exceções
**Descrição**: Hierarquia mínima de exceções da biblioteca; mensagens estáveis para cenários cobertos por testes futuros.
**Arquivos afetados**:
- `src/Exception/PzPdfException.php`
- `src/Exception/PzPdfUnsupportedOperationException.php`

---

### 5. Testes Unitários
**Descrição**: Validar invariantes e conversões dos tipos públicos.

**Arquivos de teste**:
- `tests/Unit/Enum/PzPdf*Test.php`
- `tests/Unit/ValueObject/PzPdf*Test.php`
- `tests/Unit/Exception/PzPdfExceptionTest.php`

**Casos de teste obrigatórios**:
1. **VO rejeita dimensão inválida**
   - **Cenário**: Instanciar `PzPdfPageDimension` (ou equivalente) com largura zero
   - **Expectativa**: `InvalidArgumentException` ou exceção `PzPdf*` documentada

2. **Enum backed preserva valor**
   - **Cenário**: Obter valor escalar de um enum usado na referência
   - **Expectativa**: Igual ao da referência para o mesmo caso

3. **Exceção de operação não suportada**
   - **Cenário**: Lançar `PzPdfUnsupportedOperationException` com mensagem fixa usada em asserções futuras
   - **Expectativa**: Tipo e mensagem conforme definido pelo porte (Task 8.0)

**Cobertura mínima**: ≥ 85% das linhas dos arquivos de enum/VO/exceção introduzidos nesta task

---

### 6. Testes de Integração
**Descrição**: Garantir que tipos trabalham em conjunto como na referência (ex.: cor + enum de alinhamento formando estrutura usada pelo engine na task seguinte).

**Arquivos de teste**:
- `tests/Integration/Domain/PzPdfDomainTypesCompositionTest.php` (nome ajustável)

**Cenários de teste obrigatórios**:
1. **Composição VO + enum**
   - **Fluxo**: Criar cor e enum de alinhamento; passar para uma função de construção de “estilo de célula” pura (pode viver em classe de teste dupla)
   - **Expectativa**: Estrutura resultante é consistente e serializável para uso posterior pelo `PzPdfTableColumn` (Task 5.0)

---

## Critérios de Aceitação

- [x] Todos os arquivos criados/modificados conforme especificado
- [x] Código segue os padrões definidos em `.cursor/rules/`; senão **PSR-12**
- [x] Tipagem PHP sem uso de `mixed` nos VOs e enums (preferir tipos específicos; para getters genéricos de config, usar Task 7.0 com critério específico)
- [x] Todos os testes unitários passando
- [x] Todos os testes de integração passando
- [x] Cobertura de testes >= 85% dos tipos introduzidos nesta task (executar `composer test:coverage` com driver PCOV ou Xdebug)
- [ ] Code review aprovado
- [x] PHPDoc em tipos públicos
- [x] Sem erros de lint/type-check

---

## Entregáveis

**Arquivos Criados**:
- [x] `src/Enum/PzPdf*.php`
- [x] `src/ValueObject/PzPdf*.php`
- [x] `src/Exception/PzPdf*.php`
- [x] `src/Contract/PzPdfColorInterface.php`
- [x] `tests/Unit/...`
- [x] `tests/Integration/...`

**Arquivos Modificados**:
- [x] `composer.json` (script `test:coverage`)
- [x] `phpunit.xml.dist` (elemento `<source>` para cobertura)

**Documentação**:
- [x] PHPDoc nos tipos públicos

---

## Guia de Implementação

### Passo 1: Inventariar tipos na referência
Inspecionar `/home/ljlim/code/puzl/api/app/Library/PzPdf` e copiar assinaturas públicas relevantes.

### Passo 2: Implementar por camada
Enums → VOs → Exceções, commitando testes junto a cada grupo.

### Passo 3: Executar testes
```bash
composer test
```

---

## Validação

### Checklist de Validação Manual:
- [ ] Nenhum tipo público sem prefixo `PzPdf`
- [ ] Nenhum arquivo público `*Adapter*` na superfície da biblioteca

### Comandos de Validação:
```bash
./vendor/bin/phpunit tests/Unit/Enum tests/Unit/ValueObject tests/Unit/Exception
./vendor/bin/phpunit tests/Integration/Domain
```

---

## Riscos e Mitigações

| Risco | Impacto | Probabilidade | Mitigação |
|-------|---------|---------------|-----------|
| Diferença sutil em enums backed quebrar porte de testes | Alto | Média | Comparar valores com a referência antes do porte (Task 8.0) |
| VOs excessivamente permissivos | Médio | Média | Validar invariantes com testes de borda explícitos |

---

## Notas Adicionais

- Se a referência usar `mixed` em algum VO, alinhar com o padrão do repositório `fpdf`: preferir unions ou tipos específicos; discutir em PR se inevitável.
- Interfaces públicas “largas” ficam fora do escopo salvo necessidade estrita documentada na Tech Spec §3.2.

---

## Histórico

| Data | Autor | Mudança |
|------|-------|---------|
| 2026-03-22 | Planejamento | Criação da tarefa |
