<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles FAQ style options.
 */
class AIO_Faq_Options {

	const OPTION_KEY = 'aio_faq_style_options';

	/**
	 * Default options for FAQ styles.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'container_background' => '#ffffff',
			'container_border_color' => '#e2e8f0',
			'container_border_radius' => 8,
			'container_padding' => 24,

			'question_color' => '#0f172a',
			'question_font_size' => 18,
			'question_font_weight' => 600,

			'answer_color' => '#4b5563',
			'answer_font_size' => 16,

			'accent_color' => '#3b82f6',
			'icon_style' => 'plus_minus', // plus_minus | chevron.

			'gap_between_items' => 12,
			'shadow' => true,
			'animation' => 'slide', // slide | fade | none.
		);
	}

	/**
	 * Get options merged with defaults.
	 *
	 * @return array
	 */
	public static function get_options() {
		$defaults = self::get_defaults();
		$saved    = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * Sanitize and save options.
	 *
	 * @param array $raw Raw input.
	 *
	 * @return array
	 */
	public static function sanitize( $raw ) {
		$defaults = self::get_defaults();
		$sanitized = array();

		$raw = is_array( $raw ) ? $raw : array();

		$sanitized['container_background']    = isset( $raw['container_background'] ) ? sanitize_hex_color( $raw['container_background'] ) : $defaults['container_background'];
		$sanitized['container_border_color']  = isset( $raw['container_border_color'] ) ? sanitize_hex_color( $raw['container_border_color'] ) : $defaults['container_border_color'];
		$sanitized['container_border_radius'] = isset( $raw['container_border_radius'] ) ? max( 0, intval( $raw['container_border_radius'] ) ) : $defaults['container_border_radius'];
		$sanitized['container_padding']       = isset( $raw['container_padding'] ) ? max( 0, intval( $raw['container_padding'] ) ) : $defaults['container_padding'];

		$sanitized['question_color']      = isset( $raw['question_color'] ) ? sanitize_hex_color( $raw['question_color'] ) : $defaults['question_color'];
		$sanitized['question_font_size']  = isset( $raw['question_font_size'] ) ? max( 10, intval( $raw['question_font_size'] ) ) : $defaults['question_font_size'];
		$sanitized['question_font_weight'] = isset( $raw['question_font_weight'] ) ? max( 100, min( 900, intval( $raw['question_font_weight'] ) ) ) : $defaults['question_font_weight'];

		$sanitized['answer_color']     = isset( $raw['answer_color'] ) ? sanitize_hex_color( $raw['answer_color'] ) : $defaults['answer_color'];
		$sanitized['answer_font_size'] = isset( $raw['answer_font_size'] ) ? max( 10, intval( $raw['answer_font_size'] ) ) : $defaults['answer_font_size'];

		$sanitized['accent_color'] = isset( $raw['accent_color'] ) ? sanitize_hex_color( $raw['accent_color'] ) : $defaults['accent_color'];

		$allowed_icon_styles = array( 'plus_minus', 'chevron' );
		$sanitized['icon_style'] = in_array( $raw['icon_style'] ?? '', $allowed_icon_styles, true )
			? $raw['icon_style']
			: $defaults['icon_style'];

		$sanitized['gap_between_items'] = isset( $raw['gap_between_items'] ) ? max( 0, intval( $raw['gap_between_items'] ) ) : $defaults['gap_between_items'];
		$sanitized['shadow'] = ! empty( $raw['shadow'] );

		$allowed_animation = array( 'slide', 'fade', 'none' );
		$sanitized['animation'] = in_array( $raw['animation'] ?? '', $allowed_animation, true )
			? $raw['animation']
			: $defaults['animation'];

		return $sanitized;
	}

	/**
	 * Activation callback.
	 */
	public static function activate() {
		if ( false === get_option( self::OPTION_KEY, false ) ) {
			add_option( self::OPTION_KEY, self::get_defaults() );
		}

		if ( class_exists( 'AIO_Faq_Style_Generator' ) ) {
			AIO_Faq_Style_Generator::generate_and_save();
		}
	}
}


