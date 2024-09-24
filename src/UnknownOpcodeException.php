<?php

namespace Vic\Riscy;

class UnknownOpcodeException extends \Exception
{
    public function __construct(public int $opcode)
    {
        parent::__construct("Unknown opcode or function encountered: " . decbin($this->opcode));
    }
}
