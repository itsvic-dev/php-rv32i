<?php

require_once 'emucore.php';

use \Vic\Riscy\CPU;
use \Vic\Riscy\Machine;

class TestMachine implements Machine {
  public bool $running = true;

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

  public function handle_machine_ecall(CPU &$cpu) {
    if ($cpu->regs[\Vic\Riscy\REG_A7] == 0x1) {
      // SBI: legacy putchar
      print(chr($cpu->regs[\Vic\Riscy\REG_A0]));
      $cpu->regs[\Vic\Riscy\REG_A0] = 0;
      return;
    }

    // anything else halts the machine
    $this->running = false;
  }
}

$bus = new TestMachine();
$cpu = new CPU($bus);

while ($bus->running) {
  $cpu->execute_once();
}
