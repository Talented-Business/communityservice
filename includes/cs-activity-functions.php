<?php
/**
 * CommunityService Activity Functions
 *
 * Functions for activity specific things.
 *
 * @package CommunityService/Functions
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Standard way of retrieving activities based on certain parameters.
 *
 * This function should be used for activity retrieval so that when we move to
 * custom tables, functions still work.
 *
 * Args and usage: https://github.com/communityservice/communityservice/wiki/cs_get_activities-and-CS_Activity_Query
 *
 * @since  1.0
 * @param  array $args Array of args (above).
 * @return CS_Activity[]|stdClass Number of pages and an array of activity objects if
 *                             paginate is true, or just an array of values.
 */
function cs_get_activities( $args ) {
	$map_legacy = array(
		'post_type'      => 'type',
		'post_status'    => 'status',
		'post_parent'    => 'parent',
		'author'         => 'student',
		'email'          => 'billing_email',
		'posts_per_page' => 'limit',
		'paged'          => 'page',
	);

	foreach ( $map_legacy as $from => $to ) {
		if ( isset( $args[ $from ] ) ) {
			$args[ $to ] = $args[ $from ];
		}
	}

	// Map legacy date args to modern date args.
	$date_before = false;
	$date_after  = false;

	if ( ! empty( $args['date_before'] ) ) {
		$datetime    = cs_string_to_datetime( $args['date_before'] );
		$date_before = strpos( $args['date_before'], ':' ) ? $datetime->getOffsetTimestamp() : $datetime->date( 'Y-m-d' );
	}
	if ( ! empty( $args['date_after'] ) ) {
		$datetime   = cs_string_to_datetime( $args['date_after'] );
		$date_after = strpos( $args['date_after'], ':' ) ? $datetime->getOffsetTimestamp() : $datetime->date( 'Y-m-d' );
	}

	if ( $date_before && $date_after ) {
		$args['date_created'] = $date_after . '...' . $date_before;
	} elseif ( $date_before ) {
		$args['date_created'] = '<' . $date_before;
	} elseif ( $date_after ) {
		$args['date_created'] = '>' . $date_after;
	}

	$query = new CS_Activity_Query( $args );
	return $query->get_activities();
}

/**
 * Main function for returning activities, uses the CS_Activity_Factory class.
 *
 * @since  2.2
 *
 * @param  mixed $the_activity Post object or post ID of the activity.
 *
 * @return bool|CS_Activity|CS_Refund
 */
function cs_get_activity( $the_activity = false ) {
	return new CS_Activity( $the_activity );
}

/**
 * Get all activity statuses.
 *
 * @since 2.2
 * @used-by CS_Activity::set_status
 * @return array
 */
function cs_get_activity_statuses() {
	$activity_statuses = array(
		'cs-pending' => _x( 'Pending', 'Activity status', 'communityservice' ),
		'cs-approved'  => _x( 'Approved', 'Activity status', 'communityservice' ),
		'cs-cancelled'  => _x( 'Declined', 'Activity status', 'communityservice' ),
	);
	return apply_filters( 'cs_activity_statuses', $activity_statuses );
}

/**
 * See if a string is an activity status.
 *
 * @param  string $maybe_status Status, including any cs- prefix.
 * @return bool
 */
function cs_is_activity_status( $maybe_status ) {
	$activity_statuses = cs_get_activity_statuses();
	return isset( $activity_statuses[ $maybe_status ] );
}

/**
 * Get the nice name for an activity status.
 *
 * @since  2.2
 * @param  string $status Status.
 * @return string
 */
function cs_get_activity_status_name( $status ) {
	$statuses = cs_get_activity_statuses();
	$status   = 'cs-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;
	$status   = isset( $statuses[ 'cs-' . $status ] ) ? $statuses[ 'cs-' . $status ] : $status;
	return $status;
}

