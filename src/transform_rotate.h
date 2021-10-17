#ifndef __TRANSFORM_ROTATE_H
#define __TRANSFORM_ROTATE_H

typedef struct {
    uint16_t world_x;
    uint16_t world_y;
    uint16_t world_z;
    uint16_t type;
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
    uint16_t entity_count;
    Entity *entities;
} World;

void transform_and_rotate_all_entities(World *world, int16_t *sin, int16_t *cos);

#endif
 
