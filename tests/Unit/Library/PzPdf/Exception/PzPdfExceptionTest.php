<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Exception\PzPdfException;
use RuntimeException;

#[CoversClass(PzPdfException::class)]
final class PzPdfExceptionTest extends TestCase
{
    public function test_is_instance_of_runtime_exception(): void
    {
        $exception = new PzPdfException('test');

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function test_can_be_constructed_with_message(): void
    {
        $message = 'PzPdfColor: valor de red (300) fora do range 0-255.';
        $exception = new PzPdfException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function test_can_be_constructed_with_message_and_code(): void
    {
        $exception = new PzPdfException('error', 42);

        $this->assertSame('error', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
    }

    public function test_can_be_caught_as_runtime_exception(): void
    {
        $caught = false;

        try {
            throw new PzPdfException('test');
        } catch (RuntimeException) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }
}
