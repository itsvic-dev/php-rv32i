<?php

namespace Vic\Riscy;

class Constants {
    // register defines
    public const REG_ZERO = 0;
    public const REG_RA = 1; // return address
    public const REG_SP = 2;
    public const REG_GP = 3; // global pointer
    public const REG_TP = 4; // thread pointer
    // temp pointers
    public const REG_T0 = 5;
    public const REG_T1 = 6;
    public const REG_T2 = 7;
    // callee-saved registers
    public const REG_S0 = 8;
    public const REG_S1 = 9;
    // argument registers
    public const REG_A0 = 10;
    public const REG_A1 = 11;
    public const REG_A2 = 12;
    public const REG_A3 = 13;
    public const REG_A4 = 14;
    public const REG_A5 = 15;
    public const REG_A6 = 16;
    public const REG_A7 = 17;
    // callee-saved registers
    public const REG_S2 = 18;
    public const REG_S3 = 19;
    public const REG_S4 = 20;
    public const REG_S5 = 21;
    public const REG_S6 = 22;
    public const REG_S7 = 23;
    public const REG_S8 = 24;
    public const REG_S9 = 25;
    public const REG_S10 = 26;
    public const REG_S11 = 27;
    // temp registers
    public const REG_T3 = 28;
    public const REG_T4 = 29;
    public const REG_T5 = 30;
    public const REG_T6 = 31;
};
