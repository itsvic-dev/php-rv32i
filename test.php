<?php

require_once 'emucore.php';

use \Vic\Riscy\CPU;
use \Vic\Riscy\MemoryBus;

class RAMBus implements MemoryBus {
  public function __construct() {
    $contents = file_get_contents("test.bin");
    $this->ram = array_values(unpack('C*', $contents));
  }
  
  public $ram = [];

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

$cpu->execute_once();
