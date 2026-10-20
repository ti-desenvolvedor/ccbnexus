<?php

namespace App\Exceptions\Agenda;

use InvalidArgumentException;

class InvalidAgendaReadCriteria extends InvalidArgumentException
{
    public static function period(): self
    {
        return new self('Período inválido.');
    }

    public static function channel(): self
    {
        return new self('Canal inválido.');
    }
}
