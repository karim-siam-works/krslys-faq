<?php
/**
 * Plugin Name: All-in-One FAQ
 * Description: Flexible FAQ plugin with customizable styling and live preview.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: all-in-one-faq
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'AIO_FAQ_VERSION', '1.0.0' );
define( 'AIO_FAQ_PLUGIN_FILE', __FILE__ );
define( 'AIO_FAQ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AIO_FAQ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AIO_FAQ_DB_VERSION', '1.2.0' );

/**
 * Main plugin class.
 */
final class AIO_Faq_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var AIO_Faq_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return AIO_Faq_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->includes();
		$this->hooks();
	}

	/**
	 * Load required files.
	 */
	private function includes() {
		require_once AIO_FAQ_PLUGIN_DIR . 'includes/class-aio-faq-options.php';
		require_once AIO_FAQ_PLUGIN_DIR . 'includes/class-aio-faq-style-generator.php';
		require_once AIO_FAQ_PLUGIN_DIR . 'includes/class-aio-faq-repository.php';
		require_once AIO_FAQ_PLUGIN_DIR . 'includes/class-aio-faq-group-cpt.php';
		require_once AIO_FAQ_PLUGIN_DIR . 'includes/class-aio-faq-admin.php';
		require_once AIO_FAQ_PLUGIN_DIR . 'includes/class-aio-faq-frontend.php';
	}

	/**
	 * Register hooks.
	 */
	private function hooks() {
		register_activation_hook( AIO_FAQ_PLUGIN_FILE, array( 'AIO_Faq_Options', 'activate' ) );
		register_activation_hook( AIO_FAQ_PLUGIN_FILE, array( 'AIO_Faq_Repository', 'maybe_create_table' ) );

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( 'AIO_Faq_Group_CPT', 'register' ) );
		add_action( 'init', array( 'AIO_Faq_Frontend', 'register_shortcodes' ) );
		add_action( 'wp_enqueue_scripts', array( 'AIO_Faq_Frontend', 'enqueue_styles' ) );
		add_action( 'admin_menu', array( 'AIO_Faq_Admin', 'register_menu' ) );
		add_action( 'admin_init', array( 'AIO_Faq_Admin', 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( 'AIO_Faq_Admin', 'enqueue_assets' ) );
		add_action( 'admin_post_aio_faq_save_questions', array( 'AIO_Faq_Admin', 'handle_save_questions' ) );
		add_action( 'update_option_' . AIO_Faq_Options::OPTION_KEY, array( 'AIO_Faq_Style_Generator', 'generate_and_save' ), 10, 2 );

		// Gutenberg block registration using block.json and dynamic render.
		if ( function_exists( 'register_block_type' ) ) {
			add_action(
				'init',
				function () {
					$block_dir  = AIO_FAQ_PLUGIN_DIR . 'blocks/faq';
					$block_json = $block_dir . '/block.json';

					if ( ! file_exists( $block_json ) ) {
						return;
					}

					// Register editor script defined in block.json (editorScript handle).
					wp_register_script(
						'aio-faq-block-editor',
						AIO_FAQ_PLUGIN_URL . 'blocks/faq/editor.js',
						array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor', 'wp-data' ),
						AIO_FAQ_VERSION,
						true
					);

					// Ensure front-end/editor style handle exists for block.json "style".
					$css_path = AIO_Faq_Style_Generator::get_css_file_path();
					$css_url  = AIO_Faq_Style_Generator::get_css_file_url();

					if ( $css_url ) {
						$version = file_exists( $css_path ) ? filemtime( $css_path ) : AIO_FAQ_VERSION;

						wp_register_style(
							'aio-faq-generated',
							$css_url,
							array(),
							$version
						);
					}

					register_block_type(
						$block_dir,
						array(
							'render_callback' => function( $attributes ) {
								$group_id = isset( $attributes['groupId'] ) ? (int) $attributes['groupId'] : 0;
								$title    = isset( $attributes['title'] ) ? (string) $attributes['title'] : '';

								$shortcode = '[aio_faq';

								if ( $group_id > 0 ) {
									$shortcode .= ' group="' . $group_id . '"';
								}

								if ( '' !== $title ) {
									$shortcode .= ' title="' . esc_attr( $title ) . '"';
								}

								$shortcode .= ']';

								return do_shortcode( $shortcode );
							},
						)
					);
				}
			);
		}
	}

	/**
	 * Load plugin textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'all-in-one-faq',
			false,
			dirname( plugin_basename( AIO_FAQ_PLUGIN_FILE ) ) . '/languages'
		);
	}
}

/**
 * Initialize plugin.
 */
function aio_faq_init() {
	return AIO_Faq_Plugin::instance();
}

aio_faq_init();


