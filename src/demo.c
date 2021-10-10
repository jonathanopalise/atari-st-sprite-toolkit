#include <stdio.h>
#include <inttypes.h>
#include <mint/osbind.h>
#include <mint/sysbind.h>
#include <string.h>
#include "blitter_sprites.h"
#include "ground_sprites.h"
#include "palette.h"

void main_supervisor() {
    // xpos = word at sp + 6
    // ypos = word at sp + 10
    // source_data = long at sp + 12
    // source_data_width = word at sp + 18
    // source_data_height = word at sp + 22
    // screen_buffer = long at sp + 24

    memcpy((void *)0xffff8240, palette, 32);
    uint16_t *physBase;
    physBase=Physbase();

    draw_sprite(
        160,
        80,
        ground_sprite_types[0].ground_sprites[1].words,
        ground_sprite_types[0].ground_sprites[1].source_data_width,
        ground_sprite_types[0].ground_sprites[1].source_data_height,
        (void *)physBase
    );

    draw_sprite(
        60,
        60,
        ground_sprite_types[0].ground_sprites[0].words,
        ground_sprite_types[0].ground_sprites[0].source_data_width,
        ground_sprite_types[0].ground_sprites[0].source_data_height,
        (void *)physBase
    );

    draw_sprite(
        20,
        90,
        ground_sprite_types[1].ground_sprites[2].words,
        ground_sprite_types[1].ground_sprites[2].source_data_width,
        ground_sprite_types[1].ground_sprites[2].source_data_height,
        (void *)physBase
    );

    draw_sprite(
        160,
        130,
        ground_sprite_types[1].ground_sprites[3].words,
        ground_sprite_types[1].ground_sprites[3].source_data_width,
        ground_sprite_types[1].ground_sprites[3].source_data_height,
        (void *)physBase
    );

    draw_sprite(
        200,
        20,
        ground_sprite_types[2].ground_sprites[5].words,
        ground_sprite_types[2].ground_sprites[5].source_data_width,
        ground_sprite_types[2].ground_sprites[5].source_data_height,
        (void *)physBase
    );

    draw_sprite(
        50,
        20,
        ground_sprite_types[2].ground_sprites[6].words,
        ground_sprite_types[2].ground_sprites[6].source_data_width,
        ground_sprite_types[2].ground_sprites[6].source_data_height,
        (void *)physBase
    );

    draw_sprite(
        100,
        20,
        ground_sprite_types[2].ground_sprites[3].words,
        ground_sprite_types[2].ground_sprites[3].source_data_width,
        ground_sprite_types[2].ground_sprites[3].source_data_height,
        (void *)physBase
    );


    //draw_sprite(60,40,(void *)mega_man,112,98,(void *)physBase);
    //draw_sprite(260,140,(void *)mega_man,112,98,(void *)physBase);
    //draw_sprite(80,60,(void *)mega_man,112,98,(void *)physBase);
    //for (int index = 0; index<250; index+=10) {
    //draw_sprite(20,20,(void *)mega_man,128,120,physBase);
    //    draw_sprite(index,60,(void *)mega_man,112,98,physBase);
    //    draw_sprite(index,100,(void *)mega_man,112,98,physBase);
    //    draw_sprite(index,140,(void *)mega_man,112,98,physBase);
        //*(((int *)physBase) + (index * 16)) = 0xffff;
    //}
    //draw_sprite(40,40,(void *)mega_man,112,98,(void *)0xf8000);
    //draw_sprite(60,60,(void *)mega_man,112,98,(void *)0xf8000);
    //}
    //draw_sprite(60,40,(void *)mega_man,112,98,(void *)physBase);
    while (1) {}
    //draw_sprite(-60,140,(void *)mega_man,112,98,(void *)physBase);
    //draw_sprite(260,-30,(void *)mega_man,112,98,(void *)physBase);
    //draw_sprite(-60,-30,(void *)mega_man,112,98,(void *)physBase);

}

int main(int argc, char **argv)
{
   Supexec(&main_supervisor,0, 0, 0, 0, 0);
   return 0;
}
