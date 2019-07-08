<?php
/**
 * Student Data Store Interface
 *
 * @version 3.0.0
 * @package CommunityService/Interface
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CS Student Data Store Interface
 *
 * Functions that must be defined by student store classes.
 *
 * @version  3.0.0
 */
interface CS_Student_Data_Store_Interface {

	/**
	 * Gets the students last activity.
	 *
	 * @param CS_Student $student Student object.
	 * @return CS_Activity|false
	 */
	public function get_last_activity( &$student );

	/**
	 * Return the number of activities this student has.
	 *
	 * @param CS_Student $student Student object.
	 * @return integer
	 */
	public function get_activity_count( &$student );

	/**
	 * Return how much money this student has spent.
	 *
	 * @param CS_Student $student Student object.
	 * @return float
	 */
	public function get_total_spent( &$student );
}
