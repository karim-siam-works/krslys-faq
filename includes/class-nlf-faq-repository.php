<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data access layer for FAQ items stored in a custom table.
 */
class NLF_Faq_Repository {

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'nlf_faq_items';
	}

	/**
	 * Maybe create or upgrade the custom table.
	 */
	public static function maybe_create_table() {
		$installed_version = get_option( 'nlf_faq_db_version' );

		if ( NLF_FAQ_DB_VERSION === $installed_version ) {
			return;
		}

		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			group_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			position INT(11) UNSIGNED NOT NULL DEFAULT 0,
			question TEXT NOT NULL,
			answer LONGTEXT NOT NULL,
			status TINYINT(1) NOT NULL DEFAULT 0,
			icon VARCHAR(100) NOT NULL DEFAULT '',
			initial_state TINYINT(1) NOT NULL DEFAULT 0,
			category VARCHAR(190) NOT NULL DEFAULT '',
			highlight TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY post_id (post_id),
			KEY group_id (group_id)
		) {$charset_collate};";

		dbDelta( $sql );

		update_option( 'nlf_faq_db_version', NLF_FAQ_DB_VERSION );
	}

	/**
	 * Get all FAQ items for a given group (any status) ordered by position/created date.
	 *
	 * Group ID 0 is used for the legacy global questions UI.
	 *
	 * @param int $group_id Group ID.
	 * @return array
	 */
	public static function get_all_items( $group_id = 0 ) {
		global $wpdb;

		$table = self::get_table_name();

		$sql = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE group_id = %d ORDER BY position ASC, created_at ASC",
			(int) $group_id
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $sql );
	}

	/**
	 * Retrieve FAQ records for export routines.
	 *
	 * @param int|null $group_id Optional group filter (null = all groups).
	 * @return array[]
	 */
	public static function get_all_items_for_export( $group_id = null ) {
		global $wpdb;

		$table = self::get_table_name();

		$where_sql = '';

		if ( null !== $group_id ) {
			$where_sql = $wpdb->prepare( 'WHERE group_id = %d', (int) $group_id );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results(
			"SELECT group_id, position, question, answer, status, icon, initial_state, category, highlight
			FROM {$table} {$where_sql}
			ORDER BY group_id ASC, position ASC, id ASC",
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return array();
		}

		return array_map(
			static function ( $row ) {
				return array(
					'group_id'      => isset( $row['group_id'] ) ? (int) $row['group_id'] : 0,
					'position'      => isset( $row['position'] ) ? (int) $row['position'] : 0,
					'question'      => isset( $row['question'] ) ? (string) $row['question'] : '',
					'answer'        => isset( $row['answer'] ) ? (string) $row['answer'] : '',
					'status'        => isset( $row['status'] ) ? (int) $row['status'] : 0,
					'icon'          => isset( $row['icon'] ) ? (string) $row['icon'] : '',
					'initial_state' => isset( $row['initial_state'] ) ? (int) $row['initial_state'] : 0,
					'category'      => isset( $row['category'] ) ? (string) $row['category'] : '',
					'highlight'     => isset( $row['highlight'] ) ? (int) $row['highlight'] : 0,
				);
			},
			$rows
		);
	}

	/**
	 * Get FAQ item by post ID.
	 *
	 * @param int $post_id Post ID.
	 * @return object|null
	 */
	public static function get_faq_by_post_id( $post_id ) {
		global $wpdb;

		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE post_id = %d LIMIT 1", $post_id ) );
	}

	/**
	 * Save or update FAQ item.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $question Question text.
	 * @param string $answer   Answer HTML.
	 * @param int    $status   Status flag (1 = published, 0 = otherwise).
	 *
	 * @return void
	 */
	public static function save_faq_item( $post_id, $question, $answer, $status ) {
		global $wpdb;

		$table = self::get_table_name();

		$data = array(
			'post_id'  => (int) $post_id,
			'question' => wp_kses_post( $question ),
			'answer'   => wp_kses_post( $answer ),
			'status'   => (int) $status,
		);

		$format = array( '%d', '%s', '%s', '%d' );

		$existing = self::get_faq_by_post_id( $post_id );

		if ( $existing ) {
			$wpdb->update(
				$table,
				$data,
				array( 'post_id' => (int) $post_id ),
				$format,
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$table,
				$data,
				$format
			);
		}
	}

	/**
	 * Delete FAQ item by post ID.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function delete_by_post_id( $post_id ) {
		global $wpdb;

		$table = self::get_table_name();

		$wpdb->delete(
			$table,
			array( 'post_id' => (int) $post_id ),
			array( '%d' )
		);
	}

	/**
	 * Get all published FAQs.
	 *
	 * Group ID 0 is used for the legacy global questions UI.
	 *
	 * @param int $group_id Group ID.
	 * @return array
	 */
	public static function get_all_published_faqs( $group_id = 0 ) {
		global $wpdb;

		$table = self::get_table_name();

		$sql = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE status = 1 AND group_id = %d ORDER BY position ASC, created_at ASC",
			(int) $group_id
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $sql );
	}

	/**
	 * Save or update an item by ID (used by repeater UIs).
	 *
	 * @param int    $id            Existing item ID, or 0 for insert.
	 * @param int    $group_id      Group ID (0 for global / legacy).
	 * @param string $question      Question text.
	 * @param string $answer        Answer HTML.
	 * @param int    $status        Status flag (1 = visible, 0 = hidden).
	 * @param int    $position      Item order position.
	 * @param string $icon          Icon identifier.
	 * @param int    $initial_state 1 = open by default, 0 = closed.
	 * @param string $category      Category/tag label.
	 * @param int    $highlight     1 = highlighted, 0 = normal.
	 *
	 * @return int Inserted/updated ID.
	 */
	public static function save_item( $id, $group_id, $question, $answer, $status, $position, $icon = '', $initial_state = 0, $category = '', $highlight = 0 ) {
		global $wpdb;

		$table = self::get_table_name();

		$data = array(
			'post_id'       => 0,
			'group_id'      => max( 0, (int) $group_id ),
			'position'      => max( 0, (int) $position ),
			'question'      => wp_kses_post( $question ),
			'answer'        => wp_kses_post( $answer ),
			'status'        => (int) $status,
			'icon'          => sanitize_text_field( $icon ),
			'initial_state' => (int) $initial_state,
			'category'      => sanitize_text_field( $category ),
			'highlight'     => (int) $highlight,
		);

		$format = array( '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%d', '%s', '%d' );

		if ( $id > 0 ) {
			$wpdb->update(
				$table,
				$data,
				array( 'id' => (int) $id ),
				$format,
				array( '%d' )
			);

			return (int) $id;
		}

		$wpdb->insert( $table, $data, $format );

		return (int) $wpdb->insert_id;
	}

	/**
	 * Delete all items except the specified IDs.
	 *
	 * @param int[] $keep_ids IDs to keep.
	 * @param int   $group_id Group ID scope.
	 */
	public static function delete_all_except( $keep_ids, $group_id = 0 ) {
		global $wpdb;

		$table = self::get_table_name();

		$keep_ids = array_filter(
			array_map( 'intval', (array) $keep_ids ),
			function ( $id ) {
				return $id > 0;
			}
		);

		if ( empty( $keep_ids ) ) {
			// Delete everything for this group.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE group_id = %d",
					(int) $group_id
				)
			); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return;
		}

		$placeholders = implode( ',', array_fill( 0, count( $keep_ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE group_id = %d AND id NOT IN ({$placeholders})",
				array_merge( array( (int) $group_id ), $keep_ids )
			)
		);
	}

	/**
	 * Get items for a specific group.
	 *
	 * @param int  $group_id     Group ID.
	 * @param bool $only_visible Whether to include only visible items.
	 *
	 * @return array
	 */
	public static function get_items_for_group( $group_id, $only_visible = true ) {
		global $wpdb;

		$table = self::get_table_name();

		$visible_clause = $only_visible ? 'AND status = 1' : '';

		$sql = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE group_id = %d {$visible_clause} ORDER BY position ASC, created_at ASC",
			(int) $group_id
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $sql );
	}

	/**
	 * Delete all items for a specific group.
	 *
	 * @param int $group_id Group ID.
	 */
	public static function delete_items_for_group( $group_id ) {
		global $wpdb;

		$table = self::get_table_name();

		$wpdb->delete(
			$table,
			array( 'group_id' => (int) $group_id ),
			array( '%d' )
		);
	}

	/**
	 * Delete every FAQ record.
	 *
	 * @return void
	 */
	public static function delete_all_items() {
		global $wpdb;

		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE FROM {$table}" );
	}
}


