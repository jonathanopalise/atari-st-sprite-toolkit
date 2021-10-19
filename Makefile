CC = /home/jonathan/brown/bin/m68k-atarisuperbrowner-elf-gcc-11.2.0
CFLAGS = -D__ATARI__ -D__M68000__ -DELF_CONFIG_STACK=1024 -flto -fleading-underscore -fomit-frame-pointer -O3 -m68000 -Wl,--traditional-format -Wall
VASM = vasmm68k_mot
VASM_OPTS = -no-opt
VLINK = vlink
PHP = php

OBJECT_FILES = src/demo.o src/blitter_sprites.o src/generated/palette.o src/generated/ground_sprites.o src/generated/sin_cos.o src/world.o
ASSETS_GIF = assets/pdrift.gif

bin/demo.prg: $(OBJECT_FILES)
	$(CC) -o src/demo.elf libcxx/brownboot.o libcxx/browncrti.o libcxx/browncrtn.o libcxx/browncrt++.o libcxx/zerolibc.o libcxx/zerocrtfini.o $(OBJECT_FILES) -O3 -Wl,--emit-relocs -Wl,-e_start -Ttext=0 -nostartfiles -m68000 -Ofast -fomit-frame-pointer -D__ATARI__ -D__M68000__ -DELF_CONFIG_STACK=1024 -fstrict-aliasing -fcaller-saves -flto -ffunction-sections -fdata-sections -fleading-underscore
	./brown.out -i src/demo.elf -o bin/demo.prg
	chmod +x bin/demo.prg

src/demo.o: src/demo.c src/blitter_sprites.o
	$(CC) $(CFLAGS) -c src/demo.c -o src/demo.o

src/blitter_sprites.o: src/blitter_sprites.s src/blitter_sprites.h
	$(VASM) $(VASM_OPTS) src/blitter_sprites.s -Felf -o src/blitter_sprites.o

src/world.o: src/world.c src/world.h
	$(CC) $(CFLAGS) -c src/world.c -o src/world.o

src/generated/ground_sprites.o: src/generated/ground_sprites.c src/ground_sprites.h
	$(CC) $(CFLAGS) -c src/generated/ground_sprites.c -o src/generated/ground_sprites.o

src/generated/ground_sprites.c: src/generate_ground_sprites.php $(ASSETS_GIF) src/ground_sprites_template.php src/library.php
	$(PHP) src/generate_ground_sprites.php assets/pdrift.gif src/generated/ground_sprites.c

src/generated/sin_cos.o: src/generated/sin_cos.c src/sin_cos.h
	$(CC) $(CFLAGS) -c src/generated/sin_cos.c -o src/generated/sin_cos.o

src/generated/sin_cos.c: src/generate_sin_cos.php
	$(PHP) src/generate_sin_cos.php src/generated/sin_cos.c

src/generated/palette.o: src/generated/palette.s
	$(VASM) $(VASM_OPTS) src/generated/palette.s -Felf -o src/generated/palette.o

src/generated/palette.s: $(ASSETS_GIF) src/generate_palette.php
	$(PHP) src/generate_palette.php assets/pdrift.gif src/generated/palette.s
