<?php

namespace Vic\Riscy;

interface Machine {
    public function read(int $addr): int;
    public function write(int $addr, int $value);
    public function handle_machine_ecall(CPU &$cpu);
}
