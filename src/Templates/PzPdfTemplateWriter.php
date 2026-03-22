<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Templates;

use Carbon\Carbon;
use Closure;
use Puzl\PzPdf\Config\PzPdfConfiguration;
use Puzl\PzPdf\Enum\PzPdfFontStyle;
use Puzl\PzPdf\Enum\PzPdfTextAlignment;
use Puzl\PzPdf\Exception\PzPdfException;
use Puzl\PzPdf\PzPdf;
use Puzl\PzPdf\PzPdfFactory;
use Puzl\PzPdf\ValueObject\PzPdfColor;

/**
 * Classe base para relatórios PDF usando PzPdf.
 *
 * Fornece o layout corporativo padrão: barra azul no header com logo e título,
 * footer com paginação e logo Puzl, registro de fontes Inter, paleta de cores
 * e helpers reutilizáveis (cards, column headers, summary bands, badges, filtros).
 *
 * Subclasses constroem o conteúdo específico usando o PzPdf retornado por
 * initialize(), passando title/subtitle/topRightText conforme o relatório.
 *
 * A configuração não usa helpers globais do Laravel ({@code config()}, {@code storage_path()},
 * {@code public_path()}). Passe {@see PzPdfConfiguration} ou um array com a mesma árvore de chaves
 * de {@code config/pzpdf.php}, ou use {@see self::fromConfigFile()} a partir de uma subclasse concreta.
 */
abstract class PzPdfTemplateWriter
{
    // ──────────────────────────────────────────────
    //  Dimensões e margens (mm)
    // ──────────────────────────────────────────────
    protected const MARGIN_HORIZONTAL = 5.0;

    protected const MARGIN_TOP = 28.0;

    protected const HEADER_BAR_HEIGHT = 22.0;

    protected const FOOTER_BREAK_MARGIN = 14.0;

    // ──────────────────────────────────────────────
    //  Tipografia
    // ──────────────────────────────────────────────
    protected const FONT_FAMILY = 'Inter';

    protected const FONT_SIZE_BODY = 6.5;

    // ──────────────────────────────────────────────
    //  Alturas de linha (mm)
    // ──────────────────────────────────────────────
    protected const ROW_HEIGHT_HEADER = 5.5;

    protected const ROW_HEIGHT_SUMMARY = 5.5;

    protected const ROW_HEIGHT_DATA = 4.5;

    // ──────────────────────────────────────────────
    //  Raios de arredondamento (mm)
    // ──────────────────────────────────────────────
    protected const RADIUS_SMALL = 1.2;

    protected const RADIUS_CARD = 2.0;

    // ──────────────────────────────────────────────
    //  Dimensões padrão dos cards (mm)
    // ──────────────────────────────────────────────
    protected const DEFAULT_CARD_WIDTH = 38.0;

    protected const DEFAULT_CARD_HEIGHT = 13.0;

    protected const DEFAULT_CARD_GAP = 4.0;

    /** Título padrão da barra do header quando initialize() recebe title = null. */
    protected const DEFAULT_HEADER_TITLE = 'Título';

    // ──────────────────────────────────────────────
    //  Paleta de cores corporativas
    // ──────────────────────────────────────────────
    protected PzPdfColor $brandColor;

    protected PzPdfColor $columnTitleColor;

    protected PzPdfColor $groupRowBackground;

    protected PzPdfColor $cardBorderColor;

    protected PzPdfColor $footerLineColor;

    protected PzPdfColor $footerTextColor;

    protected PzPdfColor $rowSeparatorColor;

    protected string $headerDefaultLogoPath;

    protected string $footerDefaultLogoPath;

    protected float $marginLeft;

    protected float $marginTop;

    protected float $marginRight;

    protected float $footerBreakMargin;

    protected float $headerBarHeight;

    protected float $headerLogoXOffset;

    protected float $headerLogoY;

    protected float $headerLogoHeight;

    protected float $headerTextStartX;

    protected float $footerLogoWidth;

    protected float $footerLogoYOffset;

    /**
     * Configuração injetada (espelha {@code config/pzpdf.php}); somente leitura após o construtor.
     */
    protected readonly PzPdfConfiguration $configuration;

