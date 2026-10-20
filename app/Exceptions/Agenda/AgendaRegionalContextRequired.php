<?php

namespace App\Exceptions\Agenda;

use RuntimeException;

class AgendaRegionalContextRequired extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Selecione uma regional no topo e volte a gerar o PDF.');
    }
}
