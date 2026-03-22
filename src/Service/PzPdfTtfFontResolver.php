<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Service;

use Composer\InstalledVersions;
use Puzl\PzPdf\Exception\PzPdfException;
use Puzl\PzPdf\ValueObject\PzPdfResolvedTtfFont;

/**
 * Converte TrueType/OpenType em artefatos FPDF (font.php + font.z) via utilitário makefont do pacote
 * fawno/fpdf, com cache por hash SHA-256 do arquivo, lock exclusivo por entrada e remoção da cópia
 * temporária do TTF após a conversão.
 *
 * **Cache em disco compartilhado**: dois processos que usam o mesmo `cacheRoot` e a mesma fonte
 * cooperam via `flock` no arquivo `.lock` da entrada; não use o mesmo diretório entre workers sem
 * mecanismo de isolamento (ex.: token de teste ou cache por instância de aplicação) se o volume
 * não suportar locking confiável.
 *
 * Reutilização na mesma instância do resolver: resultados de {@see self::ensureFpdfFontFiles()}
 * são memorizados em memória por par (caminho canônico da fonte, cacheRoot).
 */
final class PzPdfTtfFontResolver
{
    private const MARKER_VERSION = 1;

    private const WORK_COPY = 'font.ttf';

    private const DEFINITION = 'font.php';

    private const COMPRESSED = 'font.z';

    private const COMPLETE = '.complete';

    private const LOCK = '.lock';

    /** Encoding alinhado ao motor {@see \Puzl\PzPdf\Internal\PzPdfFpdfEngine} (UTF-8 → ISO-8859-1). */
    private const ENCODING = 'iso-8859-1';

    /** @var array<string, PzPdfResolvedTtfFont> */
    private array $instanceCache = [];

    public function __construct(
        private readonly ?string $makefontScriptPath = null,
    ) {
    }

