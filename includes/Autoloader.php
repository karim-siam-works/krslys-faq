<?php
/**
 * PSR-4 Autoloader for Krslys\NextLevelFaq namespace.
 *
 * @package Krslys\NextLevelFaq
 */

namespace Krslys\NextLevelFaq;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PSR-4 Autoloader class.
 */
class Autoloader {

	/**
	 * Namespace prefix.
	 *
	 * @var string
	 */
	private $namespace_prefix = 'Krslys\\NextLevelFaq\\';

	/**
	 * Base directory for the namespace prefix.
	 *
	 * @var string
	 */
	private $base_dir;

	/**
	 * Constructor.
	 *
	 * @param string $base_dir Base directory for the namespace.
	 */
	public function __construct( $base_dir ) {
		$this->base_dir = rtrim( $base_dir, '/\\' ) . '/';
	}

	/**
	 * Register the autoloader.
	 */
	public function register() {
		spl_autoload_register( array( $this, 'load_class' ) );
	}

	/**
	 * Load a class file based on PSR-4 standard.
	 *
	 * @param string $class The fully-qualified class name.
	 *
	 * @return void
	 */
	public function load_class( $class ) {
		// Check if the class uses the namespace prefix.
		$prefix_length = strlen( $this->namespace_prefix );
		if ( strncmp( $this->namespace_prefix, $class, $prefix_length ) !== 0 ) {
			// No, move to the next registered autoloader.
			return;
		}

		// Get the relative class name.
		$relative_class = substr( $class, $prefix_length );

		// Convert class name to file name (WordPress style).
		$file_name = $this->get_file_name_from_class( $relative_class );

		// Build the file path.
		$file = $this->base_dir . $file_name;

		// If the file exists, require it.
		if ( file_exists( $file ) ) {
			require $file;
		}
	}

	/**
	 * Convert class name to file name following WordPress conventions.
	 *
	 * Converts CamelCase to kebab-case with 'class-nlf-faq-' prefix.
	 * Examples:
	 *   Options -> class-nlf-faq-options.php
	 *   Style_Generator -> class-nlf-faq-style-generator.php
	 *   Group_CPT -> class-nlf-faq-group-cpt.php
	 *
	 * @param string $class_name The class name.
	 *
	 * @return string The file name.
	 */
	private function get_file_name_from_class( $class_name ) {
		// Convert underscores to hyphens and lowercase.
		$file_name = str_replace( '_', '-', $class_name );
		$file_name = strtolower( $file_name );

		// Add WordPress-style prefix and extension.
		return 'class-nlf-faq-' . $file_name . '.php';
	}
}

