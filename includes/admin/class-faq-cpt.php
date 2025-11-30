<?php
/**
 * FAQ custom post type and metabox integration.
 *
 * @package Krslys\NextLevelFaq
 */

namespace Krslys\NextLevelFaq;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_Post;

/**
 * FAQ custom post type and metabox integration.
 *
 * SECURITY FEATURES:
 * - All metabox saves protected with nonce verification.
 * - Capability checks for edit_post permission.
 * - Input sanitization via sanitize_text_field() and wp_kses_post().
 * - Output escaping via esc_attr(), esc_html(), esc_textarea().
 */
class FAQ_CPT {

	/**
	 * Post type slug.
	 */
	const POST_TYPE = 'faq';

	/**
	 * Register CPT.
	 */
	public static function register() {
		$labels = array(
			'name'               => __( 'FAQs', 'next-level-faq' ),
			'singular_name'      => __( 'FAQ', 'next-level-faq' ),
			'add_new'            => __( 'Add New', 'next-level-faq' ),
			'add_new_item'       => __( 'Add New FAQ', 'next-level-faq' ),
			'edit_item'          => __( 'Edit FAQ', 'next-level-faq' ),
			'new_item'           => __( 'New FAQ', 'next-level-faq' ),
			'all_items'          => __( 'All FAQs', 'next-level-faq' ),
			'view_item'          => __( 'View FAQ', 'next-level-faq' ),
			'search_items'       => __( 'Search FAQs', 'next-level-faq' ),
			'not_found'          => __( 'No FAQs found', 'next-level-faq' ),
			'not_found_in_trash' => __( 'No FAQs found in Trash', 'next-level-faq' ),
			'menu_name'          => __( 'FAQs', 'next-level-faq' ),
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
			'nlf_faq_content',
			__( 'FAQ Content', 'next-level-faq' ),
			array( __CLASS__, 'render_metabox' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render metabox fields.
	 *
	 * SECURITY: All output properly escaped.
	 *
	 * @param WP_Post $post Post object.
	 */
public static function render_metabox( $post ) {
	wp_nonce_field( 'nlf_faq_save', 'nlf_faq_nonce' );

		$faq_item = Repository::get_faq_by_post_id( $post->ID );

		$question = $faq_item ? $faq_item->question : '';
		$answer   = $faq_item ? $faq_item->answer : '';
		?>
		<p class="description">
			<?php esc_html_e( 'Define the question and answer that will appear in your FAQ section. Styles are controlled globally from the Next Level FAQ settings page.', 'next-level-faq' ); ?>
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="nlf_faq_question"><?php esc_html_e( 'Question', 'next-level-faq' ); ?></label>
				</th>
				<td>
					<input type="text" class="regular-text" id="nlf_faq_question" name="nlf_faq_question" value="<?php echo esc_attr( $question ); ?>" placeholder="<?php esc_attr_e( 'e.g. How long does shipping take?', 'next-level-faq' ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="nlf_faq_answer"><?php esc_html_e( 'Answer', 'next-level-faq' ); ?></label>
				</th>
				<td>
					<?php
					$settings = array(
						'textarea_name' => 'nlf_faq_answer',
						'media_buttons' => false,
						'teeny'         => true,
						'textarea_rows' => 5,
					);

					wp_editor( $answer, 'nlf_faq_answer', $settings );
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save metabox data.
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
	if ( ! isset( $_POST['nlf_faq_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nlf_faq_nonce'] ) ), 'nlf_faq_save' ) ) {
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

	$question = isset( $_POST['nlf_faq_question'] ) ? sanitize_text_field( wp_unslash( $_POST['nlf_faq_question'] ) ) : '';
		$answer   = isset( $_POST['nlf_faq_answer'] ) ? wp_kses_post( wp_unslash( $_POST['nlf_faq_answer'] ) ) : '';

		// Keep post title in sync with question for easier admin browsing.
		if ( $question && $post->post_title !== $question ) {
			// Temporarily remove the save hook to prevent infinite loop.
			remove_action( 'save_post_faq', array( __CLASS__, 'save_metabox' ), 10 );
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $question,
				)
			);
			// Re-add the hook.
			add_action( 'save_post_faq', array( __CLASS__, 'save_metabox' ), 10, 2 );
		}

		$status_flag = 'publish' === $post->post_status ? 1 : 0;

		Repository::save_faq_item( $post_id, $question, $answer, $status_flag );
	}

	/**
	 * Handle deletion of a FAQ post.
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

		Repository::delete_by_post_id( $post_id );
	}

	/**
	 * Customize admin columns for FAQ list.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$columns['nlf_faq_answer'] = __( 'Answer Preview', 'next-level-faq' );

		return $columns;
	}

	/**
	 * Render custom column content.
	 *
	 * SECURITY: Output escaped via esc_html().
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public static function render_column( $column, $post_id ) {
		if ( 'nlf_faq_answer' !== $column ) {
			return;
		}

		$item = Repository::get_faq_by_post_id( $post_id );

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
