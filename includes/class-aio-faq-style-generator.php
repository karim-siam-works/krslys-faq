<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds and stores generated CSS for FAQ styles.
 */
class AIO_Faq_Style_Generator {

	/**
	 * Get path to generated CSS file.
	 *
	 * @return string
	 */
	public static function get_css_file_path() {
		$upload_dir = wp_upload_dir();
		$dir        = trailingslashit( $upload_dir['basedir'] ) . 'aio-faq';

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
		$url        = trailingslashit( $upload_dir['baseurl'] ) . 'aio-faq/generated-faq-style.css';

		return $url;
	}

	/**
	 * Build CSS string from options.
	 *
	 * @param array $options Options.
	 *
	 * @return string
	 */
	public static function build_css( $options ) {
		$o = wp_parse_args( $options, AIO_Faq_Options::get_defaults() );

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
	--aio-faq-font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
	--aio-faq-line-height-tight: 1.25;
	--aio-faq-line-height-normal: 1.5;
	--aio-faq-line-height-relaxed: 1.75;
	--aio-faq-letter-spacing-tight: -0.025em;
	--aio-faq-letter-spacing-normal: 0;
	--aio-faq-letter-spacing-wide: 0.025em;

	/* Colors - User Defined */
	--aio-faq-container-bg: <?php echo esc_html( $o['container_background'] ); ?>;
	--aio-faq-border-color: <?php echo esc_html( $o['container_border_color'] ); ?>;
	--aio-faq-question-color: <?php echo esc_html( $o['question_color'] ); ?>;
	--aio-faq-answer-color: <?php echo esc_html( $o['answer_color'] ); ?>;
	--aio-faq-accent-color: <?php echo esc_html( $o['accent_color'] ); ?>;

	/* Spacing (rem-based) */
	--aio-faq-border-radius: <?php echo esc_html( $border_radius_rem ); ?>rem;
	--aio-faq-padding: <?php echo esc_html( $padding_rem ); ?>rem;
	--aio-faq-gap: <?php echo esc_html( $gap_rem ); ?>rem;

	/* Typography Sizes */
	--aio-faq-question-size: <?php echo esc_html( $question_font_rem ); ?>rem;
	--aio-faq-answer-size: <?php echo esc_html( $answer_font_rem ); ?>rem;
	--aio-faq-question-weight: <?php echo intval( $o['question_font_weight'] ); ?>;

	/* Shadows */
	--aio-faq-shadow: <?php echo esc_html( $shadow_css ); ?>;

	/* Transitions */
	--aio-faq-transition: <?php echo esc_html( $transition_base ); ?>;
	--aio-faq-answer-transition: <?php echo esc_html( $answer_transition ); ?>;
}

/* ============================================
   Base Styles
   ============================================ */

.aio-faq {
	font-family: var(--aio-faq-font-family);
	background: var(--aio-faq-container-bg);
	border: 1px solid var(--aio-faq-border-color);
	border-radius: var(--aio-faq-border-radius);
	padding: var(--aio-faq-padding);
	box-shadow: var(--aio-faq-shadow);
	box-sizing: border-box;
	width: 100%;
}

.aio-faq__item {
	border-bottom: 1px solid var(--aio-faq-border-color);
	padding: 0.75rem 0;
	transition: border-color var(--aio-faq-transition);
}

.aio-faq__item:last-child {
	border-bottom: none;
}

.aio-faq__item + .aio-faq__item {
	margin-top: var(--aio-faq-gap);
}

/* ============================================
   Question Styles
   ============================================ */

.aio-faq__question {
	display: flex;
	align-items: center;
	justify-content: space-between;
	cursor: pointer;
	color: var(--aio-faq-question-color);
	font-size: var(--aio-faq-question-size);
	font-weight: var(--aio-faq-question-weight);
	line-height: var(--aio-faq-line-height-tight);
	letter-spacing: var(--aio-faq-letter-spacing-tight);
	transition: color var(--aio-faq-transition);
	padding: 0;
	margin: 0;
	border: none;
	background: none;
	width: 100%;
	text-align: left;
}

.aio-faq__question:hover,
.aio-faq__question:focus {
	color: var(--aio-faq-accent-color);
	outline: none;
}

.aio-faq__question:focus-visible {
	outline: 2px solid var(--aio-faq-accent-color);
	outline-offset: 2px;
	border-radius: 0.25rem;
}

/* ============================================
   Icon Styles
   ============================================ */

.aio-faq__icon {
	margin-left: 0.75rem;
	color: var(--aio-faq-accent-color);
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 1.25rem;
	height: 1.25rem;
	flex-shrink: 0;
	transition: transform var(--aio-faq-transition), color var(--aio-faq-transition);
}

.aio-faq__icon::before {
	content: '+';
	font-weight: 700;
	font-size: 1.125rem;
	line-height: 1;
}

.aio-faq__item.is-open .aio-faq__icon::before {
	content: '-';
}

<?php if ( 'chevron' === $o['icon_style'] ) : ?>
.aio-faq__icon::before {
	content: '›';
	display: block;
	transform: rotate(90deg);
	transition: transform var(--aio-faq-transition);
}

.aio-faq__item.is-open .aio-faq__icon::before {
	transform: rotate(270deg);
}
<?php endif; ?>

/* ============================================
   Answer Styles
   ============================================ */

.aio-faq__answer {
	color: var(--aio-faq-answer-color);
	font-size: var(--aio-faq-answer-size);
	line-height: var(--aio-faq-line-height-relaxed);
	letter-spacing: var(--aio-faq-letter-spacing-normal);
	margin-top: 0.75rem;
	padding-top: 0.75rem;
	max-height: 0;
	overflow: hidden;
	opacity: 0;
	transform: translateY(-0.25rem);
	transition: var(--aio-faq-answer-transition);
}

.aio-faq__item.is-open .aio-faq__answer {
	max-height: 1000px;
	opacity: 1;
	transform: translateY(0);
}

.aio-faq__answer p {
	margin: 0 0 0.75rem 0;
}

.aio-faq__answer p:last-child {
	margin-bottom: 0;
}

/* ============================================
   Highlight State
   ============================================ */

.aio-faq__item--highlight {
	background: rgba(59, 130, 246, 0.04);
	border-radius: var(--aio-faq-border-radius);
	padding-inline: 0.5rem;
}

/* ============================================
   Responsive Design
   ============================================ */

/* Mobile First - Base styles above are mobile */

/* Small devices (640px and up) */
@media (min-width: 40rem) {
	.aio-faq {
		padding: calc(var(--aio-faq-padding) * 1.125);
	}

	.aio-faq__question {
		font-size: calc(var(--aio-faq-question-size) * 1.05);
	}
}

/* Medium devices (768px and up) */
@media (min-width: 48rem) {
	.aio-faq {
		padding: calc(var(--aio-faq-padding) * 1.25);
	}

	.aio-faq__item {
		padding: 0.875rem 0;
	}
}

/* Large devices (1024px and up) */
@media (min-width: 64rem) {
	.aio-faq {
		max-width: 100%;
	}

	.aio-faq__question {
		font-size: var(--aio-faq-question-size);
	}
}

/* Mobile-specific adjustments */
@media (max-width: 39.9375rem) {
	.aio-faq {
		padding: 1rem;
		border-radius: 0.5rem;
	}

	.aio-faq__question {
		font-size: calc(var(--aio-faq-question-size) * 0.95);
		line-height: var(--aio-faq-line-height-normal);
	}

	.aio-faq__answer {
		font-size: calc(var(--aio-faq-answer-size) * 0.95);
		margin-top: 0.5rem;
		padding-top: 0.5rem;
	}

	.aio-faq__icon {
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
	 * Generate CSS file from current options.
	 *
	 * @return void
	 */
	public static function generate_and_save() {
		$options = AIO_Faq_Options::get_options();
		$css     = self::build_css( $options );
		$path    = self::get_css_file_path();

		if ( ! $path ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
		file_put_contents( $path, $css );
	}
}


