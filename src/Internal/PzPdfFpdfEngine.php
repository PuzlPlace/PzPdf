<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Internal;

use Closure;
use Puzl\PzPdf\Contract\PzPdfColorInterface;
use Puzl\PzPdf\Enum\PzPdfDisplayLayout;
use Puzl\PzPdf\Enum\PzPdfDisplayZoom;
use Puzl\PzPdf\Enum\PzPdfFontStyle;
use Puzl\PzPdf\Enum\PzPdfImageAlign;
use Puzl\PzPdf\Enum\PzPdfImageFit;
use Puzl\PzPdf\Enum\PzPdfImageValign;
use Puzl\PzPdf\Enum\PzPdfLineCap;
use Puzl\PzPdf\Enum\PzPdfLineJoin;
use Puzl\PzPdf\Enum\PzPdfOutputMode;
use Puzl\PzPdf\Enum\PzPdfPageOrientation;
use Puzl\PzPdf\Enum\PzPdfPageSize;
use Puzl\PzPdf\Enum\PzPdfTextAlignment;
use Puzl\PzPdf\Enum\PzPdfTextDirection;
use Puzl\PzPdf\Exception\PzPdfException;
use Puzl\PzPdf\Service\PzPdfFpdfImageTypeResolver;
use Puzl\PzPdf\ValueObject\PzPdfGradientStop;
use Puzl\PzPdf\ValueObject\PzPdfPageDimension;
use Kaareln\SVGPathData\Attributes\PathData\ArcCurve;
use Kaareln\SVGPathData\Attributes\PathData\BezierCurve;
use Kaareln\SVGPathData\Attributes\PathData\ClosePath;
use Kaareln\SVGPathData\Attributes\PathData\HorizontalLine;
use Kaareln\SVGPathData\Attributes\PathData\Line;
use Kaareln\SVGPathData\Attributes\PathData\Move;
use Kaareln\SVGPathData\Attributes\PathData\QuadraticCurve;
use Kaareln\SVGPathData\Attributes\PathData\RelativeArcCurve;
use Kaareln\SVGPathData\Attributes\PathData\RelativeBezierCurve;
use Kaareln\SVGPathData\Attributes\PathData\RelativeHorizontalLine;
use Kaareln\SVGPathData\Attributes\PathData\RelativeLine;
use Kaareln\SVGPathData\Attributes\PathData\RelativeMove;
use Kaareln\SVGPathData\Attributes\PathData\RelativeQuadraticCurve;
use Kaareln\SVGPathData\Attributes\PathData\RelativeVerticalLine;
use Kaareln\SVGPathData\Attributes\PathData\VerticalLine;
use Kaareln\SVGPathData\Attributes\SVGPathData;

/**
 * Orquestra operações de baixo nível sobre FPDF (texto, vetorial, alpha, gradientes).
 *
 * Texto de entrada em UTF-8 é convertido para ISO-8859-1 antes de chamar FPDF.
 * Uma instância por documento — FPDF não é seguro entre threads/processos compartilhados.
 *
 * @internal Consumo pela fachada {@see \Puzl\PzPdf\PzPdf} (Task 4.0); não é API pública estável.
 */
final class PzPdfFpdfEngine
{
    private readonly PzPdfFpdfBridge $bridge;

    public function __construct()
    {
        $this->bridge = new PzPdfFpdfBridge;
    }

    /**
     * Dimensões em mm para tamanhos de página não suportados nativamente pelo FPDF.
     *
     * FPDF suporta apenas: a3, a4, a5, letter, legal.
     * Os demais são convertidos para arrays [largura, altura] em mm.
     *
     * @var array<string, array{float, float}>
     */
    private const EXTENDED_PAGE_SIZES = [
        'A6' => [105.0, 148.0],
        'B4' => [250.0, 353.0],
        'B5' => [176.0, 250.0],
        'Folio' => [210.0, 330.0],
        'Executive' => [184.15, 266.7],
        'Tabloid' => [279.4, 431.8],
    ];

    /** {@inheritDoc} */
    public function addPage(PzPdfPageOrientation $orientation, PzPdfPageSize $size): void
    {
        $sizeParam = self::EXTENDED_PAGE_SIZES[$size->value] ?? $size->value;
        $this->bridge->AddPage($orientation->value, $sizeParam);
    }

    /** {@inheritDoc} */
    public function getPageWidth(): float
    {
        return $this->bridge->GetPageWidth();
    }

    /** {@inheritDoc} */
    public function getPageHeight(): float
    {
        return $this->bridge->GetPageHeight();
    }

    /** {@inheritDoc} */
    public function pageNo(): int
    {
        return $this->bridge->PageNo();
    }

    /** {@inheritDoc} */
    public function aliasNbPages(string $alias = '{nb}'): void
    {
        $this->bridge->AliasNbPages($this->encode($alias));
    }

    /** {@inheritDoc} */
    public function setFont(string $family, PzPdfFontStyle $style, float $size): void
    {
        $this->bridge->SetFont($family, $style->value, $size);
    }

    /** {@inheritDoc} */
    public function addFont(string $family, PzPdfFontStyle $style, string $file, string $dir = ''): void
    {
        $this->bridge->AddFont($family, $style->value, $file, $dir);
    }

    /** {@inheritDoc} */
    public function getStringWidth(string $text): float
    {
        return $this->bridge->GetStringWidth($this->encode($text));
    }

