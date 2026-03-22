# Task 6.0: Serviços TTF, cache de fontes e resolução de imagem

## Informações Gerais

**Status**: ✅ Concluída
**Prioridade**: Alta
**Dependências**: Task 4.0
**Assignee**: Não atribuído

---

## Objetivo

Implementar serviços de suporte à paridade com a referência: resolução de fontes TTF, configuração de cache de metadados/arquivos gerados e resolução de tipo de imagem para o FPDF, com **escopo de cache seguro para concorrência** (diretório configurável, uso de `PZPDF_CACHE_DIR` e isolamento com `TEST_TOKEN` / `UNIQUE_TEST_TOKEN` em testes paralelos).

---

## Contexto

RF-08 e a Tech Spec §7.1 exigem atenção a cache em disco e workers paralelos. Esta task entrega `PzPdfTtfFontResolver`, `PzPdfTtfFontCacheConfig` e `PzPdfFpdfImageTypeResolver` (ou equivalentes prefixados), integrando com a fachada `PzPdf` onde a referência faz uso de TTF e imagens.

**Referências**:
- PRD: `tasks/prd-pzpdf-wrapper-fpdf/prd.md` — RF-06, RF-08, seção 11 (riscos de cache)
- Tech Spec: `tasks/prd-pzpdf-wrapper-fpdf/techspec.md` — Seções 1.2, 7.1, 8.1

---

## Escopo

### O que ESTÁ no escopo:
- ✅ `PzPdfTtfFontCacheConfig` com parâmetros equivalentes aos da referência (paths, TTL se houver, flags)
- ✅ `PzPdfTtfFontResolver` resolvendo caminhos e reutilizando cache **no escopo da instância** ou diretório isolado, conforme decisão alinhada à referência
- ✅ `PzPdfFpdfImageTypeResolver` (ou nome equivalente) determinando tipo MIME/extensão suportada pelo fluxo de imagem
- ✅ Integração com `PzPdf` para `SetFont` TTF e inclusão de imagens quando a referência o faz
- ✅ Suporte a variáveis de ambiente documentadas: `PZPDF_CACHE_DIR`, `TEST_TOKEN`

### O que NÃO está no escopo:
- ❌ `PzPdfTemplateWriter` (Task 7.0)
- ❌ Porte completo da suíte (Task 8.0)
- ❌ Alteração de código no `api`

---

## Subtarefas

### 1. PzPdfTtfFontCacheConfig
**Descrição**: Encapsular configuração imutável ou semântica clara de mutação; defaults seguros para CI.
**Arquivos afetados**:
- `src/Service/PzPdfTtfFontCacheConfig.php`

---

### 2. PzPdfTtfFontResolver
**Descrição**: Implementar busca em diretórios, geração/uso de arquivos de cache e integração com FPDF (`AddFont` ou equivalente da referência).
**Arquivos afetados**:
- `src/Service/PzPdfTtfFontResolver.php`
- Possivelmente `src/PzPdf.php` para expor API de fonte TTF

---

### 3. PzPdfFpdfImageTypeResolver
**Descrição**: Portar lógica de detecção de tipo de imagem usada antes de chamar o FPDF.
**Arquivos afetados**:
- `src/Service/PzPdfFpdfImageTypeResolver.php`

---

### 4. Testes Unitários
**Descrição**: Validar resolução pura, rejeição de paths inválidos e regras de tipo de imagem com fixtures em memória/temp.

**Arquivos de teste**:
- `tests/Unit/Service/PzPdfTtfFontCacheConfigTest.php`
- `tests/Unit/Service/PzPdfTtfFontResolverTest.php`
- `tests/Unit/Service/PzPdfFpdfImageTypeResolverTest.php`

**Casos de teste obrigatórios**:
1. **Resolver rejeita diretório inexistente**
   - **Cenário**: Config aponta para path inválido
   - **Expectativa**: Exceção clara `PzPdfException` ou equivalente

2. **Image type resolver identifica PNG/JPEG/GIF** conforme suporte da referência
   - **Cenário**: Fixtures pequenas por formato
   - **Expectativa**: Enum ou string interna consistente com o engine

