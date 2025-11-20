<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin settings page and assets.
 */
class NLF_Faq_Admin {

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
	}

	/**
	 * Register settings.
	 */
	public static function register_settings() {
		register_setting(
			'nlf_faq_style_group',
			NLF_Faq_Options::OPTION_KEY,
			array(
				'sanitize_callback' => array( 'NLF_Faq_Options', 'sanitize' ),
			)
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix Current screen hook.
	 */
	public static function enqueue_assets( $hook_suffix ) {
		if ( ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $page, array( self::STYLE_SLUG, self::QUESTIONS_SLUG, self::TOP_MENU_SLUG ), true ) ) {
			return;
		}

		wp_enqueue_style(
			'nlf-faq-admin',
			NLF_FAQ_PLUGIN_URL . 'assets/css/admin-faq-style.css',
			array(),
			NLF_FAQ_VERSION
		);

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
	 */
	public static function render_style_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = NLF_Faq_Options::get_options();
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
									<input type="text" class="nlf-color-field" id="nlf_faq_container_background" name="<?php echo esc_attr( NLF_Faq_Options::OPTION_KEY ); ?>[container_background]" value="<?php echo esc_attr( $options['container_background'] ); ?>" data-preview-prop="container_background">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="nlf_faq_container_border_color"><?php esc_html_e( 'Border color', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<input type="text" class="nlf-color-field" id="nlf_faq_container_border_color" name="<?php echo esc_attr( NLF_Faq_Options::OPTION_KEY ); ?>[container_border_color]" value="<?php echo esc_attr( $options['container_border_color'] ); ?>" data-preview-prop="container_border_color">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="nlf_faq_container_border_radius"><?php esc_html_e( 'Border radius (px)', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<input type="number" min="0" id="nlf_faq_container_border_radius" name="<?php echo esc_attr( NLF_Faq_Options::OPTION_KEY ); ?>[container_border_radius]" value="<?php echo esc_attr( $options['container_border_radius'] ); ?>" data-preview-prop="container_border_radius">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="nlf_faq_container_padding"><?php esc_html_e( 'Padding (px)', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<input type="number" min="0" id="nlf_faq_container_padding" name="<?php echo esc_attr( NLF_Faq_Options::OPTION_KEY ); ?>[container_padding]" value="<?php echo esc_attr( $options['container_padding'] ); ?>" data-preview-prop="container_padding">
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Shadow', 'next-level-faq' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="<?php echo esc_attr( NLF_Faq_Options::OPTION_KEY ); ?>[shadow]" value="1" <?php checked( $options['shadow'], true ); ?> data-preview-prop="shadow">
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
									<input type="text" class="nlf-color-field" id="nlf_faq_question_color" name="<?php echo esc_attr( NLF_Faq_Options::OPTION_KEY ); ?>[question_color]" value="<?php echo esc_attr( $options['question_color'] ); ?>" data-preview-prop="question_color">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="nlf_faq_question_font_size"><?php esc_html_e( 'Font size (px)', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<input type="number" min="10" id="nlf_faq_question_font_size" name="<?php echo esc_attr( NLF_Faq_Options::OPTION_KEY ); ?>[question_font_size]" value="<?php echo esc_attr( $options['question_font_size'] ); ?>" data-preview-prop="question_font_size">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="nlf_faq_question_font_weight"><?php esc_html_e( 'Font weight', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<input type="number" step="100" min="100" max="900" id="nlf_faq_question_font_weight" name="<?php echo esc_attr( NLF_Faq_Options::OPTION_KEY ); ?>[question_font_weight]" value="<?php echo esc_attr( $options['question_font_weight'] ); ?>" data-preview-prop="question_font_weight">
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
									<input type="text" class="nlf-color-field" id="nlf_faq_answer_color" name="<?php echo esc_attr( NLF_Faq_Options::OPTION_KEY ); ?>[answer_color]" value="<?php echo esc_attr( $options['answer_color'] ); ?>" data-preview-prop="answer_color">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="nlf_faq_answer_font_size"><?php esc_html_e( 'Font size (px)', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<input type="number" min="10" id="nlf_faq_answer_font_size" name="<?php echo esc_attr( NLF_Faq_Options::OPTION_KEY ); ?>[answer_font_size]" value="<?php echo esc_attr( $options['answer_font_size'] ); ?>" data-preview-prop="answer_font_size">
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
									<input type="text" class="nlf-color-field" id="nlf_faq_accent_color" name="<?php echo esc_attr( NLF_Faq_Options::OPTION_KEY ); ?>[accent_color]" value="<?php echo esc_attr( $options['accent_color'] ); ?>" data-preview-prop="accent_color">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="nlf_faq_icon_style"><?php esc_html_e( 'Icon style', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<select id="nlf_faq_icon_style" name="<?php echo esc_attr( NLF_Faq_Options::OPTION_KEY ); ?>[icon_style]" data-preview-prop="icon_style">
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
									<input type="number" min="0" id="nlf_faq_gap_between_items" name="<?php echo esc_attr( NLF_Faq_Options::OPTION_KEY ); ?>[gap_between_items]" value="<?php echo esc_attr( $options['gap_between_items'] ); ?>" data-preview-prop="gap_between_items">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="nlf_faq_animation"><?php esc_html_e( 'Animation', 'next-level-faq' ); ?></label>
								</th>
								<td>
									<select id="nlf_faq_animation" name="<?php echo esc_attr( NLF_Faq_Options::OPTION_KEY ); ?>[animation]" data-preview-prop="animation">
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
	 */
	public static function render_questions_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$items = NLF_Faq_Repository::get_all_items( 0 );
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
	 * Handle saving questions from repeater UI.
	 */
	public static function handle_save_questions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage FAQs.', 'next-level-faq' ) );
		}

		if ( ! isset( $_POST['nlf_faq_questions_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nlf_faq_questions_nonce'] ), 'nlf_faq_save_questions' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_die( esc_html__( 'Security check failed.', 'next-level-faq' ) );
		}

		$ids       = isset( $_POST['nlf_faq_id'] ) ? (array) wp_unslash( $_POST['nlf_faq_id'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$questions = isset( $_POST['nlf_faq_question'] ) ? (array) wp_unslash( $_POST['nlf_faq_question'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$answers   = isset( $_POST['nlf_faq_answer'] ) ? (array) wp_unslash( $_POST['nlf_faq_answer'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$active    = isset( $_POST['nlf_faq_active'] ) ? (array) wp_unslash( $_POST['nlf_faq_active'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$keep_ids = array();

		$count = max( count( $questions ), count( $answers ), count( $ids ) );

		for ( $i = 0; $i < $count; $i++ ) {
			$id       = isset( $ids[ $i ] ) ? (int) $ids[ $i ] : 0;
			$question = isset( $questions[ $i ] ) ? sanitize_text_field( $questions[ $i ] ) : '';
			$answer   = isset( $answers[ $i ] ) ? wp_kses_post( $answers[ $i ] ) : '';

			if ( '' === trim( $question ) && '' === trim( wp_strip_all_tags( $answer ) ) ) {
				continue;
			}

			$status   = isset( $active[ (string) $i ] ) ? 1 : 0;

			$new_id     = NLF_Faq_Repository::save_item( $id, 0, $question, $answer, $status, $i );
			$keep_ids[] = $new_id;
		}

		NLF_Faq_Repository::delete_all_except( $keep_ids, 0 );

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
}

