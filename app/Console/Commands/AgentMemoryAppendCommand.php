<?php

namespace App\Console\Commands;

use App\Services\AgentMemoryService;
use Illuminate\Console\Command;

class AgentMemoryAppendCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agent:memory
                            {--phase= : Fase (ex.: F0-foundation, F1-acesso)}
                            {--stage= : Etapa dentro da fase}
                            {--title= : Título curto}
                            {--body= : Detalhes (texto livre)}
                            {--tags= : Tags separadas por vírgula (opcional)}
                            {--context= : JSON opcional}
                            {--actor=cursor-agent : Quem registrou (ex.: cursor-agent|human|ci)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Registra uma entrada no histórico do projeto (Agent Memory).';

    public function __construct(private readonly AgentMemoryService $memory)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $phase = (string) $this->option('phase');
        $title = (string) $this->option('title');

        if ($phase === '' || $title === '') {
            $this->error('Informe --phase e --title.');

            return self::INVALID;
        }

        $tagsRaw = $this->option('tags');
        $tags = null;
        if (is_string($tagsRaw) && trim($tagsRaw) !== '') {
            $tags = array_values(array_filter(array_map('trim', explode(',', $tagsRaw))));
        }

        $context = null;
        $contextRaw = $this->option('context');
        if (is_string($contextRaw) && trim($contextRaw) !== '') {
            $decoded = json_decode($contextRaw, true);
            if (! is_array($decoded)) {
                $this->error('O --context precisa ser um JSON válido.');

                return self::INVALID;
            }
            $context = $decoded;
        }

        $entry = $this->memory->append(
            phase: $phase,
            title: $title,
            stage: $this->option('stage') ? (string) $this->option('stage') : null,
            body: $this->option('body') ? (string) $this->option('body') : null,
            tags: $tags,
            context: $context,
            actor: (string) $this->option('actor'),
        );

        $this->line("OK: memória #{$entry->id} registrada.");

        return self::SUCCESS;
    }
}
