<?php

namespace Tests\Feature\Console\Commands;

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
        $this->destination = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ccbnexus-ftp-delta-'.uniqid();
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
}
