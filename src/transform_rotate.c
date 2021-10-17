#include<stdio.h>
#include<inttypes.h>
#include "transform_rotate.h"

/*

    need a struct that describes the camera:

    CAMERA
    int16_t world_x;
    int16_t world_y;
    int16_t world_z;
    int16_t pitch; // look up and down
    int16_t yaw; // look left and right

    need a struct that describes the environment:

    ENTITY
    int16_t world_x;
    int16_t world_y;
    int16_t world_z;
    int16_t transformed_world_x;
    int16_t transformed_world_y;
    int16_t transformed_world_z;
    int16_t screen_x;
    int16_t screen_y;
    uint16_t type;

    need to somehow make sure that the points in the track are accessible to game logic
    as we'll need them to move around the track

    what if we just hold the count of track points and have the track points first in the points list?

    need sin/cos tables with 256 entries

    just get it working initially:
    - transform world coordinates to camera coordinates (WATCH OUT FOR OVERFLOW!)
    - rotate world coordinates to camera coordinates (initially just use yaw)
        - newX = x * cos(angle) - z * sin(angle)
        - newZ = z * cos(angle) + x * sin(angle)
        - so if camera_x = 4000 and entity_x = 6000, transformed_entity_x should be 2000
            - for all entities:
                - entity->transformed_world_x = entity->world_x - camera->world_x
                - entity->transformed_world_y = entity->world_y - camera->world_y
                - entity->transformed_world_z = entity->world_z - camera->world_z
                - entity_world_x = entity->transformed_world_x
                - entity_world_z = entity->transformed_world_z
                - entity->transformed_world_x = fixed_mul_6_10(entity_world_x,cos[camera->yaw]) - fixed_mul_6_10(entity_world_z,sin[camera->yaw]);
                - entity->transformed_world_z = fixed_mul_6_10(entity_world_z,cos[camera->yaw]) + fixed_mul_6_10(entity_world_x,sin[camera->yaw]);
    - create list of pointers to visible objects where z > 0 (each entry is a pointer to an ENTITY)
        - for all entities
    - sort list of pointers by Z
    - for all visible objects, generate screen_x and screen_y and then draw
*/

/*int16_t fixed_mul_16(int16_t x, int16_t y)
{
    return ((int32_t)x * (int32_t)y) / (1 << 8);
}

int16_t fixed_div_16(int16_t x, int16_t y)
{
    return ((int32_t)x * (1 << 8)) / y;
}*/

int16_t fixed_mul_6_10(int16_t x, int16_t y)
{
    return ((int32_t)x * (int32_t)y) / (1 << 10);
}

int16_t fixed_div_6_10(int16_t x, int16_t y)
{
    return ((int32_t)x * (1 << 10)) / y;
}

void transform_and_rotate_all_entities(World *world, int16_t *sin, int16_t *cos)
{
    int16_t entity_world_x;
    int16_t entity_world_z;

    Entity *entity = world->entities;

    for (int index = 0; index < world->entity_count; index++) {
        entity->transformed_world_x = entity->world_x - world->camera_world_x;
        entity->transformed_world_y = entity->world_y - world->camera_world_y;
        entity->transformed_world_z = entity->world_z - world->camera_world_z;

        entity_world_x = entity->transformed_world_x;
        entity_world_z = entity->transformed_world_z;

        entity->transformed_world_x = fixed_mul_6_10(entity_world_x,cos[world->camera_yaw]) - fixed_mul_6_10(entity_world_z,sin[world->camera_yaw]);
        entity->transformed_world_z = fixed_mul_6_10(entity_world_z,cos[world->camera_yaw]) + fixed_mul_6_10(entity_world_x,sin[world->camera_yaw]);

        entity++;
    }
}

/*void determine_and_project_visible_objects()
{
}*/

    // 6.10 fixed point
    // 000000 / 0000000000
    // integer: -32 to 31
    // fractional: 0 to 1023
    // examples:
    //

    // x = 12.5 : (12 << 10) + 512 : 12800
    //int x = (12 << 10) + 512;
    // y = 1.5 : (1 << 10) + 512 : 1536
    //int y = (1 << 10) + 512;
    // expected: 18432 + 768 : 19200

    /*printf(
        "%d divided by %d = %d\n",
        x,
        y,
        fixed_mul_6_10(x,y)
    );*/

    // rotation:
    //
    // newX = x * cos(angle) - z * sin(angle)
    // newZ = z * cos(angle) + x * sin(angle)

    // projection:
    // 
    // screenX = worldX/worldZ (+160?)
    // screenY = worldY/worldZ (+100?)
    // we need to shift left the worldX or worldY by 8 before we perform the division
    //
    // might want to shift the result left afterwards (field of view?)
    //
    // e.g.
    // worldX = 3136 (12.25)
    // worldY = 2560
    // worldZ = 4000 (15.62)
    //
    // fixed point range = -32768 to 32767 (approx -128 to 127)
    // so we need to watch out for overflows!
    //
    // screenX = 51380fp (200+160=360)
    // screenY = 41943fp

    // addition and subtraction just work
    // multiplication requires the result to be >> 8
    // using 8.8 format:
    // first number: 12.25 = 3136
    // second number: 10.00 = 2560
    //
    // division requires the first number to be shifted left first
    // using 8.8 format:
    // first number: 30.5 = 7808
    // second number: 5.5 = 1408
    // 1998848 = 1419 = 5.54
    //
    // 3136 * 2560 = 8028160
    // shift right by 8 = 31360
    // integer part: 31360 >> 8 = 122
    // fractional part: 31360 & 255 = 128 (0.5)
    //
    // result: 122.5
    //
    // sin and cos go from -1 to 1
    //
    // how does all this work with signed values?
