<?php

declare(strict_types=1);

namespace Puzl\PzPdf\ValueObject;

use Puzl\PzPdf\Contract\PzPdfColorInterface;
use Puzl\PzPdf\Exception\PzPdfException;

/**
 * Representa uma cor RGB imutável para uso em operações PDF.
 *
 * Cada componente (red, green, blue) deve estar no range 0-255.
 */
final readonly class PzPdfColor implements PzPdfColorInterface
{
    /**
     * @throws PzPdfException Se qualquer componente estiver fora do range 0-255
     */
    public function __construct(
        public int $red,
        public int $green,
        public int $blue,
    ) {
        self::validateComponent('red', $red);
        self::validateComponent('green', $green);
        self::validateComponent('blue', $blue);
    }

    /**
     * @throws PzPdfException Se qualquer componente estiver fora do range 0-255
     */
    public static function rgb(int $r, int $g, int $b): self
    {
        return new self($r, $g, $b);
    }

    /**
     * Aceita formatos '#337ab7' e '337ab7' (com ou sem #, case-insensitive).
     *
     * @throws PzPdfException Se o formato hexadecimal for inválido
     */
    public static function hex(string $hex): self
    {
        $normalized = ltrim($hex, '#');

        if (preg_match('/^[0-9a-fA-F]{6}$/', $normalized) !== 1) {
            throw new PzPdfException(
                "PzPdfColor: valor hexadecimal '{$hex}' inválido. Formato esperado: '#RRGGBB' ou 'RRGGBB' (6 dígitos hexadecimais)."
            );
        }

        $r = (int) hexdec(substr($normalized, 0, 2));
        $g = (int) hexdec(substr($normalized, 2, 2));
        $b = (int) hexdec(substr($normalized, 4, 2));

        return new self($r, $g, $b);
    }

    public static function white(): self
    {
        return new self(255, 255, 255);
    }

    public static function black(): self
    {
        return new self(0, 0, 0);
    }

    /**
     * @return array{int, int, int}
     */
    public function toArray(): array
    {
        return [$this->red, $this->green, $this->blue];
    }

    /**
     * @throws PzPdfException Se o valor estiver fora do range 0-255
     */
    private static function validateComponent(string $name, int $value): void
    {
        if ($value < 0 || $value > 255) {
            throw new PzPdfException(
                "PzPdfColor: valor de {$name} ({$value}) fora do range 0-255."
            );
        }
    }
}
