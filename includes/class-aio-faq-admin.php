<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin settings page and assets.
 */
class AIO_Faq_Admin {

	/**
	 * Top-level menu slug.
	 */
	const TOP_MENU_SLUG = 'aio-faq';

	/**
	 * Style page slug.
	 */
	const STYLE_SLUG = 'aio-faq-style';

	/**
	 * Questions page slug.
	 */
	const QUESTIONS_SLUG = 'aio-faq-questions';

	/**
	 * Register admin menu.
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'All-in-One FAQ', 'all-in-one-faq' ),
			__( 'FAQs', 'all-in-one-faq' ),
			'manage_options',
			self::TOP_MENU_SLUG,
			array( __CLASS__, 'render_style_page' ),
			'dashicons-editor-help',
			26
		);

		add_submenu_page(
			self::TOP_MENU_SLUG,
			__( 'FAQ Style & Layout', 'all-in-one-faq' ),
			__( 'Style & Layout', 'all-in-one-faq' ),
			'manage_options',
			self::STYLE_SLUG,
			array( __CLASS__, 'render_style_page' )
		);

		add_submenu_page(
			self::TOP_MENU_SLUG,
			__( 'FAQ Questions', 'all-in-one-faq' ),
			__( 'Questions', 'all-in-one-faq' ),
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
			'aio_faq_style_group',
			AIO_Faq_Options::OPTION_KEY,
			array(
				'sanitize_callback' => array( 'AIO_Faq_Options', 'sanitize' ),
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
			'aio-faq-admin',
			AIO_FAQ_PLUGIN_URL . 'assets/css/admin-faq-style.css',
			array(),
			AIO_FAQ_VERSION
		);

		// Enqueue WordPress color picker for style page only.
		if ( in_array( $page, array( self::STYLE_SLUG, self::TOP_MENU_SLUG ), true ) ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script(
				'aio-faq-admin',
				AIO_FAQ_PLUGIN_URL . 'assets/js/admin-faq-style.js',
				array( 'jquery', 'wp-color-picker' ),
				AIO_FAQ_VERSION,
				true
			);
		} else {
			wp_enqueue_script(
				'aio-faq-admin',
				AIO_FAQ_PLUGIN_URL . 'assets/js/admin-faq-style.js',
				array( 'jquery' ),
				AIO_FAQ_VERSION,
				true
			);
		}

		if ( self::QUESTIONS_SLUG === $page ) {
			wp_enqueue_script(
				'aio-faq-admin-questions',
				AIO_FAQ_PLUGIN_URL . 'assets/js/admin-faq-questions.js',
				array( 'jquery' ),
				AIO_FAQ_VERSION,
				true
			);
		}

		wp_localize_script(
			'aio-faq-admin',
			'aioFaqAdmin',
			array(
				'i18n' => array(
					'saving' => __( 'Saving…', 'all-in-one-faq' ),
					'saved'  => __( 'Saved', 'all-in-one-faq' ),
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

		$options = AIO_Faq_Options::get_options();
		?>
		<div class="wrap aio-faq-admin">
			<h1><?php esc_html_e( 'All-in-One FAQ – Style & Layout', 'all-in-one-faq' ); ?></h1>

			<div class="aio-faq-admin__layout">
				<div class="aio-faq-admin__left">
					<form method="post" action="options.php" id="aio-faq-style-form">
						<?php
						settings_fields( 'aio_faq_style_group' );
						?>

						<h2><?php esc_html_e( 'Layout & Container', 'all-in-one-faq' ); ?></h2>

						<table class="form-table" role="presentation">
							<tr>
								<th scope="row">
									<label for="aio_faq_container_background"><?php esc_html_e( 'Container background', 'all-in-one-faq' ); ?></label>
								</th>
								<td>
									<input type="text" class="aio-color-field" id="aio_faq_container_background" name="<?php echo esc_attr( AIO_Faq_Options::OPTION_KEY ); ?>[container_background]" value="<?php echo esc_attr( $options['container_background'] ); ?>" data-preview-prop="container_background">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="aio_faq_container_border_color"><?php esc_html_e( 'Border color', 'all-in-one-faq' ); ?></label>
								</th>
								<td>
									<input type="text" class="aio-color-field" id="aio_faq_container_border_color" name="<?php echo esc_attr( AIO_Faq_Options::OPTION_KEY ); ?>[container_border_color]" value="<?php echo esc_attr( $options['container_border_color'] ); ?>" data-preview-prop="container_border_color">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="aio_faq_container_border_radius"><?php esc_html_e( 'Border radius (px)', 'all-in-one-faq' ); ?></label>
								</th>
								<td>
									<input type="number" min="0" id="aio_faq_container_border_radius" name="<?php echo esc_attr( AIO_Faq_Options::OPTION_KEY ); ?>[container_border_radius]" value="<?php echo esc_attr( $options['container_border_radius'] ); ?>" data-preview-prop="container_border_radius">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="aio_faq_container_padding"><?php esc_html_e( 'Padding (px)', 'all-in-one-faq' ); ?></label>
								</th>
								<td>
									<input type="number" min="0" id="aio_faq_container_padding" name="<?php echo esc_attr( AIO_Faq_Options::OPTION_KEY ); ?>[container_padding]" value="<?php echo esc_attr( $options['container_padding'] ); ?>" data-preview-prop="container_padding">
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Shadow', 'all-in-one-faq' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="<?php echo esc_attr( AIO_Faq_Options::OPTION_KEY ); ?>[shadow]" value="1" <?php checked( $options['shadow'], true ); ?> data-preview-prop="shadow">
										<?php esc_html_e( 'Enable subtle card shadow', 'all-in-one-faq' ); ?>
									</label>
								</td>
							</tr>
						</table>

						<h2><?php esc_html_e( 'Question', 'all-in-one-faq' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row">
									<label for="aio_faq_question_color"><?php esc_html_e( 'Question color', 'all-in-one-faq' ); ?></label>
								</th>
								<td>
									<input type="text" class="aio-color-field" id="aio_faq_question_color" name="<?php echo esc_attr( AIO_Faq_Options::OPTION_KEY ); ?>[question_color]" value="<?php echo esc_attr( $options['question_color'] ); ?>" data-preview-prop="question_color">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="aio_faq_question_font_size"><?php esc_html_e( 'Font size (px)', 'all-in-one-faq' ); ?></label>
								</th>
								<td>
									<input type="number" min="10" id="aio_faq_question_font_size" name="<?php echo esc_attr( AIO_Faq_Options::OPTION_KEY ); ?>[question_font_size]" value="<?php echo esc_attr( $options['question_font_size'] ); ?>" data-preview-prop="question_font_size">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="aio_faq_question_font_weight"><?php esc_html_e( 'Font weight', 'all-in-one-faq' ); ?></label>
								</th>
								<td>
									<input type="number" step="100" min="100" max="900" id="aio_faq_question_font_weight" name="<?php echo esc_attr( AIO_Faq_Options::OPTION_KEY ); ?>[question_font_weight]" value="<?php echo esc_attr( $options['question_font_weight'] ); ?>" data-preview-prop="question_font_weight">
								</td>
							</tr>
						</table>

						<h2><?php esc_html_e( 'Answer', 'all-in-one-faq' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row">
									<label for="aio_faq_answer_color"><?php esc_html_e( 'Answer color', 'all-in-one-faq' ); ?></label>
								</th>
								<td>
									<input type="text" class="aio-color-field" id="aio_faq_answer_color" name="<?php echo esc_attr( AIO_Faq_Options::OPTION_KEY ); ?>[answer_color]" value="<?php echo esc_attr( $options['answer_color'] ); ?>" data-preview-prop="answer_color">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="aio_faq_answer_font_size"><?php esc_html_e( 'Font size (px)', 'all-in-one-faq' ); ?></label>
								</th>
								<td>
									<input type="number" min="10" id="aio_faq_answer_font_size" name="<?php echo esc_attr( AIO_Faq_Options::OPTION_KEY ); ?>[answer_font_size]" value="<?php echo esc_attr( $options['answer_font_size'] ); ?>" data-preview-prop="answer_font_size">
								</td>
							</tr>
						</table>

						<h2><?php esc_html_e( 'Accent & Behavior', 'all-in-one-faq' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row">
									<label for="aio_faq_accent_color"><?php esc_html_e( 'Accent color', 'all-in-one-faq' ); ?></label>
								</th>
								<td>
									<input type="text" class="aio-color-field" id="aio_faq_accent_color" name="<?php echo esc_attr( AIO_Faq_Options::OPTION_KEY ); ?>[accent_color]" value="<?php echo esc_attr( $options['accent_color'] ); ?>" data-preview-prop="accent_color">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="aio_faq_icon_style"><?php esc_html_e( 'Icon style', 'all-in-one-faq' ); ?></label>
								</th>
								<td>
									<select id="aio_faq_icon_style" name="<?php echo esc_attr( AIO_Faq_Options::OPTION_KEY ); ?>[icon_style]" data-preview-prop="icon_style">
										<option value="plus_minus" <?php selected( $options['icon_style'], 'plus_minus' ); ?>><?php esc_html_e( 'Plus / Minus', 'all-in-one-faq' ); ?></option>
										<option value="chevron" <?php selected( $options['icon_style'], 'chevron' ); ?>><?php esc_html_e( 'Chevron', 'all-in-one-faq' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="aio_faq_gap_between_items"><?php esc_html_e( 'Gap between items (px)', 'all-in-one-faq' ); ?></label>
								</th>
								<td>
									<input type="number" min="0" id="aio_faq_gap_between_items" name="<?php echo esc_attr( AIO_Faq_Options::OPTION_KEY ); ?>[gap_between_items]" value="<?php echo esc_attr( $options['gap_between_items'] ); ?>" data-preview-prop="gap_between_items">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="aio_faq_animation"><?php esc_html_e( 'Animation', 'all-in-one-faq' ); ?></label>
								</th>
								<td>
									<select id="aio_faq_animation" name="<?php echo esc_attr( AIO_Faq_Options::OPTION_KEY ); ?>[animation]" data-preview-prop="animation">
										<option value="slide" <?php selected( $options['animation'], 'slide' ); ?>><?php esc_html_e( 'Slide', 'all-in-one-faq' ); ?></option>
										<option value="fade" <?php selected( $options['animation'], 'fade' ); ?>><?php esc_html_e( 'Fade', 'all-in-one-faq' ); ?></option>
										<option value="none" <?php selected( $options['animation'], 'none' ); ?>><?php esc_html_e( 'None', 'all-in-one-faq' ); ?></option>
									</select>
								</td>
							</tr>
						</table>

						<?php submit_button( __( 'Save Styles', 'all-in-one-faq' ) ); ?>
					</form>
				</div>

				<div class="aio-faq-admin__right">
					<h2><?php esc_html_e( 'Live Preview', 'all-in-one-faq' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Preview shows how your FAQ will look with the current style settings.', 'all-in-one-faq' ); ?></p>

					<div id="aio-faq-preview-root"
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
						<div class="aio-faq aio-faq--preview">
							<div class="aio-faq__item is-open">
								<div class="aio-faq__question">
									<span><?php esc_html_e( 'How quickly can I customize my FAQs?', 'all-in-one-faq' ); ?></span>
									<span class="aio-faq__icon" aria-hidden="true"></span>
								</div>
								<div class="aio-faq__answer">
									<p><?php esc_html_e( 'Changes you make here are applied instantly and reflected on the front-end as soon as you save.', 'all-in-one-faq' ); ?></p>
								</div>
							</div>
							<div class="aio-faq__item">
								<div class="aio-faq__question">
									<span><?php esc_html_e( 'Can I match my brand colors?', 'all-in-one-faq' ); ?></span>
									<span class="aio-faq__icon" aria-hidden="true"></span>
								</div>
								<div class="aio-faq__answer">
									<p><?php esc_html_e( 'Yes. Configure colors, typography, spacing, and animations to align with your brand.', 'all-in-one-faq' ); ?></p>
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

		$items = AIO_Faq_Repository::get_all_items( 0 );
		?>
		<div class="wrap aio-faq-admin">
			<h1><?php esc_html_e( 'All-in-One FAQ – Questions', 'all-in-one-faq' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Manage the list of questions and answers that will appear in your FAQ sections. Use the checkboxes to control which items are visible.', 'all-in-one-faq' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="aio-faq-questions-form">
				<?php wp_nonce_field( 'aio_faq_save_questions', 'aio_faq_questions_nonce' ); ?>
				<input type="hidden" name="action" value="aio_faq_save_questions" />

				<table class="widefat fixed striped aio-faq-questions-table">
					<thead>
						<tr>
							<th style="width:40px;"></th>
							<th style="width:35%;"><?php esc_html_e( 'Question', 'all-in-one-faq' ); ?></th>
							<th><?php esc_html_e( 'Answer', 'all-in-one-faq' ); ?></th>
							<th style="width:80px;"><?php esc_html_e( 'Visible', 'all-in-one-faq' ); ?></th>
							<th style="width:80px;"><?php esc_html_e( 'Actions', 'all-in-one-faq' ); ?></th>
						</tr>
					</thead>
					<tbody id="aio-faq-questions-body">
						<?php if ( ! empty( $items ) ) : ?>
							<?php foreach ( $items as $index => $item ) : ?>
								<tr class="aio-faq-question-row">
									<td class="aio-faq-sort-handle">⋮⋮</td>
									<td>
										<input type="hidden" name="aio_faq_id[]" value="<?php echo esc_attr( $item->id ); ?>" />
										<input type="text" class="regular-text" name="aio_faq_question[]" value="<?php echo esc_attr( $item->question ); ?>" placeholder="<?php esc_attr_e( 'Question', 'all-in-one-faq' ); ?>" />
									</td>
									<td>
										<textarea name="aio_faq_answer[]" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Answer', 'all-in-one-faq' ); ?>"><?php echo esc_textarea( $item->answer ); ?></textarea>
									</td>
									<td class="aio-faq-visible-cell">
										<label>
											<input type="checkbox" name="aio_faq_active[<?php echo esc_attr( $index ); ?>]" value="1" <?php checked( (int) $item->status, 1 ); ?> />
											<?php esc_html_e( 'Show', 'all-in-one-faq' ); ?>
										</label>
									</td>
									<td class="aio-faq-actions-cell">
										<button type="button" class="button-link aio-faq-remove-row"><?php esc_html_e( 'Remove', 'all-in-one-faq' ); ?></button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
					<tfoot>
						<tr>
							<td colspan="5">
								<button type="button" class="button button-secondary" id="aio-faq-add-row">
									<?php esc_html_e( 'Add Question', 'all-in-one-faq' ); ?>
								</button>
							</td>
						</tr>
					</tfoot>
				</table>

				<?php submit_button( __( 'Save Questions', 'all-in-one-faq' ) ); ?>
			</form>

			<script type="text/template" id="tmpl-aio-faq-row">
				<tr class="aio-faq-question-row">
					<td class="aio-faq-sort-handle">⋮⋮</td>
					<td>
						<input type="hidden" name="aio_faq_id[]" value="" />
						<input type="text" class="regular-text" name="aio_faq_question[]" value="" placeholder="<?php esc_attr_e( 'Question', 'all-in-one-faq' ); ?>" />
					</td>
					<td>
						<textarea name="aio_faq_answer[]" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Answer', 'all-in-one-faq' ); ?>"></textarea>
					</td>
					<td class="aio-faq-visible-cell">
						<label>
							<input type="checkbox" name="aio_faq_active[{{index}}]" value="1" checked="checked" />
							<?php esc_html_e( 'Show', 'all-in-one-faq' ); ?>
						</label>
					</td>
					<td class="aio-faq-actions-cell">
						<button type="button" class="button-link aio-faq-remove-row"><?php esc_html_e( 'Remove', 'all-in-one-faq' ); ?></button>
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
			wp_die( esc_html__( 'You do not have permission to manage FAQs.', 'all-in-one-faq' ) );
		}

		if ( ! isset( $_POST['aio_faq_questions_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['aio_faq_questions_nonce'] ), 'aio_faq_save_questions' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_die( esc_html__( 'Security check failed.', 'all-in-one-faq' ) );
		}

		$ids       = isset( $_POST['aio_faq_id'] ) ? (array) wp_unslash( $_POST['aio_faq_id'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$questions = isset( $_POST['aio_faq_question'] ) ? (array) wp_unslash( $_POST['aio_faq_question'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$answers   = isset( $_POST['aio_faq_answer'] ) ? (array) wp_unslash( $_POST['aio_faq_answer'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$active    = isset( $_POST['aio_faq_active'] ) ? (array) wp_unslash( $_POST['aio_faq_active'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

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

			$new_id     = AIO_Faq_Repository::save_item( $id, 0, $question, $answer, $status, $i );
			$keep_ids[] = $new_id;
		}

		AIO_Faq_Repository::delete_all_except( $keep_ids, 0 );

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

