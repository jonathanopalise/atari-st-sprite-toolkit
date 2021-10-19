#include <stdio.h>
#include <inttypes.h>
#include "world.h"

static int16_t fixed_mul_6_10(int16_t x, int16_t y)
{
    return ((int32_t)x * (int32_t)y) / (1 << 10);
}

static int16_t fixed_div_6_10(int16_t x, int16_t y)
{
    return ((int32_t)x * (1 << 10)) / y;
}

void transform_and_rotate_world(World *world, int16_t *sin, int16_t *cos)
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

void project_entity(Entity *entity)
{
    entity->screen_x = fixed_div_6_10(entity->transformed_world_x, entity->transformed_world_z) + 160;
    entity->screen_y = fixed_div_6_10(entity->transformed_world_y, entity->transformed_world_z) + 100;
}

