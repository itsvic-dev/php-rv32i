ENTRY(_start)
SECTIONS
{
  .text 0x0 : {
    *(.text)
    *(.code)
    *(.rodata*)
  }
  .data : {
    *(.data)
  }
  .bss : {
    *(.bss)
  }
}