    /**
     * @param  array<string, mixed>|PzPdfConfiguration  $config  Mesma árvore de chaves que {@code config/pzpdf.php} ou VO imutável.
     */
    public function __construct(array|PzPdfConfiguration $config = [])
    {
        $this->configuration = is_array($config)
            ? PzPdfConfiguration::fromArray($config)
            : $config;
        $this->initLayoutConfig();
        $this->initBaseColors();
    }

    /**
     * Carrega {@code config/pzpdf.php} relativo à raiz do pacote (ou um caminho absoluto) e retorna uma instância da subclasse concreta.
     *
     * Deve ser chamado estaticamente apenas em classe concreta (ex.: {@code MyReport::fromConfigFile()}).
     *
     * @throws PzPdfException Se o arquivo não existir ou o retorno não for array
     */
    public static function fromConfigFile(?string $path = null): static
    {
        $resolved = $path ?? dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'pzpdf.php';
        if (! is_file($resolved)) {
            throw new PzPdfException("PzPdfTemplateWriter: arquivo de configuração não encontrado em {$resolved}.");
        }

        $loaded = require $resolved;
        if (! is_array($loaded)) {
            throw new PzPdfException('PzPdfTemplateWriter: o arquivo de configuração deve retornar um array.');
        }

        /** @var array<string, mixed> $loaded */
        return new static(PzPdfConfiguration::fromArray($loaded));
    }

    // ══════════════════════════════════════════════
    //  CRIAÇÃO DO DOCUMENTO
    // ══════════════════════════════════════════════

    /**
     * Inicializa o PzPdf do relatório: fontes, header/footer corporativos, margens e primeira página.
     *
     * @param  string|null  $title  Título da barra (null = self::DEFAULT_HEADER_TITLE, padrão "Título")
     * @param  string|null  $subtitle  Subtítulo abaixo do título (null = data atual localizada em maiúsculas)
     * @param  string|null  $topRightText  Texto à direita no header (ex.: nome do usuário), em maiúsculas;
     *                                     null ou string vazia = não exibe
     *
     * Retorna a instância pronta para o relatório filho desenhar o conteúdo.
     */
    protected function initialize(
        ?string $title = null,
        ?string $subtitle = null,
        ?string $topRightText = null,
    ): PzPdf {
        $pdf = PzPdfFactory::make();

        $this->registerFonts($pdf);

        $resolvedTitle = $title ?? static::DEFAULT_HEADER_TITLE;
        $tz = (string) $this->configuration->get('datetime.timezone', 'UTC');
        $locale = (string) $this->configuration->get('datetime.locale', 'en');
        $resolvedSubtitle = $subtitle ?? $this->upper(
            Carbon::now($tz)->locale($locale)->translatedFormat('d M Y'),
        );
        $resolvedTopRight = ($topRightText !== null && $topRightText !== '')
            ? $this->upper($topRightText)
            : '';

        $this->configureHeader(
            $pdf,
            title: $resolvedTitle,
            subtitle: $resolvedSubtitle,
            topRightText: $resolvedTopRight,
        );
        $this->configureFooter($pdf);

        $pdf->landscape()
            ->margins($this->marginLeft, $this->marginTop, $this->marginRight)
            ->autoPageBreak(true, $this->footerBreakMargin)
            ->aliasNbPages()
            ->addPage()
            ->font(static::FONT_FAMILY, PzPdfFontStyle::Regular, static::FONT_SIZE_BODY);

        return $pdf;
    }

    /** Registra as fontes Inter (Regular e Bold) a partir de {@code fonts.directory} e nomes em {@code fonts.families.Inter}. */
    protected function registerFonts(PzPdf $pdf): void
    {
        $fontsDir = (string) $this->configuration->get('fonts.directory', '');
        if ($fontsDir === '' || ! is_dir($fontsDir)) {
            throw new PzPdfException(
                'PzPdfTemplateWriter: defina fonts.directory (ex.: variável de ambiente PDF_FONTS_DIR) apontando para o diretório '
                .'com os arquivos .php/.z gerados pelo MakeFont do FPDF (Inter-Regular.php, Inter-Bold.php).'
            );
        }

        $regular = (string) $this->configuration->get('fonts.families.Inter.regular', 'Inter-Regular.php');
        $bold = (string) $this->configuration->get('fonts.families.Inter.bold', 'Inter-Bold.php');

        $pdf->addFont(static::FONT_FAMILY, PzPdfFontStyle::Regular, $regular, $fontsDir)
            ->addFont(static::FONT_FAMILY, PzPdfFontStyle::Bold, $bold, $fontsDir);
    }

