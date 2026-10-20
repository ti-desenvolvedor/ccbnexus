<?php

/**
 * One-off helper: wrap common UI strings in Blade files with __().
 * Skips lines that already contain __(
 */
$root = dirname(__DIR__);
$viewsDir = $root.'/resources/views';

$strings = [
    'Guardar', 'Cancelar', 'Editar', 'Eliminar', 'Excluir', 'Voltar',
    'Pesquisar', 'Filtrar', 'Limpar filtros', 'Limpar', 'Ativo', 'Inativo',
    'Confirmar', 'Fechar', 'Adicionar', 'Criar', 'Ver', 'Remover',
    'Novo evento', 'Nova regional', 'Nova administração', 'Nova casa de oração',
    'Novo local', 'Nova sala', 'Novo estacionamento', 'Novo utilizador',
    'Novo grupo', 'Novo subgrupo', 'Novo departamento', 'Novo cargo',
    'Novo público', 'Nova reserva',
    'Regionais', 'Administrações', 'Casas de oração', 'Utilizadores',
    'Locais', 'Salas', 'Reservas', 'Estacionamentos', 'Eventos',
    'Aprovações', 'Pedidos de acesso', 'Organização', 'Operação', 'Agenda',
    'Painel', 'Perfil', 'Sair', 'Menu', 'Atalhos', 'Todos', 'Nenhum',
    'Sem resultados', 'Ações', 'Nome', 'Estado', 'Regional', 'Administração',
    'Casa de oração', 'Descrição', 'Ordem', 'Ativo', 'Inativo',
    'Exportar eventos (CSV)', 'Notificações WhatsApp',
    'Aprovações de agenda', 'Rever reservas',
    'Reservas de salas', 'Salas de reunião',
    'Agenda — eventos', 'Organização — regionais',
    'Buscar…', 'Aparência', 'Claro', 'Escuro', 'Notificações',
    'Abrir menu', 'Alternar tema', 'Tema', 'Cor primária',
    'Azul', 'Azul marinho', 'Verde', 'Verde escuro', 'Vermelho', 'Vermelho escuro', 'Laranja', 'Marrom',
    'Dica', 'Aprovações e governança',
];

usort($strings, fn ($a, $b) => strlen($b) <=> strlen($a));

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($viewsDir, FilesystemIterator::SKIP_DOTS)
);

$changed = 0;

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php' || ! str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }

    $path = $file->getPathname();
    $content = file_get_contents($path);
    $original = $content;

    foreach ($strings as $text) {
        $escaped = preg_quote($text, '/');

        // >Text</tag> or >Text</x-ui.button>
        $content = preg_replace_callback(
            '/>(?!\s*\{\{ __\()'.'('.$escaped.')'.'(<\/)/u',
            function ($m) use ($text) {
                return ">{{ __('".addslashes($text)."') }}".$m[2];
            },
            $content
        );

        // title="Text" or placeholder="Text" or aria-label="Text"
        $content = preg_replace_callback(
            '/(title|placeholder|aria-label)="(?!\{\{ __\()'.'('.$escaped.')'.'"/u',
            function ($m) use ($text) {
                return $m[1].'="{{ __(\''.addslashes($text).'\') }}"';
            },
            $content
        );

        // x-ui.card title="Text"
        $content = preg_replace_callback(
            '/\btitle="(?!\{\{ __\()'.'('.$escaped.')'.'"/u',
            function ($m) use ($text) {
                return 'title="{{ __(\''.addslashes($text).'\') }}"';
            },
            $content
        );
    }

    if ($content !== $original) {
        file_put_contents($path, $content);
        $changed++;
        echo "Updated: {$path}\n";
    }
}

echo "Done. {$changed} files updated.\n";
