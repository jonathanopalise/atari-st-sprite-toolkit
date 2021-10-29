#include <stdio.h>
#include <inttypes.h>
#include "world.h"

static int16_t fixed_mul_6_10(int16_t x, int16_t y)
{
    return ((int32_t)x * (int32_t)y) / (1 << 10);
}

int16_t fixed_div_6_10(int16_t x, int16_t y)
{
    return ((int32_t)x * (1 << 10)) / y;
}

void project_entity(Entity *entity, World *world, int16_t *sin, int16_t *cos)
{
    int16_t entity_world_x;
    int16_t entity_world_y;
    int16_t entity_world_z;

    entity->transformed_world_x = entity->world_x - world->camera_world_x;
    entity->transformed_world_y = entity->world_y - world->camera_world_y;
    entity->transformed_world_z = entity->world_z - world->camera_world_z;

    entity_world_x = entity->transformed_world_x;
    entity_world_z = entity->transformed_world_z;

    entity->transformed_world_x = fixed_mul_6_10(entity_world_x,cos[world->camera_yaw]) - fixed_mul_6_10(entity_world_z,sin[world->camera_yaw]);
    entity->transformed_world_z = fixed_mul_6_10(entity_world_z,cos[world->camera_yaw]) + fixed_mul_6_10(entity_world_x,sin[world->camera_yaw]);

    entity_world_y = entity->transformed_world_y;
    entity_world_z = entity->transformed_world_z;

    entity->transformed_world_y = fixed_mul_6_10(entity_world_y,cos[world->camera_pitch]) - fixed_mul_6_10(entity_world_z,sin[world->camera_pitch]);
    entity->transformed_world_z = fixed_mul_6_10(entity_world_z,cos[world->camera_pitch]) + fixed_mul_6_10(entity_world_y,sin[world->camera_pitch]);

    entity->screen_x = ((fixed_div_6_10(entity->transformed_world_x, entity->transformed_world_z)) / 3)+ 160;
    entity->screen_y = ((fixed_div_6_10(entity->transformed_world_y, entity->transformed_world_z)) / 3) + 100;
}

int16_t get_horizon_level(World *world, int16_t *sin, int16_t *cos)
{
    Entity horizon;
    horizon.world_y = 0;
    horizon.world_z = 1000;

    int16_t entity_world_y = horizon.world_y;
    int16_t entity_world_z = horizon.world_z;

    horizon.transformed_world_y = fixed_mul_6_10(entity_world_y,cos[world->camera_pitch]) - fixed_mul_6_10(entity_world_z,sin[world->camera_pitch]);
    horizon.transformed_world_z = fixed_mul_6_10(entity_world_z,cos[world->camera_pitch]) + fixed_mul_6_10(entity_world_y,sin[world->camera_pitch]);

    return ((fixed_div_6_10(horizon.transformed_world_y, horizon.transformed_world_z)) / 3) + 100;
}
