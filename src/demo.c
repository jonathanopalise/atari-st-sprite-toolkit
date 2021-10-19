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
#include "initialise.h"

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


uint16_t *physBase;
uint16_t *logBase;

void framebuffer_open() {
    //__asm__ __volatile__("move.w #0x2700,%sr");

    physBase=Physbase();
    logBase=physBase-0x4000;
    memset(logBase,0,32000);
    memset(physBase,0,32000);
}

void framebuffer_flip() {
    uint16_t *tmp;
    tmp=physBase;
    physBase=logBase;
    logBase=tmp;
    Setscreen(logBase,physBase,-1);
}

void main_supervisor() {
    initialise();
    framebuffer_open();

	int joyUp;
	int joyDown;
	int joyLeft;
	int joyRight;


    memcpy((void *)0xffff8240, palette, 32);
    //uint16_t *physBase;
    //physBase=Physbase();

    //draw_ground_sprite(0, 20, 180, 16, physBase);
    //draw_ground_sprite(1, 60, 180, 48, physBase);
    //draw_ground_sprite(2, 100, 180, 80, physBase);
    //draw_ground_sprite(3, 140, 180, 112, physBase);
    //draw_ground_sprite(4, 180, 180, 144, physBase);
    //draw_ground_sprite(5, 220, 180, 176, physBase);
    //draw_ground_sprite(6, 10, 180, 208, physBase);

    world.camera_world_x = -100;
    world.camera_world_y = -100;
    world.camera_world_z = -100;
    world.camera_yaw = 0;

    Entity *entity;
    int position;
    int index;
    int size;
    int yaw;

    yaw = 0;
    position = 0;

    while (1) {
        joyUp=joy_data&1; 
        joyDown=joy_data&2;
        joyLeft=joy_data&4;
        joyRight=joy_data&8;

        memset(logBase, 0, 32000);

        if (joyRight) {
            world.camera_yaw++;
            if (world.camera_yaw > 1023) {
                world.camera_yaw = 0;
            }
        } else if (joyLeft) {
            world.camera_yaw--;
            if (world.camera_yaw < 0) {
                world.camera_yaw = 1023;
            }
        } 

        if (joyUp) {
            world.camera_world_x += sin_table[world.camera_yaw] / 4;
            world.camera_world_z += cos_table[world.camera_yaw] / 4;
        }

        /*entity = &world.entities[position];
        world.camera_world_x = entity->world_x;
        world.camera_world_z = entity->world_z;
        position++;
        if (position == world.entity_count) {
            position=0;
        }*/

        transform_and_rotate_world(&world, sin_table, cos_table);

        entity = world.entities;
        for (index=0; index<world.entity_count; index++) {
            if (entity->transformed_world_z > 0 && entity->transformed_world_z < 16384) {
                project_entity(entity);

                size = fixed_div_6_10(400, entity->transformed_world_z);
                if (size > 255) {
                    size = 255;
                }

                draw_ground_sprite(
                        entity->appearance,
                        entity->screen_x,
                        entity->screen_y,
                        size,
                        logBase
                );
            }
            entity++;
        }

        //yaw++;
        //if (yaw == 4) {
            /*world.camera_yaw++;
            if (world.camera_yaw > 1023) {
                world.camera_yaw = 0;
            }
            yaw = 0;*/
        //}

        framebuffer_flip();
    }
}

int main(int argc, char **argv)
{
   Supexec(&main_supervisor,0, 0, 0, 0, 0);
   return 0;
}
