<?php
/**
 * CommunityService Student Functions
 *
 * Functions for students.
 *
 * @package CommunityService/Functions
 * @version 2.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prevent any user who cannot 'edit_posts' (subscribers, students etc) from seeing the admin bar.
 *
 * Note: get_option( 'communityservice_lock_down_admin', true ) is a deprecated option here for backwards compatibility. Defaults to true.
 *
 * @param bool $show_admin_bar If should display admin bar.
 * @return bool
 */
function cs_disable_admin_bar( $show_admin_bar ) {
	if ( apply_filters( 'communityservice_disable_admin_bar', get_option( 'communityservice_lock_down_admin', 'yes' ) === 'yes' ) && ! ( current_user_can( 'edit_posts' ) || current_user_can( 'manage_communityservice' ) ) ) {
		$show_admin_bar = false;
	}

	return $show_admin_bar;
}
add_filter( 'show_admin_bar', 'cs_disable_admin_bar', 10, 1 ); // phpcs:ignore WordPress.VIP.AdminBarRemoval.RemovalDetected

if ( ! function_exists( 'cs_create_new_student' ) ) {

	/**
	 * Create a new student.
	 *
	 * @param  string $email Student email.
	 * @param  string $username Student username.
	 * @param  string $password Student password.
	 * @return int|WP_Error Returns WP_Error on failure, Int (user ID) on success.
	 */
	function cs_create_new_student( $email, $username = '', $password = '' ) {

		// Check the email address.
		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_Error( 'registration-error-invalid-email', __( 'Please provide a valid email address.', 'communityservice' ) );
		}

		if ( email_exists( $email ) ) {
			return new WP_Error( 'registration-error-email-exists', apply_filters( 'communityservice_registration_error_email_exists', __( 'An account is already registered with your email address. Please log in.', 'communityservice' ), $email ) );
		}

		// Handle username creation.
		if ( 'no' === get_option( 'communityservice_registration_generate_username' ) || ! empty( $username ) ) {
			$username = sanitize_user( $username );

			if ( empty( $username ) || ! validate_username( $username ) ) {
				return new WP_Error( 'registration-error-invalid-username', __( 'Please enter a valid account username.', 'communityservice' ) );
			}

			if ( username_exists( $username ) ) {
				return new WP_Error( 'registration-error-username-exists', __( 'An account is already registered with that username. Please choose another.', 'communityservice' ) );
			}
		} else {
			$username = sanitize_user( current( explode( '@', $email ) ), true );

			// Ensure username is unique.
			$append     = 1;
			$o_username = $username;

			while ( username_exists( $username ) ) {
				$username = $o_username . $append;
				$append++;
			}
		}

		// Handle password creation.
		$password_generated = false;
		if ( 'yes' === get_option( 'communityservice_registration_generate_password' ) && empty( $password ) ) {
			$password           = wp_generate_password();
			$password_generated = true;
		}

		if ( empty( $password ) ) {
			return new WP_Error( 'registration-error-missing-password', __( 'Please enter an account password.', 'communityservice' ) );
		}

		// Use WP_Error to handle registration errors.
		$errors = new WP_Error();

		do_action( 'communityservice_register_post', $username, $email, $errors );

		$errors = apply_filters( 'communityservice_registration_errors', $errors, $username, $email );

		if ( $errors->get_error_code() ) {
			return $errors;
		}

		$new_student_data = apply_filters(
			'communityservice_new_student_data', array(
				'user_login' => $username,
				'user_pass'  => $password,
				'user_email' => $email,
				'role'       => 'subscriber',
			)
		);

		$student_id = wp_insert_user( $new_student_data );
		
		if ( is_wp_error( $student_id ) ) {
			return new WP_Error( 'registration-error', __( 'Couldn&#8217;t register you&hellip; please contact us if you continue to have problems.', 'communityservice' ) );
		}

		do_action( 'communityservice_created_student', $student_id, $new_student_data, $password_generated );

		return $student_id;
	}
}

/**
 * Login a student (set auth cookie and set global user object).
 *
 * @param int $student_id Student ID.
 */
function cs_set_student_auth_cookie( $student_id ) {
	global $current_user;

	$current_user = get_user_by( 'id', $student_id ); // WPCS: override ok.

	wp_set_auth_cookie( $student_id, true );
}

/**
 * Get past orders (by email) and update them.
 *
 * @param  int $student_id Student ID.
 * @return int
 */
