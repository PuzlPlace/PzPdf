# Tech Spec - Wrapper PzPdf (FPDF) no repositório fpdf

## 1. Visão Geral

### 1.1 Objetivo Técnico

Implementar uma **biblioteca PHP 8.2** autônoma na raiz do repositório `fpdf`, com **fachada fluente `PzPdf`** acoplada diretamente ao **`fawno/fpdf`** (via bridge interno), **sem** expor padrão adapter nem contratos de “troca de motor” na superfície pública. O comportamento e a cobertura devem ser **equivalentes ou superiores** à referência somente leitura em `/home/ljlim/code/puzl/api/app/Library/PzPdf` e `/home/ljlim/code/puzl/api/tests/Unit/Library/PzPdf`, incluindo **componentes** (tabela, lista, serviços TTF/imagem), **exceptions**, **enums/VOs**, **`PzPdfTemplateWriter`** e o arquivo de configuração **`config/pzpdf.php`**. A implementação prioriza **módulos pequenos**, classes **`final`** onde couber, e **estado isolado por instância** para uso em jobs e requisições concorrentes.

### 1.2 Escopo Técnico

- **Núcleo**: `PzPdf` + engine FPDF interno (ex.: `PzPdfFpdfEngine` ou equivalente `final`, não publicado como “adapter”).
- **Suporte**: enums `PzPdf*`, value objects `PzPdf*`, builders (`PzPdfTableBuilder`, `PzPdfListBuilder`, `PzPdfTableWriter`, `PzPdfTableColumn`), serviços (`PzPdfTtfFontResolver`, `PzPdfTtfFontCacheConfig`, `PzPdfFpdfImageTypeResolver`), traits internos para alpha/gradiente se necessário.
- **Template**: `PzPdfTemplateWriter` (classe abstrata) em namespace `Puzl\PzPdf\Templates`, paridade funcional com a referência, porém **desacoplada** de helpers globais do Laravel (`config()`, `storage_path()`, etc.).
- **Configuração**: `config/pzpdf.php` no pacote, **mesma árvore de chaves** que `api/config/pzpdf.php`, adequado a consumo fora do Laravel (valores default portáveis + documentação para o app injetar paths).
- **Testes**: porte da suíte de referência (31 arquivos de teste) + testes de **concorrência/isolamento**; opcional hash de PDF se estável.
- **Fora do escopo**: alterações em `/home/ljlim/code/puzl/api/`; segundo motor de PDF; publicação open-source obrigatória.

### 1.3 Stack Tecnológica

| Camada | Tecnologia |
|--------|------------|
| Linguagem | PHP **8.2+**, `declare(strict_types=1);`, **PSR-12** |
| PDF | **`fawno/fpdf`** (alinhar versão à referência, ex. `^1.9`) |
| SVG / paths | **`kaareln/php-svg-path-data`** (uso em paths como na referência) |
| Testes | **PHPUnit 10** + **`brianium/paratest`** para paralelismo em CI/local |
| Template corporativo | **`nesbot/carbon`** (datas localizadas no `PzPdfTemplateWriter`, como hoje) |
| Autoload | **Composer**, **PSR-4** (`src/`, `tests/`) |

---

## 2. Arquitetura

### 2.1 Diagrama de Arquitetura

```
┌─────────────────────────────────────────────────────────────┐
│                    Consumidor (api, job, CLI)                │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  PzPdfFactory::make()  →  PzPdf (fachada fluente, pública)     │
└───────────────────────────┬─────────────────────────────────┘
                            │ delegação direta (sem interface pública)
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  PzPdfFpdfEngine (final, interno) + PzPdfFpdfBridge (final)    │
│  Traits internos: alpha, gradiente (se extraídos)            │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    fawno/fpdf (vendor)                         │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  PzPdfTemplateWriter (abstract) ← PzPdfConfiguration/array   │
│  lê defaults de config/pzpdf.php ou array injetado           │
└─────────────────────────────────────────────────────────────┘
```

