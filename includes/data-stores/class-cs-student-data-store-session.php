<?php
/**
 * Class CS_Student_Data_Store_Session file.
 *
 * @package CommunityService\DataStores
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CS Student Data Store which stores the data in session.
 *
 * @version  1.0
 */
class CS_Student_Data_Store_Session extends CS_Data_Store_WP implements CS_Student_Data_Store_Interface, CS_Object_Data_Store_Interface {

	/**
	 * Keys which are also stored in a session (so we can make sure they get updated...)
	 *
	 * @var array
	 */
	protected $session_keys = array(
		'id',
		'date_modified',
	);

	/**
	 * Simply update the session.
	 *
	 * @param CS_Student $student Student object.
	 */
	public function create( &$student ) {
		$this->save_to_session( $student );
	}

	/**
	 * Simply update the session.
	 *
	 * @param CS_Student $student Student object.
	 */
	public function update( &$student ) {
		$this->save_to_session( $student );
	}

	/**
	 * Saves all student data to the session.
	 *
	 * @param CS_Student $student Student object.
	 */
	public function save_to_session( $student ) {
		$data = array();
		foreach ( $this->session_keys as $session_key ) {
			$function_key = $session_key;
			if ( 'billing_' === substr( $session_key, 0, 8 ) ) {
				$session_key = str_replace( 'billing_', '', $session_key );
			}
			$data[ $session_key ] = (string) $student->{"get_$function_key"}( 'edit' );
		}
		CS()->session->set( 'student', $data );
	}

	/**
	 * Read student data from the session unless the user has logged in, in
	 * which case the stored ID will differ from the actual ID.
	 *
	 * @since 1.0
	 * @param CS_Student $student Student object.
	 */
	public function read( &$student ) {
		$data = (array) CS()->session->get( 'student' );

		/**
		 * There is a valid session if $data is not empty, and the ID matches the logged in user ID.
		 *
		 * If the user object has been updated since the session was created (based on date_modified) we should not load the session - data should be reloaded.
		 */
		if ( isset( $data['id'], $data['date_modified'] ) && $data['id'] === (string) $student->get_id() && $data['date_modified'] === (string) $student->get_date_modified( 'edit' ) ) {
			foreach ( $this->session_keys as $session_key ) {
				if ( in_array( $session_key, array( 'id', 'date_modified' ), true ) ) {
					continue;
				}
				$function_key = $session_key;
				if ( 'billing_' === substr( $session_key, 0, 8 ) ) {
					$session_key = str_replace( 'billing_', '', $session_key );
				}
				if ( ! empty( $data[ $session_key ] ) && is_callable( array( $student, "set_{$function_key}" ) ) ) {
					$student->{"set_{$function_key}"}( wp_unslash( $data[ $session_key ] ) );
				}
			}
		}
		$this->set_defaults( $student );
		$student->set_object_read( true );
	}

	/**
	 * Load default values if props are unset.
	 *
	 * @param CS_Student $student Student object.
	 */
	protected function set_defaults( &$student ) {
		try {
			$default = cs_get_student_default_location();

			/*if ( ! $student->get_shipping_state() ) {
				$student->set_shipping_state( $student->get_billing_state() );
			}

			if ( ! $student->get_billing_email() && is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				$student->set_billing_email( $current_user->user_email );
			}*/
		} catch ( CS_Data_Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}
	}

	/**
	 * Deletes a student from the database.
	 *
	 * @since 1.0
	 * @param CS_Student $student Student object.
	 * @param array       $args Array of args to pass to the delete method.
	 */
	public function delete( &$student, $args = array() ) {
		CS()->session->set( 'student', null );
	}

	/**
	 * Gets the students last activity.
	 *
	 * @since 1.0
	 * @param CS_Student $student Student object.
	 * @return CS_Order|false
	 */
	public function get_last_activity( &$student ) {
		return false;
	}

	/**
	 * Return the number of activities this student has.
	 *
	 * @since 1.0
	 * @param CS_Student $student Student object.
	 * @return integer
	 */
	public function get_activity_count( &$student ) {
		return 0;
	}

	/**
	 * Return how much money this student has spent.
	 *
	 * @since 1.0
	 * @param CS_Student $student Student object.
	 * @return float
	 */
	public function get_total_spent( &$student ) {
		return 0;
	}
}
