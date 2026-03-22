<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Internal;

use Closure;
use Fawno\FPDF\FawnoFPDF;
use Puzl\PzPdf\Internal\Trait\PzPdfAlphaSupport;
use Puzl\PzPdf\Internal\Trait\PzPdfGradientSupport;

/**
 * Ponte com {@see FawnoFPDF}: header/footer via closures, alpha, gradientes e utilitários de stream.
 *
 * FPDF não é thread-safe; cada instância de documento deve usar sua própria bridge.
 *
 * @internal Motor fixo FPDF — não faz parte da API pública estável.
 */
final class PzPdfFpdfBridge extends FawnoFPDF
{
    use PzPdfAlphaSupport;
    use PzPdfGradientSupport;

    private ?Closure $headerCallback = null;

    private ?Closure $footerCallback = null;

    public function setHeaderCallback(?Closure $callback): void
    {
        $this->headerCallback = $callback;
    }

    public function setFooterCallback(?Closure $callback): void
    {
        $this->footerCallback = $callback;
    }

    public function Header(): void
    {
        if ($this->headerCallback !== null) {
            ($this->headerCallback)();
        }
    }

    public function Footer(): void
    {
        if ($this->footerCallback !== null) {
            ($this->footerCallback)();
        }
    }

    public function rawOut(string $s): void
    {
        $this->_out($s);
    }

    public function getScaleFactor(): float
    {
        return $this->k;
    }

    public function getCurrentPageHeight(): float
    {
        return $this->h;
    }

    public function getLeftMargin(): float
    {
        return $this->lMargin;
    }

    public function getRightMargin(): float
    {
        return $this->rMargin;
    }

    public function getBottomMargin(): float
    {
        return $this->bMargin;
    }

    protected function _putresourcedict(): void
    {
        parent::_putresourcedict();
        $this->_putresourcedict_alpha();
        $this->_putresourcedict_gradient();
    }

    protected function _putresources(): void
    {
        $this->_putresources_alpha();
        $this->_putresources_gradient();
        parent::_putresources();
    }

    protected function _enddoc(): void
    {
        $this->_enddoc_alpha();
        parent::_enddoc();
    }
}
