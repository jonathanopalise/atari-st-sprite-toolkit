#include <inttypes.h>
#include <mint/osbind.h>
#include <mint/sysbind.h>
#include "blitter_sprites.h"

void main_supervisor() {
    // xpos = word at sp + 6
    // ypos = word at sp + 8
    // source_data = long at sp + 12
    // source_data_width = word at sp + 18
    // source_data_height = word at sp + 22
    // screen_buffer = long at sp + 24

    draw_sprite(0x2020,0x3030,(int *)0x40404040,0x5050,0x6060,(int *)0x70707070);
}

int main(int argc, char **argv)
{
   Supexec(&main_supervisor,0, 0, 0, 0, 0);
   return 0;
}
