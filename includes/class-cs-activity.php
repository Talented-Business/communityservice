<?php
/**
 * Regular activity
 *
 * @package CommunityService\Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Activity Class.
 *
 * These are regular CommunityService activities, which extend the abstract activity class.
 */
class CS_Activity extends CS_Abstract_Activity {

	/**
	 * Stores data about status changes so relevant hooks can be fired.
	 *
	 * @var bool|array
	 */
	protected $status_transition = false;

	/**
	 * Activity Data array. This is the core activity data exposed in APIs since 1.0.
	 *
	 * @since 1.0
	 * @var array
	 */

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	|
	| Methods which create, read, update and delete activities from the database.
	| Written in abstract fashion so that the way activities are stored can be
	| changed more easily in the future.
	|
	| A save method is included for convenience (chooses update or create based
	| on if the activity exists yet).
	|
	*/

	/**
	 * Save data to the database.
	 *
	 * @since 1.0
	 * @return int activity ID
	 */
	public function save() {
		try {

			if ( $this->data_store ) {
				// Trigger action before saving to the DB. Allows you to adjust object props before save.
				do_action( 'communityservice_before_' . $this->object_type . '_object_save', $this, $this->data_store );

				if ( $this->get_id() ) {
					$this->data_store->update( $this );
				} else {
					$this->data_store->create( $this );
				}
			}

			$this->status_transition();
		} catch ( Exception $e ) {
			$logger = cs_get_logger();
			$logger->error(
				sprintf( 'Error saving activity #%d', $this->get_id() ), array(
					'activity' => $this,
					'error' => $e,
				)
			);
			$this->add_activity_note( __( 'Error saving activity.', 'communityservice' ) . ' ' . $e->getMessage() );
		}

		return $this->get_id();
	}

	/**
	 * Set activity status.
	 *
	 * @since 1.0
	 * @param string $new_status    Status to change the activity to. No internal cs- prefix is required.
	 * @param string $note          Optional note to add.
	 * @param bool   $manual_update Is this a manual activity status change?.
	 * @return array
	 */
	public function set_status( $new_status, $note = '', $manual_update = false ) {
		$result = parent::set_status( $new_status );
		if ( true === $this->object_read && ! empty( $result['from'] ) && $result['from'] !== $result['to'] ) {
			$this->status_transition = array(
				'from'   => ! empty( $this->status_transition['from'] ) ? $this->status_transition['from'] : $result['from'],
				'to'     => $result['to'],
				'note'   => $note,
				'manual' => (bool) $manual_update,
			);

			if ( $manual_update ) {
				do_action( 'communityservice_activity_edit_status', $this->get_id(), $result['to'] );
			}
			$this->maybe_set_date_completed();
		}

		return $result;
	}


	/**
	 * Maybe set date completed.
	 *
	 * Sets the date completed variable when transitioning to completed status.
	 *
	 * @since 1.0
	 */
	protected function maybe_set_date_completed() {
		if ( $this->has_status( 'completed' ) ) {
			$this->set_date_completed( current_time( 'timestamp', true ) );
		}
	}

	/**
	 * Updates status of activity immediately.
	 *
	 * @uses CS_Activity::set_status()
	 * @param string $new_status    Status to change the activity to. No internal cs- prefix is required.
	 * @param string $note          Optional note to add.
	 * @param bool   $manual        Is this a manual activity status change?.
	 * @return bool
	 */
	public function update_status( $new_status, $note = '', $manual = false ) {
		if ( ! $this->get_id() ) { // Activity must exist.
			return false;
		}

		try {
			$this->set_status( $new_status, $note, $manual );
			$this->save();
		} catch ( Exception $e ) {
			$logger = cs_get_logger();
			$logger->error(
				sprintf( 'Error updating status for activity #%d', $this->get_id() ), array(
					'activity' => $this,
					'error' => $e,
				)
			);
			$this->add_activity_note( __( 'Update status event failed.', 'communityservice' ) . ' ' . $e->getMessage() );
			return false;
		}
		return true;
	}

	public function set_name( $value = null ) {
		$this->set_prop( 'name', $value );
	}

