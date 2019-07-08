<?php
/**
 * Activity Data Store Interface
 *
 * @version 1.0
 * @package CommunityService/Interface
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CS Activity Data Store Interface
 *
 * Functions that must be defined by activity store classes.
 *
 * @version  1.0
 */
interface CS_Activity_Data_Store_Interface {

	/**
	 * Return count of activities with a specific status.
	 *
	 * @param string $status Activity status.
	 * @return int
	 */
	public function get_activity_count( $status );


	/**
	 * Search activity data for a term and return ids.
	 *
	 * @param  string $term Term name.
	 * @return array of ids
	 */
	public function search_activities( $term );

	/**
	 * Get the activity type based on Activity ID.
	 *
	 * @param int $activity_id Activity ID.
	 * @return string
	 */
	public function get_activity_type( $activity_id );
}
