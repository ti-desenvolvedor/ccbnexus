<?php

namespace Tests\Feature\Console;

use App\Support\FtpDeployLayout;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class PrepareFtpDeltaCommandTest extends TestCase
{
    private string $destination;

    private Filesystem $files;

    /** @var list<string> */
    private array $tempProjectFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->destination = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexus-ftp-delta-'.uniqid();
    }

    protected function tearDown(): void
    {
        if (isset($this->destination) && is_dir($this->destination)) {
            $this->files->deleteDirectory($this->destination);
        }

        foreach ($this->tempProjectFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_packages_only_files_changed_since_mtime_and_lists_them_in_readme(): void
    {
        $marker = 'app'.DIRECTORY_SEPARATOR.'FtpDeltaProbe_'.uniqid().'.php';
        $absolute = base_path($marker);
        $this->files->put($absolute, "<?php\n// delta probe\n");
        $this->tempProjectFiles[] = $absolute;
        touch($absolute, time());

        $this->artisan('ftp:delta', [
            '--path' => $this->destination,
            '--force' => true,
            '--since-mtime' => now('America/Sao_Paulo')->subMinutes(5)->toDateTimeString(),
        ])->assertSuccessful();

        $packaged = $this->destination.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.$marker;
        $readme = $this->files->get($this->destination.DIRECTORY_SEPARATOR.'LEIA-ME.txt');

        $this->assertFileExists($packaged);
        $this->assertStringContainsString(str_replace('\\', '/', $marker), str_replace('\\', '/', $readme));
        $this->assertStringContainsString('pacote FTP INCREMENTAL', $readme);
        $this->assertStringContainsString('ccb.tinexus.com.br', $readme);
        $this->assertStringContainsString('mtime >=', $readme);
        $this->assertDirectoryDoesNotExist($this->destination.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.'vendor');
    }

    public function test_includes_full_public_build_when_frontend_source_changed(): void
    {
        $js = 'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'ftp-delta-probe-'.uniqid().'.js';
        $absolute = base_path($js);
        $this->files->ensureDirectoryExists(dirname($absolute));
        $this->files->put($absolute, 'console.log("delta");');
        $this->tempProjectFiles[] = $absolute;
        touch($absolute, time());

        $this->artisan('ftp:delta', [
            '--path' => $this->destination,
            '--force' => true,
            '--since-mtime' => now('America/Sao_Paulo')->subMinutes(5)->toDateTimeString(),
        ])->assertSuccessful();

        $this->assertFileExists(
            $this->destination.DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'manifest.json',
        );
        $readme = $this->files->get($this->destination.DIRECTORY_SEPARATOR.'LEIA-ME.txt');
        $this->assertStringContainsString('envie a pasta build inteira', strtolower($readme));
    }

    public function test_refuses_to_overwrite_destination_without_force(): void
    {
        $this->files->ensureDirectoryExists($this->destination);
        $this->files->put($this->destination.DIRECTORY_SEPARATOR.'keep.txt', 'keep');

        $this->artisan('ftp:delta', [
            '--path' => $this->destination,
            '--since-mtime' => now('America/Sao_Paulo')->subMinutes(5)->toDateTimeString(),
        ])->assertFailed();

        $this->assertFileExists($this->destination.DIRECTORY_SEPARATOR.'keep.txt');
    }

    public function test_auto_uses_start_of_day_when_no_git_or_manifest(): void
    {
        $marker = 'app'.DIRECTORY_SEPARATOR.'FtpDeltaAuto_'.uniqid().'.php';
        $absolute = base_path($marker);
        $this->files->put($absolute, "<?php\n// auto delta\n");
        $this->tempProjectFiles[] = $absolute;
        touch($absolute, time());

        $manifestBackup = is_file(FtpDeployLayout::manifestPath())
            ? $this->files->get(FtpDeployLayout::manifestPath())
            : null;
        $markerBackup = is_file(FtpDeployLayout::markerPath())
            ? $this->files->get(FtpDeployLayout::markerPath())
            : null;

        if (is_file(FtpDeployLayout::manifestPath())) {
            $this->files->delete(FtpDeployLayout::manifestPath());
        }
        if (is_file(FtpDeployLayout::markerPath())) {
            $this->files->delete(FtpDeployLayout::markerPath());
        }

        try {
            $this->artisan('ftp:delta', [
                '--path' => $this->destination,
                '--force' => true,
            ])
                ->expectsOutputToContain('Sem git/manifesto')
                ->assertSuccessful();

            $this->assertFileExists($this->destination.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.$marker);
            $this->assertFileExists(FtpDeployLayout::manifestPath());
        } finally {
            if ($manifestBackup === null) {
                $this->files->delete(FtpDeployLayout::manifestPath());
            } else {
                $this->files->put(FtpDeployLayout::manifestPath(), $manifestBackup);
            }
            if ($markerBackup === null) {
                $this->files->delete(FtpDeployLayout::markerPath());
            } else {
                $this->files->put(FtpDeployLayout::markerPath(), $markerBackup);
            }
        }
    }

    public function test_from_manifest_packages_files_that_differ_from_saved_hashes(): void
    {
        $marker = 'app'.DIRECTORY_SEPARATOR.'FtpDeltaManifest_'.uniqid().'.php';
        $absolute = base_path($marker);
        $this->files->put($absolute, "<?php\n// before\n");
        $this->tempProjectFiles[] = $absolute;

        $relative = str_replace('\\', '/', $marker);
        $previousManifest = FtpDeployLayout::manifestPath();
        $backup = is_file($previousManifest) ? $this->files->get($previousManifest) : null;

        $files = $this->currentDeployableHashes();
        $this->assertArrayHasKey($relative, $files);

        $this->files->put($previousManifest, json_encode([
            'prepared_at' => now()->subHour()->toIso8601String(),
            'commit' => null,
            'files' => $files,
        ], JSON_PRETTY_PRINT));

        $this->files->put($absolute, "<?php\n// after change\n");

        try {
            $this->artisan('ftp:delta', [
                '--path' => $this->destination,
                '--force' => true,
                '--from-manifest' => true,
            ])->assertSuccessful();

            $this->assertFileExists($this->destination.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.$marker);
            $readme = $this->files->get($this->destination.DIRECTORY_SEPARATOR.'LEIA-ME.txt');
            $this->assertStringContainsString('manifesto', strtolower($readme));
            $this->assertStringContainsString($relative, str_replace('\\', '/', $readme));
            $this->assertStringNotContainsString('config/app.php', $readme);
        } finally {
            if ($backup === null) {
                $this->files->delete($previousManifest);
            } else {
                $this->files->put($previousManifest, $backup);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function currentDeployableHashes(): array
    {
        $hashes = [];

        foreach (FtpDeployLayout::APP_DIRECTORIES as $directory) {
            $root = base_path($directory);
            if (! $this->files->isDirectory($root)) {
                continue;
            }

            foreach ($this->files->allFiles($root) as $file) {
                $rel = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
                if (FtpDeployLayout::packageRelativePath($rel) !== null) {
                    $hashes[$rel] = hash_file('sha256', $file->getPathname()) ?: '';
                }
            }
        }

        foreach (FtpDeployLayout::APP_FILES as $file) {
            $absolute = base_path($file);
            if ($this->files->isFile($absolute)) {
                $hashes[$file] = hash_file('sha256', $absolute) ?: '';
            }
        }

        if ($this->files->isDirectory(public_path())) {
            foreach ($this->files->allFiles(public_path()) as $file) {
                $rel = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
                if (FtpDeployLayout::packageRelativePath($rel) !== null) {
                    $hashes[$rel] = hash_file('sha256', $file->getPathname()) ?: '';
                }
            }
        }

        ksort($hashes);

        return $hashes;
    }
}
