<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function osec_create_block_calendar_block_block_init() {
	register_block_type( __DIR__ . '/build' );
}
add_action( 'init', 'osec_create_block_calendar_block_block_init' );