### 2.2 Fluxo de Dados

1. **Criação**: consumidor chama `PzPdfFactory::make()` → nova instância de `PzPdf` com engine FPDF **nova** por chamada (sem singletons mutáveis).
2. **Configuração**: `PzPdf` acumula orientação, margens, callbacks header/footer, fontes; engine aplica chamadas ao FPDF.
3. **Conteúdo**: texto/imagem/vetorial fluem UTF-8 → conversão para encoding nativo do FPDF **dentro** do engine (como `FpdfAdapter` atual).
4. **Fontes TTF**: `PzPdfTtfFontResolver` usa diretórios e cache; **escopo de cache** documentado (ver §7.1); nenhum estado global compartilhado entre instâncias de `PzPdf` sem necessidade explícita.
5. **Saída**: métodos equivalentes aos modos da referência (string, disco, stream HTTP fica a cargo do app; a lib expõe os mesmos modos de **obtenção de bytes** que os testes exigirem).
6. **Template**: subclasses de `PzPdfTemplateWriter` chamam `initialize()` → obtêm `PzPdf` já com header/footer; **config** vem de `PzPdfConfiguration::fromArray()` ou construtor com array carregado de `config/pzpdf.php`.

### 2.3 Decisões Arquiteturais

| Decisão | Justificativa |
|---------|----------------|
| **Sem `PdfAdapterInterface` pública** | Atende RF-02/RF-03; motor fixo FPDF. A lógica hoje no `FpdfAdapter` vira **`final` interno** testável indiretamente pela fachada. |
| **Remoção de `AdapterType` e `getAdapter()` públicos** | `AdapterType` com único caso não agrega negócio; `getAdapter()` acopla a tipo de infraestrutura. Paridade de **comportamento** é preservada; testes que inspecionavam adapter são **reescritos** para asserções sobre `PzPdf` ou sobre saída PDF. |
| **Interfaces públicas mínimas** | Permitir apenas contratos que **não** representem “motor de PDF” (ex.: futura injeção de clock para testes no template). Preferir **construtor com parâmetros opcionais** e classes concretas `PzPdf*`. |
| **`config/pzpdf.php` portátil** | O arquivo da referência usa `env()`, `storage_path()`, `public_path()`. No pacote, o mesmo **formato de array** é mantido, com **defaults** baseados em `getenv()` / paths relativos ao pacote ou **placeholders** documentados; o Laravel continua podendo copiar/sobrescrever com a versão “ricamente integrada”. |
| **`PzPdfTemplateWriter` sem `config()` global** | Injeção de **`PzPdfConfiguration`** (VO imutável ou array validado) no construtor ou método estático `fromConfigFile()`, preservando testabilidade e uso fora do Laravel. |
| **ParaTest + teste(s) de concorrência** | Alinha ao ecossistema `api` (`brianium/paratest`); `TEST_TOKEN` / `UNIQUE_TEST_TOKEN` para diretórios de cache em paralelo. |

---

## 3. Interfaces e Contratos

### 3.1 DTOs (Data Transfer Objects)

```php
// Exemplo: configuração carregada de config/pzpdf.php — imutável na camada de template
namespace Puzl\PzPdf\Config;

final readonly class PzPdfConfiguration
{
    /**
     * @param  array<string, mixed>  $data  Estrutura espelhando as chaves do config/pzpdf.php da referência
     */
    public function __construct(private array $data) {}

    public static function fromArray(array $data): self { /* ... */ }

    public function get(string $dotPath, mixed $default = null): mixed { /* ... */ }
}
```

Valores como cores continuam mapeáveis para `PzPdfPdfColor` / RGB nos métodos `initBaseColors()` equivalentes.

### 3.2 Interfaces de Serviços

