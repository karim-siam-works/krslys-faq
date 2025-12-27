<?php
/**
 * Admin settings page and assets.
 *
 * @package Krslys\NextLevelFaq
 */

namespace Krslys\NextLevelFaq;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin settings page and assets.
 *
 * SECURITY FEATURES:
 * - All admin actions require 'manage_options' capability.
 * - All forms protected with nonce verification.
 * - File uploads thoroughly validated (MIME type, size, extension).
 * - All inputs sanitized, all outputs escaped.
 * - Uses WordPress Filesystem API for file operations.
 */
class Admin_Settings {

	/**
	 * Top-level menu slug.
	 */
	const TOP_MENU_SLUG = 'nlf-faq';

	/**
	 * Style page slug.
	 */
	const STYLE_SLUG = 'nlf-faq-style';

	/**
	 * Questions page slug.
	 */
	const QUESTIONS_SLUG = 'nlf-faq-questions';

	/**
	 * Tools page slug.
	 */
	const TOOLS_SLUG = 'nlf-faq-tools';

	/**
	 * Register admin menu.
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'Next Level FAQ', 'next-level-faq' ),
			__( 'FAQs', 'next-level-faq' ),
			'manage_options',
			self::TOP_MENU_SLUG,
			array( __CLASS__, 'render_style_page' ),
			'dashicons-editor-help',
			26
		);

		add_submenu_page(
			self::TOP_MENU_SLUG,
			__( 'FAQ Style & Layout', 'next-level-faq' ),
			__( 'Style & Layout', 'next-level-faq' ),
			'manage_options',
			self::STYLE_SLUG,
			array( __CLASS__, 'render_style_page' )
		);

		add_submenu_page(
			self::TOP_MENU_SLUG,
			__( 'FAQ Questions', 'next-level-faq' ),
			__( 'Questions', 'next-level-faq' ),
			'manage_options',
			self::QUESTIONS_SLUG,
			array( __CLASS__, 'render_questions_page' )
		);

		add_submenu_page(
			self::TOP_MENU_SLUG,
			__( 'FAQ Tools', 'next-level-faq' ),
			__( 'Tools', 'next-level-faq' ),
			'manage_options',
			self::TOOLS_SLUG,
			array( __CLASS__, 'render_tools_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public static function register_settings() {
		register_setting(
			'nlf_faq_style_group',
			Options::OPTION_KEY,
			array(
				'sanitize_callback' => array( 'Krslys\NextLevelFaq\Options', 'sanitize' ),
			)
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * SECURITY: Sanitizes $_GET['page'] before use.
	 *
	 * @param string $hook_suffix Current screen hook.
	 */
public static function enqueue_assets( $hook_suffix ) {
	if ( ! isset( $_GET['page'] ) ) {
		return;
	}

	$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );

	$allowed_pages = array(
			self::STYLE_SLUG,
			self::QUESTIONS_SLUG,
			self::TOP_MENU_SLUG,
			self::TOOLS_SLUG,
		);

		if ( ! in_array( $page, $allowed_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'nlf-faq-admin',
			NLF_FAQ_PLUGIN_URL . 'assets/css/admin-faq-style.css',
			array(),
			NLF_FAQ_VERSION
		);

		// Enqueue generated CSS for style page preview.
		if ( in_array( $page, array( self::STYLE_SLUG, self::TOP_MENU_SLUG ), true ) ) {
			$css_path = Style_Generator::get_css_file_path();
			$css_url  = Style_Generator::get_css_file_url();
			if ( $css_url && $css_path && file_exists( $css_path ) ) {
				wp_enqueue_style(
					'nlf-faq-generated',
					esc_url_raw( $css_url ),
					array( 'nlf-faq-admin' ),
					filemtime( $css_path )
				);
			}
		}

		// Enqueue WordPress color picker for style page only.
		if ( in_array( $page, array( self::STYLE_SLUG, self::TOP_MENU_SLUG ), true ) ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script(
				'nlf-faq-admin',
				NLF_FAQ_PLUGIN_URL . 'assets/js/admin-faq-style.js',
				array( 'jquery', 'wp-color-picker' ),
				NLF_FAQ_VERSION,
				true
			);
		} else {
			wp_enqueue_script(
				'nlf-faq-admin',
				NLF_FAQ_PLUGIN_URL . 'assets/js/admin-faq-style.js',
				array( 'jquery' ),
				NLF_FAQ_VERSION,
				true
			);
		}

		if ( self::QUESTIONS_SLUG === $page ) {
			wp_enqueue_script(
				'nlf-faq-admin-questions',
				NLF_FAQ_PLUGIN_URL . 'assets/js/admin-faq-questions.js',
				array( 'jquery' ),
				NLF_FAQ_VERSION,
				true
			);
		}

		wp_localize_script(
			'nlf-faq-admin',
			'nlfFaqAdmin',
			array(
				'i18n' => array(
					'saving' => __( 'Saving…', 'next-level-faq' ),
					'saved'  => __( 'Saved', 'next-level-faq' ),
				),
			)
		);
	}

	/**
	 * Render style settings page.
	 *
	 * SECURITY: Capability check at start of function.
	 */
	public static function render_style_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = Options::get_options();
		?>
		<div class="wrap nlf-faq-admin">
			<h1><?php esc_html_e( 'Next Level FAQ – Style & Layout', 'next-level-faq' ); ?></h1>

