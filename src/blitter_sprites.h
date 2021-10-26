#ifndef __BLITTER_SPRITES_H
#define __BLITTER_SPRITES_H

void draw_sprite(
    int16_t xpos,
    int16_t ypos,
    void *source_data,
    int16_t source_data_width,
    int16_t source_data_height,
    void *screen_buffer,
    int16_t empty_pixels_on_right,
    uint16_t masked
);

#endif
                                  