function cs_update_new_student_past_orders( $student_id ) {
	$linked          = 0;
	$complete        = 0;
	$student        = get_user_by( 'id', absint( $student_id ) );
	$student_orders = cs_get_orders(
		array(
			'limit'    => -1,
			'student' => array( array( 0, $student->user_email ) ),
			'return'   => 'ids',
		)
	);

	if ( ! empty( $student_orders ) ) {
		foreach ( $student_orders as $order_id ) {
			$order = cs_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}

			$order->set_student_id( $student->ID );
			$order->save();

			if ( $order->has_downloadable_item() ) {
				$data_store = CS_Data_Store::load( 'student-download' );
				$data_store->delete_by_order_id( $order->get_id() );
				cs_downloadable_product_permissions( $order->get_id(), true );
			}

			do_action( 'communityservice_update_new_student_past_order', $order_id, $student );

			if ( get_post_status( $order_id ) === 'cs-completed' ) {
				$complete++;
			}

			$linked++;
		}
	}

	if ( $complete ) {
		update_user_meta( $student_id, 'paying_student', 1 );
		update_user_meta( $student_id, '_order_count', '' );
		update_user_meta( $student_id, '_money_spent', '' );
	}

	return $linked;
}

/**
 * Order Status completed - This is a paying student.
 *
 * @param int $order_id Order ID.
 */
function cs_paying_student( $order_id ) {
	$order       = cs_get_order( $order_id );
	$student_id = $order->get_student_id();

	if ( $student_id > 0 && 'shop_order_refund' !== $order->get_type() ) {
		$student = new CS_Student( $student_id );
		$student->set_is_paying_student( true );
		$student->save();
	}
}
add_action( 'communityservice_order_status_completed', 'cs_paying_student' );

/**
 * Checks if a user (by email or ID or both) has bought an item.
 *
 * @param string $student_email Student email to check.
 * @param int    $user_id User ID to check.
 * @param int    $product_id Product ID to check.
 * @return bool
 */
function cs_student_bought_product( $student_email, $user_id, $product_id ) {
	global $wpdb;

	$result = apply_filters( 'communityservice_pre_student_bought_product', null, $student_email, $user_id, $product_id );

	if ( null !== $result ) {
		return $result;
	}

	$transient_name = 'cs_cbp_' . md5( $student_email . $user_id . CS_Cache_Helper::get_transient_version( 'orders' ) );
	$result         = get_transient( $transient_name );

	if ( false === $result ) {
		$student_data = array( $user_id );

		if ( $user_id ) {
			$user = get_user_by( 'id', $user_id );

			if ( isset( $user->user_email ) ) {
				$student_data[] = $user->user_email;
			}
		}

		if ( is_email( $student_email ) ) {
			$student_data[] = $student_email;
		}

		$student_data = array_map( 'esc_sql', array_filter( array_unique( $student_data ) ) );
		$statuses      = array_map( 'esc_sql', cs_get_is_paid_statuses() );

		if ( count( $student_data ) === 0 ) {
			return false;
		}

		$result = $wpdb->get_col(
			"
			SELECT im.meta_value FROM {$wpdb->posts} AS p
			INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
			INNER JOIN {$wpdb->prefix}communityservice_order_items AS i ON p.ID = i.order_id
			INNER JOIN {$wpdb->prefix}communityservice_order_itemmeta AS im ON i.order_item_id = im.order_item_id
			WHERE p.post_status IN ( 'cs-" . implode( "','cs-", $statuses ) . "' )
			AND pm.meta_key IN ( '_billing_email', '_student_user' )
			AND im.meta_key IN ( '_product_id', '_variation_id' )
			AND im.meta_value != 0
			AND pm.meta_value IN ( '" . implode( "','", $student_data ) . "' )
		"
		); // WPCS: unprepared SQL ok.
		$result = array_map( 'absint', $result );

		set_transient( $transient_name, $result, DAY_IN_SECONDS * 30 );
	}
	return in_array( absint( $product_id ), $result, true );
}

/**
 * Checks if the current user has a role.
 *
 * @param string $role The role.
 * @return bool
 */
function cs_current_user_has_role( $role ) {
	return cs_user_has_role( wp_get_current_user(), $role );
}

/**
 * Checks if a user has a role.
 *
 * @param int|\WP_User $user The user.
 * @param string       $role The role.
 * @return bool
 */
function cs_user_has_role( $user, $role ) {
	if ( ! is_object( $user ) ) {
		$user = get_userdata( $user );
	}

	if ( ! $user || ! $user->exists() ) {
		return false;
	}

	return in_array( $role, $user->roles, true );
}

/**
 * Checks if a user has a certain capability.
 *
 * @param array $allcaps All capabilities.
 * @param array $caps    Capabilities.
 * @param array $args    Arguments.
 * @return bool
 */