```php
// Evitar interfaces públicas amplas; preferir classes finais.
// Se um mock for indispensável para um teste unitário isolado, usar:
// - doubling via PHPUnit stubs apenas em tests/, ou
// - sealed pequeno contrato interno (não exposto no composer "autoload" público além do necessário).

namespace Puzl\PzPdf\Contract;

// Opcional e restrito: apenas se os testes portados exigirem mock de fonte sem refatorar asserções.
// Caso contrário, omitir da superfície pública.
interface PzPdfFontLocatorInterface
{
    public function fontsDirectory(): string;
}
```

**Regra**: a superfície pública exportada pelo pacote deve conter **apenas** tipos prefixados `PzPdf` conforme RF-04; interfaces só se forem **estritamente necessárias** e **não** forem “motor PDF”.

### 3.3 Tipos e Enums

Portar todos os enums da referência com prefixo e namespace `Puzl\PzPdf\Enum` (ex.: `PzPdfPageSize`, `PzPdfOutputMode`, …). **Exceção**: não portar `AdapterType` como tipo público; se algum teste depender apenas do backed enum, substituir por teste da factory.

---

## 4. Endpoints e Integração com API

### 4.1 Endpoints Utilizados

| Método | Endpoint | Descrição | Request Body | Response |
|--------|----------|-----------|--------------|----------|
| — | — | **N/A.** Biblioteca backend sem HTTP próprio. | — | — |

**Superfície pública relevante**: classes `PzPdf`, `PzPdfFactory`, componentes `PzPdf*`, `PzPdfTemplateWriter`, exceptions `PzPdfException`, `PzPdfUnsupportedOperationException`, e arquivo `config/pzpdf.php`.

### 4.2 Tratamento de Erros da API

**N/A.** Erros são **`PzPdfException`** / **`PzPdfUnsupportedOperationException`** (ou nomes prefixados equivalentes) lançados na biblioteca; mapeamento para HTTP fica no consumidor.

### 4.3 Interceptors e Middleware

**N/A.**

---

## 5. Roteamento e Navegação

### 5.1 Rotas Afetadas

**N/A.**

### 5.2 Navigation Guards

**N/A.**

---

## 6. Segurança

### 6.1 Armazenamento de Tokens

**N/A** para a biblioteca. Configuração de paths e diretórios não deve embutir segredos; usar variáveis de ambiente no **aplicativo** consumidor.

### 6.2 Proteção contra Ataques

Sanitização de HTML **não** é escopo do PDF core. Entradas de texto são tratadas como dados de negócio; evitar execução de conteúdo arbitrário em paths de arquivo (validar paths em camadas superiores).

### 6.3 Sanitização de Dados

Validar dimensões > 0, paths legíveis onde a lib abre arquivos; mensagens de exceção **acionáveis** (como na referência), sem vazar caminhos internos em produção (opcional: flag de debug no consumidor).

---

## 7. Performance e Otimização

### 7.1 Caching

- **Metadados de fonte / arquivos gerados**: seguir estratégia da referência, com **escopo preferencialmente por instância** ou diretório configurável.
- **Concorrência**: suportar variável de ambiente **`PZPDF_CACHE_DIR`** e, em testes paralelos, sufixo com `getenv('TEST_TOKEN')` (ParaTest) para **evitar corrupção** em disco compartilhado.
- Documentar no README: uso de diretório compartilhado exige **filesystem seguro para escrita concorrente** ou cache por worker.

### 7.2 Otimizações

- Evitar I/O repetido em caminhos quentes (reuso de resolved font dentro da mesma instância).
- Manter dependências alinhadas à referência para **não** degradar desempenho de parsing SVG/path.

---

## 8. Testes

### 8.1 Estratégia de Testes

- **Porte mecânico** dos 31 arquivos de teste da referência para o namespace de testes do pacote (ex.: `Puzl\PzPdf\Tests\Unit\...`), ajustando imports e removendo dependências de `getAdapter()` / `AdapterType`.
- **Suíte paralela**: `vendor/bin/paratest` no CI e localmente.
- **Concorrência**: pelo menos um teste que dispara **múltiplas gerações simultâneas** (ex.: `proc_open` / pool de processos ou ParaTest com classes que geram PDF em diretórios isolados por token) validando **integridade** (tamanho mínimo, header `%PDF`, ou hash estável se adotado).
- **Template**: testes unitários mínimos para `PzPdfConfiguration` e smoke para `PzPdfTemplateWriter` com config injetada (sem Laravel).

