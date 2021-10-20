#include "../world.h"
#include "../world_data.h"

World world = {
    .log_count = <?php echo($world->getLogCount()); ?>,
    .entity_count = <?php echo($world->getEntityCount()); ?>,
    .entities = (Entity[]) {
        <?php $entities = $world->getEntities(); foreach ($world->getEntities() as $entityKey => $entity) { ?>
        {
            .world_x = <?php echo($entity->getXAsInt()); ?>,
            .world_y = 0,
            .world_z = <?php echo($entity->getYAsInt()); ?>,
            .type = <?php echo($entity->getType()); ?>,
            .appearance = <?php echo($entity->getAppearance()); ?>,
            .yaw = <?php echo($entity->getYaw()); ?>
        }<?php if ($entityKey !== array_key_last($entities)) { ?>,<?php } ?>
        <?php } ?>
    }
};
