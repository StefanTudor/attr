<?php
	defined( 'ABSPATH' ) || exit;

	/**
	 * Swatches block template.
	 *
	 * @global $attributes
	 * @global $content
	 * @global $block
	 */

	$_post_type = $block->context[ 'postType' ];
	$_post_id   = $block->context[ 'postId' ];

	$align = sprintf( 'swatches-align-%s', $attributes['textAlign'] ?? 'center' );

	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $align ) );

	echo sprintf('<div %s>', wp_kses_post( $wrapper_attributes));
	woo_variation_swatches_pro()->show_archive_page_swatches_by_id( $_post_id );
	echo '</div>';
