<?php
/**
 * CS_Activity_Data_Store_CPT class file.
 *
 * @package CommunityService/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CS Activity Data Store: Stored in CPT.
 *
 * @version  1.0
 */
class CS_Activity_Data_Store_CPT extends Abstract_CS_Activity_Data_Store_CPT implements CS_Object_Data_Store_Interface, CS_Activity_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta" for an activity.
	 *
	 * @since 1.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_activity_date',
		'_attachment',
	);

	/**
	 * Method to create a new activity in the database.
	 *
	 * @param CS_Activity $activity Activity object.
	 */
	public function create( &$activity ) {
		//$activity->set_activity_key( cs_generate_activity_key() );
		parent::create( $activity );
		do_action( 'communityservice_new_activity', $activity->get_id() );
	}

	/**
	 * Read activity data. Can be overridden by child classes to load other props.
	 *
	 * @param CS_Activity $activity Activity object.
	 * @param object   $post_object Post object.
	 * @since 1.0
	 */
	protected function read_activity_data( &$activity, $post_object ) {
		parent::read_activity_data( $activity, $post_object );
		$id             = $activity->get_id();

		$activity->set_props(
			array(
				'activity_date'            => get_post_meta( $id, '_activity_date', true ),
			)
		);
	}

	/**
	 * Method to update an activity in the database.
	 *
	 * @param CS_Activity $activity Activity object.
	 */
	public function update( &$activity ) {

		// Update the activity.
		parent::update( $activity );

		do_action( 'communityservice_update_activity', $activity->get_id() );
	}

	/**
	 * Helper method that updates all the post meta for an activity based on it's settings in the CS_Activity class.
	 *
	 * @param CS_Activity $activity Activity object.
	 * @since 1.0
	 */
	protected function update_post_meta( &$activity ) {
		$updated_props     = array();
		$id                = $activity->get_id();
		$meta_key_to_props = array(
			'_activity_date'       => 'activity_date',
		);
		$props_to_update = $this->get_props_to_update( $activity, $meta_key_to_props );

		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $activity->{"get_$prop"}( 'edit' );

			update_post_meta( $id, $meta_key, $value );

			$updated_props[] = $prop;
		}

		parent::update_post_meta( $activity );
		if($activity->get_attachment('edit')>0){
			global $wpdb;
			$result = $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_parent = %d WHERE post_type = 'attachment' AND ID =".$activity->get_attachment('edit'), $activity->get_id() ) );
		}
		do_action( 'communityservice_activity_object_updated_props', $activity, $updated_props );
	}


	/**
	 * Return count of activities with a specific status.
	 *
	 * @param  string $status Activity status. Function cs_get_activity_statuses() returns a list of valid statuses.
	 * @return int
	 */
	public function get_activity_count( $status ) {
		global $wpdb;
		return absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( * ) FROM {$wpdb->posts} WHERE post_type = 'cs-activity' AND post_status = %s", $status ) ) );
	}

	/**
	 * Search activity data for a term and return ids.
	 *
	 * @param  string $term Searched term.
	 * @return array of ids
	 */
	public function search_activities( $term ) {
		global $wpdb;

		$activity_ids     = array();

		return apply_filters( 'communityservice_shop_activity_search_results', $activity_ids, $term, $search_fields );
	}

	/**
	 * Get the activity type based on Activity ID.
	 *
	 * @since 1.0
	 * @param int $activity_id Activity ID.
	 * @return string
	 */
	public function get_activity_type( $activity_id ) {
		return 'internal';//external
	}

	/**
	 * Get valid WP_Query args from a CS_Activity_Query's query variables.
	 *
	 * @since 1.0
	 * @param array $query_vars query vars from a CS_Activity_Query.
	 * @return array
	 */
	protected function get_wp_query_args( $query_vars ) {

		// Map query vars to ones that get_wp_query_args or WP_Query recognize.
		$key_mapping = array(
			'status'         => 'post_status',
			'page'           => 'paged',
		);

		foreach ( $key_mapping as $query_key => $db_key ) {
			if ( isset( $query_vars[ $query_key ] ) ) {
				$query_vars[ $db_key ] = $query_vars[ $query_key ];
				unset( $query_vars[ $query_key ] );
			}
		}

		// Add the 'cs-' prefix to status if needed.
		if ( ! empty( $query_vars['post_status'] ) ) {
			if ( is_array( $query_vars['post_status'] ) ) {
				foreach ( $query_vars['post_status'] as &$status ) {
					$status = cs_is_activity_status( 'cs-' . $status ) ? 'cs-' . $status : $status;
				}
			} else {
				$query_vars['post_status'] = cs_is_activity_status( 'cs-' . $query_vars['post_status'] ) ? 'cs-' . $query_vars['post_status'] : $query_vars['post_status'];
			}
		}

		$wp_query_args = parent::get_wp_query_args( $query_vars );

		if ( ! isset( $wp_query_args['date_query'] ) ) {
			$wp_query_args['date_query'] = array();
		}
		if ( ! isset( $wp_query_args['meta_query'] ) ) {
			$wp_query_args['meta_query'] = array();
		}

		$date_queries = array(
			'date_created'   => 'post_date',
			'date_modified'  => 'post_modified',
			'activity_date' => '_activity_date',
		);
		foreach ( $date_queries as $query_var_key => $db_key ) {
			if ( isset( $query_vars[ $query_var_key ] ) && '' !== $query_vars[ $query_var_key ] ) {

				// Remove any existing meta queries for the same keys to prevent conflicts.
				$existing_queries = wp_list_pluck( $wp_query_args['meta_query'], 'key', true );
				$meta_query_index = array_search( $db_key, $existing_queries, true );
				if ( false !== $meta_query_index ) {
					unset( $wp_query_args['meta_query'][ $meta_query_index ] );
				}

				$wp_query_args = $this->parse_date_for_wp_query( $query_vars[ $query_var_key ], $db_key, $wp_query_args );
			}
		}

		if ( isset( $query_vars['customer'] ) && '' !== $query_vars['customer'] && array() !== $query_vars['customer'] ) {
			$values         = is_array( $query_vars['customer'] ) ? $query_vars['customer'] : array( $query_vars['customer'] );
			$customer_query = $this->get_activities_generate_customer_meta_query( $values );
			if ( is_wp_error( $customer_query ) ) {
				$wp_query_args['errors'][] = $customer_query;
			} else {
				$wp_query_args['meta_query'][] = $customer_query;
			}
		}

		if ( isset( $query_vars['anonymized'] ) ) {
			if ( $query_vars['anonymized'] ) {
				$wp_query_args['meta_query'][] = array(
					'key'   => '_anonymized',
					'value' => 'yes',
				);
			} else {
				$wp_query_args['meta_query'][] = array(
					'key'     => '_anonymized',
					'compare' => 'NOT EXISTS',
				);
			}
		}

		if ( ! isset( $query_vars['paginate'] ) || ! $query_vars['paginate'] ) {
			$wp_query_args['no_found_rows'] = true;
		}

		return apply_filters( 'communityservice_activity_data_store_cpt_get_activities_query', $wp_query_args, $query_vars, $this );
	}

	/**
	 * Query for Activities matching specific criteria.
	 *
	 * @since 1.0
	 *
	 * @param array $query_vars query vars from a CS_Activity_Query.
	 *
	 * @return array|object
	 */
	public function query( $query_vars ) {
		$args = $this->get_wp_query_args( $query_vars );

		if ( ! empty( $args['errors'] ) ) {
			$query = (object) array(
				'posts'         => array(),
				'found_posts'   => 0,
				'max_num_pages' => 0,
			);
		} else {
			$query = new WP_Query( $args );
		}

		$activities = ( isset( $query_vars['return'] ) && 'ids' === $query_vars['return'] ) ? $query->posts : array_filter( array_map( 'cs_get_activity', $query->posts ) );

		if ( isset( $query_vars['paginate'] ) && $query_vars['paginate'] ) {
			return (object) array(
				'activities'        => $activities,
				'total'         => $query->found_posts,
				'max_num_pages' => $query->max_num_pages,
			);
		}

		return $activities;
	}

}