    public function ensureFpdfFontFiles(string $ttfPath, string $cacheRoot): PzPdfResolvedTtfFont
    {
        $cacheRoot = rtrim($cacheRoot, '/\\');

        $realSource = realpath($ttfPath);
        if ($realSource === false || ! is_file($realSource) || ! is_readable($realSource)) {
            throw new PzPdfException(
                'Arquivo de fonte (.ttf/.otf) não encontrado ou ilegível: '.$ttfPath
                .'. Use um caminho absoluto ou relativo ao processo atual.'
            );
        }

        $cacheKey = $realSource.'|'.$cacheRoot;
        if (isset($this->instanceCache[$cacheKey])) {
            return $this->instanceCache[$cacheKey];
        }

        $ext = strtolower(pathinfo($realSource, PATHINFO_EXTENSION));
        if (! in_array($ext, ['ttf', 'otf'], true)) {
            throw new PzPdfException(
                'Extensão não suportada para conversão automática (esperado .ttf ou .otf): '.$realSource
            );
        }

        $hash = hash_file('sha256', $realSource);
        if ($hash === false) {
            throw new PzPdfException('Não foi possível calcular o hash do arquivo de fonte: '.$realSource);
        }

        $entryDir = $cacheRoot.DIRECTORY_SEPARATOR.$hash;
        $lockPath = $entryDir.DIRECTORY_SEPARATOR.self::LOCK;

        if (! is_dir($cacheRoot) && ! mkdir($cacheRoot, 0775, true) && ! is_dir($cacheRoot)) {
            throw new PzPdfException(
                'Não foi possível criar o diretório de cache de fontes: '.$cacheRoot
                .'. Verifique permissões ou defina PZPDF_CACHE_DIR / caminho explícito por worker.'
            );
        }

        if (! is_dir($entryDir) && ! mkdir($entryDir, 0775, true) && ! is_dir($entryDir)) {
            throw new PzPdfException('Não foi possível criar o diretório de cache da fonte: '.$entryDir);
        }

        $lockHandle = fopen($lockPath, 'c+');
        if ($lockHandle === false) {
            throw new PzPdfException('Não foi possível abrir o lock de cache de fonte: '.$lockPath);
        }

        try {
            if (! flock($lockHandle, LOCK_EX)) {
                throw new PzPdfException('Não foi possível obter lock exclusivo para o cache de fonte: '.$lockPath);
            }

            if ($this->isCacheEntryReady($entryDir, $hash)) {
                $resolved = new PzPdfResolvedTtfFont($entryDir);
                $this->instanceCache[$cacheKey] = $resolved;

                return $resolved;
            }

            $this->cleanBuildArtifacts($entryDir);
            $this->runMakeFont($realSource, $entryDir);
            $this->assertArtifactsPresent($entryDir);
            $this->writeCompleteMarker($entryDir, $hash);

            $resolved = new PzPdfResolvedTtfFont($entryDir);
            $this->instanceCache[$cacheKey] = $resolved;

            return $resolved;
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    private function isCacheEntryReady(string $entryDir, string $expectedHash): bool
    {
        $completePath = $entryDir.DIRECTORY_SEPARATOR.self::COMPLETE;
        if (! is_file($completePath) || ! is_readable($completePath)) {
            return false;
        }

        $json = file_get_contents($completePath);
        if ($json === false) {
            return false;
        }

        try {
            /** @var mixed $data */
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return false;
        }

        if (! is_array($data)
            || (int) ($data['v'] ?? 0) !== self::MARKER_VERSION
            || ($data['sha256'] ?? '') !== $expectedHash) {
            return false;
        }

        $php = $entryDir.DIRECTORY_SEPARATOR.self::DEFINITION;
        $z = $entryDir.DIRECTORY_SEPARATOR.self::COMPRESSED;

        return is_file($php) && is_readable($php) && filesize($php) > 32
            && is_file($z) && is_readable($z) && filesize($z) > 0;
    }

    /**
     * Remove restos de build anterior ou falha (não apaga .lock — está aberto pelo processo atual).
     */
    private function cleanBuildArtifacts(string $entryDir): void
    {
        $paths = [
            $entryDir.DIRECTORY_SEPARATOR.self::WORK_COPY,
            $entryDir.DIRECTORY_SEPARATOR.self::DEFINITION,
            $entryDir.DIRECTORY_SEPARATOR.self::COMPRESSED,
            $entryDir.DIRECTORY_SEPARATOR.self::COMPLETE,
        ];
        foreach ($paths as $p) {
            if (is_file($p)) {
                @unlink($p);
            }
        }
    }

    private function runMakeFont(string $realSource, string $entryDir): void
    {
        $workCopy = $entryDir.DIRECTORY_SEPARATOR.self::WORK_COPY;
        if (! copy($realSource, $workCopy)) {
            throw new PzPdfException('Falha ao copiar fonte temporária para o cache: '.$workCopy);
        }

        $makefont = $this->resolveMakefontScriptPath();
        $phpBinary = PHP_BINARY !== '' ? PHP_BINARY : 'php';

        $command = [
            $phpBinary,
            $makefont,
            $workCopy,
            self::ENCODING,
            'true',
            'true',
        ];

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $entryDir);
        // Não exposto por APIs de teste: falha de proc_open é extremamente rara em ambientes suportados.
        // @codeCoverageIgnoreStart
        if (! is_resource($process)) {
            @unlink($workCopy);

            throw new PzPdfException('Não foi possível iniciar o processo makefont para conversão da fonte.');
        }
        // @codeCoverageIgnoreEnd

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        @unlink($workCopy);

        if ($exitCode !== 0) {
            $this->cleanBuildArtifacts($entryDir);
            $detail = trim($stderr !== false && $stderr !== '' ? $stderr : (string) $stdout);
            throw new PzPdfException(
                'makefont falhou ao converter a fonte (código '.$exitCode.'). '
                .($detail !== '' ? $detail : 'Sem saída de erro.')
            );
        }
    }

    private function assertArtifactsPresent(string $entryDir): void
    {
        $php = $entryDir.DIRECTORY_SEPARATOR.self::DEFINITION;
        $z = $entryDir.DIRECTORY_SEPARATOR.self::COMPRESSED;
        if (! is_file($php) || ! is_readable($php) || filesize($php) < 32) {
            $this->cleanBuildArtifacts($entryDir);
            throw new PzPdfException('makefont não gerou font.php esperado em: '.$php);
        }
        if (! is_file($z) || ! is_readable($z) || filesize($z) < 1) {
            $this->cleanBuildArtifacts($entryDir);
            throw new PzPdfException('makefont não gerou font.z esperado em: '.$z);
        }
    }

    private function writeCompleteMarker(string $entryDir, string $hash): void
    {
        $payload = json_encode(
            ['v' => self::MARKER_VERSION, 'sha256' => $hash],
            JSON_THROW_ON_ERROR,
        );
        $completePath = $entryDir.DIRECTORY_SEPARATOR.self::COMPLETE;
        if (file_put_contents($completePath, $payload, LOCK_EX) === false) {
            $this->cleanBuildArtifacts($entryDir);
            throw new PzPdfException('Não foi possível gravar o marcador de cache: '.$completePath);
        }
    }

    private function resolveMakefontScriptPath(): string
    {
        if ($this->makefontScriptPath !== null) {
            if (! is_file($this->makefontScriptPath)) {
                throw new PzPdfException(
                    'Caminho makefont inválido (arquivo inexistente): '.$this->makefontScriptPath
                );
            }

            return $this->makefontScriptPath;
        }

        $env = getenv('FPDF_MAKEFONT_SCRIPT');
        if (is_string($env) && $env !== '' && is_file($env)) {
            return $env;
        }

        if (class_exists(InstalledVersions::class)) {
            $installPath = InstalledVersions::getInstallPath('fawno/fpdf');
            if (is_string($installPath) && $installPath !== '') {
                $candidate = $installPath.DIRECTORY_SEPARATOR.'fpdf'.DIRECTORY_SEPARATOR.'makefont'.DIRECTORY_SEPARATOR.'makefont.php';
                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        $fallback = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'fawno'
            .DIRECTORY_SEPARATOR.'fpdf'.DIRECTORY_SEPARATOR.'fpdf'.DIRECTORY_SEPARATOR.'makefont'
            .DIRECTORY_SEPARATOR.'makefont.php';
        if (is_file($fallback)) {
            return $fallback;
        }

        throw new PzPdfException(
            'Não foi localizado makefont.php do pacote fawno/fpdf. Defina FPDF_MAKEFONT_SCRIPT ou instale fawno/fpdf via Composer.'
        );
    }
}
