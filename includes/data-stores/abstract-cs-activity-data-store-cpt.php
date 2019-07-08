<?php
/**
 * Abstract_CS_Activity_Data_Store_CPT class file.
 *
 * @package CommunityService/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Activity Data Store: Stored in CPT.
 *
 * @version  1.0
 */
abstract class Abstract_CS_Activity_Data_Store_CPT extends CS_Data_Store_WP implements CS_Object_Data_Store_Interface {

	/**
	 * Internal meta type used to store activity data.
	 *
	 * @var string
	 */
	protected $meta_type = 'post';

	/**
	 * Data stored in meta keys, but not considered "meta" for an activity.
	 *
	 * @since 1.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new activity in the database.
	 *
	 * @param CS_Activity $activity Activity object.
	 */
	public function create( &$activity ) {
		$activity->set_date_created( current_time( 'timestamp', true ) );

		$id = wp_insert_post(
			apply_filters(
				'communityservice_new_activity_data',
				array(
					'post_date'     => gmdate( 'Y-m-d H:i:s', $activity->get_date_created( 'edit' )->getOffsetTimestamp() ),
					'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $activity->get_date_created( 'edit' )->getTimestamp() ),
					'post_type'     => $activity->get_type( 'edit' ),
					'post_status'   => 'cs-' . ( $activity->get_status( 'edit' ) ? $activity->get_status( 'edit' ) : apply_filters( 'communityservice_default_activity_status', 'pending' ) ),
					'ping_status'   => 'closed',
					'post_author'   =>  $activity->get_student_id(),
					'post_title'    => $activity->get_name(),
					'post_content'    => $activity->get_description(),
					//'post_password' => cs_generate_activity_key(),
					'post_parent'   => $activity->get_parent_id( 'edit' ),
					'post_excerpt'  => $this->get_post_excerpt( $activity ),
				)
			), true
		);
		if ( $id && ! is_wp_error( $id ) ) {
			$activity->set_id( $id );
			$this->update_post_meta( $activity );
			$activity->save_meta_data();
			$activity->apply_changes();
			$this->clear_caches( $activity );
		}
	}

	/**
	 * Method to read an activity from the database.
	 *
	 * @param CS_Data $activity Activity object.
	 *
	 * @throws Exception If passed activity is invalid.
	 */
	public function read( &$activity ) {
		$activity->set_defaults();
		$post_object = get_post( $activity->get_id() );
		
		if ( ! $activity->get_id() || ! $post_object ) {
			throw new Exception( __( 'Invalid activity.', 'communityservice' ) );
		}

		$activity->set_props(
			array(
				'parent_id'     => $post_object->post_parent,
				'date_created'  => 0 < $post_object->post_date_gmt ? cs_string_to_timestamp( $post_object->post_date_gmt ) : null,
				'date_modified' => 0 < $post_object->post_modified_gmt ? cs_string_to_timestamp( $post_object->post_modified_gmt ) : null,
				'status'        => $post_object->post_status,
				'name'    			=> $post_object->post_title,
				'description'   => $post_object->post_content,
				'student_id'    => $post_object->post_author,
			)
		);

		$this->read_activity_data( $activity, $post_object );
		$activity->read_meta_data();
		$activity->set_object_read( true );

	}

	/**
	 * Method to update an activity in the database.
	 *
	 * @param CS_Activity $activity Activity object.
	 */
	public function update( &$activity ) {
		$activity->save_meta_data();

		if ( null === $activity->get_date_created( 'edit' ) ) {
			$activity->set_date_created( current_time( 'timestamp', true ) );
		}

		$changes = $activity->get_changes();
		// Only update the post when the post data changes.
		if ( array_intersect( array( 'date_created', 'date_modified', 'status', 'parent_id', 'post_excerpt','name','description' ), array_keys( $changes ) ) ) {
			$post_data = array(
				'post_date'         => gmdate( 'Y-m-d H:i:s', $activity->get_date_created( 'edit' )->getOffsetTimestamp() ),
				'post_date_gmt'     => gmdate( 'Y-m-d H:i:s', $activity->get_date_created( 'edit' )->getTimestamp() ),
				'post_status'       => 'cs-' . ( $activity->get_status( 'edit' ) ? $activity->get_status( 'edit' ) : apply_filters( 'communityservice_default_activity_status', 'pending' ) ),
				'post_parent'       => $activity->get_parent_id(),
				'post_excerpt'      => $this->get_post_excerpt( $activity ),
				'post_title'        => $activity->get_name(),
				'post_content'      => $activity->get_description(),
				'post_modified'     => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $activity->get_date_modified( 'edit' )->getOffsetTimestamp() ) : current_time( 'mysql' ),
				'post_modified_gmt' => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $activity->get_date_modified( 'edit' )->getTimestamp() ) : current_time( 'mysql', 1 ),
			);

