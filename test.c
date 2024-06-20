// simple C lib for the "system"
void __attribute__((__noreturn__)) halt() {
  asm("li a7, 0; ecall");
  for (;;)
    ;
}

void putchar(char c) {
  __asm__("li a7, 1; mv a0, %0; ecall" ::"r"(c) : "a7", "a0");
}

char *msg = "hello, C world!\n";

void __attribute__((section(".start"))) _start() {
  char *ptr = msg;
  while (*ptr != 0) {
    putchar(*ptr);
    ptr++;
  }

  halt();
}