    /** {@inheritDoc} */
    public function cell(
        float $w,
        float $h,
        string $text,
        int $border,
        int $ln,
        PzPdfTextAlignment $align,
        bool $fill,
        string|int $link = '',
    ): void {
        $this->bridge->Cell(
            $w,
            $h,
            $this->encode($text),
            $border,
            $ln,
            $align->value,
            $fill,
            $link,
        );
    }

    /** {@inheritDoc} */
    public function multiCell(
        float $w,
        float $h,
        string $text,
        int $border,
        PzPdfTextAlignment $align,
        bool $fill,
    ): void {
        $this->bridge->MultiCell(
            $w,
            $h,
            $this->encode($text),
            $border,
            $align->value,
            $fill,
        );
    }

    /** {@inheritDoc} */
    public function ln(float $h): void
    {
        $this->bridge->Ln($h);
    }

    /** {@inheritDoc} */
    public function setXY(float $x, float $y): void
    {
        $this->bridge->SetXY($x, $y);
    }

    /** {@inheritDoc} */
    public function getX(): float
    {
        return $this->bridge->GetX();
    }

    /** {@inheritDoc} */
    public function getY(): float
    {
        return $this->bridge->GetY();
    }

    /** {@inheritDoc} */
    public function setMargins(float $left, float $top, float $right): void
    {
        $this->bridge->SetMargins($left, $top, $right);
    }

    /** {@inheritDoc} */
    public function setAutoPageBreak(bool $auto, float $margin): void
    {
        $this->bridge->SetAutoPageBreak($auto, $margin);
    }