    // ══════════════════════════════════════════════
    //  HEADER (barra colorida no topo de cada página)
    // ══════════════════════════════════════════════

    /**
     * Configura o callback de header com barra colorida, logo, título e subtítulo.
     *
     * @param  string  $title  Título na barra (default: 'Título')
     * @param  string  $subtitle  Subtítulo abaixo do título (default: 'Subtítulo')
     * @param  string  $topRightText  Texto alinhado à direita (default: vazio)
     * @param  string|Closure|null  $logo  null = logo corporativa padrão,
     *                                     string vazia = sem logo,
     *                                     string = caminho do arquivo,
     *                                     Closure(PzPdf, float $x, float $y): void = renderização customizada
     * @param  string  $logoType  Tipo da imagem para detecção explícita (vazio = auto-detect)
     */
    protected function configureHeader(
        PzPdf $pdf,
        string $title = 'Título',
        string $subtitle = 'Subtítulo',
        string $topRightText = '',
        string|Closure|null $logo = null,
        string $logoType = '',
    ): void {
        $resolvedLogoPath = $this->resolveLogoParam($logo, 'header');
        $isClosureLogo = $logo instanceof Closure;
        $closureLogo = $isClosureLogo ? $logo : null;

        $pdf->onHeader(function (PzPdf $p) use (
            $resolvedLogoPath,
            $isClosureLogo,
            $closureLogo,
            $logoType,
            $title,
            $subtitle,
            $topRightText,
        ): void {
            $pageWidth = $p->getPageWidth();

            $p->setY(0)
                ->fillColor($this->brandColor)
                ->rect(0, 0, $pageWidth, $this->headerBarHeight, 'F')
                ->textColor(PzPdfColor::white());

            $logoX = $this->marginLeft + $this->headerLogoXOffset;
            $logoY = $this->headerLogoY;

            if ($isClosureLogo && $closureLogo !== null) {
                $closureLogo($p, $logoX, $logoY);
            } elseif ($resolvedLogoPath !== null) {
                $p->image($resolvedLogoPath, $logoX, $logoY, 0, $this->headerLogoHeight, $logoType);
            }

            $textStartX = $this->headerTextStartX;

            $p->font(static::FONT_FAMILY, PzPdfFontStyle::Bold, 14)
                ->setXY($textStartX, 4)
                ->cell(0, 7, $title);

            $p->font(static::FONT_FAMILY, PzPdfFontStyle::Regular, 8)
                ->setXY($textStartX, 12)
                ->cell(0, 5, $subtitle);

            if ($topRightText !== '') {
                $p->font(static::FONT_FAMILY, PzPdfFontStyle::Regular, 8)
                    ->setXY(0, 8)
                    ->cell($pageWidth - $this->marginRight, 6, $topRightText, 0, 0, PzPdfTextAlignment::Right);
            }

            $p->textColor(PzPdfColor::black())
                ->setY($this->headerBarHeight + 2);
        });
    }

    // ══════════════════════════════════════════════
    //  FOOTER (traço, paginação e logo Puzl)
    // ══════════════════════════════════════════════

