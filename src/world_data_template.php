<?php

#include "world.h"

World world = {
    .log_count = <?php echo($world->getLogCount()); ?>,
    .entity_count = <?php echo($world->getEntityCount()); ?>,
    .entities = (Entity[]) {
        <?php foreach ($world->getEntities() as $entity) { ?>
        {
            .world_x = <?php echo($entity->getXAsInt()); ?>,
            .world_y = 0,
            .world_z = <?php echo($entity->getYAsInt()); ?>,
            .type = <?php echo($entity->getType()); ?>,
            .appearance = <?php echo($entity->getAppearance());
        }
        <?php } ?>
    }
};
