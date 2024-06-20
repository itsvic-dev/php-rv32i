OBJS := test.bin mintest.bin

all: $(OBJS)
.PHONY: all

%.o: %.s
	clang --target=riscv32 -march=rv32i -c $< -o $@

%.bin: %.o
	ld.lld --oformat=binary -T linker.ld $< -o $@

.PHONY: clean
clean:
	rm -f $(OBJS)
