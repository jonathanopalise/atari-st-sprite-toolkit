#ifndef __GROUND_SPRITES_H
#define __GROUND_SPRITES_H

#include<inttypes.h>

typedef struct {
    int16_t scale_factor;
    int16_t origin_x;
    int16_t origin_y;
    int16_t source_data_width;
    int16_t source_data_height;
    int16_t empty_pixels_on_right;
    int16_t *words;
} ground_sprite;

typedef struct {
    int16_t number_of_elements;
    ground_sprite *ground_sprites;
} ground_sprite_type;

extern ground_sprite_type ground_sprite_types[];

#endif
 
