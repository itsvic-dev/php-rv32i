<?php

// this will probably work reliably only on 64-bit builds of PHP

namespace Vic\Riscy;

interface MemoryBus {
  public function read(int $addr): int;
  public function write(int $addr, int $value);
}

class CPU {
  public function __construct(public MemoryBus &$bus) {
    $this->regs = array_fill(0, 32, 0);
  }

  public function fetch_i32(): int {
    $value = $this->bus->read($this->pc++);
    $value |= $this->bus->read($this->pc++) << 8;
    $value |= $this->bus->read($this->pc++) << 16;
    $value |= $this->bus->read($this->pc++) << 24;
    return $value;
  }

  public function execute_once() {
    $instruction = $this->fetch_i32();

    $opcode = $instruction & ((1 << 6) - 1);
    
    switch($opcode) {
      case 0b0010011:
        return $this->opcodes_immediate_math($instruction);
      default:
        echo("[WARN / execute_once]: unsupported opcode $opcode\n");
    }
  }

  // Registers
  public array $regs = [];
  public int $pc = 0;

  // Opcodes
  private function opcodes_immediate_math($instruction) {
    $funct3 = ($instruction & (0b111 << 12)) >> 12;
    // $rd = ($instruction & ((1 << 5) - 1) << 7) >> 7;
    $rd = $this->get_bits_at_offset($instruction, 5, 7);
    $rs1 = $this->get_bits_at_offset($instruction, 5, 15);
    // SLLI, SRLI, SRAI have a "shamt" instead, using the rest of the immediate as extra function space
    $imm = $this->sign_extended_immediate($instruction, 12, 20);

    switch ($funct3) {
      case 0b000: // ADDI
        echo("addi x$rd, x$rs1, $imm\n");
        $this->regs[$rd] = ($this->regs[$rs1] + $imm) & 0xFFFFFFFF;
        return;
      default:
        echo("[WARN / opcodes_imm_math]: unknown function $funct3\n");
    }
  }

  // Helper functions
  private static function get_bits_at_offset($instruction, $length, $offset) {
    return ($instruction >> $offset) & ((1 << $length) - 1);
  }

  private static function sign_extended_immediate($instruction, $length, $offset) {
    // extract immediate, leaving sign bit as is
    $imm = ($instruction >> $offset) & ((1 << $length) - 1);
    // get sign bit from immediate
    $sign = $imm >> ($length - 1);
    // move the sign bit to the 31st place
    $imm = ($imm & ((1 << $length - 1) - 1)) | ($sign << 31);
    return $imm;
  }
};
