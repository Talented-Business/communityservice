<?php
/**
 * Class CS_Student_Data_Store file.
 *
 * @package CommunityService\DataStores
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CS Student Data Store.
 *
 * @version  1.0
 */
class CS_Student_Data_Store extends CS_Data_Store_WP implements CS_Student_Data_Store_Interface, CS_Object_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta".
	 *
	 * @since 1.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'locale',
		'first_name',
		'last_name',
		'display_name',
		'admin_color',
		'rich_editing',
		'comment_shortcuts',
		'dismissed_wp_pointers',
		'show_welcome_panel',
		'session_tokens',
		'nickname',
		'wptests_capabilities',
		'wptests_user_level',
		'syntax_highlighting',
		'_task_count',
	);

	/**
	 * Internal meta type used to store user data.
	 *
	 * @var string
	 */
	protected $meta_type = 'user';

	/**
	 * Callback to remove unwanted meta data.
	 *
	 * @param object $meta Meta object.
	 * @return bool
	 */
	protected function exclude_internal_meta_keys( $meta ) {
		global $wpdb;

		$table_prefix = $wpdb->prefix ? $wpdb->prefix : 'wp_';

		return ! in_array( $meta->meta_key, $this->internal_meta_keys, true )
			&& 0 !== strpos( $meta->meta_key, 'closedpostboxes_' )
			&& 0 !== strpos( $meta->meta_key, 'metaboxhidden_' )
			&& 0 !== strpos( $meta->meta_key, 'manageedit-' )
			&& ! strstr( $meta->meta_key, $table_prefix )
			&& 0 !== stripos( $meta->meta_key, 'wp_' );
	}

	/**
	 * Method to create a new student in the database.
	 *
	 * @since 1.0
	 *
	 * @param CS_Student $student Student object.
	 *
	 * @throws CS_Data_Exception If unable to create new student.
	 */
	public function create( &$student ) {
		$id = cs_create_new_student( $student->get_email(), $student->get_username(), $student->get_password() );

		if ( is_wp_error( $id ) ) {
			throw new CS_Data_Exception( $id->get_error_code(), $id->get_error_message() );
		}

		$student->set_id( $id );
		$this->update_user_meta( $student );

		// Prevent wp_update_user calls in the same request and student trigger the 'Notice of Password Changed' email.
		$student->set_password( '' );

		wp_update_user(
			apply_filters(
				'communityservice_update_student_args',
				array(
					'ID'           => $student->get_id(),
					'role'         => $student->get_role(),
					'display_name' => $student->get_display_name(),
				),
				$student
			)
		);
		$wp_user = new WP_User( $student->get_id() );
		$student->set_date_created( $wp_user->user_registered );
		$student->set_date_modified( get_user_meta( $student->get_id(), 'last_update', true ) );
		$student->save_meta_data();
		$student->apply_changes();
		do_action( 'communityservice_new_student', $student->get_id() );
	}

	/**
	 * Method to read a student object.
	 *
	 * @since 1.0
	 * @param CS_Student $student Student object.
	 * @throws Exception If invalid student.
	 */
	public function read( &$student ) {
		$user_object = $student->get_id() ? get_user_by( 'id', $student->get_id() ) : false;

		// User object is required.
		if ( ! $user_object || empty( $user_object->ID ) ) {
			throw new Exception( __( 'Invalid student.', 'communityservice' ) );
		}

		$student_id = $student->get_id();

		// Load meta but exclude deprecated props.
		$user_meta = array_diff_key(
			array_change_key_case( array_map( 'cs_flatten_meta_callback', get_user_meta( $student_id ) ) ),
			array_flip( array( 'country', 'state', 'postcode', 'city', 'address', 'address_2', 'default', 'location' ) )
		);

		$student->set_props( $user_meta );
		$student->set_props(
			array(
				'is_paying_student' => get_user_meta( $student_id, 'paying_student', true ),
				'email'              => $user_object->user_email,
				'username'           => $user_object->user_login,
				'display_name'       => $user_object->display_name,
				'date_created'       => $user_object->user_registered, // Mysql string in local format.
				'date_modified'      => get_user_meta( $student_id, 'last_update', true ),
				'role'               => ! empty( $user_object->roles[0] ) ? $user_object->roles[0] : 'student',
			)
		);
		$student->read_meta_data();
		$student->set_object_read( true );
		do_action( 'communityservice_student_loaded', $student );
	}

	/**
	 * Updates a student in the database.
	 *
	 * @since 1.0
	 * @param CS_Student $student Student object.
	 */
	public function update( &$student ) {
		wp_update_user(
			apply_filters(
				'communityservice_update_student_args',
				array(
					'ID'           => $student->get_id(),
					'user_email'   => $student->get_email(),
					'display_name' => $student->get_display_name(),
				),
				$student
			)
		);

		// Only update password if a new one was set with set_password.
		if ( $student->get_password() ) {
			wp_update_user(
				array(
					'ID'        => $student->get_id(),
					'user_pass' => $student->get_password(),
				)
			);
			$student->set_password( '' );
		}

		$this->update_user_meta( $student );
		$student->set_date_modified( get_user_meta( $student->get_id(), 'last_update', true ) );
		$student->save_meta_data();
		$student->apply_changes();
		do_action( 'communityservice_update_student', $student->get_id() );
	}

	/**
	 * Deletes a student from the database.
	 *
	 * @since 1.0
	 * @param CS_Student $student Student object.
	 * @param array       $args Array of args to pass to the delete method.
	 */
	public function delete( &$student, $args = array() ) {
		if ( ! $student->get_id() ) {
			return;
		}

		$args = wp_parse_args(
			$args,
			array(
				'reassign' => 0,
			)
		);

		$id = $student->get_id();
		wp_delete_user( $id, $args['reassign'] );

		do_action( 'communityservice_delete_student', $id );
	}

	/**
	 * Helper method that updates all the meta for a student. Used for update & create.
	 *
	 * @since 1.0
	 * @param CS_Student $student Student object.
	 */
	private function update_user_meta( $student ) {
		$updated_props = array();
		$changed_props = $student->get_changes();

		$meta_key_to_props = array(
			'paying_student' => 'is_paying_student',
			'first_name'      => 'first_name',
			'last_name'       => 'last_name',
		);

		foreach ( $meta_key_to_props as $meta_key => $prop ) {
			if ( ! array_key_exists( $prop, $changed_props ) ) {
				continue;
			}

			if ( update_user_meta( $student->get_id(), $meta_key, $student->{"get_$prop"}( 'edit' ) ) ) {
				$updated_props[] = $prop;
			}
		}

		do_action( 'communityservice_student_object_updated_props', $student, $updated_props );
	}

	/**
	 * Gets the students last activity.
	 *
	 * @since 1.0
	 * @param CS_Student $student Student object.
	 * @return CS_Order|false
	 */
	public function get_last_activity( &$student ) {
		global $wpdb;

		$last_activity = $wpdb->get_var(
			// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
			"SELECT posts.ID
			FROM $wpdb->posts AS posts
			LEFT JOIN {$wpdb->postmeta} AS meta on posts.ID = meta.post_id
			WHERE meta.meta_key = '_student_user'
			AND   meta.meta_value = '" . esc_sql( $student->get_id() ) . "'
			AND   posts.post_type = 'cs-activity'
			AND   posts.post_status IN ( '" . implode( "','", array_map( 'esc_sql', array_keys( cs_get_activity_statuses() ) ) ) . "' )
			ORDER BY posts.ID DESC"
			// phpcs:enable
		);

		if ( ! $last_activity ) {
			return false;
		}

		return cs_get_activity( absint( $last_activity ) );
	}

	/**
	 * Return the number of activities this student has.
	 *
	 * @since 1.0
	 * @param CS_Student $student Student object.
	 * @return integer
	 */
	public function get_activity_count( &$student ) {
		$count = get_user_meta( $student->get_id(), '_activity_count', true );

		if ( '' === $count ) {
			global $wpdb;

			$count = $wpdb->get_var(
				// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
				"SELECT COUNT(*)
				FROM $wpdb->posts as posts
				LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
				WHERE   meta.meta_key = '_student_user'
				AND     posts.post_type = 'cs-activity'
				AND     posts.post_status IN ( '" . implode( "','", array_map( 'esc_sql', array_keys( cs_get_activity_statuses() ) ) ) . "' )
				AND     meta_value = '" . esc_sql( $student->get_id() ) . "'"
				// phpcs:enable
			);
			update_user_meta( $student->get_id(), '_activity_count', $count );
		}

		return absint( $count );
	}

	/**
	 * Return how much money this student has spent.
	 *
	 * @since 1.0
	 * @param CS_Student $student Student object.
	 * @return float
	 */
	public function get_total_spent( &$student ) {
		$spent = apply_filters(
			'communityservice_student_get_total_spent',
			get_user_meta( $student->get_id(), '_money_spent', true ),
			$student
		);

		if ( '' === $spent ) {
			global $wpdb;

			$statuses = array_map( 'esc_sql', cs_get_is_paid_statuses() );
			$spent    = $wpdb->get_var(
				// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
				apply_filters(
					'communityservice_student_get_total_spent_query',
					"SELECT SUM(meta2.meta_value)
					FROM $wpdb->posts as posts
					LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
					LEFT JOIN {$wpdb->postmeta} AS meta2 ON posts.ID = meta2.post_id
					WHERE   meta.meta_key       = '_student_user'
					AND     meta.meta_value     = '" . esc_sql( $student->get_id() ) . "'
					AND     posts.post_type     = 'cs-activity'
					AND     posts.post_status   IN ( 'cs-" . implode( "','cs-", $statuses ) . "' )
					AND     meta2.meta_key      = '_activity_total'",
					$student
				)
				// phpcs:enable
			);

			if ( ! $spent ) {
				$spent = 0;
			}
			update_user_meta( $student->get_id(), '_money_spent', $spent );
		}

		return cs_format_decimal( $spent, 2 );
	}

	/**
	 * Search students and return student IDs.
	 *
	 * @param  string     $term Search term.
	 * @param  int|string $limit Limit search results.
	 * @since 3.0.7
	 *
	 * @return array
	 */
	public function search_students( $term, $limit = '' ) {
		$results = apply_filters( 'communityservice_student_pre_search_students', false, $term, $limit );
		if ( is_array( $results ) ) {
			return $results;
		}

		$query = new WP_User_Query(
			apply_filters(
				'communityservice_student_search_students',
				array(
					'search'         => '*' . esc_attr( $term ) . '*',
					'search_columns' => array( 'user_login', 'user_url', 'user_email', 'user_nicename', 'display_name' ),
					'fields'         => 'ID',
					'number'         => $limit,
				),
				$term,
				$limit,
				'main_query'
			)
		);

		$query2 = new WP_User_Query(
			apply_filters(
				'communityservice_student_search_students',
				array(
					'fields'     => 'ID',
					'number'     => $limit,
					'meta_query' => array(
						'relation' => 'OR',
						array(
							'key'     => 'first_name',
							'value'   => $term,
							'compare' => 'LIKE',
						),
						array(
							'key'     => 'last_name',
							'value'   => $term,
							'compare' => 'LIKE',
						),
					),
				),
				$term,
				$limit,
				'meta_query'
			)
		);

		$results = wp_parse_id_list( array_merge( (array) $query->get_results(), (array) $query2->get_results() ) );

		if ( $limit && count( $results ) > $limit ) {
			$results = array_slice( $results, 0, $limit );
		}

		return $results;
	}
}
