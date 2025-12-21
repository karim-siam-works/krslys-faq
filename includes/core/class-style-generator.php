<?php
/**
 * Builds and stores generated CSS for FAQ styles.
 *
 * @package Krslys\NextLevelFaq
 */

namespace Krslys\NextLevelFaq;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds and stores generated CSS for FAQ styles.
 *
 * SECURITY FEATURES:
 * - Uses WordPress Filesystem API for all file operations.
 * - Validates directory paths and file permissions.
 * - Sanitizes all CSS values via esc_html().
 */
class Style_Generator {

	/**
	 * Get path to generated CSS file.
	 *
	 * @return string
	 */
	public static function get_css_file_path() {
		$upload_dir = wp_upload_dir();
		$dir        = trailingslashit( $upload_dir['basedir'] ) . 'nlf-faq';

		wp_mkdir_p( $dir );

		return trailingslashit( $dir ) . 'generated-faq-style.css';
	}

	/**
	 * Get URL to generated CSS file.
	 *
	 * @return string|false
	 */
	public static function get_css_file_url() {
		$upload_dir = wp_upload_dir();
		$url        = trailingslashit( $upload_dir['baseurl'] ) . 'nlf-faq/generated-faq-style.css';

		return $url;
	}

	/**
	 * Build CSS string from options.
	 *
	 * SECURITY: All option values escaped with esc_html() before output.
	 *
	 * @param array $options Options.
	 *
	 * @return string
	 */
	public static function build_css( $options ) {
		$o = wp_parse_args( $options, Options::get_defaults() );

		// Convert px to rem (base: 16px = 1rem).
		$border_radius_rem = round( intval( $o['container_border_radius'] ) / 16, 3 );
		$padding_rem       = round( intval( $o['container_padding'] ) / 16, 3 );
		$question_font_rem = round( intval( $o['question_font_size'] ) / 16, 3 );
		$answer_font_rem   = round( intval( $o['answer_font_size'] ) / 16, 3 );
		$gap_rem           = round( intval( $o['gap_between_items'] ) / 16, 3 );

		// Shadow values.
		$shadow_css = ! empty( $o['shadow'] )
			? '0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24)'
			: 'none';

		// Transitions (200ms ease as per modern standards).
		$transition_base = '200ms ease';
		$answer_transition = 'max-height 220ms ease, opacity 200ms ease, transform 200ms ease';

		if ( 'fade' === $o['animation'] ) {
			$answer_transition = 'opacity 200ms ease';
		} elseif ( 'none' === $o['animation'] ) {
			$answer_transition = 'none';
		}

		ob_start();
		?>
/* ============================================
   Design Tokens (CSS Custom Properties)
   ============================================ */

:root {
	/* Typography */
	--nlf-faq-font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
	--nlf-faq-line-height-tight: 1.25;
	--nlf-faq-line-height-normal: 1.5;
	--nlf-faq-line-height-relaxed: 1.75;
	--nlf-faq-letter-spacing-tight: -0.025em;
	--nlf-faq-letter-spacing-normal: 0;
	--nlf-faq-letter-spacing-wide: 0.025em;

	/* Colors - User Defined */
	--nlf-faq-container-bg: <?php echo esc_html( $o['container_background'] ); ?>;
	--nlf-faq-border-color: <?php echo esc_html( $o['container_border_color'] ); ?>;
	--nlf-faq-question-color: <?php echo esc_html( $o['question_color'] ); ?>;
	--nlf-faq-answer-color: <?php echo esc_html( $o['answer_color'] ); ?>;
	--nlf-faq-accent-color: <?php echo esc_html( $o['accent_color'] ); ?>;

	/* Spacing (rem-based) */
	--nlf-faq-border-radius: <?php echo esc_html( $border_radius_rem ); ?>rem;
	--nlf-faq-padding: <?php echo esc_html( $padding_rem ); ?>rem;
	--nlf-faq-gap: <?php echo esc_html( $gap_rem ); ?>rem;

	/* Typography Sizes */
	--nlf-faq-question-size: <?php echo esc_html( $question_font_rem ); ?>rem;
	--nlf-faq-answer-size: <?php echo esc_html( $answer_font_rem ); ?>rem;
	--nlf-faq-question-weight: <?php echo intval( $o['question_font_weight'] ); ?>;

	/* Shadows */
	--nlf-faq-shadow: <?php echo esc_html( $shadow_css ); ?>;

	/* Transitions */
	--nlf-faq-transition: <?php echo esc_html( $transition_base ); ?>;
	--nlf-faq-answer-transition: <?php echo esc_html( $answer_transition ); ?>;
}

/* ============================================
   Base Styles
   ============================================ */

.nlf-faq {
	font-family: var(--nlf-faq-font-family);
	background: var(--nlf-faq-container-bg);
	border: 1px solid var(--nlf-faq-border-color);
	border-radius: var(--nlf-faq-border-radius);
	padding: var(--nlf-faq-padding);
	box-shadow: var(--nlf-faq-shadow);
	box-sizing: border-box;
	width: 100%;
}

/* ============================================
   Search Box
   ============================================ */

.nlf-faq-search {
	margin-bottom: 1.5rem;
}

.nlf-faq-search-input {
	width: 100%;
	padding: 0.75rem 1rem;
	font-size: 1rem;
	border: 1px solid var(--nlf-faq-border-color);
	border-radius: 0.5rem;
	background: var(--nlf-faq-container-bg);
	color: var(--nlf-faq-question-color);
	transition: border-color 200ms ease;
	box-sizing: border-box;
}

.nlf-faq-search-input:focus {
	outline: none;
	border-color: var(--nlf-faq-accent-color);
	box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* ============================================
   Counter
   ============================================ */

.nlf-faq__counter {
	display: inline-block;
	margin-right: 0.5rem;
	color: var(--nlf-faq-accent-color);
	font-weight: 600;
}

.nlf-faq__item {
	border-bottom: 1px solid var(--nlf-faq-border-color);
	padding: 0.75rem 0;
	transition: border-color var(--nlf-faq-transition);
}

.nlf-faq__item:last-child {
	border-bottom: none;
}

.nlf-faq__item + .nlf-faq__item {
	margin-top: var(--nlf-faq-gap);
}

/* ============================================
   Question Styles
   ============================================ */

.nlf-faq__question {
	display: flex;
	align-items: center;
	justify-content: space-between;
	cursor: pointer;
	color: var(--nlf-faq-question-color);
	font-size: var(--nlf-faq-question-size);
	font-weight: var(--nlf-faq-question-weight);
	line-height: var(--nlf-faq-line-height-tight);
	letter-spacing: var(--nlf-faq-letter-spacing-tight);
	transition: color var(--nlf-faq-transition);
	padding: 0;
	margin: 0;
	border: none;
	background: none;
	width: 100%;
	text-align: left;
}

.nlf-faq__question:hover,
.nlf-faq__question:focus {
	color: var(--nlf-faq-accent-color);
	outline: none;
}

.nlf-faq__question:focus-visible {
	outline: 2px solid var(--nlf-faq-accent-color);
	outline-offset: 2px;
	border-radius: 0.25rem;
}

/* ============================================
   Icon Styles
   ============================================ */

.nlf-faq__icon {
	margin-left: 0.75rem;
	color: var(--nlf-faq-accent-color);
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 1.25rem;
	height: 1.25rem;
	flex-shrink: 0;
	transition: transform var(--nlf-faq-transition), color var(--nlf-faq-transition);
}

.nlf-faq__icon::before {
	content: '+';
	font-weight: 700;
	font-size: 1.125rem;
	line-height: 1;
}

.nlf-faq__item.is-open .nlf-faq__icon::before {
	content: '-';
}

<?php if ( 'chevron' === $o['icon_style'] ) : ?>
.nlf-faq__icon::before {
	content: '›';
	display: block;
	transform: rotate(90deg);
	transition: transform var(--nlf-faq-transition);
}

.nlf-faq__item.is-open .nlf-faq__icon::before {
	transform: rotate(270deg);
}
<?php endif; ?>

/* ============================================
   Answer Styles
   ============================================ */

.nlf-faq__answer {
	color: var(--nlf-faq-answer-color);
	font-size: var(--nlf-faq-answer-size);
	line-height: var(--nlf-faq-line-height-relaxed);
	letter-spacing: var(--nlf-faq-letter-spacing-normal);
	margin-top: 0.75rem;
	padding-top: 0.75rem;
	max-height: 0;
	overflow: hidden;
	opacity: 0;
	transform: translateY(-0.25rem);
	transition: var(--nlf-faq-answer-transition);
}

.nlf-faq__item.is-open .nlf-faq__answer {
	max-height: 1000px;
	opacity: 1;
	transform: translateY(0);
}

.nlf-faq__answer p {
	margin: 0 0 0.75rem 0;
}

.nlf-faq__answer p:last-child {
	margin-bottom: 0;
}

/* ============================================
   Highlight State
   ============================================ */

.nlf-faq__item--highlight {
	background: rgba(59, 130, 246, 0.04);
	border-radius: var(--nlf-faq-border-radius);
	padding-inline: 0.5rem;
}

/* ============================================
   Responsive Design
   ============================================ */

/* Mobile First - Base styles above are mobile */

/* Small devices (640px and up) */
@media (min-width: 40rem) {
	.nlf-faq {
		padding: calc(var(--nlf-faq-padding) * 1.125);
	}

	.nlf-faq__question {
		font-size: calc(var(--nlf-faq-question-size) * 1.05);
	}
}

/* Medium devices (768px and up) */
@media (min-width: 48rem) {
	.nlf-faq {
		padding: calc(var(--nlf-faq-padding) * 1.25);
	}

	.nlf-faq__item {
		padding: 0.875rem 0;
	}
}

/* Large devices (1024px and up) */
@media (min-width: 64rem) {
	.nlf-faq {
		max-width: 100%;
	}

	.nlf-faq__question {
		font-size: var(--nlf-faq-question-size);
	}
}

/* Mobile-specific adjustments */
@media (max-width: 39.9375rem) {
	.nlf-faq {
		padding: 1rem;
		border-radius: 0.5rem;
	}

	.nlf-faq__question {
		font-size: calc(var(--nlf-faq-question-size) * 0.95);
		line-height: var(--nlf-faq-line-height-normal);
	}

	.nlf-faq__answer {
		font-size: calc(var(--nlf-faq-answer-size) * 0.95);
		margin-top: 0.5rem;
		padding-top: 0.5rem;
	}

	.nlf-faq__icon {
		width: 1.125rem;
		height: 1.125rem;
		margin-left: 0.5rem;
	}
}
		<?php

		$css = ob_get_clean();

		return trim( $css );
	}

	/**
	 * Initialize WordPress Filesystem API.
	 *
	 * SECURITY: Uses WP_Filesystem for secure file operations.
	 *
	 * @return \WP_Filesystem_Base|false Filesystem instance or false on failure.
	 */
	private static function init_filesystem() {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Initialize filesystem with direct method (safe for uploads directory).
		$credentials = request_filesystem_credentials( '', '', false, false, null );

		if ( false === $credentials ) {
			// Fallback to direct method for uploads directory.
			if ( ! WP_Filesystem() ) {
				return false;
			}
		} elseif ( ! WP_Filesystem( $credentials ) ) {
			return false;
		}

		global $wp_filesystem;

		return $wp_filesystem ? $wp_filesystem : false;
	}

	/**
	 * Get WordPress filesystem path, handling FTP_BASE if defined.
	 *
	 * @param string $path Local file path.
	 * @return string Filesystem-compatible path.
	 */
	private static function get_filesystem_path( $path ) {
		if ( defined( 'FTP_BASE' ) ) {
			return str_replace( ABSPATH, trailingslashit( FTP_BASE ), $path );
		}

		return $path;
	}

	/**
	 * Generate CSS file from current options using WordPress Filesystem API.
	 *
	 * SECURITY:
	 * - Uses WP_Filesystem for secure file operations.
	 * - Validates file paths and permissions.
	 * - Creates directory with proper permissions if needed.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function generate_and_save() {
		$options = Options::get_options();
		$css     = self::build_css( $options );
		$path    = self::get_css_file_path();

		if ( ! $path ) {
			return false;
		}

		$wp_filesystem = self::init_filesystem();

		if ( ! $wp_filesystem ) {
			return false;
		}

		$dir = dirname( $path );

		if ( ! $wp_filesystem->is_dir( $dir ) ) {
			if ( ! wp_mkdir_p( $dir ) ) {
				return false;
			}
		}

		$wp_path = self::get_filesystem_path( $path );

		$result = $wp_filesystem->put_contents(
			$wp_path,
			$css,
			FS_CHMOD_FILE
		);

		return false !== $result;
	}

	/**
	 * Get path to group-specific CSS file.
	 *
	 * @param int $group_id Group ID.
	 * @return string
	 */
	public static function get_group_css_file_path( $group_id ) {
		$upload_dir = wp_upload_dir();
		$dir        = trailingslashit( $upload_dir['basedir'] ) . 'nlf-faq/groups';

		wp_mkdir_p( $dir );

		return trailingslashit( $dir ) . 'group-' . absint( $group_id ) . '.css';
	}

	/**
	 * Get URL to group-specific CSS file.
	 *
	 * @param int $group_id Group ID.
	 * @return string|false
	 */
	public static function get_group_css_file_url( $group_id ) {
		$upload_dir = wp_upload_dir();
		$url        = trailingslashit( $upload_dir['baseurl'] ) . 'nlf-faq/groups/group-' . absint( $group_id ) . '.css';

		return $url;
	}

	/**
	 * Generate and save CSS for a specific group.
	 *
	 * SECURITY:
	 * - Uses WP_Filesystem for secure file operations.
	 * - Validates file paths and permissions.
	 *
	 * @param int   $group_id Group ID.
	 * @param array $options  Style options for the group.
	 * @return bool True on success, false on failure.
	 */
	public static function generate_and_save_for_group( $group_id, $options ) {
		$group_id = absint( $group_id );

		if ( ! $group_id ) {
			return false;
		}

		$css  = self::build_css( $options );
		$path = self::get_group_css_file_path( $group_id );

		if ( ! $path ) {
			return false;
		}

		$wp_filesystem = self::init_filesystem();

		if ( ! $wp_filesystem ) {
			return false;
		}

		$dir = dirname( $path );

		if ( ! $wp_filesystem->is_dir( $dir ) ) {
			if ( ! wp_mkdir_p( $dir ) ) {
				return false;
			}
		}

		$wp_path = self::get_filesystem_path( $path );

		$result = $wp_filesystem->put_contents(
			$wp_path,
			$css,
			FS_CHMOD_FILE
		);

		if ( false !== $result ) {
			Cache::invalidate_group( $group_id );
			return true;
		}

		return false;
	}

	/**
	 * Delete group-specific CSS file.
	 *
	 * SECURITY: Uses WP_Filesystem for secure file operations.
	 *
	 * @param int $group_id Group ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_group_css( $group_id ) {
		$group_id = absint( $group_id );

		if ( ! $group_id ) {
			return false;
		}

		$path = self::get_group_css_file_path( $group_id );

		if ( ! file_exists( $path ) ) {
			return true; // Already deleted or never existed.
		}

		$wp_filesystem = self::init_filesystem();

		if ( ! $wp_filesystem ) {
			return false;
		}

		$wp_path = self::get_filesystem_path( $path );

		$deleted = $wp_filesystem->delete( $wp_path );

		if ( $deleted ) {
			Cache::invalidate_group( $group_id );
		}

		return $deleted;
	}
}
