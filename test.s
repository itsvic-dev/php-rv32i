.option arch, rv32i

test:
  addi a0, a0, 0x69
  
  // exits
  ecall