			<div class="nlf-faq-admin__layout">
				<div class="nlf-faq-admin__left">
					<form method="post" action="options.php" id="nlf-faq-style-form">
						<?php
						settings_fields( 'nlf_faq_style_group' );
						?>

						<h2><?php esc_html_e( 'Layout & Container', 'next-level-faq' ); ?></h2>

						<table class="form-table" role="presentation">
							<tr>
								<th scope="row">
									<label for="nlf_faq_container_background"><?php esc_html_e( 'Container background', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<input type="text" class="nlf-color-field" id="nlf_faq_container_background" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[container_background]" value="<?php echo esc_attr( $options['container_background'] ); ?>" data-preview-prop="container_background">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="nlf_faq_container_border_color"><?php esc_html_e( 'Border color', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<input type="text" class="nlf-color-field" id="nlf_faq_container_border_color" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[container_border_color]" value="<?php echo esc_attr( $options['container_border_color'] ); ?>" data-preview-prop="container_border_color">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="nlf_faq_container_border_radius"><?php esc_html_e( 'Border radius (px)', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<input type="number" min="0" id="nlf_faq_container_border_radius" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[container_border_radius]" value="<?php echo esc_attr( $options['container_border_radius'] ); ?>" data-preview-prop="container_border_radius">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="nlf_faq_container_padding"><?php esc_html_e( 'Padding (px)', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<input type="number" min="0" id="nlf_faq_container_padding" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[container_padding]" value="<?php echo esc_attr( $options['container_padding'] ); ?>" data-preview-prop="container_padding">
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Shadow', 'next-level-faq' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[shadow]" value="1" <?php checked( $options['shadow'], true ); ?> data-preview-prop="shadow">
										<?php esc_html_e( 'Enable subtle card shadow', 'next-level-faq' ); ?>
									</label>
								</td>
							</tr>
						</table>

						<h2><?php esc_html_e( 'Question', 'next-level-faq' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row">
									<label for="nlf_faq_question_color"><?php esc_html_e( 'Question color', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<input type="text" class="nlf-color-field" id="nlf_faq_question_color" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[question_color]" value="<?php echo esc_attr( $options['question_color'] ); ?>" data-preview-prop="question_color">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="nlf_faq_question_font_size"><?php esc_html_e( 'Font size (px)', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<input type="number" min="10" id="nlf_faq_question_font_size" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[question_font_size]" value="<?php echo esc_attr( $options['question_font_size'] ); ?>" data-preview-prop="question_font_size">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="nlf_faq_question_font_weight"><?php esc_html_e( 'Font weight', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<input type="number" step="100" min="100" max="900" id="nlf_faq_question_font_weight" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[question_font_weight]" value="<?php echo esc_attr( $options['question_font_weight'] ); ?>" data-preview-prop="question_font_weight">
								</td>
							</tr>
						</table>

						<h2><?php esc_html_e( 'Answer', 'next-level-faq' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row">
									<label for="nlf_faq_answer_color"><?php esc_html_e( 'Answer color', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<input type="text" class="nlf-color-field" id="nlf_faq_answer_color" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[answer_color]" value="<?php echo esc_attr( $options['answer_color'] ); ?>" data-preview-prop="answer_color">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="nlf_faq_answer_font_size"><?php esc_html_e( 'Font size (px)', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<input type="number" min="10" id="nlf_faq_answer_font_size" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[answer_font_size]" value="<?php echo esc_attr( $options['answer_font_size'] ); ?>" data-preview-prop="answer_font_size">
								</td>
							</tr>
						</table>

						<h2><?php esc_html_e( 'Accent & Behavior', 'next-level-faq' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row">
									<label for="nlf_faq_accent_color"><?php esc_html_e( 'Accent color', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<input type="text" class="nlf-color-field" id="nlf_faq_accent_color" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[accent_color]" value="<?php echo esc_attr( $options['accent_color'] ); ?>" data-preview-prop="accent_color">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="nlf_faq_icon_style"><?php esc_html_e( 'Icon style', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<select id="nlf_faq_icon_style" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[icon_style]" data-preview-prop="icon_style">
										<option value="plus_minus" <?php selected( $options['icon_style'], 'plus_minus' ); ?>><?php esc_html_e( 'Plus / Minus', 'next-level-faq' ); ?></option>
										<option value="chevron" <?php selected( $options['icon_style'], 'chevron' ); ?>><?php esc_html_e( 'Chevron', 'next-level-faq' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="nlf_faq_gap_between_items"><?php esc_html_e( 'Gap between items (px)', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<input type="number" min="0" id="nlf_faq_gap_between_items" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[gap_between_items]" value="<?php echo esc_attr( $options['gap_between_items'] ); ?>" data-preview-prop="gap_between_items">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="nlf_faq_animation"><?php esc_html_e( 'Animation', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<select id="nlf_faq_animation" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[animation]" data-preview-prop="animation">
										<option value="slide" <?php selected( $options['animation'], 'slide' ); ?>><?php esc_html_e( 'Slide', 'next-level-faq' ); ?></option>
										<option value="fade" <?php selected( $options['animation'], 'fade' ); ?>><?php esc_html_e( 'Fade', 'next-level-faq' ); ?></option>
										<option value="none" <?php selected( $options['animation'], 'none' ); ?>><?php esc_html_e( 'None', 'next-level-faq' ); ?></option>
									</select>
								</td>
							</tr>
						</table>

						<?php submit_button( __( 'Save Styles', 'next-level-faq' ) ); ?>
					</form>
				</div>

				<div class="nlf-faq-admin__right">
					<?php Admin_UI_Components::mobile_preview_notice(); ?>
					<h2><?php esc_html_e( 'Live Preview', 'next-level-faq' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Preview shows how your FAQ will look with the current style settings.', 'next-level-faq' ); ?></p>

					<div id="nlf-faq-preview-root"
						data-container-background="<?php echo esc_attr( $options['container_background'] ); ?>"
						data-container-border-color="<?php echo esc_attr( $options['container_border_color'] ); ?>"
						data-container-border-radius="<?php echo esc_attr( $options['container_border_radius'] ); ?>"
						data-container-padding="<?php echo esc_attr( $options['container_padding'] ); ?>"
						data-question-color="<?php echo esc_attr( $options['question_color'] ); ?>"
						data-question-font-size="<?php echo esc_attr( $options['question_font_size'] ); ?>"
						data-question-font-weight="<?php echo esc_attr( $options['question_font_weight'] ); ?>"
						data-answer-color="<?php echo esc_attr( $options['answer_color'] ); ?>"
						data-answer-font-size="<?php echo esc_attr( $options['answer_font_size'] ); ?>"
						data-accent-color="<?php echo esc_attr( $options['accent_color'] ); ?>"
						data-gap-between-items="<?php echo esc_attr( $options['gap_between_items'] ); ?>"
						data-shadow="<?php echo esc_attr( $options['shadow'] ? '1' : '0' ); ?>"
						data-icon-style="<?php echo esc_attr( $options['icon_style'] ); ?>"
						data-animation="<?php echo esc_attr( $options['animation'] ); ?>"
					>
						<div class="nlf-faq nlf-faq--preview">
							<div class="nlf-faq__item is-open">
								<div class="nlf-faq__question">
									<span><?php esc_html_e( 'How quickly can I customize my FAQs?', 'next-level-faq' ); ?></span>
									<span class="nlf-faq__icon" aria-hidden="true"></span>
								</div>
								<div class="nlf-faq__answer">
									<p><?php esc_html_e( 'Changes you make here are applied instantly and reflected on the front-end as soon as you save.', 'next-level-faq' ); ?></p>
								</div>
							</div>
							<div class="nlf-faq__item">
								<div class="nlf-faq__question">
									<span><?php esc_html_e( 'Can I match my brand colors?', 'next-level-faq' ); ?></span>
									<span class="nlf-faq__icon" aria-hidden="true"></span>
								</div>
								<div class="nlf-faq__answer">
									<p><?php esc_html_e( 'Yes. Configure colors, typography, spacing, and animations to align with your brand.', 'next-level-faq' ); ?></p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render questions repeater management page.
	 *
	 * SECURITY: Capability check at start of function.
	 */
	public static function render_questions_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$items = Repository::get_all_items( 0 );
		?>
		<div class="wrap nlf-faq-admin">
			<h1><?php esc_html_e( 'Next Level FAQ – Questions', 'next-level-faq' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Manage the list of questions and answers that will appear in your FAQ sections. Use the checkboxes to control which items are visible.', 'next-level-faq' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="nlf-faq-questions-form">
				<?php wp_nonce_field( 'nlf_faq_save_questions', 'nlf_faq_questions_nonce' ); ?>
				<input type="hidden" name="action" value="nlf_faq_save_questions" />

				<table class="widefat fixed striped nlf-faq-questions-table">
					<thead>
						<tr>
							<th style="width:40px;"></th>
							<th style="width:35%;"><?php esc_html_e( 'Question', 'next-level-faq' ); ?></th>
							<th><?php esc_html_e( 'Answer', 'next-level-faq' ); ?></th>
							<th style="width:80px;"><?php esc_html_e( 'Visible', 'next-level-faq' ); ?></th>
							<th style="width:80px;"><?php esc_html_e( 'Actions', 'next-level-faq' ); ?></th>
						</tr>
					</thead>
					<tbody id="nlf-faq-questions-body">
						<?php if ( ! empty( $items ) ) : ?>
							<?php foreach ( $items as $index => $item ) : ?>
								<tr class="nlf-faq-question-row">
									<td class="nlf-faq-sort-handle">⋮⋮</td>
									<td>
										<input type="hidden" name="nlf_faq_id[]" value="<?php echo esc_attr( $item->id ); ?>" />
										<input type="text" class="regular-text" name="nlf_faq_question[]" value="<?php echo esc_attr( $item->question ); ?>" placeholder="<?php esc_attr_e( 'Question', 'next-level-faq' ); ?>" />
									</td>
									<td>
										<textarea name="nlf_faq_answer[]" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Answer', 'next-level-faq' ); ?>"><?php echo esc_textarea( $item->answer ); ?></textarea>
									</td>
									<td class="nlf-faq-visible-cell">
										<label>
											<input type="checkbox" name="nlf_faq_active[<?php echo esc_attr( $index ); ?>]" value="1" <?php checked( (int) $item->status, 1 ); ?> />
											<?php esc_html_e( 'Show', 'next-level-faq' ); ?>
										</label>
									</td>
									<td class="nlf-faq-actions-cell">
										<button type="button" class="button-link nlf-faq-remove-row"><?php esc_html_e( 'Remove', 'next-level-faq' ); ?></button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
					<tfoot>
						<tr>
							<td colspan="5">
								<button type="button" class="button button-secondary" id="nlf-faq-add-row">
									<?php esc_html_e( 'Add Question', 'next-level-faq' ); ?>
								</button>
							</td>
						</tr>
					</tfoot>
				</table>

				<?php submit_button( __( 'Save Questions', 'next-level-faq' ) ); ?>
			</form>

			<script type="text/template" id="tmpl-nlf-faq-row">
				<tr class="nlf-faq-question-row">
					<td class="nlf-faq-sort-handle">⋮⋮</td>
					<td>
						<input type="hidden" name="nlf_faq_id[]" value="" />
						<input type="text" class="regular-text" name="nlf_faq_question[]" value="" placeholder="<?php esc_attr_e( 'Question', 'next-level-faq' ); ?>" />
					</td>
					<td>
						<textarea name="nlf_faq_answer[]" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Answer', 'next-level-faq' ); ?>"></textarea>
					</td>
					<td class="nlf-faq-visible-cell">
						<label>
							<input type="checkbox" name="nlf_faq_active[{{index}}]" value="1" checked="checked" />
							<?php esc_html_e( 'Show', 'next-level-faq' ); ?>
						</label>
					</td>
					<td class="nlf-faq-actions-cell">
						<button type="button" class="button-link nlf-faq-remove-row"><?php esc_html_e( 'Remove', 'next-level-faq' ); ?></button>
					</td>
				</tr>
			</script>
		</div>
		<?php
	}

	/**
	 * Render export/import tools page.
	 *
	 * SECURITY: Capability check at start of function.
	 *
	 * @return void
	 */
	public static function render_tools_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$group_choices   = self::get_group_choices();
		$selected_group  = 'all';
		?>
		<div class="wrap nlf-faq-admin nlf-faq-tools">
			<h1><?php esc_html_e( 'Next Level FAQ – Tools', 'next-level-faq' ); ?></h1>
			<?php self::output_tools_notice(); ?>

			<div class="nlf-faq-tools__grid">
				<section class="nlf-faq-tools__card">
					<h2><?php esc_html_e( 'Export', 'next-level-faq' ); ?></h2>
					<p class="description">
						<?php esc_html_e( 'Generate a JSON bundle with your FAQ styles and entries for backups or migrations.', 'next-level-faq' ); ?>
					</p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="nlf-faq-tools__form">
						<?php wp_nonce_field( 'nlf_faq_export', 'nlf_faq_export_nonce' ); ?>
						<input type="hidden" name="action" value="nlf_faq_export" />

						<label class="nlf-faq-tools__option">
							<input type="checkbox" name="nlf_faq_include_styles" value="1" checked="checked" />
							<span><?php esc_html_e( 'Include style settings', 'next-level-faq' ); ?></span>
						</label>

						<label class="nlf-faq-tools__option">
							<input type="checkbox" name="nlf_faq_include_questions" value="1" checked="checked" />
							<span><?php esc_html_e( 'Include FAQ entries', 'next-level-faq' ); ?></span>
						</label>

						<label for="nlf-faq-export-group" class="nlf-faq-tools__field-label">
							<?php esc_html_e( 'Limit FAQ export to a specific group', 'next-level-faq' ); ?>
						</label>
						<select id="nlf-faq-export-group" name="nlf_faq_export_group" class="nlf-faq-tools__select">
							<?php foreach ( $group_choices as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $selected_group ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Choose "All groups" to export every FAQ entry.', 'next-level-faq' ); ?>
						</p>

						<?php submit_button( __( 'Download Export', 'next-level-faq' ), 'primary', 'submit', false ); ?>
					</form>
				</section>

				<section class="nlf-faq-tools__card">
					<h2><?php esc_html_e( 'Import', 'next-level-faq' ); ?></h2>
					<p class="description">
						<?php esc_html_e( 'Upload an export file from this or another site to synchronize FAQs safely.', 'next-level-faq' ); ?>
					</p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="nlf-faq-tools__form" enctype="multipart/form-data">
						<?php wp_nonce_field( 'nlf_faq_import', 'nlf_faq_import_nonce' ); ?>
						<input type="hidden" name="action" value="nlf_faq_import" />

						<label for="nlf-faq-import-file" class="nlf-faq-tools__field-label">
							<?php esc_html_e( 'Select export file (.json)', 'next-level-faq' ); ?>
						</label>
						<input type="file" id="nlf-faq-import-file" name="nlf_faq_import_file" accept=".json,application/json" required />

						<label class="nlf-faq-tools__option">
							<input type="checkbox" name="nlf_faq_replace_existing" value="1" />
							<span><?php esc_html_e( 'Replace current FAQ entries before import', 'next-level-faq' ); ?></span>
						</label>
						<p class="description">
							<?php esc_html_e( 'Enable this to wipe the FAQ table before importing to avoid duplicates.', 'next-level-faq' ); ?>
						</p>

						<?php submit_button( __( 'Import Package', 'next-level-faq' ), 'primary', 'submit', false ); ?>
					</form>
				</section>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle saving questions from repeater UI.
	 *
	 * SECURITY:
	 * - Capability check: current_user_can('manage_options').
	 * - Nonce verification: wp_verify_nonce().
	 * - Input sanitization: sanitize_text_field(), wp_kses_post().
	 */
public static function handle_save_questions() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage FAQs.', 'next-level-faq' ) );
	}

	if ( ! isset( $_POST['nlf_faq_questions_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nlf_faq_questions_nonce'] ) ), 'nlf_faq_save_questions' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'next-level-faq' ) );
	}

	$ids       = isset( $_POST['nlf_faq_id'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['nlf_faq_id'] ) ) : array();
		$questions = isset( $_POST['nlf_faq_question'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['nlf_faq_question'] ) ) : array();
		$answers   = isset( $_POST['nlf_faq_answer'] ) ? array_map( 'wp_kses_post', wp_unslash( (array) $_POST['nlf_faq_answer'] ) ) : array();
		$active    = isset( $_POST['nlf_faq_active'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['nlf_faq_active'] ) ) : array();

		$keep_ids = array();

		$count = max( count( $questions ), count( $answers ), count( $ids ) );

		for ( $i = 0; $i < $count; $i++ ) {
			$id       = isset( $ids[ $i ] ) ? (int) $ids[ $i ] : 0;
			$question = isset( $questions[ $i ] ) ? $questions[ $i ] : '';
			$answer   = isset( $answers[ $i ] ) ? $answers[ $i ] : '';

			if ( '' === trim( $question ) && '' === trim( wp_strip_all_tags( $answer ) ) ) {
				continue;
			}

			$status   = isset( $active[ (string) $i ] ) ? 1 : 0;

			$new_id     = Repository::save_item( $id, 0, $question, $answer, $status, $i );
			$keep_ids[] = $new_id;
		}

		Repository::delete_all_except( $keep_ids, 0 );

		$redirect = add_query_arg(
			array(
				'page'    => self::QUESTIONS_SLUG,
				'updated' => 'true',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Export FAQ data as JSON.
	 *
	 * SECURITY:
	 * - Capability check: current_user_can('manage_options').
	 * - Nonce verification: check_admin_referer().
	 * - Output sanitization: wp_json_encode() handles escaping.
	 *
	 * @return void
	 */
public static function handle_export() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to export FAQs.', 'next-level-faq' ) );
	}

	check_admin_referer( 'nlf_faq_export', 'nlf_faq_export_nonce' );

		$include_styles    = self::get_checkbox_state_from_post( 'nlf_faq_include_styles' );
		$include_questions = self::get_checkbox_state_from_post( 'nlf_faq_include_questions' );
		$group_choice      = isset( $_POST['nlf_faq_export_group'] )
			? sanitize_text_field( wp_unslash( $_POST['nlf_faq_export_group'] ) )
			: 'all';
		$group_scope       = self::normalize_group_choice( $group_choice );

		if ( ! $include_styles && ! $include_questions ) {
			self::store_tools_notice( 'error', __( 'Select at least one component to export.', 'next-level-faq' ) );
			wp_safe_redirect( self::get_tools_page_url() );
			exit;
		}

		$payload = array(
			'meta' => array(
				'schema'         => 'nlf-faq-tools.v1',
				'plugin_version' => NLF_FAQ_VERSION,
				'db_version'     => get_option( 'nlf_faq_db_version', NLF_FAQ_DB_VERSION ),
				'site_url'       => home_url(),
				'generated_at'   => gmdate( 'c' ),
			),
		);

		if ( $include_styles ) {
			$payload['styles'] = Options::get_options();
		}

		if ( $include_questions ) {
			$payload['meta']['group_scope']       = null === $group_scope ? 'all' : (int) $group_scope;
			$payload['meta']['group_scope_label'] = self::get_group_label( $group_scope );
			$payload['faqs']                      = self::group_faq_export_items(
				Repository::get_all_items_for_export( $group_scope )
		);
	}

	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	$filename_parts = array( 'next-level-faq-export' );

	if ( $include_questions && null !== $group_scope ) {
		$filename_parts[] = 'group-' . (int) $group_scope;
	}

	$filename = sanitize_file_name( implode( '-', $filename_parts ) . '-' . gmdate( 'Ymd-His' ) . '.json' );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'X-Export-Context: nlf-faq' );

	echo wp_json_encode(
			$payload,
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);
		exit;
	}

	/**
	 * Import FAQ data from JSON.
	 *
	 * SECURITY:
	 * - Capability check: current_user_can('manage_options').
	 * - Nonce verification: check_admin_referer().
	 * - File upload validation: MIME type, extension, size checks.
	 * - Input sanitization: All imported data sanitized before use.
	 *
	 * @return void
	 */
public static function handle_import() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to import FAQs.', 'next-level-faq' ) );
	}

	check_admin_referer( 'nlf_faq_import', 'nlf_faq_import_nonce' );

	$page_url = self::get_tools_page_url();

	if ( empty( $_FILES['nlf_faq_import_file'] ) ) {
		self::store_tools_notice( 'error', __( 'Upload an export file before running import.', 'next-level-faq' ) );
		wp_safe_redirect( $page_url );
		exit;
	}

	$file = self::validate_json_file_upload( $_FILES['nlf_faq_import_file'] );

	if ( false === $file ) {
		if ( isset( $_FILES['nlf_faq_import_file']['error'] ) && (int) $_FILES['nlf_faq_import_file']['error'] !== UPLOAD_ERR_OK ) {
			self::store_tools_notice( 'error', self::describe_upload_error( (int) $_FILES['nlf_faq_import_file']['error'] ) );
		} elseif ( isset( $_FILES['nlf_faq_import_file']['size'] ) && (int) $_FILES['nlf_faq_import_file']['size'] > ( defined( 'MB_IN_BYTES' ) ? 2 * MB_IN_BYTES : 2 * 1024 * 1024 ) ) {
			self::store_tools_notice( 'error', __( 'Import file is too large. Please keep exports under 2MB.', 'next-level-faq' ) );
		} else {
			self::store_tools_notice( 'error', __( 'Only JSON files exported by this plugin are allowed.', 'next-level-faq' ) );
		}
		wp_safe_redirect( $page_url );
		exit;
	}

	$data = self::decode_import_file( $file['tmp_name'] );

		if ( null === $data ) {
			self::store_tools_notice( 'error', __( 'The uploaded file is not a valid export.', 'next-level-faq' ) );
			wp_safe_redirect( $page_url );
			exit;
		}

		$replace_existing = self::get_checkbox_state_from_post( 'nlf_faq_replace_existing' );
		$imported_count   = 0;
		$styles_applied   = false;

		$faq_entries = self::normalize_import_faqs( $data['faqs'] ?? array() );
		if ( ! empty( $faq_entries ) ) {
			if ( $replace_existing ) {
				Repository::delete_all_items();
			}

			foreach ( $faq_entries as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			// Sanitize inputs to prevent XSS - match normal save_metabox() behavior.
			$question = isset( $item['question'] ) ? sanitize_text_field( $item['question'] ) : '';
				$answer   = isset( $item['answer'] ) ? wp_kses_post( $item['answer'] ) : '';

				$trimmed_question = trim( wp_strip_all_tags( $question ) );
				$trimmed_answer   = trim( wp_strip_all_tags( $answer ) );

				if ( '' === $trimmed_question && '' === $trimmed_answer ) {
					continue;
				}

				$group_id      = isset( $item['group_id'] ) ? absint( $item['group_id'] ) : 0;
				$position      = isset( $item['position'] ) ? absint( $item['position'] ) : (int) $index;
				$status        = isset( $item['status'] ) ? absint( $item['status'] ) : 0;
				$initial_state = isset( $item['initial_state'] ) ? absint( $item['initial_state'] ) : 0;
				$highlight     = isset( $item['highlight'] ) ? absint( $item['highlight'] ) : 0;

				Repository::save_item(
					0,
					$group_id,
					$question,
					$answer,
					$status,
					$position,
					$initial_state,
					$highlight
				);

				$imported_count++;
			}
	}

	if ( isset( $data['styles'] ) && is_array( $data['styles'] ) ) {
		$sanitized = Options::sanitize( $data['styles'] );
			update_option( Options::OPTION_KEY, $sanitized );
			$styles_applied = true;
		}

		if ( 0 === $imported_count && ! $styles_applied ) {
			self::store_tools_notice( 'error', __( 'Nothing was imported. Ensure the file contains FAQ entries or style settings.', 'next-level-faq' ) );
			wp_safe_redirect( $page_url );
			exit;
		}

		$message_bits = array();

		if ( $imported_count > 0 ) {
			$message_bits[] = sprintf(
				/* translators: %d: number of imported FAQs */
				_n( '%d FAQ item imported.', '%d FAQ items imported.', $imported_count, 'next-level-faq' ),
				$imported_count
			);
		}

		if ( $styles_applied ) {
			$message_bits[] = __( 'Style settings synced.', 'next-level-faq' );
		}

		self::store_tools_notice( 'success', implode( ' ', $message_bits ) );
		wp_safe_redirect( $page_url );
		exit;
	}

	/**
	 * Persist notice data between redirects.
	 *
	 * SECURITY: Message is sanitized via wp_strip_all_tags().
	 *
	 * @param string $type    Notice severity.
	 * @param string $message Message text.
	 *
	 * @return void
	 */
private static function store_tools_notice( $type, $message ) {
	$allowed = array( 'success', 'error', 'warning', 'info' );
		$type    = in_array( $type, $allowed, true ) ? $type : 'info';

		set_transient(
			self::get_tools_notice_key(),
			array(
				'type'    => $type,
				'message' => wp_strip_all_tags( (string) $message ),
			),
			MINUTE_IN_SECONDS
		);
	}

	/**
	 * Output notice stored in transient.
	 *
	 * SECURITY: Output escaped via esc_attr() and esc_html().
	 *
	 * @return void
	 */
	private static function output_tools_notice() {
		$notice = self::consume_tools_notice();

		if ( null === $notice ) {
			return;
		}

		$class_map = array(
			'success' => 'notice-success',
			'error'   => 'notice-error',
			'warning' => 'notice-warning',
			'info'    => 'notice-info',
		);

		printf(
			'<div class="notice %1$s"><p>%2$s</p></div>',
			esc_attr( $class_map[ $notice['type'] ] ?? 'notice-info' ),
			esc_html( $notice['message'] )
		);
	}

	/**
	 * Retrieve and clear stored notice.
	 *
	 * @return array|null
	 */
	private static function consume_tools_notice() {
		$key    = self::get_tools_notice_key();
		$notice = get_transient( $key );

		if ( false === $notice || ! is_array( $notice ) ) {
			return null;
		}

		delete_transient( $key );

		if ( empty( $notice['type'] ) || empty( $notice['message'] ) ) {
			return null;
		}

		return array(
			'type'    => sanitize_key( $notice['type'] ),
			'message' => (string) $notice['message'],
		);
	}

	/**
	 * Build URL to tools page.
	 *
	 * @return string
	 */
	private static function get_tools_page_url() {
		return add_query_arg(
			array(
				'page' => self::TOOLS_SLUG,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Validate JSON file upload with comprehensive checks.
	 *
	 * SECURITY:
	 * - Validates file upload errors
	 * - Validates file size (2MB limit)
	 * - Validates file extension (.json)
	 * - Uses is_valid_json_upload() for MIME and content validation
	 *
	 * @param array  $file      Upload file array from $_FILES.
	 * @param string $error_key Optional error key for storing notices.
	 * @return array|false Returns file array on success, false on failure.
	 */
	public static function validate_json_file_upload( $file, $error_key = 'import_error' ) {
		if ( empty( $file ) || empty( $file['tmp_name'] ) ) {
			return false;
		}

		$filename = isset( $file['name'] ) ? sanitize_file_name( wp_unslash( $file['name'] ) ) : '';

		if ( isset( $file['error'] ) && (int) $file['error'] !== UPLOAD_ERR_OK ) {
			return false;
		}

		$size_limit = defined( 'MB_IN_BYTES' ) ? 2 * MB_IN_BYTES : 2 * 1024 * 1024;
		if ( isset( $file['size'] ) && (int) $file['size'] > $size_limit ) {
			return false;
		}

		$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		if ( 'json' !== $ext ) {
			return false;
		}

		if ( ! self::is_valid_json_upload( $file ) ) {
			return false;
		}

		return $file;
	}

	/**
	 * Human readable upload error.
	 *
	 * @param int $code PHP upload error code.
	 *
	 * @return string
	 */
	public static function describe_upload_error( $code ) {
		switch ( (int) $code ) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return __( 'The uploaded file exceeds the maximum allowed size.', 'next-level-faq' );
			case UPLOAD_ERR_PARTIAL:
				return __( 'The uploaded file was only partially uploaded. Please try again.', 'next-level-faq' );
			case UPLOAD_ERR_NO_FILE:
				return __( 'No file was uploaded.', 'next-level-faq' );
			case UPLOAD_ERR_NO_TMP_DIR:
				return __( 'Server configuration error: missing a temporary folder.', 'next-level-faq' );
			case UPLOAD_ERR_CANT_WRITE:
				return __( 'Server error: failed to write file to disk.', 'next-level-faq' );
			case UPLOAD_ERR_EXTENSION:
				return __( 'A PHP extension stopped the file upload.', 'next-level-faq' );
			default:
				return __( 'Unexpected upload error occurred.', 'next-level-faq' );
		}
	}

	/**
	 * Decode JSON import file using WordPress Filesystem API.
	 *
	 * SECURITY: Uses WordPress native functions for safe file reading.
	 *
	 * @param string $file_path Path to uploaded file.
	 *
	 * @return array|null
	 */
	public static function decode_import_file( $file_path ) {
		if ( ! is_readable( $file_path ) ) {
			return null;
		}

		// Try wp_json_file_decode if available (WordPress 5.9+).
		if ( function_exists( 'wp_json_file_decode' ) ) {
			$data = wp_json_file_decode(
				$file_path,
				array(
					'associative' => true,
				)
			);

			return is_array( $data ) ? $data : null;
		}

		// Fallback: Use WP_Filesystem API.
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		if ( ! $wp_filesystem || ! $wp_filesystem->exists( $file_path ) ) {
			return null;
		}

		$contents = $wp_filesystem->get_contents( $file_path );

		if ( false === $contents ) {
			return null;
		}

		$data = json_decode( $contents, true );

		return is_array( $data ) ? $data : null;
	}

	/**
	 * Transient key for notices.
	 *
	 * @return string
	 */
	private static function get_tools_notice_key() {
		return 'nlf_faq_tools_notice_' . get_current_user_id();
	}

	/**
	 * Normalize select value into group scope.
	 *
	 * @param string $choice Raw select value.
	 * @return int|null
	 */
	private static function normalize_group_choice( $choice ) {
		if ( 'all' === $choice || '' === $choice ) {
			return null;
		}

		if ( ! is_numeric( $choice ) ) {
			return null;
		}

		return max( 0, (int) $choice );
	}

	/**
	 * Format FAQ export items grouped by group ID.
	 *
	 * @param array $items Flat list of FAQ rows.
	 * @return array
	 */
	private static function group_faq_export_items( $items ) {
		if ( empty( $items ) || ! is_array( $items ) ) {
			return array();
		}

		$grouped = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$group_id = isset( $item['group_id'] ) ? (int) $item['group_id'] : 0;
			$key      = (string) $group_id;

			if ( ! isset( $grouped[ $key ] ) ) {
				$grouped[ $key ] = array();
			}

			$grouped[ $key ][] = $item;
		}

		ksort( $grouped, SORT_NUMERIC );

		return $grouped;
	}

	/**
	 * Read a checkbox-like value from $_POST and normalize to bool.
	 *
	 * SECURITY: Validates and sanitizes checkbox input.
	 *
	 * @param string $key Checkbox key.
	 * @return bool
	 */
	private static function get_checkbox_state_from_post( $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			return false;
		}

		$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );

		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		// Use filter_var for proper boolean validation.
		$validated = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

		if ( null !== $validated ) {
			return $validated;
		}

		return ! empty( $value );
	}

	/**
	 * Comprehensive JSON upload validation.
	 *
	 * SECURITY:
	 * - Validates MIME type using finfo.
	 * - Validates file extension against allowlist.
	 * - Validates file content starts with JSON structure.
	 * - Protects against directory traversal attacks.
	 *
	 * @param array $file Upload file array from $_FILES.
	 * @return bool
	 */
	public static function is_valid_json_upload( $file ) {
		if ( empty( $file['tmp_name'] ) || ! is_readable( $file['tmp_name'] ) ) {
		return false;
	}

	$filename = isset( $file['name'] ) ? sanitize_file_name( wp_unslash( $file['name'] ) ) : '';
		$ext      = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

		if ( 'json' !== $ext ) {
		return false;
	}

	$allowed_mimes = array(
			'application/json',
			'text/json',
			'text/plain', // Some servers report JSON as text/plain.
		);

		if ( function_exists( 'finfo_open' ) ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			if ( $finfo ) {
				$mime = finfo_file( $finfo, $file['tmp_name'] );
				finfo_close( $finfo );

				if ( $mime && ! in_array( $mime, $allowed_mimes, true ) ) {
					return false;
				}
		}
	}

	global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		if ( ! $wp_filesystem || ! $wp_filesystem->exists( $file['tmp_name'] ) ) {
			return false;
		}

		// Read first few bytes to check for JSON structure.
		$prefix = $wp_filesystem->get_contents( $file['tmp_name'] );

		if ( false === $prefix ) {
			return false;
		}

	// Get first 10 characters after trimming whitespace.
	$prefix = ltrim( substr( $prefix, 0, 10 ) );

	return '' !== $prefix && in_array( $prefix[0], array( '{', '[' ), true );
	}

	/**
	 * Flatten imported FAQ structures into a simple list.
	 *
	 * Supports both legacy flat arrays and new grouped objects.
	 *
	 * @param mixed $faqs_raw Raw FAQs block.
	 * @return array
	 */
	private static function normalize_import_faqs( $faqs_raw ) {
		if ( empty( $faqs_raw ) || ! is_array( $faqs_raw ) ) {
			return array();
		}

		if ( self::is_sequential_array( $faqs_raw ) ) {
			return array_values( $faqs_raw );
		}

		$normalized = array();

		foreach ( $faqs_raw as $group_id => $records ) {
			if ( ! is_array( $records ) ) {
				continue;
			}

			foreach ( $records as $record ) {
				if ( ! is_array( $record ) ) {
					continue;
				}

				if ( ! isset( $record['group_id'] ) && is_numeric( $group_id ) ) {
					$record['group_id'] = (int) $group_id;
				}

				$normalized[] = $record;
			}
		}

		return $normalized;
	}

	/**
	 * Check whether an array is sequential (0..n).
	 *
	 * @param array $array Array to inspect.
	 * @return bool
	 */
	private static function is_sequential_array( $array ) {
		if ( empty( $array ) || ! is_array( $array ) ) {
			return true;
		}

		$expected = 0;

		foreach ( $array as $key => $_value ) {
			if ( (string) (int) $key !== (string) $key || (int) $key !== $expected ) {
				return false;
			}

			$expected++;
		}

		return true;
	}

	/**
	 * Options for the export group selector.
	 *
	 * @return array
	 */
	private static function get_group_choices() {
		$choices = array(
			'all' => __( 'All groups', 'next-level-faq' ),
			'0'   => __( 'Global (legacy questions)', 'next-level-faq' ),
		);

		$groups = get_posts(
			array(
				'post_type'      => Group_CPT::POST_TYPE,
				'post_status'    => array( 'publish', 'pending', 'draft', 'future' ),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		foreach ( $groups as $group ) {
			$title = trim( get_the_title( $group ) );

			$choices[ (string) $group->ID ] = '' !== $title
				? $title
				: sprintf( __( 'Group #%d', 'next-level-faq' ), (int) $group->ID );
		}

		return $choices;
	}

	/**
	 * Retrieve human label for a group scope.
	 *
	 * @param int|null $group_id Group ID.
	 * @return string
	 */
	private static function get_group_label( $group_id ) {
		if ( null === $group_id ) {
			return __( 'All groups', 'next-level-faq' );
		}

		if ( 0 === (int) $group_id ) {
			return __( 'Global (legacy questions)', 'next-level-faq' );
		}

		$post = get_post( (int) $group_id );

		if ( $post && Group_CPT::POST_TYPE === $post->post_type ) {
			$title = trim( get_the_title( $post ) );

			return '' !== $title
				? $title
				: sprintf( __( 'Group #%d', 'next-level-faq' ), (int) $group_id );
		}

		return sprintf( __( 'Group #%d', 'next-level-faq' ), (int) $group_id );
	}
}
