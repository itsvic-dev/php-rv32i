ENTRY(_start)
SECTIONS
{
  .start : {
    *(.start)
  }

  .text : {
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
