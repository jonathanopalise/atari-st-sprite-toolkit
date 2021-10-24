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
// WARNING: needs to match or exceed entity count!
Entity *visible_entity_pointers[300];
Entity **current_visible_entity_pointer;
int visible_entity_count;

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

int compare_function(const void *entity1, const void *entity2)
{
    const Entity *entity1_ = *(const Entity **)entity1;
    const Entity *entity2_ = *(const Entity **)entity2;

    return entity2_->transformed_world_z - entity1_->transformed_world_z;
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
    world.camera_world_y = -300;
    world.camera_world_z = -100;
    world.camera_yaw = 0;

    Entity *entity;
    Entity *next_entity;
    Entity *vis_entity;
    int track_position;
    int log_index;
    int next_log_index;
    int offset_within_log;
    int index;
    int size;
    int yaw;
    uint32_t value;
    uint32_t *valuePointer;

    int16_t car_x;
    int16_t car_z;
    int16_t next_entity_yaw;
    int16_t yaw_difference;

    yaw = 0;
    track_position = 0;

    while (1) {
        joyUp=joy_data&1; 
        joyDown=joy_data&2;
        joyLeft=joy_data&4;
        joyRight=joy_data&8;

        valuePointer = logBase;
        value = 0x0000ffff;
        for (index = 0; index < 4000; index++) {
            *valuePointer = value;
            valuePointer++;
        }
        value = 0xffff0000;
        for (index = 0; index < 4000; index++) {
            *valuePointer = value;
            valuePointer++;
        }

        log_index = track_position / 600;
        offset_within_log = track_position % 600;

        next_log_index = log_index+1;
        if (next_log_index == world.log_count) {
            next_log_index = 0;
        }

        entity = &world.entities[log_index];
        next_entity = &world.entities[next_log_index];

        car_x = entity->world_x + ((next_entity->world_x - entity->world_x) * offset_within_log / 600);
        car_z = entity->world_z + ((next_entity->world_z - entity->world_z) * offset_within_log / 600);
        //world.camera_yaw = entity->yaw + ((next_entity->yaw - entity->yaw) * offset_within_log / 600);
        
        yaw_difference = next_entity->yaw - entity->yaw;
        if (yaw_difference > 511) {
            next_entity_yaw = next_entity->yaw - 1024;
        } else if (yaw_difference < -512) {
            next_entity_yaw = next_entity->yaw + 1024;
        } else {
            next_entity_yaw = next_entity->yaw;
        }

        world.camera_yaw = entity->yaw + ((next_entity_yaw - entity->yaw) * offset_within_log / 600);
        if (world.camera_yaw > 1023) {
            world.camera_yaw -= 1024;
        } else if (world.camera_yaw < 0) {
            world.camera_yaw += 1024;
        }

        // if the difference between entity_yaw and next_entity>yaw is greater than 512
        // then we have probably wrapped
        //
        // what to do in this scenario?
        //
        // example: entity_yaw = 12, next_entity_yaw = 1014
        // - yaw_difference = 1002
        // - (condition: result > 511)
        // - next_entity_yaw becomes -10 (next_entity_yaw - 1024)
        // - therefore, lerp calculation is based upon -10 - 12
        // - resulting lerped value might be less than zero
        // - if it is, we add 1024
        //
        // example: entity_yaw = 1014, next_entity_yaw = 10
        // - result = - 1004
        // - (condition: result < -512)
        // - next_entity_yaw becomes 1034 (next_entity_yaw + 1024)
        // - therefore, lerp calculation is based upon 1034 - 1014
        // - resulting lerped value might be greater than 1023
        // - if it is, we subtract 1024

        // problem happens when next entity yaw is 
        // e.g. 
        //   entity_yaw = 4
        //   next_entity_yaw = 1000
        //
        //   we need to work out the shortest way to get from entity_yaw to next_entity_yaw:
        //     whether to subtract or add

        world.camera_world_x = car_x - sin_table[world.camera_yaw];
        world.camera_world_z = car_z - cos_table[world.camera_yaw];

        track_position += 200;
        if (track_position > world.log_count * 600) {
            track_position -= (world.log_count * 600);
        }

        for (int index = 0; index < entity->visible_entities_length; index++) {
            vis_entity = &world.entities[entity->visible_entities[index]];
            project_entity(vis_entity, &world, sin_table, cos_table);

            if (vis_entity->transformed_world_z > 50) {

                size = fixed_div_6_10(400, vis_entity->transformed_world_z);
                if (size > 255) {
                    size = 255;
                }

                draw_ground_sprite(
                        vis_entity->appearance,
                        vis_entity->screen_x,
                        vis_entity->screen_y,
                        size,
                        logBase
                );
            }

        } 

        framebuffer_flip();
    }
}

int main(int argc, char **argv)
{
   Supexec(&main_supervisor,0, 0, 0, 0, 0);
   return 0;
}