	public function set_description( $value = null ) {
		$this->set_prop( 'description', $value );
	}

	/**
	 * Handle the status transition.
	 */
	protected function status_transition() {
		$status_transition = $this->status_transition;

		// Reset status transition variable.
		$this->status_transition = false;

		if ( $status_transition ) {
			try {
				do_action( 'communityservice_activity_status_' . $status_transition['to'], $this->get_id(), $this );

				if ( ! empty( $status_transition['from'] ) ) {
					/* translators: 1: old activity status 2: new activity status */
					$transition_note = sprintf( __( 'Activity status changed from %1$s to %2$s.', 'communityservice' ), cs_get_activity_status_name( $status_transition['from'] ), cs_get_activity_status_name( $status_transition['to'] ) );

					do_action( 'communityservice_activity_status_' . $status_transition['from'] . '_to_' . $status_transition['to'], $this->get_id(), $this );
					do_action( 'communityservice_activity_status_changed', $this->get_id(), $status_transition['from'], $status_transition['to'], $this );
				} else {
					/* translators: %s: new activity status */
					$transition_note = sprintf( __( 'Activity status set to %s.', 'communityservice' ), cs_get_activity_status_name( $status_transition['to'] ) );
				}

				// Note the transition occurred.
				//$this->add_activity_note( trim( $status_transition['note'] . ' ' . $transition_note ), 0, $status_transition['manual'] );
			} catch ( Exception $e ) {
				$logger = cs_get_logger();
				$logger->error(
					sprintf( 'Status transition of activity #%d errored!', $this->get_id() ), array(
						'activity' => $this,
						'error' => $e,
					)
				);
				$this->add_activity_note( __( 'Error during status transition.', 'communityservice' ) . ' ' . $e->getMessage() );
			}
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	|
	| Methods for getting data from the activity object.
	|
	*/

	/**
	 * Get all class data in array format.
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_data() {
		return array_merge(
			array(
				'id' => $this->get_id(),
			),
			$this->data,
			array(
				'number'         => $this->get_activity_number(),
				'meta_data'      => $this->get_meta_data(),
			)
		);
	}

	/**
	 * Expands the shipping and billing information in the changes array.
	 */
	public function get_changes() {
		$changed_props = parent::get_changes();
		return $changed_props;
	}

	/**
	 * Gets the activity number for display (by default, activity ID).
	 *
	 * @return string
	 */
	public function get_activity_number() {
		return (string) apply_filters( 'communityservice_activity_number', $this->get_id(), $this );
	}

	/**
	 * Get student_id.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_student_id( $context = 'view' ) {
		return $this->get_prop( 'student_id', $context );
	}

	/**
	 * Alias for get_student_id().
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_user_id( $context = 'view' ) {
		return $this->get_student_id( $context );
	}

	/**
	 * Get the user associated with the activity. False for guests.
	 *
	 * @return WP_User|false
	 */
	public function get_user() {
		return $this->get_user_id() ? get_user_by( 'id', $this->get_user_id() ) : false;
	}
	public function get_student(){
		$student = get_user_by('id',$this->get_student_id());
		return $student;
	}
	/**
	 * Get date completed.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return CS_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_completed( $context = 'view' ) {
		return $this->get_prop( 'date_completed', $context );
	}

	public function get_name($context = 'view'){
		return $this->get_prop( 'name',$context );
	}

	public function get_description($context = 'view'){
		return $this->get_prop( 'description',$context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Functions for setting activity data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object. However, for backwards compatibility pre 1.0 some of these
	| setters may handle both.
	|
	*/

	/**
	 * Set activity key.
	 *
	 * @param string $value Max length 22 chars.
	 * @throws CS_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_activity_key( $value ) {
		$this->set_prop( 'activity_key', substr( $value, 0, 22 ) );
	}

	/**
	 * Set student id.
	 *
	 * @param int $value Student ID.
	 * @throws CS_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_student_id( $value ) {
		$this->set_prop( 'student_id', absint( $value ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Conditionals
	|--------------------------------------------------------------------------
	|
	| Checks if a condition is true or false.
	|
	*/

	/**
	 * Check if an activity key is valid.
	 *
	 * @param string $key Activity key.
	 * @return bool
	 */
	public function key_is_valid( $key ) {
		return $key === $this->get_activity_key();
	}

	/*
	|--------------------------------------------------------------------------
	| URLs and Endpoints
	|--------------------------------------------------------------------------
	*/

	/**
	 * Generates a URL to view an activity from the my account page.
	 *
	 * @return string
	 */
	public function get_view_activity_url() {
		return apply_filters( 'communityservice_get_view_activity_url', cs_get_endpoint_url( 'view-activity', $this->get_id(), cs_get_page_permalink( 'myaccount' ) ), $this );
	}

	/**
	 * Get's the URL to edit the activity in the backend.
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_edit_activity_url() {
		return apply_filters( 'communityservice_get_edit_activity_url', get_admin_url( null, 'post.php?post=' . $this->get_id() . '&action=edit' ), $this );
	}
	public function process_submit(){
		$document_id = null;
		if($_FILES[ 'activity_document' ]){
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			$document_id = media_handle_upload( 'activity_document', 0 );
		}
		//if(isset($_POST['attachment']))$document_id = $_POST['attachment'];
		$posted_data = $this->get_posted_data($document_id);
		$activity_id = $this->create_activity( $posted_data );
		$activity    = cs_get_activity( $activity_id );
		if ( is_wp_error( $activity_id ) ) {
			throw new Exception( $activity_id->get_error_message() );
		}

		if ( ! $activity ) {
			throw new Exception( __( 'Unable to create activity.', 'communityservice' ) );
		}
		return $activity_id;
	}
	protected function get_posted_data($document_id=null) {
		if(isset($_POST['task_id'])){
			$task = cs_get_task($_POST['task_id']);
			$data    = array(
				'name'              => $task->get_name(),
				'activity_date'     => cs_clean( wp_unslash( $_POST['date'] )), // WPCS: input var ok, CSRF ok.
				'description'       => $task->get_description(),
				'parent_id' 			=> $task->get_id()
			);
		}else{
			$data    = array(
				'name'              => cs_clean( wp_unslash( $_POST['activityname'] )), // WPCS: input var ok, CSRF ok.
				'activity_date'                      => cs_clean( wp_unslash( $_POST['date'] )), // WPCS: input var ok, CSRF ok.
				'description'               => cs_sanitize_textarea( wp_unslash( $_POST['description'] )), // WPCS: input var ok, CSRF ok.
			);
		}
		if($document_id!=null)$data['attachment'] = $document_id;
		return $data;
	}
	/**
	 * Create an activity. Error codes:
	 *      520 - Cannot insert activity into the database.
	 *      521 - Cannot get activity after creation.
	 *      522 - Cannot update activity.
	 *
	 * @throws Exception When checkout validation fails.
	 * @param  array $data Posted data.
	 * @return int|WP_ERROR
	 */
	protected function create_activity( $data ) {
		// Give plugins the opportunity to create an activity themselves.
		$activity_id = apply_filters( 'communityservice_create_activity', null, $this );
		if ( $activity_id ) {
			return $activity_id;
		}

		try {
			$activity = $this;//new CS_Activity();
			foreach ( $data as $key => $value ) {
				if ( is_callable( array( $activity, "set_{$key}" ) ) ) {
					$activity->{"set_{$key}"}( $value );
				}
			}
			if(!$activity->get_id())$activity->set_student_id(get_current_user_id());
			/**
			 * Action hook to adjust activity before save.
			 *
			 * @since 1.0
			 */
			do_action( 'communityservice_create_activity', $activity, $data );
			// Save the activity.
			$activity_id = $activity->save();
			CS()->mailer()->emails['CS_Email_New_Activity']->trigger( $activity->get_id(), $activity );
			do_action( 'communityservice_update_activity_meta', $activity_id, $data );

			return $activity_id;
		} catch ( Exception $e ) {
			return new WP_Error( 'checkout-error', $e->getMessage() );
		}
	}

}
