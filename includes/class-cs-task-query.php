<?php
/**
 * Class for parameter-based Task querying
 *
 *
 * @package  CommunityService/Classes
 * @version  3.2.0
 * @since    3.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Task query class.
 */
class CS_Task_Query extends CS_Object_Query {

	/**
	 * Valid query vars for tasks.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array_merge(
			parent::get_default_query_vars(),
			array(
				'status'            => array( 'draft', 'pending', 'private', 'publish' ),
				'limit'             => get_option( 'posts_per_page' ),
				'include'           => array(),
				'date_created'      => '',
				'date_modified'     => '',
			)
		);
	}

	/**
	 * Get tasks matching the current query vars.
	 *
	 * @return array|object of CS_Task objects
	 */
	public function get_tasks() {
		$args    = apply_filters( 'communityservice_task_object_query_args', $this->get_query_vars() );
		$results = CS_Data_Store::load( 'task' )->query( $args );
		return apply_filters( 'communityservice_task_object_query', $results, $args );
	}
}
