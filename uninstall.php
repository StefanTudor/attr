<?php
/**
 * Uninstall plugin
 */

// If uninstall not called from WordPress exit.
defined( 'WP_UNINSTALL_PLUGIN' ) || die( 'Keep Silent' );

global $wpdb;

// Remove Option.
delete_option( 'woo_variation_swatches_pro_version' );
// Site options in Multisite.
delete_site_option( 'woo_variation_swatches_pro_version' );

// Delete Product Settings.
$product_ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s", '_woo_variation_swatches_product_settings' ) );

foreach ( $product_ids as $product_id ) {
	delete_post_meta($product_id, '_woo_variation_swatches_product_settings');
}

// Delete Term Meta.
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->termmeta} WHERE meta_key IN (%s, %s, %s, %s, %s, %s, %s)", 'is_dual_color', 'secondary_color', 'image_size', 'show_tooltip', 'tooltip_text', 'tooltip_image_id', 'group_name' ));

// Clear any cached data that has been removed.
wp_cache_flush();
