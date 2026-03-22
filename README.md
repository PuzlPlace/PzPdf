# puzl/pzpdf (PzPdf)

Biblioteca PHP **PzPdf**: API fluente para PDF com **FPDF** (`fawno/fpdf`), pensada para o ecossistema Puzl. Cada documento deve ser criado via fábrica para **isolamento** entre requisições, jobs  e workers.

## Requisitos

- PHP **8.2+**
- Laravel **10+** (opcional, para auto-discovery, config e comando `pzpdf:install`)
- Extensão **`mbstring`**
- [Composer](https://getcomposer.org/)

## Instalação no Laravel

Use sempre o branch **`production`** para instalar o pacote num projeto Laravel. **Não** use `master` para essa instalação — o código estável para consumo fica em `production`.

### Passo a passo

1. **Abrir o `composer.json` do projeto Laravel** e adicionar o repositório VCS (ajuste a URL ao repositório real):

   ```json
   {
       "repositories": [
           {
               "type": "vcs",
               "url": "https://github.com/SUA_ORG/PzPdf.git"
           }
       ]
   }
   ```

2. **Instalar a versão do branch `production`**. No Composer, branches têm o prefixo `dev-`; para `production` use `dev-production`:

   ```bash
   composer require puzl/pzpdf:dev-production
   ```

   Alternativa equivalente: declarar no `composer.json` em `require` e correr `composer update`:

   ```json
   "require": {
       "puzl/pzpdf": "dev-production"
   }
   ```

3. **Publicar config e assets** no Laravel:

   ```bash
   php artisan pzpdf:install
   ```

O pacote regista automaticamente o `PzPdfServiceProvider` (via `extra.laravel` no Composer). O comando `pzpdf:install` publica `config/pzpdf.php` e, por defeito, copia logos para `public/vendor/puzl/pzpdf/img` (opcional para URLs públicas; o PDF usa os ficheiros em `vendor` por defeito).

Opções:

- `php artisan pzpdf:install --no-assets` — só o config
- `php artisan pzpdf:install --force` — sobrescreve ficheiros já publicados

### Resolver `PzPdfConfiguration` no container

Após o provider estar ativo:

```php
use Puzl\PzPdf\Config\PzPdfConfiguration;

$config = app(PzPdfConfiguration::class);
```

## Uso rápido (qualquer PHP)

```php
<?php

declare(strict_types=1);

use Puzl\PzPdf\Enum\PzPdfFontStyle;
use Puzl\PzPdf\PzPdf;

$pdf = new PzPdf;

$binary = $pdf
    ->addPage()
    ->font('Helvetica', PzPdfFontStyle::Regular, 12)
    ->cell(0, 10, 'Olá, PzPdf', 0, 1)
    ->toString();
```

## Configuração (`config/pzpdf.php`)

O ficheiro do pacote define defaults com caminhos absolutos para `public/img` **dentro do vendor** (logos incluídas). Variáveis de ambiente listadas no docblock do ficheiro continuam a poder sobrepor paths, fontes e locale.

Em Laravel, o stub publicado mescla esses defaults com `paths.public` = `public_path()`.

## Documentação HTML

Na raiz do repositório:

```bash
composer install
php -S localhost:8765 -t docs
```

Abrir [http://localhost:8765/index.html](http://localhost:8765/index.html).

## Cache, concorrência e testes paralelos

- **`PZPDF_CACHE_DIR`**: diretório base para cache (ex.: TTF). Em vários workers no mesmo disco, use diretório por worker ou token de teste.
- **`TEST_TOKEN`** / ParaTest: a suíte isola subpastas de cache em testes paralelos.

## Testes (PHPUnit)

Na raiz do repositório, instale as dependências (inclui o PHPUnit em `vendor/`):

```bash
composer install
```

**Forma direta de rodar a suíte** (não depende do comando `composer` no PATH depois do install — use o executável do PHPUnit):

```bash
./vendor/bin/phpunit -c phpunit.xml.dist
```

- `vendor/bin` é uma **pasta**; o programa certo é `vendor/bin/phpunit` (como acima).
- Se o shebang do binário não for usado no seu ambiente, equivale a: `php vendor/bin/phpunit -c phpunit.xml.dist`.

**Atalhos via Composer** (se `composer` estiver instalado):

```bash
composer test              # mesmo que phpunit -c phpunit.xml.dist
composer test:parallel     # Paratest (vários processos)
```

## Estrutura

- `src/` — biblioteca (`Puzl\PzPdf\…`), incluindo `Laravel/` (provider e comando)
- `config/pzpdf.php` — defaults do pacote
- `resources/stubs/pzpdf.laravel.php` — stub publicado no app Laravel
- `public/img/` — logos padrão (referenciadas nos defaults)
- `docs/` — documentação estática
- `tests/` — PHPUnit

## Licença

Proprietária (Puzl).
