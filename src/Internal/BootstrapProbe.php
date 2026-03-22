<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Internal;

/**
 * Internal class used to verify Composer PSR-4 autoload during package bootstrap.
 *
 * This type is not part of the public PzPdf API surface; it may be removed or
 * replaced once production entry points exist.
 */
final class BootstrapProbe
{
    /**
     * Returns a stable marker string for smoke tests.
     */
    public function label(): string
    {
        return 'pzpdf-bootstrap';
    }
}