**Cobertura mínima**: ≥ 80% dos serviços introduzidos

---

### 5. Testes de Integração
**Descrição**: Gerar PDFs com TTF e imagens reais; usar diretório de cache único por teste.

**Arquivos de teste**:
- `tests/Integration/Service/PzPdfTtfIntegrationTest.php`
- `tests/Integration/Service/PzPdfImageIntegrationTest.php`

**Cenários de teste obrigatórios**:
1. **PDF com fonte TTF carregada**
   - **Fluxo**: Apontar resolver para diretório de fontes de teste (commitadas em `tests/fixtures/fonts` ou baixadas em setup documentado)
   - **Expectativa**: Texto renderizado sem exceção; arquivo PDF mínimo válido

2. **PDF com imagem embutida**
   - **Fluxo**: Carregar fixture de imagem e desenhar em `PzPdf`
   - **Expectativa**: PDF válido; tamanho acima de limiar definido

3. **Isolamento de cache com token**
   - **Fluxo**: Definir `TEST_TOKEN` diferente em dois testes que escrevem cache
   - **Expectativa**: Nenhuma colisão de arquivos (validar paths finais distintos)

---

## Critérios de Aceitação

- [x] Todos os arquivos criados/modificados conforme especificado
- [x] **PSR-12** e tipagem estrita
- [x] Tipagem PHP sem uso de `mixed` nas APIs públicas dos serviços
- [x] Todos os testes unitários passando
- [x] Todos os testes de integração passando
- [x] Cobertura >= 80% dos serviços novos
- [ ] Code review aprovado
- [x] PHPDoc explicando riscos de diretório compartilhado entre workers
- [x] Sem erros de lint/type-check

---

## Entregáveis

**Arquivos Criados**:
- [x] `src/Service/PzPdfTtfFontCacheConfig.php`
- [x] `src/Service/PzPdfTtfFontResolver.php`
- [x] `src/Service/PzPdfFpdfImageTypeResolver.php`
- [x] `tests/fixtures/...` (fontes/imagens pequenas, se aplicável)
- [x] Testes unitários e de integração correspondentes

**Arquivos Modificados**:
- [x] `src/PzPdf.php` / `src/Internal/...` para integrar serviços

**Documentação**:
- [x] Nota de concorrência em docblocks de cache

---

## Guia de Implementação

### Passo 1: Portar cache config e resolver da referência
Atenção a paths absolutos; substituir helpers Laravel por parâmetros.

### Passo 2: Integrar na fachada
Expor somente o mínimo necessário ao consumidor.

### Passo 3: Testes com diretório temporário
```php
$token = getenv('TEST_TOKEN') ?: uniqid('test_', true);
$cacheDir = sys_get_temp_dir() . '/pzpdf_cache_' . $token;
```

```bash
composer test
```

---

## Validação

### Checklist de Validação Manual:
- [ ] Dois processos com mesmo cache dir sem locking não são suportados implicitamente — comportamento documentado
- [ ] `PZPDF_CACHE_DIR` altera o diretório efetivo

### Comandos de Validação:
```bash
./vendor/bin/phpunit tests/Unit/Service
./vendor/bin/phpunit tests/Integration/Service
```

---

## Riscos e Mitigações

| Risco | Impacto | Probabilidade | Mitigação |
|-------|---------|---------------|-----------|
| Corrupção de cache em disco compartilhado | Alto | Média | Documentar locking ou diretório por worker; testes com tokens |
| Fontes TTF ausentes no CI | Médio | Média | Fixtures mínimas versionadas em `tests/fixtures` |

---

## Notas Adicionais

- Task paralela à 5.0 após 4.0; coordenar merge para que `PzPdf` ofereça API coerente para tabela+TTF antes da 7.0.
- Interface pública estreita para localização de fontes só se inevitável (Tech Spec §3.2); preferir classe concreta `final`.

---

## Histórico

| Data | Autor | Mudança |
|------|-------|---------|
| 2026-03-22 | Planejamento | Criação da tarefa |
