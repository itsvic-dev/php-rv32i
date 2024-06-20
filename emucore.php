<?php

// this will probably work reliably only on 64-bit builds of PHP

namespace Vic\Riscy;

// register defines
const REG_ZERO = 0;
const REG_RA = 1; // return address
const REG_SP = 2;
const REG_GP = 3; // global pointer
const REG_TP = 4; // thread pointer
// temp pointers
const REG_T0 = 5;
const REG_T1 = 6;
const REG_T2 = 7;
// callee-saved registers
const REG_S0 = 8;
const REG_S1 = 9;
// argument registers
const REG_A0 = 10;
const REG_A1 = 11;
const REG_A2 = 12;
const REG_A3 = 13;
const REG_A4 = 14;
const REG_A5 = 15;
const REG_A6 = 16;
const REG_A7 = 17;
// callee-saved registers
const REG_S2 = 18;
const REG_S3 = 19;
const REG_S4 = 20;
const REG_S5 = 21;
const REG_S6 = 22;
const REG_S7 = 23;
const REG_S8 = 24;
const REG_S9 = 25;
const REG_S10 = 26;
const REG_S11 = 27;
// temp registers
const REG_T3 = 28;
const REG_T4 = 29;
const REG_T5 = 30;
const REG_T6 = 31;

class UnknownOpcodeException extends \Exception {
  public function __construct(public int $opcode) {
    parent::__construct("Unknown opcode or function encountered: " . decbin($this->opcode));
  }
}

interface Machine {
  public function read(int $addr): int;
  public function write(int $addr, int $value);
  public function handle_machine_ecall(CPU &$cpu);
}

class CPU {
  public function __construct(public Machine &$bus) {
    $this->regs = array_fill(0, 32, 0);
  }

  public function execute_once() {
    $instruction = $this->fetch_instruction();

    $opcode = $this->get_bits_at_offset($instruction, 7, 0);
    
    switch($opcode) {
      case 0b0010011:
        return $this->opcodes_immediate_math($instruction);
      case 0b1110011:
        return $this->opcodes_system($instruction);
      default:
        throw new UnknownOpcodeException($opcode);
    }
  }

  // Registers
  public array $regs = [];
  public int $pc = 0;

  // Opcodes
  private function opcodes_immediate_math($instruction) {
    $funct3 = $this->get_bits_at_offset($instruction, 3, 12);
    $rd = $this->get_bits_at_offset($instruction, 5, 7);
    $rs1 = $this->get_bits_at_offset($instruction, 5, 15);
    // SLLI, SRLI, SRAI have a "shamt" instead, using the rest of the immediate as extra function space
    $imm = $this->sign_extended_immediate($instruction, 12, 20);

    switch ($funct3) {
      case 0b000: // ADDI
        $this->regs[$rd] = ($this->regs[$rs1] + $imm) & 0xFFFFFFFF;
        return;
      default:
        throw new UnknownOpcodeException($funct3);
    }
  }

  private function opcodes_system($instruction) {
    $funct3 = $this->get_bits_at_offset($instruction, 3, 12);
    $rd = $this->get_bits_at_offset($instruction, 5, 7);
    $rs1 = $this->get_bits_at_offset($instruction, 5, 15);
    $csr = $this->get_bits_at_offset($instruction, 12, 20);

    switch ($funct3) {
      case 0:
        // ECALL or EBREAK
        if ($csr == 0) {
          // ECALL
          return $this->bus->handle_machine_ecall($this);
        } else {
          // EBREAK
          // we don't have a debugger lol
          return;
        }
      default:
        throw new UnknownOpcodeException($funct3);
    }
  }

  // Helper functions
  private function fetch_instruction(): int {
    $value = $this->bus->read($this->pc++);
    $value |= $this->bus->read($this->pc++) << 8;
    if ($value & 0b11 == 0b11) {
      // 32-byte instruction
      $value |= $this->bus->read($this->pc++) << 16;
      $value |= $this->bus->read($this->pc++) << 24;
    }
    return $value;
  }

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
