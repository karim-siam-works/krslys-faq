<?php
/**
 * Theme preset registry.
 *
 * @package Krslys\NextLevelFaq
 */

namespace Krslys\NextLevelFaq;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralized registry of FAQ theme presets.
 */
class Presets {

	/**
	 * Default preset slug.
	 */
	const DEFAULT_PRESET = 'minimal';

	/**
	 * Get preset registry.
	 *
	 * @return array[]
	 */
	public static function get_registry() {
		return array(
			'minimal'  => array(
				'slug'        => 'minimal',
				'name'        => __( 'Minimal', 'next-level-faq' ),
				'description' => __( 'Crisp, neutral palette that fits most sites.', 'next-level-faq' ),
				'values'      => array(
					'container_background'   => '#ffffff',
					'container_border_color' => '#e2e8f0',
					'container_border_radius'=> 8,
					'container_padding'      => 20,
					'question_color'         => '#0f172a',
					'question_font_size'     => 18,
					'question_font_weight'   => 600,
					'answer_color'           => '#4b5563',
					'answer_font_size'       => 16,
					'accent_color'           => '#3b82f6',
					'icon_style'             => 'plus_minus',
					'gap_between_items'      => 12,
					'shadow'                 => false,
					'animation'              => 'slide',
				),
			),
			'modern'   => array(
				'slug'        => 'modern',
				'name'        => __( 'Modern', 'next-level-faq' ),
				'description' => __( 'Soft neutrals with violet accents and smooth fades.', 'next-level-faq' ),
				'values'      => array(
					'container_background'   => '#f8fafc',
					'container_border_color' => '#cbd5e1',
					'container_border_radius'=> 14,
					'container_padding'      => 24,
					'question_color'         => '#0f172a',
					'question_font_size'     => 19,
					'question_font_weight'   => 600,
					'answer_color'           => '#475569',
					'answer_font_size'       => 16,
					'accent_color'           => '#8b5cf6',
					'icon_style'             => 'chevron',
					'gap_between_items'      => 14,
					'shadow'                 => true,
					'animation'              => 'fade',
				),
			),
			'card'     => array(
				'slug'        => 'card',
				'name'        => __( 'Card', 'next-level-faq' ),
				'description' => __( 'Layered card look with soft shadow and green accent.', 'next-level-faq' ),
				'values'      => array(
					'container_background'   => '#ffffff',
					'container_border_color' => '#d9e2ec',
					'container_border_radius'=> 12,
					'container_padding'      => 24,
					'question_color'         => '#0f172a',
					'question_font_size'     => 18,
					'question_font_weight'   => 700,
					'answer_color'           => '#475569',
					'answer_font_size'       => 16,
					'accent_color'           => '#10b981',
					'icon_style'             => 'plus_minus',
					'gap_between_items'      => 16,
					'shadow'                 => true,
					'animation'              => 'slide',
				),
			),
			'outline'  => array(
				'slug'        => 'outline',
				'name'        => __( 'Outline', 'next-level-faq' ),
				'description' => __( 'Bordered layout with crisp chevrons and no shadow.', 'next-level-faq' ),
				'values'      => array(
					'container_background'   => '#ffffff',
					'container_border_color' => '#cbd5e1',
					'container_border_radius'=> 10,
					'container_padding'      => 16,
					'question_color'         => '#0f172a',
					'question_font_size'     => 17,
					'question_font_weight'   => 600,
					'answer_color'           => '#475569',
					'answer_font_size'       => 15,
					'accent_color'           => '#0ea5e9',
					'icon_style'             => 'chevron',
					'gap_between_items'      => 12,
					'shadow'                 => false,
					'animation'              => 'none',
				),
			),
			'contrast' => array(
				'slug'        => 'contrast',
				'name'        => __( 'Contrast', 'next-level-faq' ),
				'description' => __( 'High-contrast dark surface with warm accent.', 'next-level-faq' ),
				'values'      => array(
					'container_background'   => '#0f172a',
					'container_border_color' => '#1f2937',
					'container_border_radius'=> 10,
					'container_padding'      => 22,
					'question_color'         => '#f8fafc',
					'question_font_size'     => 18,
					'question_font_weight'   => 700,
					'answer_color'           => '#e2e8f0',
					'answer_font_size'       => 16,
					'accent_color'           => '#f97316',
					'icon_style'             => 'chevron',
					'gap_between_items'      => 14,
					'shadow'                 => true,
					'animation'              => 'slide',
				),
			),
		);
	}

	/**
	 * Get preset values for a slug.
	 *
	 * @param string $slug Preset slug.
	 * @return array|null
	 */
	public static function get_preset_values( $slug ) {
		$registry = self::get_registry();

		return $registry[ $slug ]['values'] ?? null;
	}

	/**
	 * Validate preset slug.
	 *
	 * @param string|null $slug Candidate slug.
	 * @return string
	 */
	public static function normalize_slug( $slug ) {
		$slug = sanitize_key( $slug );

		if ( isset( self::get_registry()[ $slug ] ) ) {
			return $slug;
		}

		return self::DEFAULT_PRESET;
	}

	/**
	 * Get default preset values.
	 *
	 * @return array
	 */
	public static function get_default_values() {
		$values = self::get_preset_values( self::DEFAULT_PRESET );

		return is_array( $values ) ? $values : array();
	}
}