    /**
     * Configura o callback de footer com linha, paginação e logo.
     *
     * @param  string  $paginationFormat  Formato da paginação ({page} e {total} são substituídos)
     * @param  string|Closure|null  $logo  null = logo Puzl padrão,
     *                                     string vazia = sem logo,
     *                                     string = caminho do arquivo,
     *                                     Closure(PzPdf, float $pageWidth, float $lineY): void
     * @param  string  $logoType  Tipo da imagem (vazio = auto-detect)
     * @param  bool  $showPagination  Exibir paginação
     * @param  bool  $showLogo  Exibir logo
     */
    protected function configureFooter(
        PzPdf $pdf,
        string $paginationFormat = 'Pagina {page}/{total}',
        string|Closure|null $logo = null,
        string $logoType = '',
        bool $showPagination = true,
        bool $showLogo = true,
    ): void {
        $resolvedLogoPath = $this->resolveLogoParam($logo, 'footer');
        $isClosureLogo = $logo instanceof Closure;
        $closureLogo = $isClosureLogo ? $logo : null;

        $pdf->onFooter(function (PzPdf $p) use (
            $paginationFormat,
            $resolvedLogoPath,
            $isClosureLogo,
            $closureLogo,
            $logoType,
            $showPagination,
            $showLogo,
        ): void {
            $pageWidth = $p->getPageWidth();

            $p->setY(-14);
            $lineY = $p->getY();

            $p->drawColor($this->footerLineColor)
                ->lineWidth(0.3)
                ->line($this->marginLeft, $lineY, $pageWidth - $this->marginRight, $lineY);

            if ($showPagination) {
                $text = str_replace(
                    ['{page}', '{total}'],
                    [(string) $p->pageNo(), '{nb}'],
                    $paginationFormat,
                );

                $p->setX($this->marginLeft)
                    ->setY($lineY + 2)
                    ->font(static::FONT_FAMILY, PzPdfFontStyle::Regular, 7)
                    ->textColor($this->footerTextColor)
                    ->cell(0, 4, $text);
            }

            if ($showLogo) {
                if ($isClosureLogo && $closureLogo !== null) {
                    $closureLogo($p, $pageWidth, $lineY);
                } elseif ($resolvedLogoPath !== null) {
                    $p->image(
                        $resolvedLogoPath,
                        $pageWidth - $this->marginRight - $this->footerLogoWidth,
                        $lineY + $this->footerLogoYOffset,
                        $this->footerLogoWidth,
                        0,
                        $logoType,
                    );
                }
            }

            $p->textColor(PzPdfColor::black());
        });
    }

    // ══════════════════════════════════════════════
    //  CARD (individual)
    // ══════════════════════════════════════════════

    /**
     * Desenha um card arredondado com título e valor centralizados.
     *
     * @param  float  $x  Posição X
     * @param  float  $y  Posição Y
     * @param  string  $title  Texto do título (topo do card)
     * @param  string  $value  Texto do valor (parte inferior do card)
     * @param  PzPdfColor|null  $valueColor  Cor do valor (null = preto)
     * @param  float  $width  Largura do card
     * @param  float  $height  Altura do card
     * @param  float  $radius  Raio dos cantos arredondados
     * @param  PzPdfColor|null  $borderColor  Cor da borda (null = padrão corporativo)
     * @param  PzPdfColor|null  $backgroundColor  Cor de fundo (null = branco)
     * @param  float  $titleFontSize  Tamanho da fonte do título
     * @param  float  $valueFontSize  Tamanho da fonte do valor
     */
    protected function drawCard(
        PzPdf $pdf,
        float $x,
        float $y,
        string $title,
        string $value,
        ?PzPdfColor $valueColor = null,
        float $width = self::DEFAULT_CARD_WIDTH,
        float $height = self::DEFAULT_CARD_HEIGHT,
        float $radius = self::RADIUS_CARD,
        ?PzPdfColor $borderColor = null,
        ?PzPdfColor $backgroundColor = null,
        float $titleFontSize = 7.0,
        float $valueFontSize = 10.0,
    ): void {
        $borderColor ??= $this->cardBorderColor;
        $backgroundColor ??= PzPdfColor::white();
        $valueColor ??= PzPdfColor::black();

        $pdf->drawColor($borderColor)
            ->lineWidth(0.4)
            ->fillColor($backgroundColor)
            ->roundedRect($x, $y, $width, $height, $radius, 'DF');

        $pdf->font(static::FONT_FAMILY, PzPdfFontStyle::Bold, $titleFontSize)
            ->textColor(PzPdfColor::black())
            ->setXY($x, $y + 1.5)
            ->cell($width, 4, $title, 0, 0, PzPdfTextAlignment::Center);

        $pdf->textColor($valueColor)
            ->font(static::FONT_FAMILY, PzPdfFontStyle::Bold, $valueFontSize)
            ->setXY($x, $y + 6)
            ->cell($width, 6, $value, 0, 0, PzPdfTextAlignment::Center);

        $pdf->textColor(PzPdfColor::black());
    }

