#include "../ground_sprites.h"

ground_sprite_type ground_sprite_types[] = {
    <?php foreach ($types as $typeKey => $type) { ?>
    {
        // <?php echo($type['label']); ?>

        <?php echo($type['number_of_sizes']); ?>,
        <?php echo($type['masked']); ?>,
        (ground_sprite[]) {
            <?php foreach ($type['sprites'] as $sizeKey => $size) { ?>
            {
                <?php echo($size['scale_factor']); ?>,
                <?php echo($size['origin_x']); ?>,
                <?php echo($size['origin_y']); ?>,
                <?php echo($size['source_data_width']); ?>,
                <?php echo($size['source_data_height']); ?>,
                <?php echo($size['empty_pixels_on_right']); ?>,
                ( int16_t[] ){ <?php echo implode(', ', $size['words']); ?> }
            }<?php if ($sizeKey !== array_key_last($type['sprites'])) { ?>,<?php } ?>
            <?php } ?>
        }
    }<?php if ($typeKey !== array_key_last($types)) { ?>,<?php } ?>
    <?php } ?>
};

