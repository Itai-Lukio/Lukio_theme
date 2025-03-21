<?php

/**
 * Lukio number range input min and max template part.
 * 
 * @param string $args['target_class'] class to add the all items
 * @param string $args['inner_color'] color to use in side the range
 * @param string $args['outer_color'] color to use put of the range
 * @param string $args['name'] name for the inputs to be grouped under
 * @param int $args['min'] min value for the inputs
 * @param int $args['max'] max value fot the inputs
 * @param string $args['thumb_color'] color of the range thumb
 * @param string $args['format'] [optional] format for the display string, default '%d'
 * @param bool $args['use_input'] [optional] true to use input as the display, default `false` to use span
 * 
 * @author Itai Dotan
 */

defined('ABSPATH') || exit;

lukio_enqueue('/assets/css/partials/lukio_range_min_max.css', 'lukio_range_min_max_stylesheet', array(), array('parent' => true));
lukio_enqueue('/assets/js/partials/lukio_range_min_max.js', 'lukio_range_min_max_script', array('jquery'), array('parent' => true));

global $lukio_range_min_max_counter;
!isset($lukio_range_min_max_counter) ? $lukio_range_min_max_counter = 0 : $lukio_range_min_max_counter++;

$target_class = ' ' . trim($args['target_class']);
$input_name = substr($args['name'], -2) == '[]' ? $args['name'] : $args['name'] . '[]';

?>

<div class="lukio_range_min_max<?php echo $target_class; ?>" data-count="<?php echo $lukio_range_min_max_counter; ?>" inner_color="<?php echo $args['inner_color']; ?>" outer_color="<?php echo $args['outer_color']; ?>" format="<?php echo $args['format']; ?>">
    <div class="lukio_range_min_max_track_wrapper<?php echo $target_class; ?>">
        <div class="lukio_range_min_max_track<?php echo $target_class; ?>"></div>
        <input class="lukio_range_min_max_input min<?php echo $target_class; ?>" title="min" type="range" name="<?php echo $input_name; ?>" min="<?php echo $args['min'] ?>" max="<?php echo $args['max'] ?>" value="<?php echo $args['min'] ?>" autocomplete="off">
        <input class="lukio_range_min_max_input max<?php echo $target_class; ?>" title="max" type="range" name="<?php echo $input_name; ?>" min="<?php echo $args['min'] ?>" max="<?php echo $args['max'] ?>" value="<?php echo $args['max'] ?>" autocomplete="off">
    </div>
    <div class="lukio_range_min_max_display_wrapper<?php echo $target_class; ?>">
        <?php
        if ($args['use_input']) {
        ?>
            <input type="text" class="lukio_range_min_max_display min<?php echo $target_class; ?>" value="<?php echo sprintf($args['format'], $args['min']); ?>" min="<?php echo $args['min'] ?>" max="<?php echo $args['max'] ?>" autocomplete="off">
            <input type="text" class="lukio_range_min_max_display max<?php echo $target_class; ?>" value="<?php echo sprintf($args['format'], $args['max']); ?>" min="<?php echo $args['min'] ?>" max="<?php echo $args['max'] ?>" autocomplete="off">
        <?php
        } else {
        ?>
            <span class="lukio_range_min_max_display min<?php echo $target_class; ?>"><?php echo sprintf($args['format'], $args['min']); ?></span>
            <span class="lukio_range_min_max_display max<?php echo $target_class; ?>"><?php echo sprintf($args['format'], $args['max']); ?></span>
        <?php
        }
        ?>
    </div>
</div>

<style>
    .lukio_range_min_max[data-count="<?php echo $lukio_range_min_max_counter ?>"] {
        --thumb_color: <?php echo $args['thumb_color']; ?>;
    }
</style>