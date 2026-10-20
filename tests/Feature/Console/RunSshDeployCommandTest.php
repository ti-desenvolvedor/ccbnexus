<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RunSshDeployCommandTest extends TestCase
{
    public function test_shows_usage_when_called_without_arguments(): void
    {
        $this->artisan('ssh:run')
            ->expectsOutputToContain('php artisan ssh:run optimize')
            ->expectsOutputToContain('php artisan ssh:run down')
            ->expectsOutputToContain('ftp:push --delta --remote')
            ->assertSuccessful();
    }

    public function test_dry_run_optimize_prints_remote_artisan_commands(): void
    {
        config([
            'deploy.ssh.host' => 'ssh.example.test',
            'deploy.ssh.username' => 'u123',
            'deploy.ssh.password' => 'secret',
            'deploy.ssh_path' => 'domains/ccb.tinexus.com.br',
            'deploy.php_cli' => '/opt/alt/php85/usr/bin/php',
        ]);

        $exit = Artisan::call('ssh:run', [
            'preset' => 'optimize',
            '--dry-run' => true,
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('optimize:clear', $output);
        $this->assertStringContainsString('artisan optimize', $output);
        $this->assertStringContainsString('ccb.tinexus.com.br', $output);
        $this->assertStringContainsString('/opt/alt/php85/usr/bin/php', $output);
    }

    public function test_dry_run_down_and_up_presets(): void
    {
        config([
            'deploy.ssh.host' => 'ssh.example.test',
            'deploy.ssh.username' => 'u123',
            'deploy.ssh.password' => 'secret',
            'deploy.ssh_path' => 'domains/ccb.tinexus.com.br',
            'deploy.php_cli' => '/opt/alt/php85/usr/bin/php',
        ]);

        Artisan::call('ssh:run', ['preset' => 'down', '--dry-run' => true]);
        $down = Artisan::output();
        $this->assertStringContainsString('artisan down', $down);
        $this->assertStringContainsString('--retry=60', $down);
        $this->assertStringContainsString('--render=errors.503', $down);

        Artisan::call('ssh:run', ['preset' => 'up', '--dry-run' => true]);
        $this->assertStringContainsString('artisan up', Artisan::output());
    }

    public function test_dry_run_seed_prints_catalog_command(): void
    {
        config([
            'deploy.ssh.host' => 'ssh.example.test',
            'deploy.ssh.username' => 'u123',
            'deploy.ssh.password' => 'secret',
            'deploy.ssh_path' => 'domains/ccb.tinexus.com.br',
            'deploy.php_cli' => '/opt/alt/php85/usr/bin/php',
        ]);

        $exit = Artisan::call('ssh:run', [
            'preset' => 'seed',
            '--dry-run' => true,
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('nexus:seed-catalog', $output);
        $this->assertStringContainsString('ccb.tinexus.com.br', $output);
        $this->assertStringNotContainsString('db:seed', $output);
    }

    public function test_requires_credentials_when_executing(): void
    {
        config([
            'deploy.ssh.host' => '',
            'deploy.ssh.username' => '',
            'deploy.ssh.password' => '',
            'deploy.ftp.host' => '',
            'deploy.ftp.username' => '',
            'deploy.ftp.password' => '',
        ]);

        $this->artisan('ssh:run', ['preset' => 'ping'])
            ->expectsOutputToContain('DEPLOY_SSH_HOST')
            ->assertFailed();
    }
}
