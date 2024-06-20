.option arch, rv32i

// stolen from a C compiler lololol

_start:
  li a7, 1
  
  lui a1, %hi(msg_ptr)
  lw a1, %lo(msg_ptr)(a1)
  lbu a0, 0(a1)
  beqz a0, end
  addi a1, a1, 1
loop:
  ecall
  lbu a0, 0(a1)
  addi a1, a1, 1
  bnez a1, loop

end:
  // exits
  li a7, 0
  ecall

msg:
  .string "hello, world!\n"

msg_ptr:
  .word msg
