<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end rendering and assets.
 *
 * SECURITY FEATURES:
 * - All shortcode attributes sanitized.
 * - All output properly escaped.
 * - No direct user input accepted without validation.
 */
class NLF_Faq_Frontend {

	/**
	 * Register shortcodes.
	 */
	public static function register_shortcodes() {
		add_shortcode( 'nlf_faq', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * Enqueue front-end styles and scripts.
	 *
	 * SECURITY: Uses esc_url_raw() for CSS URL.
	 */
	public static function enqueue_styles() {
		$css_path = NLF_Faq_Style_Generator::get_css_file_path();
		$css_url  = NLF_Faq_Style_Generator::get_css_file_url();

		$uploads = wp_upload_dir();
		$baseurl = isset( $uploads['baseurl'] ) ? trailingslashit( $uploads['baseurl'] ) : '';

		// SECURITY: Validate that CSS URL is within uploads directory.
		if ( $css_url && $css_path && file_exists( $css_path ) && $baseurl && 0 === strpos( $css_url, $baseurl ) ) {
			wp_enqueue_style(
				'nlf-faq-generated',
				esc_url_raw( $css_url ),
				array(),
				filemtime( $css_path )
			);
		}

		wp_enqueue_script(
			'nlf-faq-frontend',
			NLF_FAQ_PLUGIN_URL . 'assets/js/frontend-faq.js',
			array(),
			NLF_FAQ_VERSION,
			true
		);
	}

	/**
	 * Render FAQ shortcode.
	 *
	 * SECURITY:
	 * - All attributes sanitized via sanitize_shortcode_atts().
	 * - All output escaped via esc_html(), esc_attr().
	 * - HTML content sanitized via wp_kses_post().
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Enclosed content (unused for now).
	 *
	 * @return string
	 */
	public static function render_shortcode( $atts, $content = '' ) {
		// SECURITY: Sanitize all shortcode attributes.
		$atts = self::sanitize_shortcode_atts(
			shortcode_atts(
				array(
					'title'      => __( 'Frequently Asked Questions', 'next-level-faq' ),
					'group'      => '',
					'group_slug' => '',
				),
				$atts,
				'nlf_faq'
			)
		);

		$group_id = $atts['group'];

		// SECURITY: Lookup group by slug if provided (sanitized in sanitize_shortcode_atts).
		if ( 0 === $group_id && '' !== $atts['group_slug'] ) {
			$group_post = get_page_by_path( $atts['group_slug'], OBJECT, 'nlf_faq_group' );
			if ( $group_post instanceof WP_Post ) {
				$group_id = (int) $group_post->ID;
			}
		}

		$items = NLF_Faq_Repository::get_all_published_faqs( $group_id );

		if ( ! is_array( $items ) ) {
			$items = array();
		}

		ob_start();
		?>
		<div class="nlf-faq">
			<?php if ( '' !== $atts['title'] ) : ?>
				<h2 class="nlf-faq__title"><?php echo esc_html( $atts['title'] ); ?></h2>
			<?php endif; ?>

			<?php if ( ! empty( $items ) ) : ?>
				<?php foreach ( $items as $index => $item ) : ?>
					<?php
					// SECURITY: Type-cast all values from database.
					$is_open   = isset( $item->initial_state ) ? ( 1 === (int) $item->initial_state ) : ( 0 === (int) $index );
					$is_active = isset( $item->highlight ) ? ( 1 === (int) $item->highlight ) : false;
					$item_class = array();

					if ( $is_open ) {
						$item_class[] = 'is-open';
					}
					if ( $is_active ) {
						$item_class[] = 'nlf-faq__item--highlight';
					}
					?>
					<div class="nlf-faq__item <?php echo esc_attr( implode( ' ', $item_class ) ); ?>">
						<div class="nlf-faq__question">
							<span><?php echo esc_html( (string) $item->question ); ?></span>
							<span class="nlf-faq__icon" aria-hidden="true"></span>
						</div>
						<div class="nlf-faq__answer">
							<?php
							// SECURITY: wp_kses_post allows safe HTML, wpautop adds paragraphs.
							echo wp_kses_post( wpautop( (string) $item->answer ) );
							?>
						</div>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<p class="nlf-faq__empty">
					<?php esc_html_e( 'No FAQs found yet. Add some FAQs in the admin to populate this section.', 'next-level-faq' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php

		return trim( ob_get_clean() );
	}

	/**
	 * Sanitize shortcode attributes.
	 *
	 * SECURITY:
	 * - title: sanitize_text_field() to prevent XSS.
	 * - group: absint() to ensure positive integer.
	 * - group_slug: sanitize_title() for safe slug format.
	 *
	 * @param array $atts Raw shortcode attributes.
	 *
	 * @return array
	 */
	private static function sanitize_shortcode_atts( array $atts ) : array {
		return array(
			'title'      => sanitize_text_field( $atts['title'] ?? '' ),
			'group'      => absint( $atts['group'] ?? 0 ),
			'group_slug' => sanitize_title( $atts['group_slug'] ?? '' ),
		);
	}
}
