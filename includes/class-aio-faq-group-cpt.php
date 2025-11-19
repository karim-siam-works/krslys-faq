<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FAQ Group custom post type.
 *
 * Each post represents a group/section that will hold a repeater
 * of FAQ items stored in the custom aio_faq_items table.
 */
class AIO_Faq_Group_CPT {

	const POST_TYPE = 'aio_faq_group';

	/**
	 * Register CPT.
	 */
	public static function register() {
		$labels = array(
			'name'               => __( 'FAQ Groups', 'all-in-one-faq' ),
			'singular_name'      => __( 'FAQ Group', 'all-in-one-faq' ),
			'add_new'            => __( 'Add New', 'all-in-one-faq' ),
			'add_new_item'       => __( 'Add New FAQ Group', 'all-in-one-faq' ),
			'edit_item'          => __( 'Edit FAQ Group', 'all-in-one-faq' ),
			'new_item'           => __( 'New FAQ Group', 'all-in-one-faq' ),
			'all_items'          => __( 'FAQ Groups', 'all-in-one-faq' ),
			'view_item'          => __( 'View FAQ Group', 'all-in-one-faq' ),
			'search_items'       => __( 'Search FAQ Groups', 'all-in-one-faq' ),
			'not_found'          => __( 'No FAQ groups found', 'all-in-one-faq' ),
			'not_found_in_trash' => __( 'No FAQ groups found in Trash', 'all-in-one-faq' ),
			'menu_name'          => __( 'FAQ Groups', 'all-in-one-faq' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'show_ui'            => true,
			'show_in_rest'       => false,
			'show_in_nav_menus'  => false,
			'show_in_admin_bar'  => false,
			'show_in_menu'       => 'aio-faq',
			'supports'           => array( 'title' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
		);

		register_post_type( self::POST_TYPE, $args );

		add_action( 'add_meta_boxes', array( __CLASS__, 'register_metaboxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_metabox' ), 10, 2 );
		add_action( 'before_delete_post', array( __CLASS__, 'handle_delete' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register metaboxes.
	 */
	public static function register_metaboxes() {
		add_meta_box(
			'aio_faq_group_items',
			__( 'FAQ Items', 'all-in-one-faq' ),
			array( __CLASS__, 'render_metabox' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Enqueue admin assets for group editor.
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

		wp_enqueue_style(
			'aio-faq-admin',
			AIO_FAQ_PLUGIN_URL . 'assets/css/admin-faq-style.css',
			array(),
			AIO_FAQ_VERSION
		);

		wp_enqueue_script(
			'aio-faq-group-metabox',
			AIO_FAQ_PLUGIN_URL . 'assets/js/admin-faq-group-metabox.js',
			array( 'jquery', 'wp-editor' ),
			AIO_FAQ_VERSION,
			true
		);
	}

	/**
	 * Render FAQ items repeater metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function render_metabox( $post ) {
		wp_nonce_field( 'aio_faq_group_save', 'aio_faq_group_nonce' );

		$items = AIO_Faq_Repository::get_items_for_group( $post->ID, false );
		?>
		<p class="description">
			<?php esc_html_e( 'Add multiple questions and answers to this FAQ group. You can control visibility, icons, initial open state, category tags, and highlight important items.', 'all-in-one-faq' ); ?>
		</p>

		<table class="widefat fixed striped aio-faq-questions-table aio-faq-group-table">
			<thead>
				<tr>
					<th style="width:32px;"></th>
					<th><?php esc_html_e( 'Question & Answer', 'all-in-one-faq' ); ?></th>
					<th style="width:200px;"><?php esc_html_e( 'Options', 'all-in-one-faq' ); ?></th>
				</tr>
			</thead>
			<tbody id="aio-faq-group-questions-body">
				<?php if ( ! empty( $items ) ) : ?>
					<?php foreach ( $items as $index => $item ) : ?>
						<tr class="aio-faq-question-row">
							<td class="aio-faq-sort-handle">⋮⋮</td>
							<td class="aio-faq-content-cell">
								<input type="hidden" name="aio_faq_group_item_id[]" value="<?php echo esc_attr( $item->id ); ?>" />
								<div class="aio-faq-question-field">
									<label class="aio-faq-field-label"><?php esc_html_e( 'Question', 'all-in-one-faq' ); ?></label>
									<input type="text" class="regular-text" name="aio_faq_group_question[]" value="<?php echo esc_attr( $item->question ); ?>" placeholder="<?php esc_attr_e( 'Enter your question...', 'all-in-one-faq' ); ?>" />
								</div>
								<div class="aio-faq-answer-field">
									<label class="aio-faq-field-label"><?php esc_html_e( 'Answer', 'all-in-one-faq' ); ?></label>
									<?php
									$editor_id = 'aio_faq_group_answer_' . $index;
									wp_editor(
										$item->answer,
										$editor_id,
										array(
											'textarea_name' => 'aio_faq_group_answer[]',
											'media_buttons' => false,
											'teeny'         => true,
											'textarea_rows' => 4,
										)
									);
									?>
								</div>
								<button type="button" class="aio-faq-remove-row" aria-label="<?php esc_attr_e( 'Remove', 'all-in-one-faq' ); ?>" title="<?php esc_attr_e( 'Remove', 'all-in-one-faq' ); ?>">
									<span class="aio-faq-remove-icon">×</span>
								</button>
							</td>
							<td class="aio-faq-options-cell">
								<div class="aio-faq-options-group">
									<p>
										<label>
											<input type="checkbox" name="aio_faq_group_open[<?php echo esc_attr( $index ); ?>]" value="1" <?php checked( (int) $item->initial_state, 1 ); ?> />
											<?php esc_html_e( 'Open by default', 'all-in-one-faq' ); ?>
										</label>
									</p>
									<p>
										<label>
											<input type="checkbox" name="aio_faq_group_visible[<?php echo esc_attr( $index ); ?>]" value="1" <?php checked( (int) $item->status, 1 ); ?> />
											<?php esc_html_e( 'Show', 'all-in-one-faq' ); ?>
										</label>
									</p>
									<p>
										<label>
											<input type="checkbox" name="aio_faq_group_highlight[<?php echo esc_attr( $index ); ?>]" value="1" <?php checked( (int) $item->highlight, 1 ); ?> />
											<?php esc_html_e( 'Highlight', 'all-in-one-faq' ); ?>
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
						<button type="button" class="button button-secondary" id="aio-faq-group-add-row">
							<?php esc_html_e( 'Add Question', 'all-in-one-faq' ); ?>
						</button>
					</td>
				</tr>
			</tfoot>
		</table>

		<script type="text/template" id="tmpl-aio-faq-group-row">
			<tr class="aio-faq-question-row">
				<td class="aio-faq-sort-handle">⋮⋮</td>
				<td class="aio-faq-content-cell">
					<input type="hidden" name="aio_faq_group_item_id[]" value="" />
					<div class="aio-faq-question-field">
						<label class="aio-faq-field-label"><?php esc_html_e( 'Question', 'all-in-one-faq' ); ?></label>
						<input type="text" class="regular-text" name="aio_faq_group_question[]" value="" placeholder="<?php esc_attr_e( 'Enter your question...', 'all-in-one-faq' ); ?>" />
					</div>
					<div class="aio-faq-answer-field">
						<label class="aio-faq-field-label"><?php esc_html_e( 'Answer', 'all-in-one-faq' ); ?></label>
						<textarea id="aio-faq-group-answer-{{index}}" name="aio_faq_group_answer[]" rows="4" class="large-text aio-faq-group-answer-editor" placeholder="<?php esc_attr_e( 'Enter your answer...', 'all-in-one-faq' ); ?>"></textarea>
					</div>
					<button type="button" class="aio-faq-remove-row" aria-label="<?php esc_attr_e( 'Remove', 'all-in-one-faq' ); ?>" title="<?php esc_attr_e( 'Remove', 'all-in-one-faq' ); ?>">
						<span class="aio-faq-remove-icon">×</span>
					</button>
				</td>
				<td class="aio-faq-options-cell">
					<div class="aio-faq-options-group">
						<p>
							<label>
								<input type="checkbox" name="aio_faq_group_open[{{index}}]" value="1" checked="checked" />
								<?php esc_html_e( 'Open by default', 'all-in-one-faq' ); ?>
							</label>
						</p>
						<p>
							<label>
								<input type="checkbox" name="aio_faq_group_visible[{{index}}]" value="1" checked="checked" />
								<?php esc_html_e( 'Show', 'all-in-one-faq' ); ?>
							</label>
						</p>
						<p>
							<label>
								<input type="checkbox" name="aio_faq_group_highlight[{{index}}]" value="1" />
								<?php esc_html_e( 'Highlight', 'all-in-one-faq' ); ?>
							</label>
						</p>
					</div>
				</td>
			</tr>
		</script>
		<?php
	}

	/**
	 * Save metabox data for a group.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save_metabox( $post_id, $post ) {
		if ( ! isset( $_POST['aio_faq_group_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['aio_faq_group_nonce'] ), 'aio_faq_group_save' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
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

		$ids       = isset( $_POST['aio_faq_group_item_id'] ) ? (array) wp_unslash( $_POST['aio_faq_group_item_id'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$questions = isset( $_POST['aio_faq_group_question'] ) ? (array) wp_unslash( $_POST['aio_faq_group_question'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$answers   = isset( $_POST['aio_faq_group_answer'] ) ? (array) wp_unslash( $_POST['aio_faq_group_answer'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$icons     = isset( $_POST['aio_faq_group_icon'] ) ? (array) wp_unslash( $_POST['aio_faq_group_icon'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$categories = isset( $_POST['aio_faq_group_category'] ) ? (array) wp_unslash( $_POST['aio_faq_group_category'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$visible   = isset( $_POST['aio_faq_group_visible'] ) ? (array) wp_unslash( $_POST['aio_faq_group_visible'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$open      = isset( $_POST['aio_faq_group_open'] ) ? (array) wp_unslash( $_POST['aio_faq_group_open'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$highlight = isset( $_POST['aio_faq_group_highlight'] ) ? (array) wp_unslash( $_POST['aio_faq_group_highlight'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$keep_ids = array();

		$count = max( count( $questions ), count( $answers ), count( $ids ) );

		for ( $i = 0; $i < $count; $i++ ) {
			$id       = isset( $ids[ $i ] ) ? (int) $ids[ $i ] : 0;
			$question = isset( $questions[ $i ] ) ? sanitize_text_field( $questions[ $i ] ) : '';
			$answer   = isset( $answers[ $i ] ) ? wp_kses_post( $answers[ $i ] ) : '';

			if ( '' === trim( $question ) && '' === trim( wp_strip_all_tags( $answer ) ) ) {
				continue;
			}

			$icon          = isset( $icons[ $i ] ) ? sanitize_text_field( $icons[ $i ] ) : '';
			$category      = isset( $categories[ $i ] ) ? sanitize_text_field( $categories[ $i ] ) : '';
			$status        = isset( $visible[ (string) $i ] ) ? 1 : 0;
			$initial_state = isset( $open[ (string) $i ] ) ? 1 : 0;
			$is_highlight  = isset( $highlight[ (string) $i ] ) ? 1 : 0;

			$new_id     = AIO_Faq_Repository::save_item( $id, $post_id, $question, $answer, $status, $i, $icon, $initial_state, $category, $is_highlight );
			$keep_ids[] = $new_id;
		}

		AIO_Faq_Repository::delete_all_except( $keep_ids, $post_id );
	}

	/**
	 * Handle deletion of a group.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function handle_delete( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return;
		}

		AIO_Faq_Repository::delete_items_for_group( $post_id );
	}
}


