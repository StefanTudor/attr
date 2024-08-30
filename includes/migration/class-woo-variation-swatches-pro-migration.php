<?php

defined( 'ABSPATH' ) || exit;

class Woo_Variation_Swatches_Pro_Migration {

	protected static $instance = null;

	private $migration_functions = array(
		'2.0.0' => array(
			'woo_variation_swatches_pro_migrate_200_product_attributes',
			'woo_variation_swatches_pro_migrate_200_products_swatches_settings',
			'woo_variation_swatches_pro_migrate_200_global_settings',
		),
		'2.0.2' => array(
			'woo_variation_swatches_pro_migrate_202_group_slugs',
		),
		'2.1.0' => array(
			'woo_variation_swatches_pro_migrate_210_products_swatches_settings',
		)
	);

	private $version_hook   = 'woo_variation_swatches_pro_update_version';
	private $migration_hook = 'woo_variation_swatches_pro_run_migrate';
	private $queue_group    = 'woo-variation-swatches-pro-migration';

	protected function __construct() {
		$this->includes();
		$this->hooks();
	}

	public function includes() {
		include_once dirname( __FILE__ ) . '/migration-functions.php';
	}

	protected function hooks() {
		add_filter( 'woocommerce_debug_tools', array( $this, 'add_to_debug_tool' ) );
		add_action( 'admin_init', array( $this, 'init' ), 11 );
		add_action( 'admin_init', array( $this, 'migrate_notice' ), 12 );
		add_action( $this->migration_hook, array( $this, 'run_migration' ) );
		add_action( $this->version_hook, array( $this, 'update_version' ) );
	}

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init() {


		// delete_option( 'woo_variation_swatches_pro_version' );
		// return false;

		if ( ! $this->needs_migration() ) {
			return false;
		}

		// Current Installed Version
		// $current_version = get_option( 'woo_variation_swatches_pro_version', '1.1.18' );
		$current_version = get_option( 'woo_variation_swatches_pro_version', false );
		$latest_version  = woo_variation_swatches()->pro_version();

		$loop = 5;

		// 1. Updating current version
		if ( version_compare( $current_version, $latest_version, '<' ) ) {

			$args = array( 'version' => $latest_version );

			$is_pending = WC()->queue()->get_next( $this->version_hook, $args, $this->queue_group );
			if ( null === $is_pending ) {
				// WC()->queue()->cancel_all( $this->version_hook, $args, $this->queue_group );
				WC()->queue()->schedule_single( time(), $this->version_hook, $args, $this->queue_group );
			}
		}

		// 2. Run Migrator
		foreach ( $this->get_migration_callbacks() as $version => $migration_callbacks ) {
			if ( version_compare( $current_version, $version, '<' ) ) {
				foreach ( $migration_callbacks as $migration_callback ) {

					$callback = array( 'callback' => $migration_callback );

					$is_pending = WC()->queue()->get_next( $this->migration_hook, $callback, $this->queue_group );

					if ( null === $is_pending ) {
						// WC()->queue()->cancel_all( $this->migration_hook, $callback, $this->queue_group );
						WC()->queue()->schedule_single( time() + $loop, $this->migration_hook, $callback, $this->queue_group );
					}

					$loop +=5;
				}
			}
		}
	}

	public function needs_migration() {
		// $current_version = get_option( 'woo_variation_swatches_pro_version', '1.1.18' );
		// Current Installed Version.
		$current_version = get_option( 'woo_variation_swatches_pro_version', false );

		// Fresh Installed So we don't need to run migration job.
		if ( false === $current_version ) {
		   return false;
		}

		$updates         = $this->get_migration_callbacks();
		$update_versions = array_keys( $updates );
		usort( $update_versions, 'version_compare' );
		$last_version = end( $update_versions );

		return version_compare( $current_version, $last_version, '<' );
	}

	public function get_migration_callbacks() {
		return $this->migration_functions;
	}

	public function add_to_debug_tool( $tools = array() ) {
		$tools['woo_variation_swatches_run_migrator'] = array(
			'name'     => esc_html__( '"Variation Swatches for WooCommerce" Migrator', 'woo-variation-swatches-pro' ),
			'button'   => esc_html__( 'Run Migration', 'woo-variation-swatches-pro' ),
			'desc'     => esc_html__( 'This will migrate from old version to new version of "Variation Swatches for WooCommerce".', 'woo-variation-swatches-pro' ),
			'callback' => array( $this, 'rerun_migration' )
		);

		return $tools;
	}

	public function rerun_migration() {
		// delete_option( 'woo_variation_swatches_pro_version' );
		$this->update_version('1.1.18');
		$latest_version = woo_variation_swatches()->pro_version();
		WC()->queue()->cancel_all( $this->version_hook, array( 'version' => $latest_version ), $this->queue_group );

		foreach ( $this->get_migration_callbacks() as $version => $migration_callbacks ) {
			foreach ( $migration_callbacks as $migration_callback ) {
				$callback = array( 'callback' => $migration_callback );
				WC()->queue()->cancel_all( $this->migration_hook, $callback, $this->queue_group );
			}
		}

		return esc_html__( 'Variation Swatches for WooCommerce migration has been re scheduled to run in the background.', 'woo-variation-swatches-pro' );
	}

	public function update_version( $version ) {
		update_option( 'woo_variation_swatches_pro_version', $version );
	}

	public function run_migration( $callback ) {

		if ( is_callable( $callback ) ) {
			$this->run_update_callback_start( $callback );
			$result = (bool) call_user_func( $callback );
			// Function should return true.
			$this->run_update_callback_end( $callback, $result );
		}
	}

	public function run_update_callback_start( $callback ) {
		wc_maybe_define_constant( 'WOO_VARIATION_SWATCHES_PRO_MIGRATING', true );
	}

	public function run_update_callback_end( $callback, $result ) {
		if ( ! $result ) {
			WC()->queue()->add( $this->migration_hook, array( 'callback' => $callback, ), $this->queue_group );
		}
	}

	public function migrate_notice() {
		if ( $this->is_running() ) {
			WC_Admin_Notices::add_custom_notice( 'woo_variation_swatches_pro_update', $this->notice() );
		} else {
			WC_Admin_Notices::remove_notice( 'woo_variation_swatches_pro_update' );
		}
	}

	public function is_running() {
		$updates_pending = WC()->queue()->search( array(
			'status'   => 'pending',
			'group'    => $this->queue_group,
			'per_page' => 1,
			'orderby' => 'date',
			'order' => 'DESC'
		), 'ids' );

		return 0 < count( $updates_pending );
	}

	public function notice() {
		ob_start();

		$args = array(
			'page'=>'wc-status',
			'tab'=>'action-scheduler',
			'status'=>'pending',
			's'=>$this->migration_hook,
		);

		$pending_actions_url = add_query_arg( $args, admin_url('admin.php'));

		include_once dirname( __FILE__ ) . '/html-notice-updating.php';

		return ob_get_clean();
	}
}
