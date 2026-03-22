<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Integration\Bootstrap;

use PHPUnit\Framework\TestCase;

final class TestSuiteSmokeTest extends TestCase
{
    public function testParatestBinaryIsInstalled(): void
    {
        $root = dirname(__DIR__, 3);

        self::assertFileExists($root . '/vendor/bin/paratest');
        self::assertFileIsReadable($root . '/vendor/bin/paratest');
    }

    public function testUnitTestsPassViaPhpunitSubprocess(): void
    {
        $root = dirname(__DIR__, 3);
        $phpunit = $root . '/vendor/bin/phpunit';

        self::assertFileExists($phpunit);

        $command = [
            PHP_BINARY,
            $phpunit,
            '-c',
            $root . '/phpunit.xml.dist',
            '--testsuite',
            'unit',
            '--colors=never',
        ];

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, $root, null, ['bypass_shell' => true]);

        self::assertIsResource($process);

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        self::assertSame(
            0,
            $exitCode,
            'Expected unit testsuite to pass when run in a subprocess. Output: '
            . $stdout . $stderr
        );
    }
}
