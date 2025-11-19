<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FAQ custom post type and metabox integration.
 */
class AIO_Faq_CPT {

	/**
	 * Post type slug.
	 */
	const POST_TYPE = 'faq';

	/**
	 * Register CPT.
	 */
	public static function register() {
		$labels = array(
			'name'               => __( 'FAQs', 'all-in-one-faq' ),
			'singular_name'      => __( 'FAQ', 'all-in-one-faq' ),
			'add_new'            => __( 'Add New', 'all-in-one-faq' ),
			'add_new_item'       => __( 'Add New FAQ', 'all-in-one-faq' ),
			'edit_item'          => __( 'Edit FAQ', 'all-in-one-faq' ),
			'new_item'           => __( 'New FAQ', 'all-in-one-faq' ),
			'all_items'          => __( 'All FAQs', 'all-in-one-faq' ),
			'view_item'          => __( 'View FAQ', 'all-in-one-faq' ),
			'search_items'       => __( 'Search FAQs', 'all-in-one-faq' ),
			'not_found'          => __( 'No FAQs found', 'all-in-one-faq' ),
			'not_found_in_trash' => __( 'No FAQs found in Trash', 'all-in-one-faq' ),
			'menu_name'          => __( 'FAQs', 'all-in-one-faq' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => false,
			'show_in_rest'       => false,
			'supports'           => array( 'title' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'menu_icon'          => 'dashicons-editor-help',
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register metaboxes.
	 */
	public static function register_metaboxes() {
		add_meta_box(
			'aio_faq_content',
			__( 'FAQ Content', 'all-in-one-faq' ),
			array( __CLASS__, 'render_metabox' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render metabox fields.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function render_metabox( $post ) {
		wp_nonce_field( 'aio_faq_save', 'aio_faq_nonce' );

		$faq_item = AIO_Faq_Repository::get_faq_by_post_id( $post->ID );

		$question = $faq_item ? $faq_item->question : '';
		$answer   = $faq_item ? $faq_item->answer : '';
		?>
		<p class="description">
			<?php esc_html_e( 'Define the question and answer that will appear in your FAQ section. Styles are controlled globally from the All-in-One FAQ settings page.', 'all-in-one-faq' ); ?>
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="aio_faq_question"><?php esc_html_e( 'Question', 'all-in-one-faq' ); ?></label>
				</th>
				<td>
					<input type="text" class="regular-text" id="aio_faq_question" name="aio_faq_question" value="<?php echo esc_attr( $question ); ?>" placeholder="<?php esc_attr_e( 'e.g. How long does shipping take?', 'all-in-one-faq' ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="aio_faq_answer"><?php esc_html_e( 'Answer', 'all-in-one-faq' ); ?></label>
				</th>
				<td>
					<?php
					$settings = array(
						'textarea_name' => 'aio_faq_answer',
						'media_buttons' => false,
						'teeny'         => true,
						'textarea_rows' => 5,
					);

					wp_editor( $answer, 'aio_faq_answer', $settings );
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save metabox data.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save_metabox( $post_id, $post ) {
		if ( ! isset( $_POST['aio_faq_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['aio_faq_nonce'] ), 'aio_faq_save' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
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

		$question = isset( $_POST['aio_faq_question'] ) ? sanitize_text_field( wp_unslash( $_POST['aio_faq_question'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$answer   = isset( $_POST['aio_faq_answer'] ) ? wp_kses_post( wp_unslash( $_POST['aio_faq_answer'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Keep post title in sync with question for easier admin browsing.
		if ( $question && $post->post_title !== $question ) {
			remove_action( 'save_post_faq', array( __CLASS__, 'save_metabox' ), 10 );
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $question,
				)
			);
			add_action( 'save_post_faq', array( __CLASS__, 'save_metabox' ), 10, 2 );
		}

		$status_flag = 'publish' === $post->post_status ? 1 : 0;

		AIO_Faq_Repository::save_faq_item( $post_id, $question, $answer, $status_flag );
	}

	/**
	 * Handle deletion of a FAQ post.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function handle_delete( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return;
		}

		AIO_Faq_Repository::delete_by_post_id( $post_id );
	}

	/**
	 * Customize admin columns for FAQ list.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$columns['aio_faq_answer'] = __( 'Answer Preview', 'all-in-one-faq' );

		return $columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public static function render_column( $column, $post_id ) {
		if ( 'aio_faq_answer' !== $column ) {
			return;
		}

		$item = AIO_Faq_Repository::get_faq_by_post_id( $post_id );

		if ( ! $item || empty( $item->answer ) ) {
			echo '&mdash;';
			return;
		}

		$plain = wp_strip_all_tags( $item->answer );

		if ( strlen( $plain ) > 120 ) {
			$plain = substr( $plain, 0, 117 ) . '…';
		}

		echo esc_html( $plain );
	}
}


