#include <stdio.h>
#include <inttypes.h>
#include <mint/osbind.h>
#include <mint/sysbind.h>
#include <string.h>
#include "blitter_sprites.h"
#include "ground_sprites.h"
#include "palette.h"
#include "sin_cos.h"
#include "world.h"
#include "world_data.h"

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

    //draw_ground_sprite(0, 20, 180, 16, physBase);
    //draw_ground_sprite(1, 60, 180, 48, physBase);
    //draw_ground_sprite(2, 100, 180, 80, physBase);
    //draw_ground_sprite(3, 140, 180, 112, physBase);
    //draw_ground_sprite(4, 180, 180, 144, physBase);
    //draw_ground_sprite(5, 220, 180, 176, physBase);
    draw_ground_sprite(6, 10, 180, 208, physBase);

    world.camera_world_x = 2000;
    world.camera_world_y = 100;
    world.camera_world_z = 2000;
    world.camera_yaw = 0;

    Entity *entity;
    int index;

    while (1) {
        transform_and_rotate_world(&world, sin_table, cos_table);

        entity = world.entities;
        for (index=0; index<world.entity_count; index++) {
            if (entity->type == ENTITY_TYPE_SCENERY && entity->transformed_world_z > 0) {
                project_entity(entity);
                //draw_ground_sprite(entity->appearance, entity->screen_x, entity->screen_y, 240, physBase);
            }
            entity++;
        }

        /*world.camera_yaw++;
        if (world.camera_yaw > 255) {
            world.camera_yaw = 0;
        }*/
        world.camera_world_x += 20;
    }
}

int main(int argc, char **argv)
{
   Supexec(&main_supervisor,0, 0, 0, 0, 0);
   return 0;
}
