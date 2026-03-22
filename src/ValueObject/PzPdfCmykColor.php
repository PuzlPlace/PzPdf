<?php

declare(strict_types=1);

namespace Puzl\PzPdf\ValueObject;

use Puzl\PzPdf\Contract\PzPdfColorInterface;
use Puzl\PzPdf\Exception\PzPdfException;

/**
 * Representa uma cor CMYK imutável para uso em operações PDF.
 *
 * Cada componente (cyan, magenta, yellow, black) deve estar no range 0-100.
 */
final readonly class PzPdfCmykColor implements PzPdfColorInterface
{
    /**
     * @throws PzPdfException Se qualquer componente estiver fora do range 0-100
     */
    public function __construct(
        public int $cyan,
        public int $magenta,
        public int $yellow,
        public int $black,
    ) {
        self::validateComponent('cyan', $cyan);
        self::validateComponent('magenta', $magenta);
        self::validateComponent('yellow', $yellow);
        self::validateComponent('black', $black);
    }

    /**
     * @throws PzPdfException Se qualquer componente estiver fora do range 0-100
     */
    public static function cmyk(int $c, int $m, int $y, int $k): self
    {
        return new self($c, $m, $y, $k);
    }

    /**
     * @return array{int, int, int, int}
     */
    public function toArray(): array
    {
        return [$this->cyan, $this->magenta, $this->yellow, $this->black];
    }

    /**
     * @throws PzPdfException Se o valor estiver fora do range 0-100
     */
    private static function validateComponent(string $name, int $value): void
    {
        if ($value < 0 || $value > 100) {
            throw new PzPdfException(
                "Invalid CMYK component '{$name}': {$value}. "
                .'The value must be between 0 and 100.'
            );
        }
    }
}
