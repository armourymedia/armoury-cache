<?php
/**
 * Plugin Name: Armoury Cache
 * Plugin URI: https://www.armourymedia.com/
 * Description: Integrates SpinupWP cache purging with Cloudflare cache management for optimized performance.
 * Version: 1.0.0
 * Author: Armoury Media
 * Author URI: https://www.armourymedia.com/
 * License: GPL v2 or later
 * Text Domain: armoury-cache
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 */
class Armoury_Cache {

	/**
	 * Instance of this class
	 *
	 * @var Armoury_Cache|null
	 */
	private static $instance = null;

	/**
	 * Cloudflare API endpoint
	 *
	 * @var string
	 */
	private $cf_api_endpoint = 'https://api.cloudflare.com/client/v4/zones/';

	/**
	 * Get singleton instance
	 *
	 * @return Armoury_Cache
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize plugin
	 */
	private function init() {
		// Check dependencies
		if ( ! $this->check_dependencies() ) {
			add_action( 'admin_notices', array( $this, 'show_dependency_notice' ) );
			return;
		}

		// Check if required constants are defined
		if ( ! $this->check_requirements() ) {
			add_action( 'admin_notices', array( $this, 'show_requirements_notice' ) );
			return;
		}

		// Hook into SpinupWP site-wide cache purge action
		add_action( 'spinupwp_site_purged', array( $this, 'handle_site_purged' ), 10, 1 );
	}

	/**
	 * Check if SpinupWP plugin is active
	 *
	 * @return bool
	 */
	private function check_dependencies() {
		return class_exists( 'SpinupWp\Plugin' ) || defined( 'SPINUPWP_PLUGIN_VERSION' );
	}

	/**
	 * Check if required constants are defined
	 *
	 * @return bool
	 */
	private function check_requirements() {
		return defined( 'ARMOURY_CF_ZONE_ID' ) && 
		       defined( 'ARMOURY_CF_API_TOKEN' );
	}

	/**
	 * Show admin notice if dependencies are not met
	 */
	public function show_dependency_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Armoury Cache: This plugin requires the SpinupWP plugin to be installed and activated.', 'armoury-cache' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Show admin notice if requirements are not met
	 */
	public function show_requirements_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Armoury Cache: Please define ARMOURY_CF_ZONE_ID and ARMOURY_CF_API_TOKEN in wp-config.php', 'armoury-cache' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Handle when SpinupWP purges entire site cache
	 *
	 * @param bool $result The result of the SpinupWP cache purge
	 */
	public function handle_site_purged( $result ) {
		// Only proceed if SpinupWP purge was successful
		if ( ! $result ) {
			return;
		}

		// Purge entire Cloudflare cache
		$cf_result = $this->purge_cloudflare_cache_all();
		
		if ( $cf_result ) {
			$this->log_info( 'Successfully purged all Cloudflare cache after SpinupWP site purge' );
		} else {
			$this->log_error( 'Failed to purge Cloudflare cache after SpinupWP site purge' );
		}
	}

	/**
	 * Purge all Cloudflare cache
	 *
	 * @return bool
	 */
	private function purge_cloudflare_cache_all() {
		$api_url = $this->cf_api_endpoint . ARMOURY_CF_ZONE_ID . '/purge_cache';
		
		$response = wp_remote_post( $api_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . ARMOURY_CF_API_TOKEN,
				'Content-Type'  => 'application/json',
			),
			'body' => json_encode( array( 'purge_everything' => true ) ),
			'timeout' => 30,
		) );
		
		if ( is_wp_error( $response ) ) {
			$this->log_error( 'Cloudflare API error: ' . $response->get_error_message() );
			return false;
		}
		
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		if ( empty( $data['success'] ) ) {
			$error_message = isset( $data['errors'][0]['message'] ) ? $data['errors'][0]['message'] : 'Unknown error';
			$error_code = isset( $data['errors'][0]['code'] ) ? $data['errors'][0]['code'] : 'Unknown code';
			$this->log_error( sprintf( 'Cloudflare purge failed: %s (Code: %s)', $error_message, $error_code ) );
			
			// Log full response for debugging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$this->log_error( 'Full Cloudflare response: ' . wp_json_encode( $data ) );
			}
			
			return false;
		}
		
		return true;
	}

	/**
	 * Log error message
	 *
	 * @param string $message Error message
	 */
	private function log_error( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Armoury Cache Error: ' . $message );
		}
	}

	/**
	 * Log info message
	 *
	 * @param string $message Info message
	 */
	private function log_info( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Armoury Cache Info: ' . $message );
		}
	}
}

// Initialize plugin
add_action( 'plugins_loaded', array( 'Armoury_Cache', 'get_instance' ) );

// Activation hook
register_activation_hook( __FILE__, 'armoury_cache_activate' );

/**
 * Plugin activation
 */
function armoury_cache_activate() {
	// Check PHP version
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		deactivate_plugins( basename( __FILE__ ) );
		wp_die( 'Armoury Cache requires PHP 7.4 or higher.' );
	}
	
	// Check WordPress version
	if ( version_compare( get_bloginfo( 'version' ), '5.8', '<' ) ) {
		deactivate_plugins( basename( __FILE__ ) );
		wp_die( 'Armoury Cache requires WordPress 5.8 or higher.' );
	}
	
	// Check if SpinupWP plugin is active
	if ( ! class_exists( 'SpinupWp\Plugin' ) && ! defined( 'SPINUPWP_PLUGIN_VERSION' ) ) {
		deactivate_plugins( basename( __FILE__ ) );
		wp_die( 'Armoury Cache requires the SpinupWP plugin to be installed and activated. Please install SpinupWP from wordpress.org/plugins/spinupwp/' );
	}
}

// Deactivation hook
register_deactivation_hook( __FILE__, 'armoury_cache_deactivate' );

/**
 * Plugin deactivation
 */
function armoury_cache_deactivate() {
	// Clean up any transients if needed
	// Currently nothing to clean up
}
