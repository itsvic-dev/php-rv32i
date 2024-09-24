# Riscy

Riscy is a RISC-V emulator written in pure PHP.
It implements a subset of the `RV32IM` architecture, just enough to run small, specialized C programs for my purposes.

Riscy is used internally in Nova v1.3.0+ to run binary blobs.
It has been tested only on 64-bit PHP. There may be issues when running on 32-bit PHP because of the different integer size.

This emulator is not meant to be a general-purpose emulator, it will not boot Linux or OpenSBI or anything.
You are expected to implement your own hypercalls for your software.

## Future plans

- maybe fully implementing the `RV32IM` spec
- maybe implementing the `C` extension to reduce code size