    /**
     * Desenha uma fileira horizontal de cards com espaçamento uniforme.
     *
     * Cada item do array deve conter 'title' e 'value', e opcionalmente 'color' (PzPdfColor).
     *
     * @param  array<array{title: string, value: string, color?: PzPdfColor}>  $cards
     */
    protected function drawHorizontalCards(
        PzPdf $pdf,
        float $startX,
        float $topY,
        array $cards,
        float $cardWidth = self::DEFAULT_CARD_WIDTH,
        float $cardHeight = self::DEFAULT_CARD_HEIGHT,
        float $gap = self::DEFAULT_CARD_GAP,
        float $radius = self::RADIUS_CARD,
        float $titleFontSize = 7.0,
        float $valueFontSize = 10.0,
    ): void {
        foreach ($cards as $index => $card) {
            $cardX = $startX + $index * ($cardWidth + $gap);

            $this->drawCard(
                $pdf,
                $cardX,
                $topY,
                $card['title'],
                $card['value'],
                valueColor: $card['color'] ?? null,
                width: $cardWidth,
                height: $cardHeight,
                radius: $radius,
                titleFontSize: $titleFontSize,
                valueFontSize: $valueFontSize,
            );
        }
    }

    // ══════════════════════════════════════════════
    //  CABEÇALHO DE COLUNAS
    // ══════════════════════════════════════════════

    /**
     * Desenha os rótulos das colunas de uma tabela.
     *
     * @param  array<string>  $labels  Rótulos das colunas
     * @param  array<float>  $widths  Largura de cada coluna (mm)
     * @param  array<PzPdfTextAlignment>|null  $alignments  Alinhamento por coluna (null = tudo à esquerda)
     */
    protected function drawColumnHeaders(
        PzPdf $pdf,
        array $labels,
        array $widths,
        ?array $alignments = null,
        float $rowHeight = self::ROW_HEIGHT_HEADER,
        ?PzPdfColor $textColor = null,
        float $fontSize = self::FONT_SIZE_BODY,
        float $startX = self::MARGIN_HORIZONTAL,
    ): void {
        $textColor ??= $this->columnTitleColor;

        $pdf->setX($startX)
            ->font(static::FONT_FAMILY, PzPdfFontStyle::Bold, $fontSize)
            ->textColor($textColor);

        foreach ($labels as $index => $label) {
            $align = $alignments[$index] ?? PzPdfTextAlignment::Left;
            $pdf->cell($widths[$index] ?? 0, $rowHeight, $label, 0, 0, $align);
        }

        $pdf->newLine($rowHeight)
            ->setX($startX)
            ->textColor(PzPdfColor::black());
    }

    // ══════════════════════════════════════════════
    //  FAIXA DE RESUMO (título + valores)
    // ══════════════════════════════════════════════

