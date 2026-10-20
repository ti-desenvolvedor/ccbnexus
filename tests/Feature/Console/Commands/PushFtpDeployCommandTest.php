<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class PushFtpDeployCommandTest extends TestCase
{
    private string $destination;

    private Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->destination = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ccbnexus-ftp-push-'.uniqid();
        $this->files->ensureDirectoryExists($this->destination.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.'app');
        $this->files->ensureDirectoryExists($this->destination.DIRECTORY_SEPARATOR.'public_html');
        $this->files->put($this->destination.DIRECTORY_SEPARATOR.'LEIA-ME.txt', 'ignore');
        $this->files->put($this->destination.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Probe.php', '<?php');
        $this->files->put($this->destination.DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.'index.php', '<?php');
    }

    protected function tearDown(): void
    {
        if (isset($this->destination) && is_dir($this->destination)) {
            $this->files->deleteDirectory($this->destination);
        }

        parent::tearDown();
    }

    public function test_dry_run_lists_package_files_without_requiring_credentials(): void
    {
        $this->artisan('ftp:push', [
            '--path' => $this->destination,
            '--dry-run' => true,
            '--concurrency' => 4,
        ])
            ->expectsOutputToContain('Arquivos a enviar: 2 (paralelismo: 4)')
            ->expectsOutputToContain('laravel/app/Probe.php')
            ->expectsOutputToContain('public_html/index.php')
            ->assertSuccessful();
    }

    public function test_requires_credentials_when_not_dry_run(): void
    {
        config([
            'deploy.ftp.host' => '',
            'deploy.ftp.username' => '',
            'deploy.ftp.password' => '',
        ]);

        $this->artisan('ftp:push', [
            '--path' => $this->destination,
        ])
            ->expectsOutputToContain('DEPLOY_FTP_HOST')
            ->assertFailed();
    }

    public function test_ftp_stop_reports_when_nothing_is_running(): void
    {
        $this->artisan('ftp:stop', ['--force' => true])
            ->expectsOutputToContain('Nenhum ftp:push')
            ->assertSuccessful();
    }
}