function cs_student_has_capability( $allcaps, $caps, $args ) {
	if ( isset( $caps[0] ) ) {
		switch ( $caps[0] ) {
			case 'view_order':
				$user_id = intval( $args[1] );
				$order   = cs_get_order( $args[2] );

				if ( $order && $user_id === $order->get_user_id() ) {
					$allcaps['view_order'] = true;
				}
				break;
			case 'pay_for_order':
				$user_id  = intval( $args[1] );
				$order_id = isset( $args[2] ) ? $args[2] : null;

				// When no order ID, we assume it's a new order
				// and thus, student can pay for it.
				if ( ! $order_id ) {
					$allcaps['pay_for_order'] = true;
					break;
				}

				$order = cs_get_order( $order_id );

				if ( $order && ( $user_id === $order->get_user_id() || ! $order->get_user_id() ) ) {
					$allcaps['pay_for_order'] = true;
				}
				break;
			case 'order_again':
				$user_id = intval( $args[1] );
				$order   = cs_get_order( $args[2] );

				if ( $order && $user_id === $order->get_user_id() ) {
					$allcaps['order_again'] = true;
				}
				break;
			case 'cancel_order':
				$user_id = intval( $args[1] );
				$order   = cs_get_order( $args[2] );

				if ( $order && $user_id === $order->get_user_id() ) {
					$allcaps['cancel_order'] = true;
				}
				break;
			case 'download_file':
				$user_id  = intval( $args[1] );
				$download = $args[2];

				if ( $download && $user_id === $download->get_user_id() ) {
					$allcaps['download_file'] = true;
				}
				break;
		}
	}
	return $allcaps;
}
add_filter( 'user_has_cap', 'cs_student_has_capability', 10, 3 );

/**
 * Modify the list of editable roles to prevent non-admin adding admin users.
 *
 * @param  array $roles Roles.
 * @return array
 */
function cs_modify_editable_roles( $roles ) {
	if ( is_multisite() && is_super_admin() ) {
		return $roles;
	}
	if ( ! cs_current_user_has_role( 'administrator' ) ) {
		unset( $roles['administrator'] );

		if ( cs_current_user_has_role( 'shop_manager' ) ) {
			$shop_manager_editable_roles = apply_filters( 'communityservice_shop_manager_editable_roles', array( 'student' ) );
			return array_intersect_key( $roles, array_flip( $shop_manager_editable_roles ) );
		}
	}

	return $roles;
}
add_filter( 'editable_roles', 'cs_modify_editable_roles' );

/**
 * Modify capabilities to prevent non-admin users editing admin users.
 *
 * $args[0] will be the user being edited in this case.
 *
 * @param  array  $caps    Array of caps.
 * @param  string $cap     Name of the cap we are checking.
 * @param  int    $user_id ID of the user being checked against.
 * @param  array  $args    Arguments.
 * @return array
 */
function cs_modify_map_meta_cap( $caps, $cap, $user_id, $args ) {
	if ( is_multisite() && is_super_admin() ) {
		return $caps;
	}
	switch ( $cap ) {
		case 'edit_user':
		case 'remove_user':
		case 'promote_user':
		case 'delete_user':
			if ( ! isset( $args[0] ) || $args[0] === $user_id ) {
				break;
			} else {
				if ( ! cs_current_user_has_role( 'administrator' ) ) {
					if ( cs_user_has_role( $args[0], 'administrator' ) ) {
						$caps[] = 'do_not_allow';
					} elseif ( cs_current_user_has_role( 'shop_manager' ) ) {
						// Shop managers can only edit student info.
						$userdata = get_userdata( $args[0] );
						$shop_manager_editable_roles = apply_filters( 'communityservice_shop_manager_editable_roles', array( 'student' ) );
						if ( property_exists( $userdata, 'roles' ) && ! empty( $userdata->roles ) && ! array_intersect( $userdata->roles, $shop_manager_editable_roles ) ) {
							$caps[] = 'do_not_allow';
						}
					}
				}
			}
			break;
	}
	return $caps;
}
add_filter( 'map_meta_cap', 'cs_modify_map_meta_cap', 10, 4 );

/**
 * Get student download permissions from the database.
 *
 * @param int $student_id Student/User ID.
 * @return array
 */
function cs_get_student_download_permissions( $student_id ) {
	$data_store = CS_Data_Store::load( 'student-download' );
	return apply_filters( 'communityservice_permission_list', $data_store->get_downloads_for_student( $student_id ), $student_id );
}

/**
 * Get student available downloads.
 *
 * @param int $student_id Student/User ID.
 * @return array
 */
