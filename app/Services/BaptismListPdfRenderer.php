<?php

namespace App\Services;

use App\Models\BaptismListReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

/**
 * PDF da Lista de Batismos. O caminho principal converte o próprio Word, para o PDF sair igual ao .docx.
 */
class BaptismListPdfRenderer
{
    private const CACHE_DIR = 'baptism-list-pdf';

    public function __construct(private BaptismListDocxExporter $docx) {}

    public function binary(BaptismListReport $report): string
    {
        $sections = BaptismListDocumentBlocks::orderedSections($report);

        return $this->fromWord($report, $sections) ?? $this->fromHtml($report, $sections);
    }

    /**
     * @param  array<string, Collection>  $sections
     */
    public function fromWord(BaptismListReport $report, array $sections): ?string
    {
        $soffice = config('nexus.baptism_pdf.soffice');
        if (! is_string($soffice) || $soffice === '' || ! is_file($soffice)) {
            return null;
        }

        $work = storage_path('app/tmp/baptism-pdf-'.Str::random(16));
        File::ensureDirectoryExists($work);

        try {
            $docxPath = $work.'/lista.docx';
            file_put_contents($docxPath, $this->docx->binary($report, $sections));

            $disk = Storage::disk('local');
            $cachePath = self::CACHE_DIR.'/'.$report->id.'-'.$this->fingerprint($docxPath).'.pdf';
            if ($disk->exists($cachePath)) {
                return $disk->get($cachePath);
            }

            // O perfil do LibreOffice não aceita duas instâncias ao mesmo tempo.
            $binary = Cache::lock('baptism-list-soffice', 180)->block(60, fn () => $this->convert($soffice, $work, $docxPath));
            if ($binary === null) {
                return null;
            }

            foreach ($disk->files(self::CACHE_DIR) as $old) {
                if (str_starts_with(basename($old), $report->id.'-')) {
                    $disk->delete($old);
                }
            }
            $disk->put($cachePath, $binary);

            return $binary;
        } catch (Throwable $e) {
            Log::warning('baptism_list.pdf_from_word_failed', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        } finally {
            File::deleteDirectory($work);
        }
    }

    /**
     * @param  array<string, Collection>  $sections
     */
    public function fromHtml(BaptismListReport $report, array $sections): string
    {
        return Pdf::loadView('reports.baptism-list.pdf', [
            'report' => $report,
            'sections' => $sections,
        ])->setPaper('a4')->output();
    }

    private function convert(string $soffice, string $work, string $docxPath): ?string
    {
        $profile = storage_path('app/soffice-profile');
        File::ensureDirectoryExists($profile);

        $process = new Process([
            $soffice,
            '--headless',
            '--norestore',
            '--nologo',
            '--nodefault',
            '-env:UserInstallation=file://'.str_replace('\\', '/', $profile),
            '--convert-to',
            'pdf:writer_pdf_Export',
            '--outdir',
            $work,
            $docxPath,
        ], $work, ['HOME' => $profile]);
        $process->setTimeout((int) config('nexus.baptism_pdf.timeout', 90));
        $process->run();

        $pdfPath = $work.'/lista.pdf';
        if (! $process->isSuccessful() || ! is_file($pdfPath) || filesize($pdfPath) === 0) {
            Log::warning('baptism_list.soffice_failed', [
                'exit_code' => $process->getExitCode(),
                'error' => Str::limit(trim($process->getErrorOutput().' '.$process->getOutput()), 500),
            ]);

            return null;
        }

        return (string) file_get_contents($pdfPath);
    }

    /**
     * Hash do conteúdo do Word sem os metadados de data, que mudam a cada exportação.
     */
    private function fingerprint(string $docxPath): string
    {
        $zip = new ZipArchive;
        if ($zip->open($docxPath) !== true) {
            return md5_file($docxPath) ?: Str::random(32);
        }
        $hash = hash_init('md5');
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (str_starts_with($name, 'word/')) {
                hash_update($hash, $name.(string) $zip->getFromIndex($i));
            }
        }
        $zip->close();

        return hash_final($hash);
    }
}
