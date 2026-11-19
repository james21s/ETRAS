<?php

namespace App\Exceptions;

use App\Enums\PaperStatus;
use DomainException;

class InvalidPaperTransition extends DomainException
{
    public static function notAllowed(PaperStatus $from, PaperStatus $to): self
    {
        return new self("A paper cannot move from \"{$from->label()}\" to \"{$to->label()}\".");
    }

    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