function cs_get_student_available_downloads( $student_id ) {
	$downloads   = array();
	$_product    = null;
	$order       = null;
	$file_number = 0;

	// Get results from valid orders only.
	$results = cs_get_student_download_permissions( $student_id );

	if ( $results ) {
		foreach ( $results as $result ) {
			$order_id = intval( $result->order_id );

			if ( ! $order || $order->get_id() !== $order_id ) {
				// New order.
				$order    = cs_get_order( $order_id );
				$_product = null;
			}

			// Make sure the order exists for this download.
			if ( ! $order ) {
				continue;
			}

			// Check if downloads are permitted.
			if ( ! $order->is_download_permitted() ) {
				continue;
			}

			$product_id = intval( $result->product_id );

			if ( ! $_product || $_product->get_id() !== $product_id ) {
				// New product.
				$file_number = 0;
				$_product    = cs_get_product( $product_id );
			}

			// Check product exists and has the file.
			if ( ! $_product || ! $_product->exists() || ! $_product->has_file( $result->download_id ) ) {
				continue;
			}

			$download_file = $_product->get_file( $result->download_id );

			// Download name will be 'Product Name' for products with a single downloadable file, and 'Product Name - File X' for products with multiple files.
			$download_name = apply_filters(
				'communityservice_downloadable_product_name',
				$download_file['name'],
				$_product,
				$result->download_id,
				$file_number
			);

			$downloads[] = array(
				'download_url'        => add_query_arg(
					array(
						'download_file' => $product_id,
						'order'         => $result->order_key,
						'email'         => rawurlencode( $result->user_email ),
						'key'           => $result->download_id,
					),
					home_url( '/' )
				),
				'download_id'         => $result->download_id,
				'product_id'          => $_product->get_id(),
				'product_name'        => $_product->get_name(),
				'product_url'         => $_product->is_visible() ? $_product->get_permalink() : '', // Since 3.3.0.
				'download_name'       => $download_name,
				'order_id'            => $order->get_id(),
				'order_key'           => $order->get_order_key(),
				'downloads_remaining' => $result->downloads_remaining,
				'access_expires'      => $result->access_expires,
				'file'                => array(
					'name' => $download_file->get_name(),
					'file' => $download_file->get_file(),
				),
			);

			$file_number++;
		}
	}

	return apply_filters( 'communityservice_student_available_downloads', $downloads, $student_id );
}

/**
 * Get total spent by student.
 *
 * @param  int $user_id User ID.
 * @return string
 */
function cs_get_student_total_spent( $user_id ) {
	$student = new CS_Student( $user_id );
	return $student->get_total_spent();
}

/**
 * Get total orders by student.
 *
 * @param  int $user_id User ID.
 * @return int
 */
function cs_get_student_order_count( $user_id ) {
	$student = new CS_Student( $user_id );
	return $student->get_order_count();
}

/**
 * Reset _student_user on orders when a user is deleted.
 *
 * @param int $user_id User ID.
 */
function cs_reset_order_student_id_on_deleted_user( $user_id ) {
	global $wpdb;

	$wpdb->update(
		$wpdb->postmeta, array( 'meta_value' => 0 ), array(
			'meta_key'   => '_student_user',
			'meta_value' => $user_id,
		)
	); // WPCS: slow query ok.
}

add_action( 'deleted_user', 'cs_reset_order_student_id_on_deleted_user' );

/**
 * Get review verification status.
 *
 * @param  int $comment_id Comment ID.
 * @return bool
 */
function cs_review_is_from_verified_owner( $comment_id ) {
	$verified = get_comment_meta( $comment_id, 'verified', true );
	return '' === $verified ? CS_Comments::add_comment_purchase_verification( $comment_id ) : (bool) $verified;
}

/**
 * Disable author archives for students.
 *
 * @since 2.5.0
 */
function cs_disable_author_archives_for_students() {
	global $author;

	if ( is_author() ) {
		$user = get_user_by( 'id', $author );

		if ( user_can( $user, 'student' ) && ! user_can( $user, 'edit_posts' ) ) {
			wp_redirect( cs_get_page_permalink( 'shop' ) );
		}
	}
}

add_action( 'template_redirect', 'cs_disable_author_archives_for_students' );

/**
 * Hooks into the `profile_update` hook to set the user last updated timestamp.
 *
 * @since 1.0
 * @param int   $user_id The user that was updated.
 * @param array $old     The profile fields pre-change.
 */
function cs_update_profile_last_update_time( $user_id, $old ) {
	cs_set_user_last_update_time( $user_id );
}

add_action( 'profile_update', 'cs_update_profile_last_update_time', 10, 2 );