    /**
     * Desenha uma faixa arredondada com rótulo à esquerda e valores alinhados à direita.
     *
     * @param  PzPdfColor  $background  Cor de fundo da faixa
     * @param  PzPdfColor  $textColor  Cor do texto
     * @param  string  $leftText  Rótulo à esquerda (bold)
     * @param  float  $labelWidth  Largura do bloco do rótulo
     * @param  array<string>  $values  Valores à direita
     * @param  array<float>  $valueWidths  Largura de cada célula de valor
     * @param  float  $tableWidth  Largura total da faixa
     * @param  float  $trailingWidth  Largura de uma célula vazia final (0 = sem)
     * @param  float  $rowHeight  Altura da faixa
     * @param  float  $radius  Raio dos cantos
     * @param  PzPdfTextAlignment  $valuesAlignment  Alinhamento dos valores
     * @param  float  $startX  Posição X inicial
     */
    protected function drawSummaryBand(
        PzPdf $pdf,
        PzPdfColor $background,
        PzPdfColor $textColor,
        string $leftText,
        float $labelWidth,
        array $values,
        array $valueWidths,
        float $tableWidth,
        float $trailingWidth = 0,
        float $rowHeight = self::ROW_HEIGHT_SUMMARY,
        float $radius = self::RADIUS_SMALL,
        PzPdfTextAlignment $valuesAlignment = PzPdfTextAlignment::Right,
        float $startX = self::MARGIN_HORIZONTAL,
    ): void {
        $y = $pdf->getY();

        $pdf->fillColor($background)
            ->roundedRect($startX, $y, $tableWidth, $rowHeight, $radius, 'F');

        $pdf->setXY($startX, $y)
            ->textColor($textColor)
            ->font(static::FONT_FAMILY, PzPdfFontStyle::Bold, static::FONT_SIZE_BODY)
            ->cell($labelWidth, $rowHeight, $leftText);

        $pdf->font(static::FONT_FAMILY, PzPdfFontStyle::Regular, static::FONT_SIZE_BODY);

        foreach ($values as $index => $value) {
            $pdf->cell(
                $valueWidths[$index] ?? 0,
                $rowHeight,
                $this->upper($value),
                0,
                0,
                $valuesAlignment,
            );
        }

        if ($trailingWidth > 0) {
            $pdf->cell($trailingWidth, $rowHeight, '', 0, 1, PzPdfTextAlignment::Center);
        } else {
            $pdf->newLine($rowHeight);
        }

        $pdf->textColor(PzPdfColor::black());
    }

    // ══════════════════════════════════════════════
    //  BADGE (pílula arredondada dentro de uma célula)
    // ══════════════════════════════════════════════

    /**
     * Desenha um badge colorido centralizado dentro do espaço de uma célula.
     *
     * Avança o cursor à posição seguinte da célula após desenhar.
     *
     * @param  float  $cellWidth  Largura da célula que contém o badge
     * @param  float  $cellHeight  Altura da célula que contém o badge
     * @param  string  $text  Texto do badge
     * @param  PzPdfColor|null  $backgroundColor  Cor de fundo (null = brandColor)
     * @param  PzPdfColor|null  $textColor  Cor do texto (null = branco)
     * @param  float  $maxBadgeWidth  Largura máxima do badge
     * @param  float  $badgeHeight  Altura do badge
     * @param  float  $fontSize  Tamanho da fonte do badge
     * @param  float  $radius  Raio dos cantos
     */
    protected function drawBadge(
        PzPdf $pdf,
        float $cellWidth,
        float $cellHeight,
        string $text,
        ?PzPdfColor $backgroundColor = null,
        ?PzPdfColor $textColor = null,
        float $maxBadgeWidth = 11.0,
        float $badgeHeight = 3.5,
        float $fontSize = 5.0,
        float $radius = self::RADIUS_SMALL,
    ): void {
        $backgroundColor ??= $this->brandColor;
        $textColor ??= PzPdfColor::white();

        $cellX = $pdf->getX();
        $cellY = $pdf->getY();

        $badgeWidth = min($cellWidth - 1, $maxBadgeWidth);
        $badgeX = $cellX + ($cellWidth - $badgeWidth) / 2;
        $badgeY = $cellY + ($cellHeight - $badgeHeight) / 2;

        $pdf->cell($cellWidth, $cellHeight, '');

        $pdf->fillColor($backgroundColor)
            ->roundedRect($badgeX, $badgeY, $badgeWidth, $badgeHeight, $radius, 'F');

        $pdf->textColor($textColor)
            ->font(static::FONT_FAMILY, PzPdfFontStyle::Bold, $fontSize)
            ->setXY($badgeX, $badgeY)
            ->cell($badgeWidth, $badgeHeight, $text, 0, 0, PzPdfTextAlignment::Center);

        $pdf->textColor(PzPdfColor::black())
            ->font(static::FONT_FAMILY, PzPdfFontStyle::Regular, static::FONT_SIZE_BODY)
            ->setXY($cellX + $cellWidth, $cellY);
    }

    // ══════════════════════════════════════════════
    //  LINHA DE FILTRO (rótulo + valor)
    // ══════════════════════════════════════════════

