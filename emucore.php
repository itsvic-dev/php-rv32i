<?php

// this will probably work reliably only on 64-bit builds of PHP

namespace Vic\Riscy;

const INSN_LOGS = false;

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
    // print("pc=" . dechex($this->pc) . ", insn=" . dechex($instruction) . "\n");

    $opcode = $this->get_bits_at_offset($instruction, 7, 0);

    $this->regs[0] = 0; // im stupid lol
    
    switch($opcode) {
      case 0b0010111:
        return $this->opcode_auipc($instruction);
      case 0b0110111:
        return $this->opcode_lui($instruction);
      case 0b0000011:
        return $this->opcodes_load($instruction);
      case 0b0100011:
        return $this->opcodes_store($instruction);
      case 0b0010011:
        return $this->opcodes_immediate_math($instruction);
      case 0b1100011:
        return $this->opcodes_branch($instruction);
      case 0b1101111:
        return $this->opcode_jal($instruction);
      case 0b1100111:
        return $this->opcode_jalr($instruction);
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
    $imm = $this->i_immediate($instruction);

    switch ($funct3) {
      case 0b000: // ADDI
        INSN_LOGS && print("addi x$rd, x$rs1, $imm\n");
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

  private function opcode_auipc($instruction) {
    $rd = $this->get_bits_at_offset($instruction, 5, 7);
    $imm = $this->u_immediate($instruction);
    $addr = $this->pc - 4 + $imm;
    INSN_LOGS && print("auipc x$rd, $addr\n");
    $this->regs[$rd] = $addr;
  }

  private function opcode_lui($instruction) {
    $rd = $this->get_bits_at_offset($instruction, 5, 7);
    $imm = $this->u_immediate($instruction);
    INSN_LOGS && print("lui x$rd, $imm\n");
    $this->regs[$rd] = $imm;
  }

  private function opcodes_load($instruction) {
    $rd = $this->get_bits_at_offset($instruction, 5, 7);
    $funct3 = $this->get_bits_at_offset($instruction, 3, 12);
    $rs1 = $this->get_bits_at_offset($instruction, 5, 15);
    $imm = $this->i_immediate($instruction);
    
    $effectiveAddress = $this->regs[$rs1] + $imm;

    switch ($funct3) {
      case 0b000: // load byte
        INSN_LOGS && print("lb x$rd, x$rs1, $imm\n");
        $this->regs[$rd] = $this->sign_extended_immediate($this->bus->read($effectiveAddress), 8, 0);
        return;
      case 0b100: // load byte unsigned
        INSN_LOGS && print("lbu x$rd, x$rs1, $imm\n");
        $this->regs[$rd] = $this->bus->read($effectiveAddress);
        return;
      case 0b001: // load half-word
        INSN_LOGS && print("lh x$rd, x$rs1, $imm\n");
        $this->regs[$rd] = $this->sign_extended_immediate($this->load_halfword($effectiveAddress), 16, 0);
        return;
      case 0b101: // load half-word unsigned
        INSN_LOGS && print("lhu x$rd, x$rs1, $imm\n");
        $this->regs[$rd] = $this->load_halfword($effectiveAddress);
        return;
      case 0b010: // load word
        INSN_LOGS && print("lw x$rd, x$rs1, $imm\n");
        $this->regs[$rd] = $this->load_word($effectiveAddress);
        return;
    }
  }

  private function opcodes_store($instruction) {
    $funct3 = $this->get_bits_at_offset($instruction, 3, 12);
    $rs1 = $this->get_bits_at_offset($instruction, 5, 15);
    $rs2 = $this->get_bits_at_offset($instruction, 5, 20);
    $imm = $this->s_immediate($instruction);

    $effectiveAddress = $this->regs[$rs1] + $imm;

    switch($funct3) {
      case 0b000: // store byte
        INSN_LOGS && print("sb x$rs2, $imm(x$rs1)\n");
        $this->bus->write($effectiveAddress, $this->regs[$rs2]);
        return;
      case 0b001: // store half-word
        INSN_LOGS && print("sh x$rs2, $imm(x$rs1)\n");
        $this->bus->write($effectiveAddress, $this->regs[$rs2]);
        $this->bus->write($effectiveAddress + 1, $this->regs[$rs2] >> 8);
        return;
      case 0b010: // store word
        INSN_LOGS && print("sw x$rs2, $imm(x$rs1)\n");
        $this->bus->write($effectiveAddress, $this->regs[$rs2]);
        $this->bus->write($effectiveAddress + 1, $this->regs[$rs2] >> 8);
        $this->bus->write($effectiveAddress + 2, $this->regs[$rs2] >> 16);
        $this->bus->write($effectiveAddress + 3, $this->regs[$rs2] >> 24);
        return;
    }
  }

  private function opcodes_branch($instruction) {
    $funct3 = $this->get_bits_at_offset($instruction, 3, 12);
    $rs1 = $this->get_bits_at_offset($instruction, 5, 15);
    $rs2 = $this->get_bits_at_offset($instruction, 5, 20);
    $imm = $this->b_immediate($instruction);

    // idk if its -4 or not, guess we'll see!!!
    $effectiveAddress = $imm + $this->pc - 4;

    switch ($funct3) {
      case 0b000:
        INSN_LOGS && print("beq x$rs1, x$rs2, $effectiveAddress\n");
        if ($this->regs[$rs1] == $this->regs[$rs2]) $this->pc = $effectiveAddress;
        return;
      case 0b001:
        INSN_LOGS && print("bne x$rs1, x$rs2, $effectiveAddress\n");
        if ($this->regs[$rs1] != $this->regs[$rs2]) $this->pc = $effectiveAddress;
        return;
      default:
        throw new UnknownOpcodeException($funct3);
    }
  }

  private function opcode_jal($instruction) {
    $rd = $this->get_bits_at_offset($instruction, 5, 7);
    $imm = $this->j_immediate($instruction);
    $effectiveAddress = $this->pc + $imm - 4;
    INSN_LOGS && print("jal x$rd, $effectiveAddress\n");
    $this->regs[$rd] = $this->pc;
    $this->pc = $effectiveAddress;
  }

  private function opcode_jalr($instruction) {
    $rd = $this->get_bits_at_offset($instruction, 5, 7);
    $rs1 = $this->get_bits_at_offset($instruction, 5, 15);
    $imm = $this->i_immediate($instruction);

    $effectiveAddress = ($this->regs[$rs1] + $imm) & ~1;
    $this->regs[$rd] = $this->pc;
    $this->pc = $effectiveAddress;
  
    INSN_LOGS && print("jalr x$rd, $imm(x$rs1)\n");
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

  private function load_halfword(int $addr): int {
    return $this->bus->read($addr)
      | $this->bus->read($addr + 1) << 8;
  }

  private function load_word(int $addr): int {
    return $this->bus->read($addr)
      | $this->bus->read($addr + 1) << 8
      | $this->bus->read($addr + 2) << 16
      | $this->bus->read($addr + 3) << 24;
  }

  private static function get_bits_at_offset($instruction, $length, $offset) {
    return ($instruction >> $offset) & ((1 << $length) - 1);
  }

  private static function i_immediate($instruction, bool $sign64 = PHP_INT_SIZE == 8) {
    $imm = CPU::get_bits_at_offset($instruction, 11, 20);
    $sign = $instruction >> 31;
    if ($sign) {
      $imm |= ((1 << (($sign64 ? 64 : 32) - 11)) - 1) << 11;
    }

    return $imm;
  }

  private static function s_immediate($instruction, bool $sign64 = PHP_INT_SIZE == 8) {
    $imm = CPU::get_bits_at_offset($instruction, 5, 7);
    $imm |= CPU::get_bits_at_offset($instruction, 6, 25) << 5;
    $sign = $instruction >> 31;
    if ($sign) {
      $imm |= ((1 << (($sign64 ? 64 : 32) - 11)) - 1) << 11;
    }

    return $imm;
  }

  private static function b_immediate($instruction, bool $sign64 = PHP_INT_SIZE == 8) {
    $imm = CPU::get_bits_at_offset($instruction, 4, 8) << 1;
    $imm |= CPU::get_bits_at_offset($instruction, 6, 25) << 5;
    $imm |= (($instruction >> 7) & 1) << 11;
    $sign = $instruction >> 31;
    if ($sign) {
      $imm |= ((1 << (($sign64 ? 64 : 32) - 12)) - 1) << 12;
    }

    return $imm;
  }

  private static function u_immediate($instruction, bool $sign64 = PHP_INT_SIZE == 8) {
    return CPU::get_bits_at_offset($instruction, 20, 12) << 12;
  }

  private static function j_immediate($instruction, bool $sign64 = PHP_INT_SIZE == 8) {
    $imm = CPU::get_bits_at_offset($instruction, 10, 21) << 1;
    $imm |= (($instruction >> 20) & 1) << 11;
    $imm |= CPU::get_bits_at_offset($instruction, 8, 12) << 12;
    $sign = $instruction >> 31;
    if ($sign) {
      $imm |= ((1 << (($sign64 ? 64 : 32) - 20)) - 1) << 20;
    }

    return $imm;
  }
};
