#include<inttypes.h>

typedef struct {
    int16_t scale_factor;
    int16_t origin_x;
    int16_t origin_y;
    int16_t source_data_width;
    int16_t source_data_height;
    int16_t *words;
} ground_sprite;

typedef struct {
    int16_t number_of_elements;
    ground_sprite *ground_sprites;
} ground_sprite_type;

// draw_scaled_ground_sprite(int16_t type, int16_t desired_scale_factor, sint16_t xpos, sint16_t ypos):

ground_sprite_type ground_sprite_types[] = {
    {
        2,
        (ground_sprite[]) {
            {
                1,
                2,
                3,
                4,
                5,
                ( int16_t[] ){ 4, 2, 9 }
            },
            {
                1,
                2,
                3,
                4,
                5,
                ( int16_t[] ){ 0, 3, 9, 5 }
            }
        }
    },
    {
        2,
        (ground_sprite[]) {
            {
                1,
                2,
                3,
                4,
                5,
                ( int16_t[] ){ 4, 2, 9 }
            },
            {
                1,
                2,
                3,
                4,
                5,
                ( int16_t[] ){ 0, 3, 9, 5 }
            }
        }
    }
};

int main(void)
{
}