/**
 * Hooks into the update user meta function to set the user last updated timestamp.
 *
 * @since 1.0
 * @param int    $meta_id     ID of the meta object that was changed.
 * @param int    $user_id     The user that was updated.
 * @param string $meta_key    Name of the meta key that was changed.
 * @param string $_meta_value Value of the meta that was changed.
 */
function cs_meta_update_last_update_time( $meta_id, $user_id, $meta_key, $_meta_value ) {
	$keys_to_track = apply_filters( 'communityservice_user_last_update_fields', array( 'first_name', 'last_name' ) );

	$update_time = in_array( $meta_key, $keys_to_track, true ) ? true : false;
	$update_time = 'billing_' === substr( $meta_key, 0, 8 ) ? true : $update_time;
	$update_time = 'shipping_' === substr( $meta_key, 0, 9 ) ? true : $update_time;

	if ( $update_time ) {
		cs_set_user_last_update_time( $user_id );
	}
}

add_action( 'update_user_meta', 'cs_meta_update_last_update_time', 10, 4 );

/**
 * Sets a user's "last update" time to the current timestamp.
 *
 * @since 1.0
 * @param int $user_id The user to set a timestamp for.
 */
function cs_set_user_last_update_time( $user_id ) {
	update_user_meta( $user_id, 'last_update', gmdate( 'U' ) );
}

/**
 * Get student saved payment methods list.
 *
 * @since 1.0
 * @param int $student_id Student ID.
 * @return array
 */
function cs_get_student_saved_methods_list( $student_id ) {
	return apply_filters( 'communityservice_saved_payment_methods_list', array(), $student_id );
}

/**
 * Get info about student's last order.
 *
 * @since 1.0
 * @param int $student_id Student ID.
 * @return CS_Order|bool Order object if successful or false.
 */
function cs_get_student_last_order( $student_id ) {
	$student = new CS_Student( $student_id );

	return $student->get_last_order();
}

/**
 * Add support for searching by display_name.
 *
 * @since 3.2.0
 * @param array $search_columns Column names.
 * @return array
 */
function cs_user_search_columns( $search_columns ) {
	$search_columns[] = 'display_name';
	return $search_columns;
}
add_filter( 'user_search_columns', 'cs_user_search_columns' );

/**
 * When a user is deleted in WordPress, delete corresponding CommunityService data.
 *
 * @param int $user_id User ID being deleted.
 */
function cs_delete_user_data( $user_id ) {
	global $wpdb;

	// Clean up sessions.
	$wpdb->delete(
		$wpdb->prefix . 'communityservice_sessions',
		array(
			'session_key' => $user_id,
		)
	);

	// Revoke API keys.
	$wpdb->delete(
		$wpdb->prefix . 'communityservice_api_keys',
		array(
			'user_id' => $user_id,
		)
	);

}
add_action( 'delete_user', 'cs_delete_user_data' );

/**
 * Store user agents. Used for tracker.
 *
 * @since 3.0.0
 * @param string     $user_login User login.
 * @param int|object $user       User.
 */
function cs_maybe_store_user_agent( $user_login, $user ) {
	if ( 'yes' === get_option( 'communityservice_allow_tracking', 'no' ) && user_can( $user, 'manage_communityservice' ) ) {
		$admin_user_agents   = array_filter( (array) get_option( 'communityservice_tracker_ua', array() ) );
		$admin_user_agents[] = cs_get_user_agent();
		update_option( 'communityservice_tracker_ua', array_unique( $admin_user_agents ) );
	}
}
add_action( 'wp_login', 'cs_maybe_store_user_agent', 10, 2 );

/**
 * Update logic triggered on login.
 *
 * @since 3.4.0
 * @param string $user_login User login.
 * @param object $user       User.
 */
function cs_user_logged_in( $user_login, $user ) {
	cs_update_user_last_active( $user->ID );
	update_user_meta( $user->ID, '_communityservice_load_saved_cart_after_login', 1 );
}
add_action( 'wp_login', 'cs_user_logged_in', 10, 2 );

/**
 * Update when the user was last active.
 *
 * @since 3.4.0
 */
function cs_current_user_is_active() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	cs_update_user_last_active( get_current_user_id() );
}
add_action( 'wp', 'cs_current_user_is_active', 10 );

/**
 * Set the user last active timestamp to now.
 *
 * @since 3.4.0
 * @param int $user_id User ID to mark active.
 */
function cs_update_user_last_active( $user_id ) {
	if ( ! $user_id ) {
		return;
	}
	update_user_meta( $user_id, 'cs_last_active', (string) strtotime( date( 'Y-m-d', current_time( 'timestamp', true ) ) ) );
}
