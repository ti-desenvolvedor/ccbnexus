<?php

namespace Tests\Feature\Console;

use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class PrepareFtpDeployCommandTest extends TestCase
{
    private string $destination;

    private Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->destination = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexus-ftp-'.uniqid();
    }

    protected function tearDown(): void
    {
        if (isset($this->destination) && is_dir($this->destination)) {
            $this->files->deleteDirectory($this->destination);
        }

        foreach ([base_path('.ftp-last-deploy'), base_path('.ftp-deploy-manifest.json')] as $marker) {
            if (is_file($marker)) {
                $this->files->delete($marker);
            }
        }

        parent::tearDown();
    }

    public function test_creates_laravel_and_public_html_with_front_controller_pointing_at_app(): void
    {
        $this->artisan('ftp:prepare', [
            '--path' => $this->destination,
            '--skip-composer' => true,
            '--force' => true,
        ])->assertSuccessful();

        $index = $this->files->get($this->destination.DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.'index.php');
        $deny = $this->files->get($this->destination.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.'.htaccess');
        $readme = $this->files->get($this->destination.DIRECTORY_SEPARATOR.'LEIA-ME.txt');

        $this->assertDirectoryExists($this->destination.DIRECTORY_SEPARATOR.'laravel');
        $this->assertDirectoryExists($this->destination.DIRECTORY_SEPARATOR.'public_html');
        $this->assertFileExists($this->destination.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.'artisan');
        $this->assertFileExists($this->destination.DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.'.htaccess');
        $this->assertFileExists($this->destination.DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'manifest.json');
        $this->assertFileDoesNotExist($this->destination.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'packages.php');
        $this->assertFileExists($this->destination.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'database.sqlite');
        $this->assertStringContainsString("__DIR__.'/../laravel'", $index);
        $this->assertStringContainsString('usePublicPath(__DIR__)', $index);
        $this->assertStringNotContainsString("__DIR__.'/../vendor/autoload.php'", $index);
        $this->assertStringContainsString('Require all denied', $deny);
        $this->assertStringContainsString('cd ~/domains/ccb.tinexus.com.br/laravel', $readme);
        $this->assertStringContainsString('https://ccb.tinexus.com.br', $readme);
        $this->assertStringContainsString('ccb.tinexus.com.br', $readme);
        $this->assertStringContainsString('composer install', $readme);
        $this->assertStringContainsString('/opt/alt/php85/usr/bin/php artisan migrate --force', $readme);
        $this->assertStringContainsString('/opt/alt/php85/usr/bin/php artisan nexus:seed-catalog', $readme);
        $this->assertStringContainsString('MAIL_MAILER=smtp', $readme);
        $this->assertStringContainsString('contato@ccb.tinexus.com.br', $readme);

        if (is_file(base_path('.env'))) {
            $env = $this->files->get($this->destination.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.'.env');
            $this->assertMatchesRegularExpression('/^APP_ENV=production/m', $env);
            $this->assertMatchesRegularExpression('/^APP_DEBUG=false/m', $env);
        }
    }

    public function test_refuses_to_overwrite_destination_without_force(): void
    {
        $this->files->ensureDirectoryExists($this->destination);
        $this->files->put($this->destination.DIRECTORY_SEPARATOR.'keep.txt', 'keep');

        $this->artisan('ftp:prepare', [
            '--path' => $this->destination,
            '--skip-composer' => true,
        ])->assertFailed();

        $this->assertFileExists($this->destination.DIRECTORY_SEPARATOR.'keep.txt');
        $this->assertSame('keep', $this->files->get($this->destination.DIRECTORY_SEPARATOR.'keep.txt'));
    }
}
