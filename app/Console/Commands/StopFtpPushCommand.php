<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class StopFtpPushCommand extends Command
{
    protected $signature = 'ftp:stop
                            {--force : Encerra sem pedir confirmacao}';

    protected $description = 'Para processos locais de ftp:push / ftp:prepare / ftp:delta em execucao.';

    public function handle(): int
    {
        $matches = $this->findFtpProcesses();

        if ($matches === []) {
            $this->info('Nenhum ftp:push / ftp:prepare / ftp:delta em execucao.');

            return self::SUCCESS;
        }

        $this->warn('Processos encontrados:');
        foreach ($matches as $row) {
            $this->line("  PID {$row['pid']}  {$row['command']}");
        }

        if (! $this->option('force') && ! $this->confirm('Encerrar esses processos agora?', true)) {
            $this->warn('Cancelado.');

            return self::SUCCESS;
        }

        $killed = 0;
        foreach ($matches as $row) {
            if ($this->killPid((int) $row['pid'])) {
                $this->line("  parado PID {$row['pid']}");
                $killed++;
            } else {
                $this->error("  falha ao parar PID {$row['pid']}");
            }
        }

        // Segunda passada (pais cmd.exe / php.bat no Windows).
        usleep(300_000);
        foreach ($this->findFtpProcesses() as $row) {
            if ($this->killPid((int) $row['pid'])) {
                $this->line("  parado PID {$row['pid']} (residual)");
                $killed++;
            }
        }

        $this->info($killed > 0
            ? "FTP local parado ({$killed} processo(s))."
            : 'Nenhum processo precisou ser encerrado.');

        return self::SUCCESS;
    }

    /**
     * @return list<array{pid: int, command: string}>
     */
    private function findFtpProcesses(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return $this->findWindowsFtpProcesses();
        }

        return $this->findUnixFtpProcesses();
    }

    /**
     * @return list<array{pid: int, command: string}>
     */
    private function findWindowsFtpProcesses(): array
    {
        // Apenas processos PHP/CMD que de fato rodam artisan ftp:* (nao o powershell do finder).
        $script = <<<'PS'
Get-CimInstance Win32_Process |
  Where-Object {
    $_.CommandLine -and
    $_.Name -match '^(php|php\.exe|cmd\.exe)$' -and
    $_.CommandLine -match 'artisan\s+ftp:(push|prepare|delta)\b' -and
    $_.CommandLine -notmatch 'artisan\s+ftp:stop\b'
  } |
  Select-Object ProcessId, CommandLine |
  ConvertTo-Json -Compress
PS;

        $process = new Process(['powershell', '-NoProfile', '-Command', $script]);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            return [];
        }

        $json = trim($process->getOutput());
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return [];
        }

        // Um unico objeto nao vem como lista.
        if (isset($decoded['ProcessId'])) {
            $decoded = [$decoded];
        }

        $selfPid = getmypid();
        $rows = [];
        foreach ($decoded as $item) {
            $pid = (int) ($item['ProcessId'] ?? 0);
            $command = (string) ($item['CommandLine'] ?? '');
            if ($pid <= 0 || $pid === $selfPid || $command === '') {
                continue;
            }
            // Nao mate o proprio ftp:stop.
            if (preg_match('/artisan\s+ftp:stop\b/', $command)) {
                continue;
            }
            $rows[] = ['pid' => $pid, 'command' => $command];
        }

        return $rows;
    }

    /**
     * @return list<array{pid: int, command: string}>
     */
    private function findUnixFtpProcesses(): array
    {
        $process = new Process(['ps', '-eo', 'pid=,args=']);
        $process->setTimeout(15);
        $process->run();

        if (! $process->isSuccessful()) {
            return [];
        }

        $selfPid = getmypid();
        $rows = [];
        foreach (preg_split("/\r\n|\n|\r/", trim($process->getOutput())) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (! preg_match('/^(\d+)\s+(.+)$/', $line, $m)) {
                continue;
            }
            $pid = (int) $m[1];
            $command = $m[2];
            if ($pid === $selfPid) {
                continue;
            }
            if (! preg_match('/artisan\s+ftp:(push|prepare|delta)\b/', $command)) {
                continue;
            }
            if (preg_match('/artisan\s+ftp:stop\b/', $command)) {
                continue;
            }
            $rows[] = ['pid' => $pid, 'command' => $command];
        }

        return $rows;
    }

    private function killPid(int $pid): bool
    {
        if ($pid <= 0 || $pid === getmypid()) {
            return false;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $process = new Process(['taskkill', '/PID', (string) $pid, '/T', '/F']);
            $process->setTimeout(15);
            $process->run();

            return $process->isSuccessful() || $process->getExitCode() === 128;
        }

        return posix_kill($pid, SIGTERM) || posix_kill($pid, SIGKILL);
    }
}
