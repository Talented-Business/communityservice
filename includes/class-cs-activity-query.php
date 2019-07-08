<?php
/**
 * Parameter-based Activity querying
 * Args and usage: https://github.com/communityservice/communityservice/wiki/cs_get_activities-and-CS_Activity_Query
 *
 * @package communityservice/Classes
 * @version 3.1.0
 * @since   3.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Activity query class.
 */
class CS_Activity_Query extends CS_Object_Query {

	/**
	 * Valid query vars for activities.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array_merge(
			parent::get_default_query_vars(),
			array(
				'status'               => array_keys( cs_get_activity_statuses() ),
				'type'                 => cs_get_activity_types( 'view-activities' ),
				'date_created'         => '',
				'date_modified'        => '',
				'date_completed'       => '',
				'student'             => '',
				'student_id'          => '',
			)
		);
	}

	/**
	 * Get activities matching the current query vars.
	 *
	 * @return array|object of CS_Activity objects
	 *
	 * @throws Exception When CS_Data_Store validation fails.
	 */
	public function get_activities() {
		$args    = apply_filters( 'communityservice_activity_query_args', $this->get_query_vars() );
		$results = CS_Data_Store::load( 'activity' )->query( $args );
		return apply_filters( 'communityservice_activity_query', $results, $args );
	}
}
