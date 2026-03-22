<?php

declare(strict_types=1);

namespace Puzl\PzPdf\ValueObject;

use Puzl\PzPdf\Contract\PzPdfColorInterface;
use Puzl\PzPdf\Exception\PzPdfException;

/**
 * Representa um color stop imutável para gradientes PDF.
 */
final readonly class PzPdfGradientStop
{
    /**
     * @throws PzPdfException Se a posição estiver fora do range 0.0-1.0
     */
    public function __construct(
        public float $position,
        public PzPdfColorInterface $color,
    ) {
        if ($position < 0.0 || $position > 1.0) {
            throw new PzPdfException(
                "Invalid gradient stop position: {$position}. "
                .'The value must be between 0.0 and 1.0.'
            );
        }
    }

    /**
     * @throws PzPdfException Se a posição estiver fora do range 0.0-1.0
     */
    public static function at(float $position, PzPdfColorInterface $color): self
    {
        return new self($position, $color);
    }
}
