SRCS := test.c
OBJS := $(addsuffix .bin,$(SRCS))

all: $(OBJS)
.PHONY: all

%.c.o: %.c
	clang -MD --target=riscv32 -march=rv32i -mabi=ilp32 -nostdlib -nostdinc -fno-asynchronous-unwind-tables -O -c $< -o $@

%.s.o: %.s
	clang --target=riscv32 -march=rv32i -c $< -o $@

%.bin: %.o
	ld.lld --oformat=binary -T linker.ld $< -o $@

.PHONY: clean
clean:
	rm -f $(OBJS) $(addsuffix .o,$(SRCS))

-include $(addsuffix .d,$(SRCS))