			/**
			 * When updating this object, to prevent infinite loops, use $wpdb
			 * to update data, since wp_update_post spawns more calls to the
			 * save_post action.
			 *
			 * This ensures hooks are fired by either WP itself (admin screen save),
			 * or an update purely from CRUD.
			 */
			if ( doing_action( 'save_post' ) ) {
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $activity->get_id() ) );
				clean_post_cache( $activity->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $activity->get_id() ), $post_data ) );
			}
			$activity->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		}
		$this->update_post_meta( $activity );
		$activity->apply_changes();
		$this->clear_caches( $activity );
	}

	/**
	 * Method to delete an activity from the database.
	 *
	 * @param CS_Activity $activity Activity object.
	 * @param array    $args Array of args to pass to the delete method.
	 *
	 * @return void
	 */
	public function delete( &$activity, $args = array() ) {
		$id   = $activity->get_id();
		$args = wp_parse_args(
			$args,
			array(
				'force_delete' => false,
			)
		);

		if ( ! $id ) {
			return;
		}

		if ( $args['force_delete'] ) {
			wp_delete_post( $id );
			$activity->set_id( 0 );
			do_action( 'communityservice_delete_activity', $id );
		} else {
			wp_trash_post( $id );
			$activity->set_status( 'trash' );
			do_action( 'communityservice_trash_activity', $id );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Excerpt for post.
	 *
	 * @param  CS_activity $activity Activity object.
	 * @return string
	 */
	protected function get_post_excerpt( $activity ) {
		return '';
	}

	/**
	 * Get a title for the new post type.
	 *
	 * @return string
	 */
	protected function get_post_title() {
		// @codingStandardsIgnoreStart
		/* translators: %s: Activity date */
		return sprintf( __( 'Activity &ndash; %s', 'communityservice' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Activity date parsed by strftime', 'communityservice' ) ) );
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Read activity data. Can be overridden by child classes to load other props.
	 *
	 * @param CS_Activity $activity Activity object.
	 * @param object   $post_object Post object.
	 * @since 1.0
	 */
	protected function read_activity_data( &$activity, $post_object ) {
		$id = $activity->get_id();

		$activity->set_props(
			array(
			)
		);

		// Gets extra data associated with the activity if needed.
		foreach ( $activity->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $activity, $function ) ) ) {
				$activity->{$function}( get_post_meta( $activity->get_id(), '_' . $key, true ) );
			}
		}
	}

	/**
	 * Helper method that updates all the post meta for an activity based on it's settings in the CS_Activity class.
	 *
	 * @param CS_Activity $activity Activity object.
	 * @since 1.0
	 */
	protected function update_post_meta( &$activity ) {
		$updated_props     = array();
		$meta_key_to_props = array(
		);

		$props_to_update = $this->get_props_to_update( $activity, $meta_key_to_props );
		
		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $activity->{"get_$prop"}( 'edit' );
			if ( update_post_meta( $activity->get_id(), $meta_key, $value ) ) {
				$updated_props[] = $prop;
			}
		}

		do_action( 'communityservice_activity_object_updated_props', $activity, $updated_props );
	}

	/**
	 * Clear any caches.
	 *
	 * @param CS_Activity $activity Activity object.
	 * @since 1.0
	 */
	protected function clear_caches( &$activity ) {
		clean_post_cache( $activity->get_id() );
		wp_cache_delete( 'activity-items-' . $activity->get_id(), 'activities' );
	}
}
