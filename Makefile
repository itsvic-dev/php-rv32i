OBJS := test.bin

all: $(OBJS)
.PHONY: all

%.o: %.s
	clang --target=riscv32 -c $< -o $@

%.bin : %.o
	llvm-objcopy -O binary $< $@
