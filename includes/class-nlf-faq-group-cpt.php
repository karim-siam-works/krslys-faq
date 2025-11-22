<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
class NLF_Faq_Group_CPT {

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
	}

	/**
	 * Register metaboxes.
	 */
	public static function register_metaboxes() {
		add_meta_box(
			'nlf_faq_group_items',
			__( 'FAQ Items', 'next-level-faq' ),
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

		wp_enqueue_style(
			'nlf-faq-admin',
			NLF_FAQ_PLUGIN_URL . 'assets/css/admin-faq-style.css',
			array(),
			NLF_FAQ_VERSION
		);

		wp_enqueue_script(
			'nlf-faq-group-metabox',
			NLF_FAQ_PLUGIN_URL . 'assets/js/admin-faq-group-metabox.js',
			array( 'jquery', 'jquery-ui-sortable', 'wp-editor' ),
			NLF_FAQ_VERSION,
			true
		);
	}

	/**
	 * Render FAQ items repeater metabox.
	 *
	 * SECURITY: All output properly escaped.
	 *
	 * @param WP_Post $post Post object.
	 */
public static function render_metabox( $post ) {
	wp_nonce_field( 'nlf_faq_group_save', 'nlf_faq_group_nonce' );

		$items = NLF_Faq_Repository::get_items_for_group( $post->ID, false );
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

			$new_id     = NLF_Faq_Repository::save_item( $id, $post_id, $question, $answer, $status, $i, $initial_state, $is_highlight );
			$keep_ids[] = $new_id;
		}

		NLF_Faq_Repository::delete_all_except( $keep_ids, $post_id );
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

		NLF_Faq_Repository::delete_items_for_group( $post_id );
	}
}