    /** {@inheritDoc} */
    public function line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->bridge->Line($x1, $y1, $x2, $y2);
    }

    /** {@inheritDoc} */
    public function rect(float $x, float $y, float $w, float $h, string $style): void
    {
        $this->bridge->Rect($x, $y, $w, $h, $style);
    }

    /** {@inheritDoc} */
    public function roundedRect(float $x, float $y, float $w, float $h, float $radius, string $style): void
    {
        $this->bridge->RoundedRect($x, $y, $w, $h, $radius, '1111', $style);
    }

    /** {@inheritDoc} */
    public function setLineWidth(float $width): void
    {
        $this->bridge->SetLineWidth($width);
    }

    /** {@inheritDoc} */
    public function setDrawColor(PzPdfColorInterface $color): void
    {
        $this->bridge->SetDrawColor(...$color->toArray());
    }

    /** {@inheritDoc} */
    public function setFillColor(PzPdfColorInterface $color): void
    {
        $this->bridge->SetFillColor(...$color->toArray());
    }

    /** {@inheritDoc} */
    public function setTextColor(PzPdfColorInterface $color): void
    {
        $this->bridge->SetTextColor(...$color->toArray());
    }

    /** {@inheritDoc} */
    public function image(string $file, float $x, float $y, float $w, float $h, string $type, string|int $link = ''): void
    {
        $this->putRasterImageFromFile($file, $x, $y, $w, $h, $type, $link);
    }

    /** {@inheritDoc} */
    public function output(string $name, PzPdfOutputMode $mode): string
    {
        return $this->bridge->Output($mode->value, $name);
    }

    /** {@inheritDoc} */
    public function setHeaderCallback(?Closure $callback): void
    {
        $this->bridge->setHeaderCallback($callback);
    }

    /** {@inheritDoc} */
    public function setFooterCallback(?Closure $callback): void
    {
        $this->bridge->setFooterCallback($callback);
    }

    /** {@inheritDoc} */
    public function addPageWithDimension(PzPdfPageOrientation $orientation, PzPdfPageDimension $dimension): void
    {
        $this->bridge->AddPage($orientation->value, $dimension->toFpdfParam());
    }

    /** {@inheritDoc} */
    public function setTitle(string $title): void
    {
        $this->bridge->SetTitle($title, true);
    }

    /** {@inheritDoc} */
    public function setAuthor(string $author): void
    {
        $this->bridge->SetAuthor($author, true);
    }

    /** {@inheritDoc} */
    public function setSubject(string $subject): void
    {
        $this->bridge->SetSubject($subject, true);
    }

    /** {@inheritDoc} */
    public function setKeywords(string $keywords): void
    {
        $this->bridge->SetKeywords($keywords, true);
    }

    /** {@inheritDoc} */
    public function setCreator(string $creator): void
    {
        $this->bridge->SetCreator($creator, true);
    }

    /** {@inheritDoc} */
    public function setDisplayMode(PzPdfDisplayZoom $zoom, PzPdfDisplayLayout $layout): void
    {
        $this->bridge->SetDisplayMode($zoom->value, $layout->value);
    }

    /** {@inheritDoc} */
    public function setLineCap(PzPdfLineCap $cap): void
    {
        $this->bridge->SetLineStyle(['cap' => $cap->value]);
    }

    /** {@inheritDoc} */
    public function setLineJoin(PzPdfLineJoin $join): void
    {
        $this->bridge->SetLineStyle(['join' => $join->value]);
    }

    /** {@inheritDoc} */
    public function setDash(float $length, float $space, float $phase): void
    {
        $resolvedSpace = $space > 0 ? $space : $length;

        $this->bridge->SetLineStyle([
            'dash' => sprintf('%.2F,%.2F', $length, $resolvedSpace),
            'phase' => $phase,
        ]);
    }

    /** {@inheritDoc} */
    public function removeDash(): void
    {
        $this->bridge->SetLineStyle(['dash' => '']);
    }

    // --- Estado gráfico ---

    private int $stateDepth = 0;

    /** {@inheritDoc} */
    public function save(): void
    {
        $this->bridge->StartTransform();
        $this->stateDepth++;
    }

    /** {@inheritDoc} */
    public function restore(): void
    {
        if ($this->stateDepth <= 0) {
            throw new PzPdfException(
                'Não é possível restaurar o estado gráfico: nenhum save() pendente. '
                .'Chame pushState() antes de popState().'
            );
        }
        $this->bridge->StopTransform();
        $this->stateDepth--;
    }

    // --- Formas vetoriais ---

    /** {@inheritDoc} */
    public function circle(float $x, float $y, float $r, string $style): void
    {
        $this->bridge->Circle($x, $y, $r, 0, 360, $style);
    }

    /** {@inheritDoc} */
    public function ellipse(float $x, float $y, float $rx, float $ry, float $angle, string $style): void
    {
        $this->bridge->Ellipse($x, $y, $rx, $ry, $angle, 0, 360, $style);
    }

    /** {@inheritDoc} */
    public function polygon(array $points, string $style): void
    {
        $flat = [];
        foreach ($points as $point) {
            $flat[] = $point[0];
            $flat[] = $point[1];
        }
        $this->bridge->Polygon($flat, $style);
    }

    /** {@inheritDoc} */
    public function regularPolygon(float $x, float $y, float $r, int $sides, float $angle, string $style): void
    {
        $this->bridge->RegularPolygon($x, $y, $r, $sides, $angle, false, $style);
    }

    /** {@inheritDoc} */
    public function curve(float $x0, float $y0, float $x1, float $y1, float $x2, float $y2, float $x3, float $y3, string $style): void
    {
        $this->bridge->Curve($x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3, $style);
    }

    /** {@inheritDoc} */
    public function starPolygon(float $x, float $y, float $r, int $nv, int $ng, float $angle, string $style): void
    {
        $this->bridge->StarPolygon($x, $y, $r, $nv, $ng, $angle, false, $style);
    }

    // --- Transformações 2D ---

    /** {@inheritDoc} */
    public function translate(float $x, float $y): void
    {
        $this->bridge->Translate($x, $y);
    }

    /** {@inheritDoc} */
    public function rotate(float $angle, ?float $x = null, ?float $y = null): void
    {
        $this->bridge->Rotate($angle, $x, $y);
    }

    /** {@inheritDoc} */
    public function scale(float $sx, float $sy, ?float $x = null, ?float $y = null): void
    {
        $this->bridge->Scale($sx, $sy, $x, $y);
    }

    /** {@inheritDoc} */
    public function scaleXY(float $s, ?float $x = null, ?float $y = null): void
    {
        $this->bridge->ScaleXY($s, $x, $y);
    }

    /** {@inheritDoc} */
    public function skew(float $ax, float $ay, ?float $x = null, ?float $y = null): void
    {
        $this->bridge->Skew($ax, $ay, $x, $y);
    }

    /** {@inheritDoc} */
    public function mirrorH(?float $x = null): void
    {
        $this->bridge->MirrorH($x);
    }

    /** {@inheritDoc} */
    public function mirrorV(?float $y = null): void
    {
        $this->bridge->MirrorV($y);
    }

    // --- Links ---

    /** {@inheritDoc} */
    public function addLink(): int
    {
        return $this->bridge->AddLink();
    }

    /** {@inheritDoc} */
    public function setLink(int $linkId, float $y, int $page): void
    {
        $this->bridge->SetLink($linkId, $y, $page);
    }

    /** {@inheritDoc} */
    public function link(float $x, float $y, float $w, float $h, string|int $link): void
    {
        $this->bridge->Link($x, $y, $w, $h, $link);
    }

    /** {@inheritDoc} */
    public function write(float $h, string $text, string|int $link = ''): void
    {
        $this->bridge->Write($h, $this->encode($text), $link);
    }

    // --- Texto e imagem rotacionados ---

    /** {@inheritDoc} */
    public function rotatedText(float $x, float $y, string $text, float $angle): void
    {
        $this->bridge->RotatedText($x, $y, $this->encode($text), $angle);
    }

    /** {@inheritDoc} */
    public function rotatedImage(string $file, float $x, float $y, float $w, float $h, float $angle): void
    {
        $this->bridge->RotatedImage($file, $x, $y, $w, $h, $angle);
    }

    /** {@inheritDoc} */
    public function textWithDirection(float $x, float $y, string $text, PzPdfTextDirection $direction): void
    {
        $this->bridge->TextWithDirection($x, $y, $this->encode($text), $direction->value);
    }

    // --- Proteção ---

    /** {@inheritDoc} */
    public function setProtection(array $permissions, ?string $userPass, ?string $ownerPass): void
    {
        $this->bridge->SetProtection($permissions, $userPass ?? '', $ownerPass);
    }

    // --- Bookmarks ---

    /** {@inheritDoc} */
    public function bookmark(string $title, int $level, ?float $y): void
    {
        $this->bridge->Bookmark($title, true, $level, $y ?? -1);
    }

    // --- Imagens em memória ---

    /** {@inheritDoc} */
    public function imageFromString(string $data, float $x, float $y, float $w, float $h): void
    {
        $this->bridge->MemImage($data, $x, $y, $w, $h);
    }

    /** {@inheritDoc} */
    public function imageFromGd(\GdImage $gd, float $x, float $y, float $w, float $h): void
    {
        $this->bridge->GDImage($gd, $x, $y, $w, $h);
    }

    // --- Margens individuais ---

    /** {@inheritDoc} */
    public function setLeftMargin(float $margin): void
    {
        $this->bridge->SetLeftMargin($margin);
    }

    /** {@inheritDoc} */
    public function setTopMargin(float $margin): void
    {
        $this->bridge->SetTopMargin($margin);
    }

    /** {@inheritDoc} */
    public function setRightMargin(float $margin): void
    {
        $this->bridge->SetRightMargin($margin);
    }

    // --- Medição de texto ---

    /** {@inheritDoc} */
    public function getStringHeight(string $text, float $width, float $lineHeight): float
    {
        $text = $this->encode($text);
        $lines = 0;

        foreach (explode("\n", $text) as $paragraph) {
            if ($paragraph === '') {
                $lines++;

                continue;
            }

            $currentWidth = 0.0;
            $words = explode(' ', $paragraph);
            $lines++;

            foreach ($words as $word) {
                $wordWidth = $this->bridge->GetStringWidth($word.' ');
                if ($currentWidth + $wordWidth > $width && $currentWidth > 0) {
                    $lines++;
                    $currentWidth = $wordWidth;
                } else {
                    $currentWidth += $wordWidth;
                }
            }
        }

        return $lines * $lineHeight;
    }

    // --- Posicionamento avançado de imagens ---

    /** {@inheritDoc} */
    public function imageFit(
        string $file,
        float $x,
        float $y,
        float $areaW,
        float $areaH,
        PzPdfImageFit $fit,
        PzPdfImageAlign $align,
        PzPdfImageValign $valign,
    ): void {
        $this->assertPositiveDimension($areaW, 'Largura da área (imageFit)');
        $this->assertPositiveDimension($areaH, 'Altura da área (imageFit)');

        $info = @getimagesize($file);
        if ($info === false) {
            throw new PzPdfException(
                "imageFit() não conseguiu obter as dimensões da imagem '{$file}'. "
                .'Verifique se o arquivo existe e é um formato de imagem suportado (PNG, JPEG, GIF).'
            );
        }

        [$origW, $origH] = $info;
        $imgRatio = $origW / $origH;
        $areaRatio = $areaW / $areaH;

        switch ($fit) {
            case PzPdfImageFit::Fit:
                if ($imgRatio > $areaRatio) {
                    $renderW = $areaW;
                    $renderH = $areaW / $imgRatio;
                } else {
                    $renderH = $areaH;
                    $renderW = $areaH * $imgRatio;
                }
                break;

            case PzPdfImageFit::Cover:
                if ($imgRatio > $areaRatio) {
                    $renderH = $areaH;
                    $renderW = $areaH * $imgRatio;
                } else {
                    $renderW = $areaW;
                    $renderH = $areaW / $imgRatio;
                }
                break;

            case PzPdfImageFit::None:
            default:
                $renderW = $this->pixelsToMm($origW);
                $renderH = $this->pixelsToMm($origH);
                break;
        }

        $offsetX = match ($align) {
            PzPdfImageAlign::Left => 0.0,
            PzPdfImageAlign::Center => ($areaW - $renderW) / 2,
            PzPdfImageAlign::Right => $areaW - $renderW,
        };

        $offsetY = match ($valign) {
            PzPdfImageValign::Top => 0.0,
            PzPdfImageValign::Center => ($areaH - $renderH) / 2,
            PzPdfImageValign::Bottom => $areaH - $renderH,
        };

        $this->putRasterImageFromFile($file, $x + $offsetX, $y + $offsetY, $renderW, $renderH);
    }

    /** {@inheritDoc} */
    public function imageScaled(string $file, float $x, float $y, float $scale): void
    {
        $info = @getimagesize($file);
        if ($info === false) {
            throw new PzPdfException(
                "imageScaled() não conseguiu obter as dimensões da imagem '{$file}'. "
                .'Verifique se o arquivo existe e é um formato de imagem suportado (PNG, JPEG, GIF).'
            );
        }

        [$origW, $origH] = $info;
        $renderW = $this->pixelsToMm($origW) * $scale;
        $renderH = $this->pixelsToMm($origH) * $scale;

        $this->putRasterImageFromFile($file, $x, $y, $renderW, $renderH);
    }

    /**
     * Insere imagem raster a partir de arquivo, corrigindo tipo FPDF quando a extensão
     * não bate com o conteúdo (ex.: .jpg com PNG) e usando GD para WebP/outros.
     */
    private function putRasterImageFromFile(
        string $file,
        float $x,
        float $y,
        float $w,
        float $h,
        string $fallbackType = '',
        string|int $link = '',
    ): void {
        $resolved = PzPdfFpdfImageTypeResolver::resolve($file);

        if ($resolved['mode'] === PzPdfFpdfImageTypeResolver::MODE_GD) {
            $gd = $resolved['gd'];

            try {
                $this->bridge->GDImage($gd, $x, $y, $w, $h, $link);
            } finally {
                imagedestroy($gd);
            }

            return;
        }

        $fpdfType = $resolved['type'] !== '' ? $resolved['type'] : $fallbackType;
        $this->bridge->Image($file, $x, $y, $w, $h, $fpdfType, $link);
    }

    // --- Barcodes e QR Codes ---

    /** {@inheritDoc} */
    public function barcodeEAN13(float $x, float $y, string $barcode, float $h, float $w): void
    {
        $this->bridge->BarcodeEAN13($x, $y, $barcode, null, false, $w, $h);
    }

    /** {@inheritDoc} */
    public function barcodeCode128(float $x, float $y, string $code, float $w, float $h): void
    {
        $this->bridge->Code128($x, $y, $code, $w, $h);
    }

    /** {@inheritDoc} */
    public function qrCode(float $x, float $y, string $data, float $size): void
    {
        $this->bridge->QRcode($x, $y, $size, $data);
    }

    // --- Anexos ---

    /** {@inheritDoc} */
    public function attach(string $file, string $name, string $desc): void
    {
        $this->bridge->Attach($file, $name, $desc);
    }

    /** {@inheritDoc} */
    public function openAttachmentPane(): void
    {
        $this->bridge->OpenAttachmentPane();
    }

    // --- Assinatura digital ---

    /** {@inheritDoc} */
    public function signatureField(float $x, float $y, float $w, float $h, string $name): void
    {
        $this->bridge->AddSignatureField($x, $y, $w, $h, $name);
    }

    // --- Texto circular ---

    /** {@inheritDoc} */
    public function circularText(float $x, float $y, float $r, string $text, string $align): void
    {
        $this->bridge->CircularText($x, $y, $r, $this->encode($text), $align);
    }

    // --- Opacidade ---

    /** {@inheritDoc} */
    public function setAlpha(float $alpha): void
    {
        $this->bridge->SetAlpha($alpha);
    }

    /** {@inheritDoc} */
    public function setFillAlpha(float $alpha): void
    {
        $this->bridge->SetFillAlpha($alpha);
    }

    /** {@inheritDoc} */
    public function setStrokeAlpha(float $alpha): void
    {
        $this->bridge->SetStrokeAlpha($alpha);
    }

    // --- Clipping paths ---

    /** {@inheritDoc} */
    public function clipRect(float $x, float $y, float $w, float $h): void
    {
        $this->assertPositiveDimension($w, 'Largura (clipRect)');
        $this->assertPositiveDimension($h, 'Altura (clipRect)');

        $k = $this->bridge->getScaleFactor();
        $pageH = $this->bridge->getCurrentPageHeight();

        $this->bridge->rawOut(sprintf(
            '%.2F %.2F %.2F %.2F re W n',
            $x * $k,
            ($pageH - $y) * $k,
            $w * $k,
            -$h * $k,
        ));
    }

    /** {@inheritDoc} */
    public function clipCircle(float $x, float $y, float $r): void
    {
        $this->clipEllipse($x, $y, $r, $r);
    }

    /** {@inheritDoc} */
    public function clipEllipse(float $x, float $y, float $rx, float $ry): void
    {
        $kappa = 0.5522847498;
        $k = $this->bridge->getScaleFactor();
        $pageH = $this->bridge->getCurrentPageHeight();

        $cx = $x * $k;
        $cy = ($pageH - $y) * $k;
        $rxk = $rx * $k;
        $ryk = $ry * $k;
        $lx = $kappa * $rxk;
        $ly = $kappa * $ryk;

        $this->bridge->rawOut(sprintf(
            '%.2F %.2F m',
            $cx + $rxk,
            $cy,
        ));

        $this->bridge->rawOut(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            $cx + $rxk, $cy + $ly,
            $cx + $lx, $cy + $ryk,
            $cx, $cy + $ryk,
        ));

        $this->bridge->rawOut(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            $cx - $lx, $cy + $ryk,
            $cx - $rxk, $cy + $ly,
            $cx - $rxk, $cy,
        ));

        $this->bridge->rawOut(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            $cx - $rxk, $cy - $ly,
            $cx - $lx, $cy - $ryk,
            $cx, $cy - $ryk,
        ));

        $this->bridge->rawOut(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            $cx + $lx, $cy - $ryk,
            $cx + $rxk, $cy - $ly,
            $cx + $rxk, $cy,
        ));

        $this->bridge->rawOut('W n');
    }

    // --- Gradientes ---

    /** {@inheritDoc} */
    public function linearGradient(
        float $x,
        float $y,
        float $w,
        float $h,
        array $stops,
        array $coords = [0, 0, 1, 0],
    ): void {
        $this->assertPositiveDimension($w, 'Largura (linearGradient)');
        $this->assertPositiveDimension($h, 'Altura (linearGradient)');
        $this->validateGradientStops($stops);
        $this->bridge->LinearGradient($x, $y, $w, $h, $stops, $coords);
    }

    /** {@inheritDoc} */
    public function radialGradient(
        float $x,
        float $y,
        float $w,
        float $h,
        array $stops,
        array $coords = [0.5, 0.5, 0.5, 0.5, 1],
    ): void {
        $this->assertPositiveDimension($w, 'Largura (radialGradient)');
        $this->assertPositiveDimension($h, 'Altura (radialGradient)');
        $this->validateGradientStops($stops);
        $this->bridge->RadialGradient($x, $y, $w, $h, $stops, $coords);
    }

    /**
     * @param  PzPdfGradientStop[]  $stops
     *
     * @throws PzPdfException
     */
    private function validateGradientStops(array $stops): void
    {
        if (count($stops) < 2) {
            throw new PzPdfException(
                'Gradientes requerem no mínimo 2 color stops, '
                .count($stops).' fornecido(s).'
            );
        }

        foreach ($stops as $i => $stop) {
            if (! $stop instanceof PzPdfGradientStop) {
                throw new PzPdfException(
                    "O item na posição {$i} não é uma instância de PzPdfGradientStop."
                );
            }
        }
    }

    // --- SVG path ---

    /** {@inheritDoc} */
    public function svgPath(string $pathData, string $style): void
    {
        if (trim($pathData) === '') {
            throw new PzPdfException(
                'svgPath() requer uma string de path data SVG não vazia.'
            );
        }

        try {
            $parsed = SVGPathData::fromString($pathData);
        } catch (\RuntimeException $e) {
            throw new PzPdfException(
                'Erro ao parsear SVG path data: '.$e->getMessage(),
                0,
                $e,
            );
        }

        $commands = $this->collectSvgCommands($parsed);
        if ($commands === []) {
            return;
        }

        $k = $this->bridge->getScaleFactor();
        $pageH = $this->bridge->getCurrentPageHeight();
        $pdfOps = '';
        $lastCp2X = null;
        $lastCp2Y = null;
        $lastCmd = null;

        foreach ($commands as $cmd) {
            $point = $cmd->getLastPoint();
            $x = (float) $point[0];
            $y = (float) $point[1];

            if ($cmd instanceof Move || ($cmd instanceof RelativeMove && ! $cmd instanceof RelativeLine)) {
                $pdfOps .= sprintf("%.2F %.2F m\n", $x * $k, ($pageH - $y) * $k);
                $lastCp2X = null;
                $lastCp2Y = null;
            } elseif ($cmd instanceof Line || $cmd instanceof RelativeLine) {
                $pdfOps .= sprintf("%.2F %.2F l\n", $x * $k, ($pageH - $y) * $k);
                $lastCp2X = null;
                $lastCp2Y = null;
            } elseif ($cmd instanceof HorizontalLine || $cmd instanceof RelativeHorizontalLine) {
                $pdfOps .= sprintf("%.2F %.2F l\n", $x * $k, ($pageH - $y) * $k);
                $lastCp2X = null;
                $lastCp2Y = null;
            } elseif ($cmd instanceof VerticalLine || $cmd instanceof RelativeVerticalLine) {
                $pdfOps .= sprintf("%.2F %.2F l\n", $x * $k, ($pageH - $y) * $k);
                $lastCp2X = null;
                $lastCp2Y = null;
            } elseif ($cmd instanceof BezierCurve || $cmd instanceof RelativeBezierCurve) {
                $points = $cmd->getPoints();
                if (count($points) === 3) {
                    $cp1x = (float) $points[0][0];
                    $cp1y = (float) $points[0][1];
                    $cp2x = (float) $points[1][0];
                    $cp2y = (float) $points[1][1];
                    $ex = (float) $points[2][0];
                    $ey = (float) $points[2][1];
                    $pdfOps .= sprintf(
                        "%.2F %.2F %.2F %.2F %.2F %.2F c\n",
                        $cp1x * $k, ($pageH - $cp1y) * $k,
                        $cp2x * $k, ($pageH - $cp2y) * $k,
                        $ex * $k, ($pageH - $ey) * $k,
                    );
                    $lastCp2X = $cp2x;
                    $lastCp2Y = $cp2y;
                } elseif (count($points) === 2) {
                    $prevPoint = $cmd->getPrevious()?->getLastPoint() ?? [0, 0];
                    $prevX = (float) $prevPoint[0];
                    $prevY = (float) $prevPoint[1];
                    if ($lastCp2X !== null && ($lastCmd instanceof BezierCurve || $lastCmd instanceof RelativeBezierCurve)) {
                        $cp1x = 2 * $prevX - $lastCp2X;
                        $cp1y = 2 * $prevY - $lastCp2Y;
                    } else {
                        $cp1x = $prevX;
                        $cp1y = $prevY;
                    }
                    $cp2x = (float) $points[0][0];
                    $cp2y = (float) $points[0][1];
                    $ex = (float) $points[1][0];
                    $ey = (float) $points[1][1];
                    $pdfOps .= sprintf(
                        "%.2F %.2F %.2F %.2F %.2F %.2F c\n",
                        $cp1x * $k, ($pageH - $cp1y) * $k,
                        $cp2x * $k, ($pageH - $cp2y) * $k,
                        $ex * $k, ($pageH - $ey) * $k,
                    );
                    $lastCp2X = $cp2x;
                    $lastCp2Y = $cp2y;
                }
            } elseif ($cmd instanceof QuadraticCurve || $cmd instanceof RelativeQuadraticCurve) {
                $points = $cmd->getPoints();
                $prevPoint = $cmd->getPrevious()?->getLastPoint() ?? [0, 0];
                $prevX = (float) $prevPoint[0];
                $prevY = (float) $prevPoint[1];

                if (count($points) === 2) {
                    $qx = (float) $points[0][0];
                    $qy = (float) $points[0][1];
                    $ex = (float) $points[1][0];
                    $ey = (float) $points[1][1];
                } elseif (count($points) === 1) {
                    if ($lastCp2X !== null && ($lastCmd instanceof QuadraticCurve || $lastCmd instanceof RelativeQuadraticCurve)) {
                        $qx = 2 * $prevX - $lastCp2X;
                        $qy = 2 * $prevY - $lastCp2Y;
                    } else {
                        $qx = $prevX;
                        $qy = $prevY;
                    }
                    $ex = (float) $points[0][0];
                    $ey = (float) $points[0][1];
                } else {
                    $lastCmd = $cmd;

                    continue;
                }

                $cp1x = $prevX + (2.0 / 3.0) * ($qx - $prevX);
                $cp1y = $prevY + (2.0 / 3.0) * ($qy - $prevY);
                $cp2x = $ex + (2.0 / 3.0) * ($qx - $ex);
                $cp2y = $ey + (2.0 / 3.0) * ($qy - $ey);
                $pdfOps .= sprintf(
                    "%.2F %.2F %.2F %.2F %.2F %.2F c\n",
                    $cp1x * $k, ($pageH - $cp1y) * $k,
                    $cp2x * $k, ($pageH - $cp2y) * $k,
                    $ex * $k, ($pageH - $ey) * $k,
                );
                $lastCp2X = $qx;
                $lastCp2Y = $qy;
            } elseif ($cmd instanceof ArcCurve || $cmd instanceof RelativeArcCurve) {
                $this->emitArcAsBezier($cmd, $k, $pageH, $pdfOps);
                $lastCp2X = null;
                $lastCp2Y = null;
            } elseif ($cmd instanceof ClosePath) {
                $pdfOps .= "h\n";
                $lastCp2X = null;
                $lastCp2Y = null;
            }

            $lastCmd = $cmd;
        }

        $op = match (strtoupper($style)) {
            'F' => 'f',
            'DF', 'FD' => 'B',
            default => 'S',
        };
        $pdfOps .= $op;

        $this->bridge->rawOut($pdfOps);
    }

    /**
     * Collects SVG path commands from the reverse-linked-list into a forward-ordered array.
     *
     * @return \Kaareln\SVGPathData\Attributes\PathData\PathDataCommandInterface[]
     */
    private function collectSvgCommands(SVGPathData $parsed): array
    {
        $commands = [];
        $parsed->rewind();
        while ($parsed->valid()) {
            $commands[] = $parsed->current();
            $parsed->next();
        }

        return array_reverse($commands);
    }

    /**
     * Approximates an SVG arc command as cubic Bézier curves.
     *
     * Uses the standard endpoint-to-center parameterization conversion
     * and splits arcs > 90° into multiple segments.
     */
    private function emitArcAsBezier(ArcCurve|RelativeArcCurve $arc, float $k, float $pageH, string &$pdfOps): void
    {
        $prevPoint = $arc->getPrevious()?->getLastPoint() ?? [0, 0];
        $x1 = (float) $prevPoint[0];
        $y1 = (float) $prevPoint[1];
        $endPoint = $arc->getLastPoint();
        $x2 = (float) $endPoint[0];
        $y2 = (float) $endPoint[1];

        $rx = abs($arc->rx);
        $ry = abs($arc->ry);

        if ($rx == 0 || $ry == 0) {
            $pdfOps .= sprintf("%.2F %.2F l\n", $x2 * $k, ($pageH - $y2) * $k);

            return;
        }

        $phi = deg2rad($arc->angle);
        $fA = $arc->largeArcFlag ? 1 : 0;
        $fS = $arc->sweepFlag ? 1 : 0;

        $cosPhi = cos($phi);
        $sinPhi = sin($phi);

        $dx2 = ($x1 - $x2) / 2.0;
        $dy2 = ($y1 - $y2) / 2.0;
        $x1p = $cosPhi * $dx2 + $sinPhi * $dy2;
        $y1p = -$sinPhi * $dx2 + $cosPhi * $dy2;

        $lambda = ($x1p * $x1p) / ($rx * $rx) + ($y1p * $y1p) / ($ry * $ry);
        if ($lambda > 1) {
            $sqrtLambda = sqrt($lambda);
            $rx *= $sqrtLambda;
            $ry *= $sqrtLambda;
        }

        $rxSq = $rx * $rx;
        $rySq = $ry * $ry;
        $x1pSq = $x1p * $x1p;
        $y1pSq = $y1p * $y1p;

        $num = $rxSq * $rySq - $rxSq * $y1pSq - $rySq * $x1pSq;
        $den = $rxSq * $y1pSq + $rySq * $x1pSq;
        $sq = max(0, $num / $den);
        $sq = sqrt($sq);
        if ($fA === $fS) {
            $sq = -$sq;
        }

        $cxp = $sq * $rx * $y1p / $ry;
        $cyp = -$sq * $ry * $x1p / $rx;

        $cx = $cosPhi * $cxp - $sinPhi * $cyp + ($x1 + $x2) / 2.0;
        $cy = $sinPhi * $cxp + $cosPhi * $cyp + ($y1 + $y2) / 2.0;

        $theta1 = $this->svgAngle(1, 0, ($x1p - $cxp) / $rx, ($y1p - $cyp) / $ry);
        $dTheta = $this->svgAngle(
            ($x1p - $cxp) / $rx, ($y1p - $cyp) / $ry,
            (-$x1p - $cxp) / $rx, (-$y1p - $cyp) / $ry,
        );

        if ($fS === 0 && $dTheta > 0) {
            $dTheta -= 2 * M_PI;
        } elseif ($fS === 1 && $dTheta < 0) {
            $dTheta += 2 * M_PI;
        }

        $segments = (int) ceil(abs($dTheta) / (M_PI / 2));
        $segAngle = $dTheta / $segments;

        for ($i = 0; $i < $segments; $i++) {
            $a1 = $theta1 + $i * $segAngle;
            $a2 = $theta1 + ($i + 1) * $segAngle;
            $this->emitArcSegment($cx, $cy, $rx, $ry, $a1, $a2, $cosPhi, $sinPhi, $k, $pageH, $pdfOps);
        }
    }

    /**
     * Emits a single arc segment (<=90°) as a cubic Bézier curve.
     */
    private function emitArcSegment(
        float $cx, float $cy, float $rx, float $ry,
        float $a1, float $a2,
        float $cosPhi, float $sinPhi,
        float $k, float $pageH, string &$pdfOps,
    ): void {
        $alpha = sin($a2 - $a1) * (sqrt(4 + 3 * pow(tan(($a2 - $a1) / 2), 2)) - 1) / 3;

        $cosA1 = cos($a1);
        $sinA1 = sin($a1);
        $cosA2 = cos($a2);
        $sinA2 = sin($a2);

        $ep1x = $rx * $cosA1;
        $ep1y = $ry * $sinA1;
        $ep2x = $rx * $cosA2;
        $ep2y = $ry * $sinA2;

        $q1x = $ep1x - $alpha * $rx * $sinA1;
        $q1y = $ep1y + $alpha * $ry * $cosA1;
        $q2x = $ep2x + $alpha * $rx * $sinA2;
        $q2y = $ep2y - $alpha * $ry * $cosA2;

        $cp1x = $cosPhi * $q1x - $sinPhi * $q1y + $cx;
        $cp1y = $sinPhi * $q1x + $cosPhi * $q1y + $cy;
        $cp2x = $cosPhi * $q2x - $sinPhi * $q2y + $cx;
        $cp2y = $sinPhi * $q2x + $cosPhi * $q2y + $cy;
        $endX = $cosPhi * $ep2x - $sinPhi * $ep2y + $cx;
        $endY = $sinPhi * $ep2x + $cosPhi * $ep2y + $cy;

        $pdfOps .= sprintf(
            "%.2F %.2F %.2F %.2F %.2F %.2F c\n",
            $cp1x * $k, ($pageH - $cp1y) * $k,
            $cp2x * $k, ($pageH - $cp2y) * $k,
            $endX * $k, ($pageH - $endY) * $k,
        );
    }

    private function svgAngle(float $ux, float $uy, float $vx, float $vy): float
    {
        $dot = $ux * $vx + $uy * $vy;
        $len = sqrt($ux * $ux + $uy * $uy) * sqrt($vx * $vx + $vy * $vy);
        $angle = acos(max(-1.0, min(1.0, $dot / $len)));
        if ($ux * $vy - $uy * $vx < 0) {
            $angle = -$angle;
        }

        return $angle;
    }

    // --- Colunas de texto ---

    /** {@inheritDoc} */
    public function textColumns(string $text, int $columns, float $gap, float $w, float $h): void
    {
        if ($columns < 2) {
            throw new PzPdfException(
                "textColumns() requer no mínimo 2 colunas, {$columns} fornecida(s)."
            );
        }

        $pageW = $this->bridge->GetPageWidth();
        $leftMargin = $this->bridge->getLeftMargin();
        $rightMargin = $this->bridge->getRightMargin();

        if ($w <= 0) {
            $w = $pageW - $leftMargin - $rightMargin;
        }
        if ($h <= 0) {
            $h = $this->bridge->GetPageHeight() - $this->bridge->GetY() - $this->bridge->getBottomMargin();
        }

        $totalGap = ($columns - 1) * $gap;
        $colWidth = ($w - $totalGap) / $columns;
        $startX = $this->bridge->GetX();
        $startY = $this->bridge->GetY();
        $text = $this->encode($text);

        $lineHeight = 5.0;
        $currentCol = 0;
        $colX = $startX;
        $colY = $startY;
        $maxY = $startY + $h;

        $paragraphs = explode("\n", $text);
        $remaining = [];
        foreach ($paragraphs as $para) {
            $words = $para !== '' ? explode(' ', $para) : [];
            $remaining[] = $words;
        }

        foreach ($remaining as $words) {
            if ($words === []) {
                $colY += $lineHeight;
                if ($colY + $lineHeight > $maxY) {
                    $currentCol++;
                    if ($currentCol >= $columns) {
                        break;
                    }
                    $colX = $startX + $currentCol * ($colWidth + $gap);
                    $colY = $startY;
                }

                continue;
            }

            $currentLine = '';
            foreach ($words as $word) {
                $testLine = $currentLine === '' ? $word : $currentLine.' '.$word;
                $testWidth = $this->bridge->GetStringWidth($testLine);

                if ($testWidth > $colWidth && $currentLine !== '') {
                    $this->bridge->SetXY($colX, $colY);
                    $this->bridge->Cell($colWidth, $lineHeight, $currentLine);
                    $colY += $lineHeight;
                    $currentLine = $word;

                    if ($colY + $lineHeight > $maxY) {
                        $currentCol++;
                        if ($currentCol >= $columns) {
                            break 2;
                        }
                        $colX = $startX + $currentCol * ($colWidth + $gap);
                        $colY = $startY;
                    }
                } else {
                    $currentLine = $testLine;
                }
            }

            if ($currentLine !== '') {
                $this->bridge->SetXY($colX, $colY);
                $this->bridge->Cell($colWidth, $lineHeight, $currentLine);
                $colY += $lineHeight;

                if ($colY + $lineHeight > $maxY) {
                    $currentCol++;
                    if ($currentCol >= $columns) {
                        break;
                    }
                    $colX = $startX + $currentCol * ($colWidth + $gap);
                    $colY = $startY;
                }
            }
        }
    }

    /**
     * Converte pixels para milímetros assumindo 96 DPI (padrão de tela).
     *
     * Fórmula: mm = px * 25.4 / 96
     */
    private function pixelsToMm(int $px): float
    {
        return $px * 25.4 / 96;
    }

    /**
     * Converte texto UTF-8 para ISO-8859-1 (encoding nativo do fawno/fpdf).
     *
     * Caracteres fora do range ISO-8859-1 são substituídos pelo mecanismo
     * padrão do mbstring (substituição por '?').
     */
    private function encode(string $text): string
    {
        $result = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');

        return is_string($result) ? $result : $text;
    }

    /**
     * @throws PzPdfException
     */
    private function assertPositiveDimension(float $value, string $label): void
    {
        if ($value <= 0) {
            throw new PzPdfException(
                "{$label} inválida: o valor deve ser maior que zero (recebido: {$value})."
            );
        }
    }
}
