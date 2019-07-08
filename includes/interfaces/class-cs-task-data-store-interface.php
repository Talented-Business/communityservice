<?php
/**
 * Task Data Store Interface
 *
 * @version 1.0
 * @package CommunityService/Interface
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CS Task Data Store Interface
 *
 * Functions that must be defined by task store classes.
 *
 * @version  1.0
 */
interface CS_Task_Data_Store_Interface {

	/**
	 * Returns a list of task IDs ( id as key => parent as value) that are
	 * featured. Uses get_posts instead of wc_get_tasks since we want
	 * some extra meta queries and ALL tasks (posts_per_page = -1).
	 *
	 * @return array
	 */
//	public function get_featured_task_ids();

	/**
	 * Return a list of related tasks (using data like categories and IDs).
	 *
	 * @param array $cats_array List of categories IDs.
	 * @param array $tags_array List of tags IDs.
	 * @param array $exclude_ids Excluded IDs.
	 * @param int   $limit Limit of results.
	 * @param int   $task_id Task ID.
	 * @return array
	 */
	public function get_related_tasks( $cats_array, $tags_array, $exclude_ids, $limit, $task_id );

	/**
	 * Returns an array of tasks.
	 *
	 * @param array $args @see wc_get_tasks.
	 * @return array
	 */
	public function get_tasks( $args = array() );

	/**
	 * Get the task type based on task ID.
	 *
	 * @param int $task_id Task ID.
	 * @return bool|string
	 */
	public function get_task_type( $task_id );
}
