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

		$shadow_css = ! empty( $o['shadow'] ) ? '0 10px 25px rgba(15, 23, 42, 0.08)' : 'none';

		$transition = 'all 180ms ease-out';
		$answer_transition = 'max-height 220ms ease-out, opacity 180ms ease-out, transform 180ms ease-out';

		if ( 'fade' === $o['animation'] ) {
			$answer_transition = 'opacity 200ms ease-out';
		} elseif ( 'none' === $o['animation'] ) {
			$answer_transition = 'none';
		}

		ob_start();
		?>
.aio-faq {
	background: <?php echo esc_html( $o['container_background'] ); ?>;
	border: 1px solid <?php echo esc_html( $o['container_border_color'] ); ?>;
	border-radius: <?php echo intval( $o['container_border_radius'] ); ?>px;
	padding: <?php echo intval( $o['container_padding'] ); ?>px;
	box-shadow: <?php echo esc_html( $shadow_css ); ?>;
}

.aio-faq__item {
	border-bottom: 1px solid <?php echo esc_html( $o['container_border_color'] ); ?>;
	padding: 12px 0;
}

.aio-faq__item:last-child {
	border-bottom: none;
}

.aio-faq__question {
	display: flex;
	align-items: center;
	justify-content: space-between;
	cursor: pointer;
	color: <?php echo esc_html( $o['question_color'] ); ?>;
	font-size: <?php echo intval( $o['question_font_size'] ); ?>px;
	font-weight: <?php echo intval( $o['question_font_weight'] ); ?>;
	transition: <?php echo esc_html( $transition ); ?>;
}

.aio-faq__icon {
	margin-left: 12px;
	color: <?php echo esc_html( $o['accent_color'] ); ?>;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 20px;
	height: 20px;
}

.aio-faq__answer {
	color: <?php echo esc_html( $o['answer_color'] ); ?>;
	font-size: <?php echo intval( $o['answer_font_size'] ); ?>px;
	margin-top: 6px;
	max-height: 0;
	overflow: hidden;
	opacity: 0;
	transform: translateY(-4px);
	transition: <?php echo esc_html( $answer_transition ); ?>;
}

.aio-faq__item.is-open .aio-faq__answer {
	max-height: 400px;
	opacity: 1;
	transform: translateY(0);
}

.aio-faq__item--highlight {
	background: rgba(59, 130, 246, 0.04);
	border-radius: <?php echo intval( $o['container_border_radius'] ); ?>px;
	padding-inline: <?php echo intval( $o['container_padding'] ) > 16 ? 8 : 0; ?>px;
}

.aio-faq__item + .aio-faq__item {
	margin-top: <?php echo intval( $o['gap_between_items'] ); ?>px;
}

.aio-faq__question:hover {
	color: <?php echo esc_html( $o['accent_color'] ); ?>;
}

.aio-faq__icon::before {
	content: '+';
	font-weight: 700;
}

.aio-faq__item.is-open .aio-faq__icon::before {
	content: '-';
}

<?php if ( 'chevron' === $o['icon_style'] ) : ?>
.aio-faq__icon::before {
	content: '›';
	display: block;
	transform: rotate(90deg);
	transition: transform 180ms ease-out;
}

.aio-faq__item.is-open .aio-faq__icon::before {
	transform: rotate(270deg);
}
<?php endif; ?>
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


