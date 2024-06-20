{
  pkgs ? import <nixpkgs> { },
}:
with pkgs;
mkShell {
  buildInputs = [
    php
    gnumake
    libllvm
    lld
    clang
  ];
}
