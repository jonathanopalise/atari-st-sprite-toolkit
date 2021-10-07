CC = /home/jonathan/brown/bin/m68k-atarisuperbrowner-elf-gcc-11.2.0
CFLAGS = -D__ATARI__ -D__M68000__ -DELF_CONFIG_STACK=1024 -flto -fleading-underscore -fomit-frame-pointer -O3 -m68000 -Wl,--traditional-format -Wall
VASM = vasmm68k_mot
VASM_OPTS = -no-opt
VLINK = vlink

OBJECT_FILES = src/demo.o src/blitter_sprites.o src/mega_man.o

bin/demo.prg: $(OBJECT_FILES)
	$(CC) -o src/demo.elf libcxx/brownboot.o libcxx/browncrti.o libcxx/browncrtn.o libcxx/browncrt++.o libcxx/zerolibc.o libcxx/zerocrtfini.o $(OBJECT_FILES) -O3 -Wl,--emit-relocs -Wl,-e_start -Ttext=0 -nostartfiles -m68000 -Ofast -fomit-frame-pointer -D__ATARI__ -D__M68000__ -DELF_CONFIG_STACK=1024 -fstrict-aliasing -fcaller-saves -flto -ffunction-sections -fdata-sections -fleading-underscore
	./brown.out -i src/demo.elf -o bin/demo.prg
	chmod +x bin/demo.prg

src/demo.o: src/demo.c src/blitter_sprites.o
	$(CC) $(CFLAGS) -c src/demo.c -o src/demo.o

src/blitter_sprites.o: src/blitter_sprites.s src/blitter_sprites.h
	$(VASM) $(VASM_OPTS) src/blitter_sprites.s -Felf -o src/blitter_sprites.o

src/mega_man.o: src/mega_man.s
	$(VASM) $(VASM_OPTS) src/mega_man.s -Felf -o src/mega_man.o

src/mega_man.s: megaman.data src/generate_mega_man.php src/library.php
	php src/generate_mega_man.php
