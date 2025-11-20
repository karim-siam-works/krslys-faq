<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end rendering and assets.
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
	 */
	public static function enqueue_styles() {
		$css_path = NLF_Faq_Style_Generator::get_css_file_path();
		$css_url  = NLF_Faq_Style_Generator::get_css_file_url();

		if ( $css_url && file_exists( $css_path ) ) {
			wp_enqueue_style(
				'nlf-faq-generated',
				$css_url,
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
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Enclosed content (unused for now).
	 *
	 * @return string
	 */
	public static function render_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'title'      => __( 'Frequently Asked Questions', 'next-level-faq' ),
				'group'      => '',
				'group_slug' => '',
			),
			$atts,
			'nlf_faq'
		);

		$group_id = 0;

		if ( ! empty( $atts['group'] ) ) {
			$group_id = (int) $atts['group'];
		} elseif ( ! empty( $atts['group_slug'] ) ) {
			$group_post = get_page_by_path( sanitize_title( $atts['group_slug'] ), OBJECT, 'nlf_faq_group' );
			if ( $group_post ) {
				$group_id = (int) $group_post->ID;
			}
		}

		$items = NLF_Faq_Repository::get_all_published_faqs( $group_id );

		ob_start();
		?>
		<div class="nlf-faq">
			<?php if ( ! empty( $atts['title'] ) ) : ?>
				<h2 class="nlf-faq__title"><?php echo esc_html( $atts['title'] ); ?></h2>
			<?php endif; ?>

			<?php if ( ! empty( $items ) ) : ?>
				<?php foreach ( $items as $index => $item ) : ?>
					<?php
					$is_open   = isset( $item->initial_state ) ? (int) $item->initial_state === 1 : ( 0 === $index );
					$is_active = isset( $item->highlight ) ? (int) $item->highlight === 1 : false;
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
							<span><?php echo esc_html( $item->question ); ?></span>
							<span class="nlf-faq__icon" aria-hidden="true"></span>
						</div>
						<div class="nlf-faq__answer">
							<?php echo wp_kses_post( wpautop( $item->answer ) ); ?>
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
}


