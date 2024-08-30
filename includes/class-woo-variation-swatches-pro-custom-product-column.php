<?php

defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( 'Woo_Variation_Swatches_Pro_Custom_Product_Column' ) ) :
	class Woo_Variation_Swatches_Pro_Custom_Product_Column {

		public function __construct() {

			add_filter( 'manage_product_posts_columns', array( $this, 'header' ) );

			add_action( 'manage_product_posts_custom_column', array( $this, 'column' ), 10, 2 );

			do_action( 'woo_variation_swatches_custom_product_column_loaded', $this );
		}

		public function header( $columns ) {
			$columns['woo_variation_swatches_product_label_settings'] = sprintf( '<span class="wvs-info tips" data-tip="%1$s">%1$s</span>', esc_html__( 'Customized Swatches Settings', 'woo-variation-swatches-pro' ) );

			return $columns;
		}

		public function column( $column_key, $product_id ) {
			if ( 'woo_variation_swatches_product_label_settings' === $column_key ) {

				$product_options   = (array) woo_variation_swatches_pro()->get_product_options( $product_id );
				$has_saved_options = ( 0 < count( array_keys( $product_options ) ) );

				if ( $has_saved_options ) {
					printf( '<span class="tips dashicons dashicons-admin-tools" data-tip="%s"></span>', esc_attr__( 'Customized Variation Swatches Settings', 'woo-variation-swatches-pro' ) );
				}
			}
		}
	}
endif;
