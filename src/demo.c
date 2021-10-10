#include <stdio.h>
#include <inttypes.h>
#include <mint/osbind.h>
#include <mint/sysbind.h>
#include <string.h>
#include "blitter_sprites.h"
#include "ground_sprites.h"
#include "palette.h"

void draw_ground_sprite(uint16_t sprite_index, int16_t xpos, int16_t ypos, uint16_t desired_scale_factor, void *screenBuffer)
{
    ground_sprite *selected_ground_sprite = &ground_sprite_types[sprite_index].ground_sprites[desired_scale_factor >> 5];

    draw_sprite(
        xpos - selected_ground_sprite->origin_x,
        ypos - selected_ground_sprite->origin_y,
        selected_ground_sprite->words,
        selected_ground_sprite->source_data_width,
        selected_ground_sprite->source_data_height,
        screenBuffer
    );
}

void main_supervisor() {
    memcpy((void *)0xffff8240, palette, 32);
    uint16_t *physBase;
    physBase=Physbase();

    draw_ground_sprite(0, 20, 180, 16, physBase);
    draw_ground_sprite(1, 60, 180, 48, physBase);
    draw_ground_sprite(2, 100, 180, 80, physBase);
    draw_ground_sprite(3, 140, 180, 112, physBase);
    draw_ground_sprite(4, 180, 180, 144, physBase);
    draw_ground_sprite(5, 220, 180, 176, physBase);
    draw_ground_sprite(6, 260, 180, 208, physBase);
    draw_ground_sprite(7, 300, 180, 240, physBase);

    while (1) {}
}

int main(int argc, char **argv)
{
   Supexec(&main_supervisor,0, 0, 0, 0, 0);
   return 0;
}
