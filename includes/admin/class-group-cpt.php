<?php
/**
 * FAQ Group custom post type.
 *
 * @package Krslys\NextLevelFaq
 */

namespace Krslys\NextLevelFaq;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_Post;

/**
 * FAQ Group custom post type.
 *
 * Each post represents a group/section that will hold a repeater
 * of FAQ items stored in the custom nlf_faq_items table.
 *
 * SECURITY FEATURES:
 * - All metabox saves protected with nonce verification.
 * - Capability checks for edit_post permission.
 * - Input sanitization via sanitize_text_field() and wp_kses_post().
 * - Output escaping via esc_attr(), esc_html().
 */
class Group_CPT {

	const POST_TYPE = 'nlf_faq_group';

	/**
	 * Register CPT.
	 */
	public static function register() {
		$labels = array(
			'name'               => __( 'FAQ Groups', 'next-level-faq' ),
			'singular_name'      => __( 'FAQ Group', 'next-level-faq' ),
			'add_new'            => __( 'Add New', 'next-level-faq' ),
			'add_new_item'       => __( 'Add New FAQ Group', 'next-level-faq' ),
			'edit_item'          => __( 'Edit FAQ Group', 'next-level-faq' ),
			'new_item'           => __( 'New FAQ Group', 'next-level-faq' ),
			'all_items'          => __( 'FAQ Groups', 'next-level-faq' ),
			'view_item'          => __( 'View FAQ Group', 'next-level-faq' ),
			'search_items'       => __( 'Search FAQ Groups', 'next-level-faq' ),
			'not_found'          => __( 'No FAQ groups found', 'next-level-faq' ),
			'not_found_in_trash' => __( 'No FAQ groups found in Trash', 'next-level-faq' ),
			'menu_name'          => __( 'FAQ Groups', 'next-level-faq' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'show_ui'            => true,
			'show_in_rest'       => true,
			'rest_base'          => 'nlf-faq-groups',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'show_in_nav_menus'  => false,
			'show_in_admin_bar'  => false,
			'show_in_menu'       => 'nlf-faq',
			'supports'           => array( 'title' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
		);

		register_post_type( self::POST_TYPE, $args );

		add_action( 'add_meta_boxes', array( __CLASS__, 'register_metaboxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_metabox' ), 10, 2 );
		add_action( 'before_delete_post', array( __CLASS__, 'handle_delete' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_nlf_get_group_preview', array( __CLASS__, 'ajax_get_group_preview' ) );
	}

	/**
	 * Register metaboxes.
	 */
	public static function register_metaboxes() {
		add_meta_box(
			'nlf_faq_group_tabs',
			__( 'FAQ Group Configuration', 'next-level-faq' ),
			array( __CLASS__, 'render_metabox' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Enqueue admin assets for group editor.
	 *
	 * SECURITY: Validates screen object and post type.
	 *
	 * @param string $hook_suffix Hook suffix.
	 */
public static function enqueue_admin_assets( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		// Ensure WordPress editor (TinyMCE/Quicktags) assets are available for WYSIWYG answers.
		if ( function_exists( 'wp_enqueue_editor' ) ) {
			wp_enqueue_editor();
		}

		// Enqueue color picker
		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_style(
			'nlf-faq-admin',
			NLF_FAQ_PLUGIN_URL . 'assets/css/admin-faq-style.css',
			array( 'wp-color-picker' ),
			NLF_FAQ_VERSION
		);

		wp_enqueue_script(
			'nlf-faq-group-metabox',
			NLF_FAQ_PLUGIN_URL . 'assets/js/admin-faq-group-metabox.js',
			array( 'jquery', 'jquery-ui-sortable', 'wp-editor', 'wp-color-picker' ),
			NLF_FAQ_VERSION,
			true
		);

		// Localize script for AJAX
		wp_localize_script(
			'nlf-faq-group-metabox',
			'nlfGroupData',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'nlf_group_preview' ),
			)
		);
	}

	/**
	 * Render tabbed metabox for FAQ Group.
	 *
	 * SECURITY: All output properly escaped.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function render_metabox( $post ) {
		wp_nonce_field( 'nlf_faq_group_save', 'nlf_faq_group_nonce' );

		// Get saved data
		$items              = Repository::get_items_for_group( $post->ID, false );
		$current_theme      = get_post_meta( $post->ID, '_nlf_faq_group_theme', true );
		$theme_custom       = get_post_meta( $post->ID, '_nlf_faq_group_theme_custom', true );
		$settings           = get_post_meta( $post->ID, '_nlf_faq_group_settings', true );
		$use_custom_style   = get_post_meta( $post->ID, '_nlf_faq_group_use_custom_style', true );
		$custom_styles      = get_post_meta( $post->ID, '_nlf_faq_group_custom_styles', true );

		// Set defaults
		if ( empty( $current_theme ) ) {
			$current_theme = 'default';
		}
		if ( ! is_array( $theme_custom ) ) {
			$theme_custom = array();
		}
		if ( ! is_array( $settings ) ) {
			$settings = self::get_default_settings();
		}
		if ( ! is_array( $custom_styles ) ) {
			$custom_styles = Options::get_defaults();
		}
		?>

		<div class="nlf-faq-group-tabs-wrapper">
			<!-- Tab Navigation -->
			<div class="nlf-faq-tabs-nav">
				<button type="button" class="nlf-faq-tab-button active" data-tab="faq-items">
					<span class="dashicons dashicons-list-view"></span>
					<?php esc_html_e( 'FAQ Items', 'next-level-faq' ); ?>
				</button>
				<button type="button" class="nlf-faq-tab-button" data-tab="themes">
					<span class="dashicons dashicons-art"></span>
					<?php esc_html_e( 'Themes', 'next-level-faq' ); ?>
				</button>
				<button type="button" class="nlf-faq-tab-button" data-tab="settings">
					<span class="dashicons dashicons-admin-settings"></span>
					<?php esc_html_e( 'Settings', 'next-level-faq' ); ?>
				</button>
				<button type="button" class="nlf-faq-tab-button" data-tab="style">
					<span class="dashicons dashicons-admin-customizer"></span>
					<?php esc_html_e( 'Style', 'next-level-faq' ); ?>
				</button>
				<button type="button" class="nlf-faq-tab-button" data-tab="live-view">
					<span class="dashicons dashicons-visibility"></span>
					<?php esc_html_e( 'Live View', 'next-level-faq' ); ?>
				</button>
			</div>

			<!-- Tab Panels -->
			<div class="nlf-faq-tabs-content">
				<!-- FAQ Items Tab -->
				<div class="nlf-faq-tab-panel active" data-tab="faq-items">
					<?php self::render_faq_items_tab( $items ); ?>
				</div>

				<!-- Themes Tab -->
				<div class="nlf-faq-tab-panel" data-tab="themes">
					<?php self::render_themes_tab( $current_theme, $theme_custom ); ?>
				</div>

				<!-- Settings Tab -->
				<div class="nlf-faq-tab-panel" data-tab="settings">
					<?php self::render_settings_tab( $settings ); ?>
				</div>

				<!-- Style Tab -->
				<div class="nlf-faq-tab-panel" data-tab="style">
					<?php self::render_style_tab( $use_custom_style, $custom_styles ); ?>
				</div>

				<!-- Live View Tab -->
				<div class="nlf-faq-tab-panel" data-tab="live-view">
					<?php self::render_live_view_tab( $post->ID ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render FAQ Items tab content.
	 *
	 * @param array $items FAQ items.
	 */
	private static function render_faq_items_tab( $items ) {
		?>
		<p class="description">
			<?php esc_html_e( 'Add multiple questions and answers to this FAQ group. Control visibility, default open state, and highlight notable items.', 'next-level-faq' ); ?>
		</p>

		<table class="widefat fixed striped nlf-faq-questions-table nlf-faq-group-table">
			<thead>
				<tr>
					<th style="width:32px;"></th>
					<th><?php esc_html_e( 'Question & Answer', 'next-level-faq' ); ?></th>
					<th style="width:200px;"><?php esc_html_e( 'Options', 'next-level-faq' ); ?></th>
				</tr>
			</thead>
			<tbody id="nlf-faq-group-questions-body">
				<?php if ( ! empty( $items ) ) : ?>
					<?php foreach ( $items as $index => $item ) : ?>
						<tr class="nlf-faq-question-row">
							<td class="nlf-faq-sort-handle">⋮⋮</td>
							<td class="nlf-faq-content-cell">
								<input type="hidden" name="nlf_faq_group_item_id[]" value="<?php echo esc_attr( $item->id ); ?>" />
								<div class="nlf-faq-question-field">
									<label class="nlf-faq-field-label"><?php esc_html_e( 'Question', 'next-level-faq' ); ?></label>
									<input type="text" class="regular-text" name="nlf_faq_group_question[]" value="<?php echo esc_attr( $item->question ); ?>" placeholder="<?php esc_attr_e( 'Enter your question...', 'next-level-faq' ); ?>" />
								</div>
								<div class="nlf-faq-answer-field">
									<label class="nlf-faq-field-label"><?php esc_html_e( 'Answer', 'next-level-faq' ); ?></label>
									<?php
									$editor_id = 'nlf_faq_group_answer_' . $index;
									wp_editor(
										$item->answer,
										$editor_id,
										array(
											'textarea_name' => 'nlf_faq_group_answer[]',
											'media_buttons' => false,
											'teeny'         => true,
											'textarea_rows' => 4,
										)
									);
									?>
								</div>
								<button type="button" class="nlf-faq-remove-row" aria-label="<?php esc_attr_e( 'Remove', 'next-level-faq' ); ?>" title="<?php esc_attr_e( 'Remove', 'next-level-faq' ); ?>">
									<span class="nlf-faq-remove-icon">×</span>
								</button>
							</td>
							<td class="nlf-faq-options-cell">
								<div class="nlf-faq-options-group">
									<p>
										<label>
											<input type="checkbox" name="nlf_faq_group_open[<?php echo esc_attr( $index ); ?>]" value="1" <?php checked( (int) $item->initial_state, 1 ); ?> />
											<?php esc_html_e( 'Open by default', 'next-level-faq' ); ?>
										</label>
									</p>
									<p>
										<label>
											<input type="checkbox" name="nlf_faq_group_visible[<?php echo esc_attr( $index ); ?>]" value="1" <?php checked( (int) $item->status, 1 ); ?> />
											<?php esc_html_e( 'Show', 'next-level-faq' ); ?>
										</label>
									</p>
									<p>
										<label>
											<input type="checkbox" name="nlf_faq_group_highlight[<?php echo esc_attr( $index ); ?>]" value="1" <?php checked( (int) $item->highlight, 1 ); ?> />
											<?php esc_html_e( 'Highlight', 'next-level-faq' ); ?>
										</label>
									</p>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="3">
						<button type="button" class="button button-secondary" id="nlf-faq-group-add-row">
							<?php esc_html_e( 'Add Question', 'next-level-faq' ); ?>
						</button>
					</td>
				</tr>
			</tfoot>
		</table>

		<script type="text/template" id="tmpl-nlf-faq-group-row">
			<tr class="nlf-faq-question-row">
				<td class="nlf-faq-sort-handle">⋮⋮</td>
				<td class="nlf-faq-content-cell">
					<input type="hidden" name="nlf_faq_group_item_id[]" value="" />
					<div class="nlf-faq-question-field">
						<label class="nlf-faq-field-label"><?php esc_html_e( 'Question', 'next-level-faq' ); ?></label>
						<input type="text" class="regular-text" name="nlf_faq_group_question[]" value="" placeholder="<?php esc_attr_e( 'Enter your question...', 'next-level-faq' ); ?>" />
					</div>
					<div class="nlf-faq-answer-field">
						<label class="nlf-faq-field-label"><?php esc_html_e( 'Answer', 'next-level-faq' ); ?></label>
						<textarea id="nlf-faq-group-answer-{{index}}" name="nlf_faq_group_answer[]" rows="4" class="large-text nlf-faq-group-answer-editor" placeholder="<?php esc_attr_e( 'Enter your answer...', 'next-level-faq' ); ?>"></textarea>
					</div>
					<button type="button" class="nlf-faq-remove-row" aria-label="<?php esc_attr_e( 'Remove', 'next-level-faq' ); ?>" title="<?php esc_attr_e( 'Remove', 'next-level-faq' ); ?>">
						<span class="nlf-faq-remove-icon">×</span>
					</button>
				</td>
				<td class="nlf-faq-options-cell">
					<div class="nlf-faq-options-group">
						<p>
							<label>
								<input type="checkbox" name="nlf_faq_group_open[{{index}}]" value="1" checked="checked" />
								<?php esc_html_e( 'Open by default', 'next-level-faq' ); ?>
							</label>
						</p>
						<p>
							<label>
								<input type="checkbox" name="nlf_faq_group_visible[{{index}}]" value="1" checked="checked" />
								<?php esc_html_e( 'Show', 'next-level-faq' ); ?>
							</label>
						</p>
						<p>
							<label>
								<input type="checkbox" name="nlf_faq_group_highlight[{{index}}]" value="1" />
								<?php esc_html_e( 'Highlight', 'next-level-faq' ); ?>
							</label>
						</p>
					</div>
				</td>
			</tr>
		</script>
		<?php
	}

	/**
	 * Render Themes tab content.
	 *
	 * @param string $current_theme Selected theme.
	 * @param array  $theme_custom  Custom theme colors.
	 */
	private static function render_themes_tab( $current_theme, $theme_custom ) {
		$themes = self::get_theme_presets();
		?>
		<div class="nlf-themes-wrapper">
			<h3><?php esc_html_e( 'Choose a Theme Preset', 'next-level-faq' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Select a pre-designed theme and optionally customize its colors.', 'next-level-faq' ); ?>
			</p>

			<div class="nlf-theme-grid">
				<?php foreach ( $themes as $theme_id => $theme_data ) : ?>
					<div class="nlf-theme-card <?php echo ( $current_theme === $theme_id ) ? 'active' : ''; ?>" data-theme="<?php echo esc_attr( $theme_id ); ?>">
						<input type="radio" name="nlf_faq_group_theme" value="<?php echo esc_attr( $theme_id ); ?>" <?php checked( $current_theme, $theme_id ); ?> id="theme_<?php echo esc_attr( $theme_id ); ?>" />
						<label for="theme_<?php echo esc_attr( $theme_id ); ?>">
							<div class="nlf-theme-preview" style="
								background: <?php echo esc_attr( $theme_data['background'] ); ?>;
								border-color: <?php echo esc_attr( $theme_data['border'] ); ?>;
							">
								<div class="nlf-theme-preview-question" style="color: <?php echo esc_attr( $theme_data['question'] ); ?>;">
									<?php esc_html_e( 'Sample Question?', 'next-level-faq' ); ?>
								</div>
								<div class="nlf-theme-preview-answer" style="color: <?php echo esc_attr( $theme_data['answer'] ); ?>;">
									<?php esc_html_e( 'Sample answer text...', 'next-level-faq' ); ?>
								</div>
								<div class="nlf-theme-preview-accent" style="background: <?php echo esc_attr( $theme_data['accent'] ); ?>;"></div>
							</div>
							<div class="nlf-theme-name"><?php echo esc_html( $theme_data['name'] ); ?></div>
						</label>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="nlf-theme-customizer">
				<h3><?php esc_html_e( 'Customize Colors', 'next-level-faq' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Override theme colors with your own custom values.', 'next-level-faq' ); ?>
				</p>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="theme_custom_primary"><?php esc_html_e( 'Primary Color', 'next-level-faq' ); ?></label>
						</th>
						<td>
							<input type="text" id="theme_custom_primary" name="nlf_faq_group_theme_custom[primary]" value="<?php echo esc_attr( $theme_custom['primary'] ?? '' ); ?>" class="nlf-color-picker" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="theme_custom_secondary"><?php esc_html_e( 'Secondary Color', 'next-level-faq' ); ?></label>
						</th>
						<td>
							<input type="text" id="theme_custom_secondary" name="nlf_faq_group_theme_custom[secondary]" value="<?php echo esc_attr( $theme_custom['secondary'] ?? '' ); ?>" class="nlf-color-picker" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="theme_custom_accent"><?php esc_html_e( 'Accent Color', 'next-level-faq' ); ?></label>
						</th>
						<td>
							<input type="text" id="theme_custom_accent" name="nlf_faq_group_theme_custom[accent]" value="<?php echo esc_attr( $theme_custom['accent'] ?? '' ); ?>" class="nlf-color-picker" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="theme_custom_background"><?php esc_html_e( 'Background Color', 'next-level-faq' ); ?></label>
						</th>
						<td>
							<input type="text" id="theme_custom_background" name="nlf_faq_group_theme_custom[background]" value="<?php echo esc_attr( $theme_custom['background'] ?? '' ); ?>" class="nlf-color-picker" />
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Settings tab content.
	 *
	 * @param array $settings Group settings.
	 */
	private static function render_settings_tab( $settings ) {
		?>
		<div class="nlf-settings-wrapper">
			<h3><?php esc_html_e( 'Behavior Settings', 'next-level-faq' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="setting_accordion_mode"><?php esc_html_e( 'Accordion Mode', 'next-level-faq' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" id="setting_accordion_mode" name="nlf_faq_group_settings[accordion_mode]" value="1" <?php checked( ! empty( $settings['accordion_mode'] ) ); ?> />
							<?php esc_html_e( 'Only allow one item to be open at a time', 'next-level-faq' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="setting_initial_state"><?php esc_html_e( 'Initial State', 'next-level-faq' ); ?></label>
					</th>
					<td>
						<select id="setting_initial_state" name="nlf_faq_group_settings[initial_state]">
							<option value="all_closed" <?php selected( $settings['initial_state'] ?? 'all_closed', 'all_closed' ); ?>><?php esc_html_e( 'All Closed', 'next-level-faq' ); ?></option>
							<option value="first_open" <?php selected( $settings['initial_state'] ?? '', 'first_open' ); ?>><?php esc_html_e( 'First Item Open', 'next-level-faq' ); ?></option>
							<option value="custom" <?php selected( $settings['initial_state'] ?? '', 'custom' ); ?>><?php esc_html_e( 'Custom (Use item settings)', 'next-level-faq' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="setting_animation_speed"><?php esc_html_e( 'Animation Speed', 'next-level-faq' ); ?></label>
					</th>
					<td>
						<select id="setting_animation_speed" name="nlf_faq_group_settings[animation_speed]">
							<option value="fast" <?php selected( $settings['animation_speed'] ?? 'normal', 'fast' ); ?>><?php esc_html_e( 'Fast (150ms)', 'next-level-faq' ); ?></option>
							<option value="normal" <?php selected( $settings['animation_speed'] ?? 'normal', 'normal' ); ?>><?php esc_html_e( 'Normal (300ms)', 'next-level-faq' ); ?></option>
							<option value="slow" <?php selected( $settings['animation_speed'] ?? '', 'slow' ); ?>><?php esc_html_e( 'Slow (500ms)', 'next-level-faq' ); ?></option>
						</select>
					</td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Display Options', 'next-level-faq' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="setting_show_search"><?php esc_html_e( 'Search Box', 'next-level-faq' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" id="setting_show_search" name="nlf_faq_group_settings[show_search]" value="1" <?php checked( ! empty( $settings['show_search'] ) ); ?> />
							<?php esc_html_e( 'Show search box above FAQ items', 'next-level-faq' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="setting_show_counter"><?php esc_html_e( 'Item Counter', 'next-level-faq' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" id="setting_show_counter" name="nlf_faq_group_settings[show_counter]" value="1" <?php checked( ! empty( $settings['show_counter'] ) ); ?> />
							<?php esc_html_e( 'Display item numbers (e.g., 1., 2., 3.)', 'next-level-faq' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="setting_smooth_scroll"><?php esc_html_e( 'Smooth Scroll', 'next-level-faq' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" id="setting_smooth_scroll" name="nlf_faq_group_settings[smooth_scroll]" value="1" <?php checked( ! empty( $settings['smooth_scroll'] ) ); ?> />
							<?php esc_html_e( 'Scroll to item when opened via URL hash', 'next-level-faq' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Render Style tab content.
	 *
	 * @param bool  $use_custom_style Whether to use custom styles.
	 * @param array $custom_styles    Custom style values.
	 */
	private static function render_style_tab( $use_custom_style, $custom_styles ) {
		?>
		<div class="nlf-style-wrapper">
			<div class="nlf-style-toggle">
				<label>
					<input type="checkbox" name="nlf_faq_group_use_custom_style" value="1" <?php checked( ! empty( $use_custom_style ) ); ?> id="nlf-use-custom-style-toggle" />
					<strong><?php esc_html_e( 'Use custom styles for this group', 'next-level-faq' ); ?></strong>
				</label>
				<p class="description">
					<?php esc_html_e( 'Enable this to override global styles with group-specific styling.', 'next-level-faq' ); ?>
				</p>
			</div>

			<div class="nlf-custom-style-fields" style="<?php echo empty( $use_custom_style ) ? 'display: none;' : ''; ?>">
				<h3><?php esc_html_e( 'Container', 'next-level-faq' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="custom_container_background"><?php esc_html_e( 'Background', 'next-level-faq' ); ?></label>
						</th>
						<td>
							<input type="text" id="custom_container_background" name="nlf_faq_group_custom_styles[container_background]" value="<?php echo esc_attr( $custom_styles['container_background'] ?? '#ffffff' ); ?>" class="nlf-color-picker" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="custom_container_border_color"><?php esc_html_e( 'Border Color', 'next-level-faq' ); ?></label>
						</th>
						<td>
							<input type="text" id="custom_container_border_color" name="nlf_faq_group_custom_styles[container_border_color]" value="<?php echo esc_attr( $custom_styles['container_border_color'] ?? '#e2e8f0' ); ?>" class="nlf-color-picker" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="custom_container_border_radius"><?php esc_html_e( 'Border Radius (px)', 'next-level-faq' ); ?></label>
						</th>
						<td>
							<input type="number" min="0" id="custom_container_border_radius" name="nlf_faq_group_custom_styles[container_border_radius]" value="<?php echo esc_attr( $custom_styles['container_border_radius'] ?? 8 ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="custom_container_padding"><?php esc_html_e( 'Padding (px)', 'next-level-faq' ); ?></label>
						</th>
						<td>
							<input type="number" min="0" id="custom_container_padding" name="nlf_faq_group_custom_styles[container_padding]" value="<?php echo esc_attr( $custom_styles['container_padding'] ?? 24 ); ?>" />
						</td>
					</tr>
				</table>

				<h3><?php esc_html_e( 'Question', 'next-level-faq' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="custom_question_color"><?php esc_html_e( 'Color', 'next-level-faq' ); ?></label>
						</th>
						<td>
							<input type="text" id="custom_question_color" name="nlf_faq_group_custom_styles[question_color]" value="<?php echo esc_attr( $custom_styles['question_color'] ?? '#0f172a' ); ?>" class="nlf-color-picker" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="custom_question_font_size"><?php esc_html_e( 'Font Size (px)', 'next-level-faq' ); ?></label>
						</th>
						<td>
							<input type="number" min="10" id="custom_question_font_size" name="nlf_faq_group_custom_styles[question_font_size]" value="<?php echo esc_attr( $custom_styles['question_font_size'] ?? 18 ); ?>" />
						</td>
					</tr>
				</table>

				<h3><?php esc_html_e( 'Answer', 'next-level-faq' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="custom_answer_color"><?php esc_html_e( 'Color', 'next-level-faq' ); ?></label>
						</th>
						<td>
							<input type="text" id="custom_answer_color" name="nlf_faq_group_custom_styles[answer_color]" value="<?php echo esc_attr( $custom_styles['answer_color'] ?? '#4b5563' ); ?>" class="nlf-color-picker" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="custom_answer_font_size"><?php esc_html_e( 'Font Size (px)', 'next-level-faq' ); ?></label>
						</th>
						<td>
							<input type="number" min="10" id="custom_answer_font_size" name="nlf_faq_group_custom_styles[answer_font_size]" value="<?php echo esc_attr( $custom_styles['answer_font_size'] ?? 16 ); ?>" />
						</td>
					</tr>
				</table>

				<h3><?php esc_html_e( 'Accent & Animation', 'next-level-faq' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="custom_accent_color"><?php esc_html_e( 'Accent Color', 'next-level-faq' ); ?></label>
						</th>
						<td>
							<input type="text" id="custom_accent_color" name="nlf_faq_group_custom_styles[accent_color]" value="<?php echo esc_attr( $custom_styles['accent_color'] ?? '#3b82f6' ); ?>" class="nlf-color-picker" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="custom_icon_style"><?php esc_html_e( 'Icon Style', 'next-level-faq' ); ?></label>
						</th>
						<td>
							<select id="custom_icon_style" name="nlf_faq_group_custom_styles[icon_style]">
								<option value="plus_minus" <?php selected( $custom_styles['icon_style'] ?? 'plus_minus', 'plus_minus' ); ?>><?php esc_html_e( 'Plus / Minus', 'next-level-faq' ); ?></option>
								<option value="chevron" <?php selected( $custom_styles['icon_style'] ?? '', 'chevron' ); ?>><?php esc_html_e( 'Chevron', 'next-level-faq' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="custom_animation"><?php esc_html_e( 'Animation', 'next-level-faq' ); ?></label>
						</th>
						<td>
							<select id="custom_animation" name="nlf_faq_group_custom_styles[animation]">
								<option value="slide" <?php selected( $custom_styles['animation'] ?? 'slide', 'slide' ); ?>><?php esc_html_e( 'Slide', 'next-level-faq' ); ?></option>
								<option value="fade" <?php selected( $custom_styles['animation'] ?? '', 'fade' ); ?>><?php esc_html_e( 'Fade', 'next-level-faq' ); ?></option>
								<option value="none" <?php selected( $custom_styles['animation'] ?? '', 'none' ); ?>><?php esc_html_e( 'None', 'next-level-faq' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Live View tab content.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function render_live_view_tab( $post_id ) {
		?>
		<div class="nlf-live-view-wrapper">
			<div class="nlf-live-view-notice">
				<p>
					<span class="dashicons dashicons-info"></span>
					<?php esc_html_e( 'Save the group to see the live preview with all your settings applied.', 'next-level-faq' ); ?>
				</p>
			</div>
			<div class="nlf-live-view-container" data-group-id="<?php echo esc_attr( $post_id ); ?>">
				<div class="nlf-live-view-loading">
					<span class="spinner is-active"></span>
					<?php esc_html_e( 'Loading preview...', 'next-level-faq' ); ?>
				</div>
				<div class="nlf-live-view-content"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get theme presets.
	 *
	 * @return array
	 */
	private static function get_theme_presets() {
		return array(
			'default'      => array(
				'name'       => __( 'Default', 'next-level-faq' ),
				'background' => '#ffffff',
				'border'     => '#e2e8f0',
				'question'   => '#0f172a',
				'answer'     => '#4b5563',
				'accent'     => '#3b82f6',
			),
			'modern'       => array(
				'name'       => __( 'Modern', 'next-level-faq' ),
				'background' => '#f8fafc',
				'border'     => '#cbd5e1',
				'question'   => '#1e293b',
				'answer'     => '#64748b',
				'accent'     => '#0ea5e9',
			),
			'classic'      => array(
				'name'       => __( 'Classic', 'next-level-faq' ),
				'background' => '#fefce8',
				'border'     => '#fde047',
				'question'   => '#713f12',
				'answer'     => '#78716c',
				'accent'     => '#f59e0b',
			),
			'minimal'      => array(
				'name'       => __( 'Minimal', 'next-level-faq' ),
				'background' => '#fafafa',
				'border'     => '#e5e5e5',
				'question'   => '#171717',
				'answer'     => '#737373',
				'accent'     => '#404040',
			),
			'bold'         => array(
				'name'       => __( 'Bold', 'next-level-faq' ),
				'background' => '#fef2f2',
				'border'     => '#fca5a5',
				'question'   => '#7f1d1d',
				'answer'     => '#991b1b',
				'accent'     => '#dc2626',
			),
			'professional' => array(
				'name'       => __( 'Professional', 'next-level-faq' ),
				'background' => '#f0f9ff',
				'border'     => '#bae6fd',
				'question'   => '#082f49',
				'answer'     => '#0c4a6e',
				'accent'     => '#0284c7',
			),
		);
	}

	/**
	 * Get default settings.
	 *
	 * @return array
	 */
	private static function get_default_settings() {
		return array(
			'accordion_mode'  => false,
			'initial_state'   => 'all_closed',
			'animation_speed' => 'normal',
			'show_search'     => false,
			'show_counter'    => false,
			'smooth_scroll'   => true,
		);
	}

	/**
	 * Save metabox data for a group.
	 *
	 * SECURITY:
	 * - Nonce verification via wp_verify_nonce().
	 * - Capability check via current_user_can('edit_post').
	 * - Autosave and post type validation.
	 * - Input sanitization via sanitize_text_field() and wp_kses_post().
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
public static function save_metabox( $post_id, $post ) {
	if ( ! isset( $_POST['nlf_faq_group_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nlf_faq_group_nonce'] ) ), 'nlf_faq_group_save' ) ) {
		return; 
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( self::POST_TYPE !== $post->post_type ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Save FAQ Items
	$ids       = isset( $_POST['nlf_faq_group_item_id'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['nlf_faq_group_item_id'] ) ) : array();
		$questions = isset( $_POST['nlf_faq_group_question'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['nlf_faq_group_question'] ) ) : array();
		$answers   = isset( $_POST['nlf_faq_group_answer'] ) ? array_map( 'wp_kses_post', wp_unslash( (array) $_POST['nlf_faq_group_answer'] ) ) : array();
		$visible   = isset( $_POST['nlf_faq_group_visible'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['nlf_faq_group_visible'] ) ) : array();
		$open      = isset( $_POST['nlf_faq_group_open'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['nlf_faq_group_open'] ) ) : array();
		$highlight = isset( $_POST['nlf_faq_group_highlight'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['nlf_faq_group_highlight'] ) ) : array();

		$keep_ids = array();

		$count = max( count( $questions ), count( $answers ), count( $ids ) );

		for ( $i = 0; $i < $count; $i++ ) {
			$id       = isset( $ids[ $i ] ) ? (int) $ids[ $i ] : 0;
			$question = isset( $questions[ $i ] ) ? $questions[ $i ] : '';
			$answer   = isset( $answers[ $i ] ) ? $answers[ $i ] : '';

			// Skip empty entries.
			if ( '' === trim( $question ) && '' === trim( wp_strip_all_tags( $answer ) ) ) {
				continue;
			}

			$status        = isset( $visible[ (string) $i ] ) ? 1 : 0;
			$initial_state = isset( $open[ (string) $i ] ) ? 1 : 0;
			$is_highlight  = isset( $highlight[ (string) $i ] ) ? 1 : 0;

			$new_id     = Repository::save_item( $id, $post_id, $question, $answer, $status, $i, $initial_state, $is_highlight );
			$keep_ids[] = $new_id;
		}

		Repository::delete_all_except( $keep_ids, $post_id );

		// Save Theme
		if ( isset( $_POST['nlf_faq_group_theme'] ) ) {
			update_post_meta( $post_id, '_nlf_faq_group_theme', sanitize_text_field( wp_unslash( $_POST['nlf_faq_group_theme'] ) ) );
		}

		if ( isset( $_POST['nlf_faq_group_theme_custom'] ) && is_array( $_POST['nlf_faq_group_theme_custom'] ) ) {
			$theme_custom = array_map( 'sanitize_hex_color', wp_unslash( $_POST['nlf_faq_group_theme_custom'] ) );
			update_post_meta( $post_id, '_nlf_faq_group_theme_custom', $theme_custom );
		}

		// Save Settings
		if ( isset( $_POST['nlf_faq_group_settings'] ) && is_array( $_POST['nlf_faq_group_settings'] ) ) {
			$settings = wp_unslash( $_POST['nlf_faq_group_settings'] );
			$sanitized_settings = array(
				'accordion_mode'  => ! empty( $settings['accordion_mode'] ),
				'initial_state'   => in_array( $settings['initial_state'] ?? '', array( 'all_closed', 'first_open', 'custom' ), true ) ? $settings['initial_state'] : 'all_closed',
				'animation_speed' => in_array( $settings['animation_speed'] ?? '', array( 'fast', 'normal', 'slow' ), true ) ? $settings['animation_speed'] : 'normal',
				'show_search'     => ! empty( $settings['show_search'] ),
				'show_counter'    => ! empty( $settings['show_counter'] ),
				'smooth_scroll'   => ! empty( $settings['smooth_scroll'] ),
			);
			update_post_meta( $post_id, '_nlf_faq_group_settings', $sanitized_settings );
		}

		// Save Custom Style Settings
		$use_custom_style = ! empty( $_POST['nlf_faq_group_use_custom_style'] );
		update_post_meta( $post_id, '_nlf_faq_group_use_custom_style', $use_custom_style );

		if ( $use_custom_style && isset( $_POST['nlf_faq_group_custom_styles'] ) && is_array( $_POST['nlf_faq_group_custom_styles'] ) ) {
			$custom_styles = Options::sanitize( wp_unslash( $_POST['nlf_faq_group_custom_styles'] ) );
			update_post_meta( $post_id, '_nlf_faq_group_custom_styles', $custom_styles );

			// Generate group-specific CSS
			if ( class_exists( 'Krslys\NextLevelFaq\Style_Generator' ) ) {
				Style_Generator::generate_and_save_for_group( $post_id, $custom_styles );
			}
		} else {
			// Remove group-specific CSS if custom styles disabled
			delete_post_meta( $post_id, '_nlf_faq_group_custom_styles' );
			if ( class_exists( 'Krslys\NextLevelFaq\Style_Generator' ) ) {
				Style_Generator::delete_group_css( $post_id );
			}
		}
	}

	/**
	 * Handle deletion of a group.
	 *
	 * SECURITY: Validates post type before deletion.
	 *
	 * @param int $post_id Post ID.
	 */
public static function handle_delete( $post_id ) {
	$post = get_post( $post_id );

	if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return;
		}

		Repository::delete_items_for_group( $post_id );
	}

	/**
	 * AJAX handler for live preview.
	 *
	 * SECURITY: Nonce verification and capability check.
	 */
	public static function ajax_get_group_preview() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'nlf_group_preview' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'next-level-faq' ) ) );
		}

		// Check capability
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'next-level-faq' ) ) );
		}

		$group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;

		if ( ! $group_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid group ID.', 'next-level-faq' ) ) );
		}

		// Get group items
		$items = Repository::get_items_for_group( $group_id );

		if ( empty( $items ) ) {
			wp_send_json_success(
				array(
					'html' => '<div class="nlf-live-view-empty"><p>' . esc_html__( 'No FAQ items found. Add some items in the FAQ Items tab.', 'next-level-faq' ) . '</p></div>',
				)
			);
		}

		// Get settings
		$settings         = get_post_meta( $group_id, '_nlf_faq_group_settings', true );
		$use_custom_style = get_post_meta( $group_id, '_nlf_faq_group_use_custom_style', true );

		if ( ! is_array( $settings ) ) {
			$settings = self::get_default_settings();
		}

		// Build FAQ HTML
		ob_start();
		?>
		<div class="nlf-faq nlf-faq--preview" data-group-id="<?php echo esc_attr( $group_id ); ?>">
			<?php if ( ! empty( $settings['show_search'] ) ) : ?>
				<div class="nlf-faq-search">
					<input type="text" class="nlf-faq-search-input" placeholder="<?php esc_attr_e( 'Search FAQs...', 'next-level-faq' ); ?>" />
				</div>
			<?php endif; ?>

			<?php foreach ( $items as $index => $item ) : ?>
				<?php
				$is_open      = ! empty( $item->initial_state ) && 'custom' === ( $settings['initial_state'] ?? 'all_closed' );
				$is_first     = 0 === $index && 'first_open' === ( $settings['initial_state'] ?? 'all_closed' );
				$is_highlight = ! empty( $item->highlight );
				?>
				<div class="nlf-faq__item <?php echo ( $is_open || $is_first ) ? 'is-open' : ''; ?> <?php echo $is_highlight ? 'is-highlighted' : ''; ?>" data-faq-id="<?php echo esc_attr( $item->id ); ?>">
					<div class="nlf-faq__question">
						<?php if ( ! empty( $settings['show_counter'] ) ) : ?>
							<span class="nlf-faq__counter"><?php echo esc_html( $index + 1 ); ?>.</span>
						<?php endif; ?>
						<span><?php echo esc_html( $item->question ); ?></span>
						<span class="nlf-faq__icon" aria-hidden="true"></span>
					</div>
					<div class="nlf-faq__answer">
						<?php echo wp_kses_post( $item->answer ); ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}
}
