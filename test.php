<?php

require_once 'emucore.php';

use \Vic\Riscy\CPU;
use \Vic\Riscy\MemoryBus;

class RAMBus implements MemoryBus {
  public $ram = [
    0x13, 0x05, 0x95, 0x06, // addi a0, a0, 105
    0x67, 0x80, 0x00, 0x00, // ret
  ];

  public function read(int $pc): int {
    return $this->ram[$pc];
  }

  public function write(int $pc, int $value) {
    $this->ram[$pc] = $value & 0xFF;
  }
}

$bus = new RAMBus();
$cpu = new CPU($bus);

$cpu->execute_once();

$x10 = $cpu->regs[10];
print("register x10 is now $x10\n");