/**
 * Get all registered activity types.
 *
 * @since  2.2
 * @param  string $for Optionally define what you are getting activity types for so
 *                     only relevant types are returned.
 *                     e.g. for 'activity-meta-boxes', 'activity-count'.
 * @return array
 */
function cs_get_activity_types( $for = '' ) {
	global $cs_activity_types;
	
	if ( ! is_array( $cs_activity_types ) ) {
		$cs_activity_types = array();
	}

	$activity_types = array();

	switch ( $for ) {
		case 'activity-count':
			foreach ( $cs_activity_types as $type => $args ) {
				if ( ! $args['exclude_from_activity_count'] ) {
					$activity_types[] = $type;
				}
			}
			break;
		case 'activity-meta-boxes':
			foreach ( $cs_activity_types as $type => $args ) {
				if ( $args['add_activity_meta_boxes'] ) {
					$activity_types[] = $type;
				}
			}
			break;
		case 'view-activities':
			foreach ( $cs_activity_types as $type => $args ) {
				if ( ! $args['exclude_from_activity_views'] ) {
					$activity_types[] = $type;
				}
			}
			break;
		case 'reports':
			foreach ( $cs_activity_types as $type => $args ) {
				if ( ! $args['exclude_from_activity_reports'] ) {
					$activity_types[] = $type;
				}
			}
			break;
		case 'sales-reports':
			foreach ( $cs_activity_types as $type => $args ) {
				if ( ! $args['exclude_from_activity_sales_reports'] ) {
					$activity_types[] = $type;
				}
			}
			break;
		case 'activity-webhooks':
			foreach ( $cs_activity_types as $type => $args ) {
				if ( ! $args['exclude_from_activity_webhooks'] ) {
					$activity_types[] = $type;
				}
			}
			break;
		default:
			$activity_types = array_keys( $cs_activity_types );
			break;
	}

	return apply_filters( 'cs_activity_types', $activity_types, $for );
}

/**
 * Get an activity type by post type name.
 *
 * @param  string $type Post type name.
 * @return bool|array Details about the activity type.
 */
function cs_get_activity_type( $type ) {
	global $cs_activity_types;

	if ( isset( $cs_activity_types[ $type ] ) ) {
		return $cs_activity_types[ $type ];
	}

	return false;
}

/**
 * Register activity type. Do not use before init.
 *
 * Wrapper for register post type, as well as a method of telling CS which.
 * post types are types of activities, and having them treated as such.
 *
 * $args are passed to register_post_type, but there are a few specific to this function:
 *      - exclude_from_activities_screen (bool) Whether or not this activity type also get shown in the main.
 *      activities screen.
 *      - add_activity_meta_boxes (bool) Whether or not the activity type gets cs-activity meta boxes.
 *      - exclude_from_activity_count (bool) Whether or not this activity type is excluded from counts.
 *      - exclude_from_activity_views (bool) Whether or not this activity type is visible by students when.
 *      viewing activities e.g. on the my account page.
 *      - exclude_from_activity_reports (bool) Whether or not to exclude this type from core reports.
 *      - exclude_from_activity_sales_reports (bool) Whether or not to exclude this type from core sales reports.
 *
 * @since  2.2
 * @see    register_post_type for $args used in that function
 * @param  string $type Post type. (max. 20 characters, can not contain capital letters or spaces).
 * @param  array  $args An array of arguments.
 * @return bool Success or failure
 */
