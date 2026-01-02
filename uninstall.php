<?php
/**
 * Uninstall script for Next Level FAQ plugin.
 *
 * Runs when the plugin is deleted via WordPress admin.
 * Cleans up all plugin data from the database.
 *
 * @package Krslys\NextLevelFaq
 */

// Exit if not called by WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load the autoloader and classes
require_once plugin_dir_path( __FILE__ ) . 'includes/Autoloader.php';

$autoloader = new \Krslys\NextLevelFaq\Autoloader( plugin_dir_path( __FILE__ ) . 'includes' );
$autoloader->register();

// Import the Database class
use Krslys\NextLevelFaq\Database;
use Krslys\NextLevelFaq\Settings_Repository;

/**
 * Drop all custom tables.
 */
Database::drop_tables();

/**
 * Delete plugin options.
 */
delete_option( 'nlf_faq_schema_version' );
delete_option( 'nlf_faq_db_version' );
delete_option( 'nlf_faq_style_options' );
delete_option( 'nlf_faq_presets_css_version' );

/**
 * Clean up any remaining CPT posts (shouldn't exist, but just in case).
 */
global $wpdb;

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->posts} WHERE post_type = %s",
		'nlf_faq_group'
	)
);

// Delete orphaned postmeta
$wpdb->query(
	"DELETE pm FROM {$wpdb->postmeta} pm 
	LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
	WHERE p.ID IS NULL"
);

/**
 * Delete generated CSS files from uploads directory.
 */
$uploads = wp_upload_dir();
$css_dir = trailingslashit( $uploads['basedir'] ) . 'nlf-faq-styles';

if ( is_dir( $css_dir ) ) {
	// Remove all files in directory
	$files = glob( $css_dir . '/*' );
	if ( is_array( $files ) ) {
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				@unlink( $file );
			}
		}
	}
	// Remove directory
	@rmdir( $css_dir );
}

/**
 * Clear any transients.
 */
$wpdb->query(
	"DELETE FROM {$wpdb->options} 
	WHERE option_name LIKE '_transient_nlf_%' 
	OR option_name LIKE '_transient_timeout_nlf_%'"
);

// Log uninstall for debugging (if WP_DEBUG is enabled)
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	error_log( 'Next Level FAQ: Plugin uninstalled and all data removed.' );
}