    /**
     * Desenha uma linha de filtro no formato "RÓTULO: valor".
     *
     * O rótulo é exibido em bold e o valor em regular.
     * Posiciona o cursor na linha seguinte após desenhar.
     *
     * @param  string  $label  Rótulo (ex: 'PERÍODO: ')
     * @param  string  $value  Valor (ex: '01/01/2026 A 31/12/2026')
     * @param  float  $maxWidth  Largura máxima da linha (0 = largura disponível)
     * @param  float  $lineHeight  Altura da linha
     * @param  float  $fontSize  Tamanho da fonte
     * @param  float  $startX  Posição X inicial
     */
    protected function drawFilterLine(
        PzPdf $pdf,
        string $label,
        string $value,
        float $maxWidth = 0,
        float $lineHeight = 5.0,
        float $fontSize = 8.5,
        float $startX = self::MARGIN_HORIZONTAL,
    ): void {
        $pdf->setX($startX);

        $pdf->font(static::FONT_FAMILY, PzPdfFontStyle::Bold, $fontSize);
        $labelWidth = $pdf->getStringWidth($label);
        $pdf->cell($labelWidth, $lineHeight, $label);

        $valueWidth = $maxWidth > 0 ? $maxWidth - $labelWidth : 0;

        $pdf->font(static::FONT_FAMILY, PzPdfFontStyle::Regular, $fontSize);
        $pdf->cell($valueWidth, $lineHeight, $value, 0, 2);
        $pdf->setX($startX);
    }

    // ══════════════════════════════════════════════
    //  VERIFICAÇÃO DE OVERFLOW
    // ══════════════════════════════════════════════

    /** Verifica se o conteúdo ultrapassaria a margem inferior. */
    protected function wouldOverflow(PzPdf $pdf, float $requiredHeight): bool
    {
        return ($pdf->getY() + $requiredHeight) > ($pdf->getPageHeight() - $this->footerBreakMargin);
    }

    // ══════════════════════════════════════════════
    //  SEPARADOR ENTRE LINHAS
    // ══════════════════════════════════════════════

    /** Desenha o traço horizontal fino entre linhas de dados. */
    protected function drawRowSeparator(PzPdf $pdf, float $tableWidth): void
    {
        $pdf->drawColor($this->rowSeparatorColor)
            ->lineWidth(0.15)
            ->line(
                static::MARGIN_HORIZONTAL,
                $pdf->getY(),
                static::MARGIN_HORIZONTAL + $tableWidth,
                $pdf->getY(),
            );
    }

    // ══════════════════════════════════════════════
    //  HELPERS DE TEXTO
    // ══════════════════════════════════════════════

    /** Converte string para maiúsculas (UTF-8). */
    protected function upper(string $text): string
    {
        return mb_strtoupper($text, 'UTF-8');
    }

    // ══════════════════════════════════════════════
    //  RESOLUÇÃO DE LOGOS
    // ══════════════════════════════════════════════

    /** Resolve o caminho da logo branca do header. */
    protected function resolveHeaderLogoPath(): ?string
    {
        return is_file($this->headerDefaultLogoPath) ? $this->headerDefaultLogoPath : null;
    }

    /**
     * Resolve o caminho de um asset sob o diretório público configurado em {@code paths.public}
     * (ex.: variável de ambiente {@code PZPDF_PUBLIC_PATH} no pacote portátil).
     */
    protected function resolveLocalAssetPath(string $relativePath): ?string
    {
        $base = (string) $this->configuration->get('paths.public', '');
        if ($base === '') {
            return null;
        }

        $base = rtrim($base, '/\\');
        $rel = ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        $path = $base.DIRECTORY_SEPARATOR.$rel;

        return is_file($path) ? $path : null;
    }

    /**
     * Resolve um parâmetro de logo para caminho de arquivo.
     *
     * @param  string|Closure|null  $logo  Parâmetro do logo
     * @param  string  $scope  'header' ou 'footer' — determina o logo padrão
     */
    private function resolveLogoParam(string|Closure|null $logo, string $scope): ?string
    {
        if ($logo instanceof Closure) {
            return null;
        }

        if ($logo === null) {
            return $scope === 'header'
                ? $this->resolveHeaderLogoPath()
                : (is_file($this->footerDefaultLogoPath) ? $this->footerDefaultLogoPath : null);
        }

        if ($logo === '') {
            return null;
        }

        return is_file($logo) ? $logo : null;
    }

