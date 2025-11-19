<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end rendering and assets.
 */
class AIO_Faq_Frontend {

	/**
	 * Register shortcodes.
	 */
	public static function register_shortcodes() {
		add_shortcode( 'aio_faq', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * Enqueue front-end styles and scripts.
	 */
	public static function enqueue_styles() {
		$css_path = AIO_Faq_Style_Generator::get_css_file_path();
		$css_url  = AIO_Faq_Style_Generator::get_css_file_url();

		if ( $css_url && file_exists( $css_path ) ) {
			wp_enqueue_style(
				'aio-faq-generated',
				$css_url,
				array(),
				filemtime( $css_path )
			);
		}

		wp_enqueue_script(
			'aio-faq-frontend',
			AIO_FAQ_PLUGIN_URL . 'assets/js/frontend-faq.js',
			array(),
			AIO_FAQ_VERSION,
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
				'title'      => __( 'Frequently Asked Questions', 'all-in-one-faq' ),
				'group'      => '',
				'group_slug' => '',
			),
			$atts,
			'aio_faq'
		);

		$group_id = 0;

		if ( ! empty( $atts['group'] ) ) {
			$group_id = (int) $atts['group'];
		} elseif ( ! empty( $atts['group_slug'] ) ) {
			$group_post = get_page_by_path( sanitize_title( $atts['group_slug'] ), OBJECT, 'aio_faq_group' );
			if ( $group_post ) {
				$group_id = (int) $group_post->ID;
			}
		}

		$items = AIO_Faq_Repository::get_all_published_faqs( $group_id );

		ob_start();
		?>
		<div class="aio-faq">
			<?php if ( ! empty( $atts['title'] ) ) : ?>
				<h2 class="aio-faq__title"><?php echo esc_html( $atts['title'] ); ?></h2>
			<?php endif; ?>

			<?php if ( ! empty( $items ) ) : ?>
				<?php foreach ( $items as $index => $item ) : ?>
					<?php
					$is_open    = isset( $item->initial_state ) ? (int) $item->initial_state === 1 : ( 0 === $index );
					$is_active  = isset( $item->highlight ) ? (int) $item->highlight === 1 : false;
					$category   = ! empty( $item->category ) ? sanitize_title( $item->category ) : '';
					$item_class = array();

					if ( $is_open ) {
						$item_class[] = 'is-open';
					}
					if ( $is_active ) {
						$item_class[] = 'aio-faq__item--highlight';
					}
					if ( $category ) {
						$item_class[] = 'aio-faq__item--category-' . $category;
					}
					?>
					<div class="aio-faq__item <?php echo esc_attr( implode( ' ', $item_class ) ); ?>">
						<div class="aio-faq__question">
							<span><?php echo esc_html( $item->question ); ?></span>
							<span class="aio-faq__icon" aria-hidden="true">
								<?php if ( ! empty( $item->icon ) ) : ?>
									<?php echo esc_html( $item->icon ); ?>
								<?php endif; ?>
							</span>
						</div>
						<div class="aio-faq__answer">
							<?php echo wp_kses_post( wpautop( $item->answer ) ); ?>
						</div>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<p class="aio-faq__empty">
					<?php esc_html_e( 'No FAQs found yet. Add some FAQs in the admin to populate this section.', 'all-in-one-faq' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php

		return trim( ob_get_clean() );
	}
}


