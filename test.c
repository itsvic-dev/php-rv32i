// simple C lib for the "system"
#include "libc.c"

char *msg = "hello, C world!\n";

void __attribute__((section(".start"))) _start() {
  char *ptr = msg;
  while (*ptr != 0) {
    putchar(*ptr);
    ptr++;
  }

  halt();
}
