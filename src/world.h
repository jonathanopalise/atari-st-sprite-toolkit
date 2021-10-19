#ifndef __WORLD_H
#define __WORLD_H

#include <inttypes.h>

#define ENTITY_TYPE_LOG 0
#define ENTITY_TYPE_SCENERY 1

typedef struct {
    int16_t world_x;
    int16_t world_y;
    int16_t world_z;
    uint16_t type; // log or scenery
    uint16_t appearance; // log appearance or scenery id
    int16_t transformed_world_x;
    int16_t transformed_world_y;
    int16_t transformed_world_z;
    int16_t screen_x;
    int16_t screen_y;
} Entity;

typedef struct {
    uint16_t camera_world_x;
    uint16_t camera_world_y;
    uint16_t camera_world_z;
    int16_t camera_yaw;
    uint16_t log_count;
    uint16_t entity_count;
    Entity *entities;
} World;

void transform_and_rotate_world(World *world, int16_t *sin, int16_t *cos);

void project_entity(Entity *entity);

#endif
 
