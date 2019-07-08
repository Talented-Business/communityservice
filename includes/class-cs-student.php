<?php
/**
 * The CommunityStudent student class handles storage of the current student's data, such as location.
 *
 * @package CommunityStudent/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Student class.
 */
class CS_Student extends CS_Data {

	/**
	 * Stores student data.
	 *
	 * @var array
	 */
	protected $data = array(
		'date_created'       => null,
		'date_modified'      => null,
		'email'              => '',
		'first_name'         => '',
		'last_name'          => '',
		'display_name'       => '',
		'role'               => 'student',
		'username'           => '',
		'is_paying_student' => false,
	);

	/**
	 * Stores a password if this needs to be changed. Write-only and hidden from _data.
	 *
	 * @var string
	 */
	protected $password = '';


	/**
	 * Load student data based on how CS_Student is called.
	 *
	 * If $student is 'new', you can build a new CS_Student object. If it's empty, some
	 * data will be pulled from the session for the current user/student.
	 *
	 * @param CS_Student|int $data       Student ID or data.
	 * @param bool            $is_session True if this is the student session.
	 * @throws Exception If student cannot be read/found and $data is set.
	 */
	public function __construct( $data = 0, $is_session = false ) {
		parent::__construct( $data );

		if ( $data instanceof CS_Student ) {
			$this->set_id( absint( $data->get_id() ) );
		} elseif ( is_numeric( $data ) ) {
			$this->set_id( $data );
		}

		$this->data_store = CS_Data_Store::load( 'student' );

		// If we have an ID, load the user from the DB.
		if ( $this->get_id() ) {
			try {
				$this->data_store->read( $this );
			} catch ( Exception $e ) {
				$this->set_id( 0 );
				$this->set_object_read( true );
			}
		} else {
			$this->set_object_read( true );
		}

		// If this is a session, set or change the data store to sessions. Changes do not persist in the database.
		if ( $is_session ) {
			$this->data_store = CS_Data_Store::load( 'student-session' );
			$this->data_store->read( $this );
		}
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @since  1.0
	 * @return string
	 */
	protected function get_hook_prefix() {
		return 'communityservice_student_get_';
	}

	/**
	 * Delete a student and reassign posts..
	 *
	 * @param int $reassign Reassign posts and links to new User ID.
	 * @since 1.0
	 * @return bool
	 */
	public function delete_and_reassign( $reassign = null ) {
		if ( $this->data_store ) {
			$this->data_store->delete(
				$this, array(
					'force_delete' => true,
					'reassign'     => $reassign,
				)
			);
			$this->set_id( 0 );
			return true;
		}
		return false;
	}

	/**
	 * Is student outside base country (for tax purposes)?
	 *
	 * @return bool
	 */
	public function is_student_outside_base() {
		list( $country, $state ) = $this->get_taxable_address();
		if ( $country ) {
			$default = cs_get_base_location();
			if ( $default['country'] !== $country ) {
				return true;
			}
			if ( $default['state'] && $default['state'] !== $state ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return this student's avatar.
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_avatar_url() {
		return get_avatar_url( $this->get_email() );
	}

	/**
	 * Gets a student's downloadable products.
	 *
	 * @return array Array of downloadable products
	 */
	public function get_downloadable_products() {
		$downloads = array();
		if ( $this->get_id() ) {
			$downloads = cs_get_student_available_downloads( $this->get_id() );
		}
		return apply_filters( 'communityservice_student_get_downloadable_products', $downloads );
	}

	/**
	 * Get password (only used when updating the user object).
	 *
	 * @return string
	 */
	public function get_password() {
		return $this->password;
	}


	/**
	 * Set student's password.
	 *
	 * @since 1.0
	 * @param string $password Password.
	 */
	public function set_password( $password ) {
		$this->password = $password;
	}

	/**
	 * Gets the students last activity.
	 *
	 * @return CS_Activity|false
	 */
	public function get_last_activity() {
		return $this->data_store->get_last_activity( $this );
	}

	/**
	 * Return the number of activities this student has.
	 *
	 * @return integer
	 */
	public function get_activity_count() {
		return $this->data_store->get_activity_count( $this );
	}

	/**
	 * Return how much money this student has spent.
	 *
	 * @return float
	 */
	public function get_total_spent() {
		return $this->data_store->get_total_spent( $this );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Return the student's username.
	 *
	 * @since  1.0
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_username( $context = 'view' ) {
		return $this->get_prop( 'username', $context );
	}

	/**
	 * Return the student's email.
	 *
	 * @since  1.0
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_email( $context = 'view' ) {
		return $this->get_prop( 'email', $context );
	}

	/**
	 * Return student's first name.
	 *
	 * @since  1.0
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_first_name( $context = 'view' ) {
		return $this->get_prop( 'first_name', $context );
	}

	/**
	 * Return student's last name.
	 *
	 * @since  1.0
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_last_name( $context = 'view' ) {
		return $this->get_prop( 'last_name', $context );
	}

	/**
	 * Return student's display name.
	 *
	 * @since  3.1.0
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_display_name( $context = 'view' ) {
		return $this->get_prop( 'display_name', $context );
	}

	/**
	 * Return student's user role.
	 *
	 * @since  1.0
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_role( $context = 'view' ) {
		return $this->get_prop( 'role', $context );
	}

	/**
	 * Return the date this student was created.
	 *
	 * @since  1.0
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return CS_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Return the date this student was last updated.
	 *
	 * @since  1.0
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return CS_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}


	/**
	 * Is the user a paying student?
	 *
	 * @since  1.0
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_is_paying_student( $context = 'view' ) {
		return $this->get_prop( 'is_paying_student', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set student's username.
	 *
	 * @since 1.0
	 * @param string $username Username.
	 */
	public function set_username( $username ) {
		$this->set_prop( 'username', $username );
	}

	/**
	 * Set student's email.
	 *
	 * @since 1.0
	 * @param string $value Email.
	 */
	public function set_email( $value ) {
		if ( $value && ! is_email( $value ) ) {
			$this->error( 'student_invalid_email', __( 'Invalid email address', 'communityservice' ) );
		}
		$this->set_prop( 'email', sanitize_email( $value ) );
	}

	/**
	 * Set student's first name.
	 *
	 * @since 1.0
	 * @param string $first_name First name.
	 */
	public function set_first_name( $first_name ) {
		$this->set_prop( 'first_name', $first_name );
	}

	/**
	 * Set student's last name.
	 *
	 * @since 1.0
	 * @param string $last_name Last name.
	 */
	public function set_last_name( $last_name ) {
		$this->set_prop( 'last_name', $last_name );
	}

	/**
	 * Set student's display name.
	 *
	 * @since 3.1.0
	 * @param string $display_name Display name.
	 */
	public function set_display_name( $display_name ) {
		/* translators: 1: first name 2: last name */
		$this->set_prop( 'display_name', is_email( $display_name ) ? sprintf( _x( '%1$s %2$s', 'display name', 'communityservice' ), $this->get_first_name(), $this->get_last_name() ) : $display_name );
	}

	/**
	 * Set student's user role(s).
	 *
	 * @since 1.0
	 * @param mixed $role User role.
	 */
	public function set_role( $role ) {
		global $wp_roles;

		if ( $role && ! empty( $wp_roles->roles ) && ! in_array( $role, array_keys( $wp_roles->roles ), true ) ) {
			$this->error( 'student_invalid_role', __( 'Invalid role', 'communityservice' ) );
		}
		$this->set_prop( 'role', $role );
	}

	/**
	 * Set the date this student was last updated.
	 *
	 * @since  1.0
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set the date this student was last updated.
	 *
	 * @since  1.0
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_modified( $date = null ) {
		$this->set_date_prop( 'date_modified', $date );
	}

	/**
	 * Set if the user a paying student.
	 *
	 * @since 1.0
	 * @param bool $is_paying_student If is a paying student.
	 */
	public function set_is_paying_student( $is_paying_student ) {
		$this->set_prop( 'is_paying_student', (bool) $is_paying_student );
	}
}