    // ══════════════════════════════════════════════
    //  PALETA DE CORES
    // ══════════════════════════════════════════════

    /** Carrega margens, logos e medidas de layout a partir da configuração injetada. */
    private function initLayoutConfig(): void
    {
        $this->headerDefaultLogoPath = (string) $this->configuration->get('logos.header', '');
        $this->footerDefaultLogoPath = (string) $this->configuration->get('logos.footer', '');
        $this->marginLeft = (float) $this->configuration->get('margins.left', static::MARGIN_HORIZONTAL);
        $this->marginTop = (float) $this->configuration->get('margins.top', static::MARGIN_TOP);
        $this->marginRight = (float) $this->configuration->get('margins.right', static::MARGIN_HORIZONTAL);
        $this->footerBreakMargin = (float) $this->configuration->get('margins.footer_break', static::FOOTER_BREAK_MARGIN);
        $this->headerBarHeight = (float) $this->configuration->get('layout.header_bar_height', static::HEADER_BAR_HEIGHT);
        $this->headerLogoXOffset = (float) $this->configuration->get('layout.header_logo.x_offset', 1.0);
        $this->headerLogoY = (float) $this->configuration->get('layout.header_logo.y', 5.0);
        $this->headerLogoHeight = (float) $this->configuration->get('layout.header_logo.height', 10.0);
        $this->headerTextStartX = (float) $this->configuration->get('layout.header_logo.text_start_x', 22.0);
        $this->footerLogoWidth = (float) $this->configuration->get('layout.footer_logo.width', 20.0);
        $this->footerLogoYOffset = (float) $this->configuration->get('layout.footer_logo.y_offset', 1.5);
    }

    /** Inicializa a paleta corporativa a partir de {@code colors.*} na configuração. */
    private function initBaseColors(): void
    {
        $primary = (array) $this->configuration->get('colors.primary', ['r' => 51, 'g' => 122, 'b' => 183]);
        $text = (array) $this->configuration->get('colors.text', ['r' => 50, 'g' => 50, 'b' => 50]);
        $groupBg = (array) $this->configuration->get('colors.group_bg', ['r' => 213, 'g' => 213, 'b' => 213]);
        $border = (array) $this->configuration->get('colors.border', ['r' => 178, 'g' => 178, 'b' => 178]);
        $rowSeparator = (array) $this->configuration->get('colors.row_separator', $border);
        $textLight = (array) $this->configuration->get('colors.text_light', ['r' => 100, 'g' => 100, 'b' => 100]);

        $this->brandColor = PzPdfColor::rgb((int) ($primary['r'] ?? 51), (int) ($primary['g'] ?? 122), (int) ($primary['b'] ?? 183));
        $this->columnTitleColor = PzPdfColor::rgb((int) ($text['r'] ?? 50), (int) ($text['g'] ?? 50), (int) ($text['b'] ?? 50));
        $this->groupRowBackground = PzPdfColor::rgb((int) ($groupBg['r'] ?? 213), (int) ($groupBg['g'] ?? 213), (int) ($groupBg['b'] ?? 213));
        $this->cardBorderColor = PzPdfColor::rgb((int) ($border['r'] ?? 178), (int) ($border['g'] ?? 178), (int) ($border['b'] ?? 178));
        $this->footerLineColor = PzPdfColor::rgb((int) ($border['r'] ?? 178), (int) ($border['g'] ?? 178), (int) ($border['b'] ?? 178));
        $this->footerTextColor = PzPdfColor::rgb((int) ($textLight['r'] ?? 100), (int) ($textLight['g'] ?? 100), (int) ($textLight['b'] ?? 100));
        $this->rowSeparatorColor = PzPdfColor::rgb(
            (int) ($rowSeparator['r'] ?? ($border['r'] ?? 178)),
            (int) ($rowSeparator['g'] ?? ($border['g'] ?? 178)),
            (int) ($rowSeparator['b'] ?? ($border['b'] ?? 178)),
        );
    }
}