### 8.2 Testes Unitários

Enums, VOs, exceptions, `PzPdfTableColumn`, writers, resolver de imagem, cache TTF, trechos da fachada `PzPdf` com mocks reduzidos (preferir integração leve com engine real quando simples).

### 8.3 Testes de Integração

Fluxo completo `PzPdfFactory::make()` → conteúdo → `Output`/string; cenários equivalentes aos testes grandes da referência; regressão de encoding e fontes.

---

## 9. Observabilidade

### 9.1 Logging

**Sem logger obrigatório na biblioteca** (manter simplicidade). Opcional: pontos de extensão futuros via callback no consumidor; não bloquear MVP.

### 9.2 Métricas

**N/A** no pacote; o consumidor pode medir tempo de geração ao redor das chamadas.

### 9.3 Monitoramento de Erros

Exceções tipadas permitem classificação no consumidor (Sentry, etc.). Documentar códigos/mensagens estáveis onde os testes dependam de string exata.

---

## 10. Análise de Impacto

### 10.1 Arquivos Criados (previstos)

- `composer.json`, `phpunit.xml.dist`, `.gitignore`
- `src/PzPdf.php`, `src/PzPdfFactory.php`
- `src/Engine/` ou `src/Internal/` — bridge FPDF, engine final (nomes internos sem `Adapter` na pasta pública se possível)
- `src/Component/PzPdf*.php`, `src/Service/PzPdf*.php`, `src/ValueObject/PzPdf*.php`, `src/Enum/PzPdf*.php`, `src/Exception/PzPdf*.php`
- `src/Templates/PzPdfTemplateWriter.php`
- `src/Config/PzPdfConfiguration.php` (ou equivalente)
- `config/pzpdf.php` — espelho estrutural do `api/config/pzpdf.php`
- `tests/Unit/...` — espelho da árvore da referência (+ concorrência)
- `README.md` — uso mínimo: factory, instância isolada, config, template

### 10.2 Arquivos Modificados

Nenhum em `api`. No repo `fpdf`, baseline atual vazia ou snapshot `.firstry/`: **substituir** por estrutura nova conforme acima.

### 10.3 Dependências Adicionadas

`fawno/fpdf`, `kaareln/php-svg-path-data`, `phpunit/phpunit`, `brianium/paratest`, `nesbot/carbon` (para paridade do template).

### 10.4 Breaking Changes

Primeira versão do pacote; consumidores futuros da `api` que migrarem do namespace `App\Library\PzPdf` precisarão **ajustar imports** e **substituir** `config()` por injeção de `PzPdfConfiguration` no template base.

---

## 11. Plano de Implementação

### 11.1 Fases de Desenvolvimento

1. **Bootstrap do pacote**: Composer, PSR-4, PHPUnit, Paratest, CI local (`composer test`).
2. **Engine + bridge**: portar `FpdfBridge` / lógica de `FpdfAdapter` para classes **internas finais**, mantendo comportamento.
3. **Fachada `PzPdf`**: portar métodos fluentes da referência um grupo por vez (página, texto, imagem, desenho, permissões, etc.).
4. **Componentes e serviços**: tabelas, listas, TTF, imagem.
5. **Config + template**: `config/pzpdf.php`, `PzPdfConfiguration`, `PzPdfTemplateWriter` desacoplado do Laravel.
6. **Testes**: portar suíte, ajustar testes de adapter/factory, adicionar concorrência.
7. **Polimento**: README, checklist prefixo `PzPdf`, revisão de dependências efetivas.

### 11.2 Ordem de Implementação