function cs_register_activity_type( $type, $args = array() ) {
	if ( post_type_exists( $type ) ) {
		return false;
	}

	global $cs_activity_types;

	if ( ! is_array( $cs_activity_types ) ) {
		$cs_activity_types = array();
	}

	// Register as a post type.
	if ( is_wp_error( register_post_type( $type, $args ) ) ) {
		return false;
	}

	// Register for CS usage.
	$activity_type_args = array(
		'exclude_from_activities_screen'       => false,
		'add_activity_meta_boxes'             => true,
		'exclude_from_activity_count'         => false,
		'exclude_from_activity_views'         => false,
		'exclude_from_activity_webhooks'      => false,
		'exclude_from_activity_reports'       => false,
		'exclude_from_activity_sales_reports' => false,
		'class_name'                       => 'CS_Activity',
	);

	$args                    = array_intersect_key( $args, $activity_type_args );
	$args                    = wp_parse_args( $args, $activity_type_args );
	$cs_activity_types[ $type ] = $args;

	return true;
}

/**
 * Return the count of pending activities.
 *
 * @access public
 * @return int
 */
function cs_pending_activity_count() {
	return cs_activities_count( 'pending' );
}

/**
 * Return the activities count of a specific activity status.
 *
 * @param string $status Status.
 * @return int
 */
function cs_activities_count( $status ) {
	$count          = 0;
	$status         = 'cs-' . $status;
	$activity_statuses = array_keys( cs_get_activity_statuses() );

	if ( ! in_array( $status, $activity_statuses, true ) ) {
		return 0;
	}

	$cache_key    = CS_Cache_Helper::get_cache_prefix( 'activities' ) . $status;
	$cached_count = wp_cache_get( $cache_key, 'counts' );

	if ( false !== $cached_count ) {
		return $cached_count;
	}

	foreach ( cs_get_activity_types( 'activity-count' ) as $type ) {
		$data_store = CS_Data_Store::load( 'cs-activity' === $type ? 'activity' : $type );
		if ( $data_store ) {
			$count += $data_store->get_activity_count( $status );
		}
	}

	wp_cache_set( $cache_key, $count, 'counts' );

	return $count;
}

/**
 * Clear all transients cache for activity data.
 *
 * @param int|CS_Activity $activity Activity instance or ID.
 */
function cs_delete_cs_activity_transients( $activity = 0 ) {
	if ( is_numeric( $activity ) ) {
		$activity = cs_get_activity( $activity );
	}
	$reports             = CS_Admin_Reports::get_reports();
	$transients_to_clear = array(
		'cs_admin_report',
	);

	foreach ( $reports as $report_group ) {
		foreach ( $report_group['reports'] as $report_key => $report ) {
			$transients_to_clear[] = 'cs_report_' . $report_key;
		}
	}

	foreach ( $transients_to_clear as $transient ) {
		delete_transient( $transient );
	}

	// Clear money spent for user associated with activity.
	if ( is_a( $activity, 'CS_Activity' ) ) {
		$activity_id = $activity->get_id();
		delete_user_meta( $activity->get_student_id(), '_money_spent' );
		delete_user_meta( $activity->get_student_id(), '_activity_count' );
	} else {
		$activity_id = 0;
	}

	// Increments the transient version to invalidate cache.
	CS_Cache_Helper::get_transient_version( 'activities', true );

	// Do the same for regular cache.
	CS_Cache_Helper::incr_cache_prefix( 'activities' );

	do_action( 'communityservice_delete_cs-activity_transients', $activity_id );
}

/**
 * Search activities.
 *
 * @since  1.0
 * @param  string $term Term to search.
 * @return array List of activities ID.
 */
function cs_activity_search( $term ) {
	$data_store = CS_Data_Store::load( 'activity' );
	return $data_store->search_activities( str_replace( 'Activity #', '', cs_clean( $term ) ) );
}

/**
 * Sanitize activity id removing unwanted characters.
 *
 * E.g Users can sometimes try to track an activity id using # with no success.
 * This function will fix this.
 *
 * @since 3.1.0
 * @param int $activity_id Activity ID.
 */
function cs_sanitize_activity_id( $activity_id ) {
	return filter_var( $activity_id, FILTER_SANITIZE_NUMBER_INT );
}
add_filter( 'communityservice_shortcode_activity_tracking_activity_id', 'cs_sanitize_activity_id' );