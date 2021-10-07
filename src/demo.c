#include <inttypes.h>
#include <mint/osbind.h>
#include <mint/sysbind.h>
#include "blitter_sprites.h"
#include "mega_man.h"

void main_supervisor() {
    // xpos = word at sp + 6
    // ypos = word at sp + 10
    // source_data = long at sp + 12
    // source_data_width = word at sp + 18
    // source_data_height = word at sp + 22
    // screen_buffer = long at sp + 24

    uint16_t *physBase;
    physBase=Physbase();
    draw_sprite(120,100,(void *)mega_man,112,98,(void *)physBase);
}

int main(int argc, char **argv)
{
   Supexec(&main_supervisor,0, 0, 0, 0, 0);
   return 0;
}