Engine estável → `PzPdf` mínimo viável → componentes compartilhados pelos testes → serviços → template/config → testes portados → hardening concorrente.

### 11.3 Dependências entre Tarefas

`PzPdfTemplateWriter` depende de `PzPdf` + config; testes de componentes dependem da fachada; Paratest depende de suíte compilando.

---

## 12. Considerações Adicionais

### 12.1 Compatibilidade

PHP 8.2+, extensões exigidas por `fawno/fpdf` (ex.: `mbstring`); ambientes CLI (Horizon/queues) e php-fpm.

### 12.2 Acessibilidade

**N/A** (PDF gerado; sem UI da biblioteca).

### 12.3 Internacionalização

UTF-8 na API pública; datas no template via Carbon como na referência; locale configurável no **consumidor** ao instanciar Carbon se necessário.

### 12.4 Migração e Deploy

- **App Laravel (`api`)**: em trabalho futuro (fora deste PRD), registrar provider que carrega `config/pzpdf.php` e injeta `PzPdfConfiguration` em subclasses de template, ou manter duplicata de config no app até migração de código para `composer require puzl/...`.
- **Repo `fpdf`**: publicar tag interna quando suíte verde + checklist de paridade.

---

## 13. Referências Técnicas

### 13.1 Documentação

- [fawno/fpdf (Packagist)](https://packagist.org/packages/fawno/fpdf)
- [ParaTest — execução paralela](https://github.com/paratestphp/paratest)
- [PHPUnit 10.5 — processos isolados](https://docs.phpunit.de/en/10.5/attributes.html)

### 13.2 Padrões do Projeto

- [@.cursor/rules/context7.md](../../.cursor/rules/context7.md) — uso de Context7 para docs de libs.
- Arquivos citados no template do repositório (`project.md`, `dto.md`, etc.) **ainda não existem** em `.cursor/rules`; aderência **PSR-12** e convenção de prefixo **`PzPdf`** prevalecem até criação dessas rules.

### 13.3 Recursos Externos

- Referência de código: `/home/ljlim/code/puzl/api/app/Library/PzPdf`
- Referência de testes: `/home/ljlim/code/puzl/api/tests/Unit/Library/PzPdf`
- Template: `/home/ljlim/code/puzl/api/app/Library/PzPdf/Templates/PzPdfTemplateWriter.php`
- Config legada Laravel: `/home/ljlim/code/puzl/api/config/pzpdf.php`

---

## Histórico de Versões

| Versão | Data | Autor | Descrição |
|--------|------|-------|-----------|
| 1.0 | 2026-03-22 | Tech | Versão inicial com decisões consolidadas e escopo template + config |

---

## Anexo: decisões consolidadas (respostas às perguntas de clarificação)

| Tema | Decisão |
|------|---------|
| Namespace / pacote | **`Puzl\PzPdf`** com PSR-4 `src/`; nome Composer sugerido **`puzl/pzpdf`** (ajustável pelo time antes do primeiro `composer publish`). |
| `getAdapter()` | **Removido** da API pública; lógica permanece em classes **internas finais**. Testes portados **reescrevidos** para não depender do tipo do adapter. |
| `AdapterType` | **Não** expor como tipo público; factory sem parâmetro de “motor”. |
| Interfaces | **Mínimas**; nenhuma interface de motor PDF; eventual contrato estreito só para localização de fontes **se** inevitável para mocks. |
| Concorrência | **Paratest** + teste(s) dedicados; uso de **`TEST_TOKEN`** para isolamento de diretórios. |
| Cache TTF | Diretório configurável + documentação de risco em disco compartilhado; defaults seguros para testes paralelos. |
| Organização dos testes | Árvore espelhada em `tests/Unit/...` com namespace de testes do pacote. |
| Seções “web” do template | Preenchidas como **N/A** com indicação da superfície da biblioteca. |
| `PzPdfTemplateWriter` + `config/pzpdf.php` | **Incluídos**; config **portátil** + **injeção** no template; Carbon como dependência. |
