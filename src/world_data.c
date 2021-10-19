
#include "world.h"

World world = {
    .log_count = 4,
    .entity_count = 8,
    .entities = (Entity[]) {
        {
            .world_x = 1,
            .world_y = 2,
            .world_z = 3,
            .type = 4,
            .appearance = 5
        }
    }
};
