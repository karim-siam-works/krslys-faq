<?php
/**
 * Front-end rendering and assets.
 *
 * @package Krslys\NextLevelFaq
 */

namespace Krslys\NextLevelFaq;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_Post;

/**
 * Front-end rendering and assets.
 *
 * SECURITY FEATURES:
 * - All shortcode attributes sanitized.
 * - All output properly escaped.
 * - No direct user input accepted without validation.
 */
class Frontend_Renderer {

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
		// Enqueue global CSS
		$css_path = Style_Generator::get_css_file_path();
		$css_url  = Style_Generator::get_css_file_url();

		$uploads = wp_upload_dir();
		$baseurl = isset( $uploads['baseurl'] ) ? trailingslashit( $uploads['baseurl'] ) : '';

		if ( $css_url && $css_path && file_exists( $css_path ) && $baseurl && 0 === strpos( $css_url, $baseurl ) ) {
			wp_enqueue_style(
				'nlf-faq-generated',
				esc_url_raw( $css_url ),
				array(),
				filemtime( $css_path )
			);
		}

		// Enqueue group-specific CSS if needed (will be done per group in shortcode)
		wp_enqueue_script(
			'nlf-faq-frontend',
			NLF_FAQ_PLUGIN_URL . 'assets/js/frontend-faq.js',
			array(),
			NLF_FAQ_VERSION,
			true
		);
	}

	/**
	 * Enqueue group-specific CSS if custom styles are enabled.
	 *
	 * @param int $group_id Group ID.
	 */
	private static function maybe_enqueue_group_css( $group_id ) {
		if ( ! $group_id ) {
			return;
		}

		$use_custom_style = get_post_meta( $group_id, '_nlf_faq_group_use_custom_style', true );

		if ( empty( $use_custom_style ) ) {
			return;
		}

		$css_path = Style_Generator::get_group_css_file_path( $group_id );
		$css_url  = Style_Generator::get_group_css_file_url( $group_id );

		$uploads = wp_upload_dir();
		$baseurl = isset( $uploads['baseurl'] ) ? trailingslashit( $uploads['baseurl'] ) : '';

		if ( $css_url && $css_path && file_exists( $css_path ) && $baseurl && 0 === strpos( $css_url, $baseurl ) ) {
			wp_enqueue_style(
				'nlf-faq-group-' . $group_id,
				esc_url_raw( $css_url ),
				array( 'nlf-faq-generated' ),
				filemtime( $css_path )
			);
		}
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

	if ( 0 === $group_id && '' !== $atts['group_slug'] ) {
			$group_post = get_page_by_path( $atts['group_slug'], OBJECT, 'nlf_faq_group' );
			if ( $group_post instanceof WP_Post ) {
				$group_id = (int) $group_post->ID;
			}
		}

		// Enqueue group-specific CSS if custom styles enabled
		if ( $group_id ) {
			self::maybe_enqueue_group_css( $group_id );
		}

		// Get group-specific settings
		$settings = array();
		if ( $group_id ) {
			$settings = get_post_meta( $group_id, '_nlf_faq_group_settings', true );
		}
		if ( ! is_array( $settings ) ) {
			$settings = array(
				'accordion_mode'  => false,
				'initial_state'   => 'all_closed',
				'animation_speed' => 'normal',
				'show_search'     => false,
				'show_counter'    => false,
				'smooth_scroll'   => true,
			);
		}

		$items = Repository::get_all_published_faqs( $group_id );

		if ( ! is_array( $items ) ) {
			$items = array();
		}

		$cache_context = array(
			'atts'     => array(
				'title' => $atts['title'],
			),
			'settings' => $settings,
		);

		if ( $group_id > 0 ) {
			$cached_output = Cache::get_rendered_group( $group_id, $cache_context );

			if ( $cached_output ) {
				return $cached_output;
			}
		}

		$faq_classes = array( 'nlf-faq' );
		if ( ! empty( $settings['accordion_mode'] ) ) {
			$faq_classes[] = 'nlf-faq--accordion';
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $faq_classes ) ); ?>" 
			data-animation-speed="<?php echo esc_attr( $settings['animation_speed'] ?? 'normal' ); ?>"
			data-accordion="<?php echo ! empty( $settings['accordion_mode'] ) ? '1' : '0'; ?>"
			data-smooth-scroll="<?php echo ! empty( $settings['smooth_scroll'] ) ? '1' : '0'; ?>">
			<?php if ( '' !== $atts['title'] ) : ?>
				<h2 class="nlf-faq__title"><?php echo esc_html( $atts['title'] ); ?></h2>
			<?php endif; ?>

			<?php if ( ! empty( $settings['show_search'] ) ) : ?>
				<div class="nlf-faq-search">
					<input type="text" class="nlf-faq-search-input" placeholder="<?php esc_attr_e( 'Search FAQs...', 'next-level-faq' ); ?>" />
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $items ) ) : ?>
			<?php foreach ( $items as $index => $item ) : ?>
				<?php
				// Determine initial open state based on settings
					$is_open = false;
					if ( 'first_open' === $settings['initial_state'] && 0 === $index ) {
						$is_open = true;
					} elseif ( 'custom' === $settings['initial_state'] && isset( $item->initial_state ) && 1 === (int) $item->initial_state ) {
						$is_open = true;
					}

					$is_active = isset( $item->highlight ) ? ( 1 === (int) $item->highlight ) : false;
					$item_class = array();

					if ( $is_open ) {
						$item_class[] = 'is-open';
					}
					if ( $is_active ) {
						$item_class[] = 'nlf-faq__item--highlight';
					}
					?>
					<div class="nlf-faq__item <?php echo esc_attr( implode( ' ', $item_class ) ); ?>" data-faq-id="<?php echo esc_attr( $item->id ); ?>">
						<div class="nlf-faq__question">
							<?php if ( ! empty( $settings['show_counter'] ) ) : ?>
								<span class="nlf-faq__counter"><?php echo esc_html( $index + 1 ); ?>.</span>
							<?php endif; ?>
							<span><?php echo esc_html( (string) $item->question ); ?></span>
							<span class="nlf-faq__icon" aria-hidden="true"></span>
						</div>
					<div class="nlf-faq__answer">
						<?php
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

		$output = trim( ob_get_clean() );

		if ( $group_id > 0 ) {
			Cache::set_rendered_group( $group_id, $cache_context, $output );
		}

		return $output;
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
