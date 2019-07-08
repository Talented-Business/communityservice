<?php
/**
 * Abstract Activity
 *
 * Handles generic activity data and database interaction which is extended by both
 * CS_Activity (regular activities) and CS_Activity_Refund (refunds are negative activities).
 *
 * @class       CS_Abstract_Activity
 * @version     1.0
 * @package     CommunityService/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * CS_Abstract_Activity class.
 */
abstract class CS_Abstract_Activity extends CS_Data {

	/**
	 * Activity Data array. This is the core activity data exposed in APIs since 1.0.
	 *
	 * Notes: cart_tax = cart_tax is the new name for the legacy 'activity_tax'
	 * which is the tax for items only, not shipping.
	 *
	 * @since 1.0
	 * @var array
	 */
	protected $data = array(
		'parent_id'          => 0,
		'status'             => '',
		'name'               => '',
		'student_id'				 =>'',	
		'description'        => '',
		'date_created'       => null,
		'activity_date'      =>'',
		'date_modified'      => null,
		'attachment'         =>'',
	);

	/**
	 * Activity items will be stored here, sometimes before they persist in the DB.
	 *
	 * @since 1.0
	 * @var array
	 */
	protected $items = array();

	/**
	 * Activity items that need deleting are stored here.
	 *
	 * @since 1.0
	 * @var array
	 */
	protected $items_to_delete = array();

	/**
	 * Stores meta in cache for future reads.
	 *
	 * A group must be set to to enable caching.
	 *
	 * @var string
	 */
	protected $cache_group = 'activities';

	/**
	 * Which data store to load.
	 *
	 * @var string
	 */
	protected $data_store_name = 'activity';

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'activity';

	/**
	 * Get the activity if ID is passed, otherwise the activity is new and empty.
	 * This class should NOT be instantiated, but the get_activity function or new CS_Activity_Factory.
	 * should be used. It is possible, but the aforementioned are preferred and are the only.
	 * methods that will be maintained going forward.
	 *
	 * @param  int|object|CS_Activity $activity Activity to read.
	 */
	public function __construct( $activity = 0 ) {
		parent::__construct( $activity );

		if ( is_numeric( $activity ) && $activity > 0 ) {
			$this->set_id( $activity );
		} elseif ( $activity instanceof self ) {
			$this->set_id( $activity->get_id() );
		} elseif ( ! empty( $activity->ID ) ) {
			$this->set_id( $activity->ID );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = CS_Data_Store::load( $this->data_store_name );

		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'cs-activity';
	}

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
				'meta_data'      => $this->get_meta_data(),
			)
		);
	}

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
		if ( $this->data_store ) {
			// Trigger action before saving to the DB. Allows you to adjust object props before save.
			do_action( 'communityservice_before_' . $this->object_type . '_object_save', $this, $this->data_store );

			if ( $this->get_id() ) {
				$this->data_store->update( $this );
			} else {
				$this->data_store->create( $this );
			}
		}
		return $this->get_id();
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get parent activity ID.
	 *
	 * @since 1.0
	 * @param  string $context View or edit context.
	 * @return integer
	 */
	public function get_parent_id( $context = 'view' ) {
		return $this->get_prop( 'parent_id', $context );
	}

	/**
	 * Get date_created.
	 *
	 * @param  string $context View or edit context.
	 * @return CS_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get activity_date.
	 *
	 * @param  string $context View or edit context.
	 * @return CS_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_activity_date( $context = 'view' ) {
		return $this->get_prop( 'activity_date', $context );
	}

	/**
	 * Get date_modified.
	 *
	 * @param  string $context View or edit context.
	 * @return CS_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Return the activity statuses without cs- internal prefix.
	 *
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		$status = $this->get_prop( 'status', $context );

		if ( empty( $status ) && 'view' === $context ) {
			// In view context, return the default status if no status has been set.
			$status = apply_filters( 'communityservice_default_activity_status', 'pending' );
		}
		return $status;
	}

	public function get_attachment( $context = 'view' ) {
		if($context == 'edit' ){
			return $this->get_prop( 'attachment', $context );
		}
		global $wpdb;
		$result = $wpdb->get_var(  "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_parent =".$this->get_id()  );
		return $result;
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD Getters
	|--------------------------------------------------------------------------
	*/
	/**
	 * Get all valid statuses for this activity
	 *
	 * @since 1.0
	 * @return array Internal status keys e.g. 'cs-processing'
	 */
	protected function get_valid_statuses() {
		return array_keys( cs_get_activity_statuses() );
	}

	/**
	 * Get user ID. Used by activities, not other activity types like refunds.
	 *
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_user_id( $context = 'view' ) {
		return 0;
	}

	/**
	 * Get user. Used by activities, not other activity types like refunds.
	 *
	 * @return WP_User|false
	 */
	public function get_user() {
		return false;
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
	*/

	/**
	 * Set parent activity ID.
	 *
	 * @since 1.0
	 * @param int $value Value to set.
	 * @throws CS_Data_Exception Exception thrown if parent ID does not exist or is invalid.
	 */
	public function set_parent_id( $value ) {
		if ( $value && ( $value === $this->get_id() || ! cs_get_activity( $value ) ) ) {
			$this->error( 'activity_invalid_parent_id', __( 'Invalid parent ID', 'communityservice' ) );
		}
		$this->set_prop( 'parent_id', absint( $value ) );
	}

	/**
	 * Set activity status.
	 *
	 * @since 1.0
	 * @param string $new_status Status to change the activity to. No internal cs- prefix is required.
	 * @return array details of change
	 */
	public function set_status( $new_status ) {
		$old_status = $this->get_status();
		$new_status = 'cs-' === substr( $new_status, 0, 3 ) ? substr( $new_status, 3 ) : $new_status;
		// If setting the status, ensure it's set to a valid status.
		if ( true === $this->object_read ) {
			// Only allow valid new status.
			if ( ! in_array( 'cs-' . $new_status, $this->get_valid_statuses(), true ) && 'trash' !== $new_status ) {
				$new_status = 'pending';
			}

			// If the old status is set but unknown (e.g. draft) assume its pending for action usage.
			if ( $old_status && ! in_array( 'cs-' . $old_status, $this->get_valid_statuses(), true ) && 'trash' !== $old_status ) {
				$old_status = 'pending';
			}
		}

		$this->set_prop( 'status', $new_status );

		return array(
			'from' => $old_status,
			'to'   => $new_status,
		);
	}
	public function set_attachment($attachment_id){
		$this->set_prop( 'attachment', $attachment_id );
	}
	/**
	 * Set date_created.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 * @throws CS_Data_Exception Exception may be thrown if value is invalid.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set activity_date.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 * @throws CS_Data_Exception Exception may be thrown if value is invalid.
	 */
	public function set_activity_date( $date = null ) {
		$this->set_prop( 'activity_date', $date );
	}

	/**
	 * Set date_modified.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 * @throws CS_Data_Exception Exception may be thrown if value is invalid.
	 */
	public function set_date_modified( $date = null ) {
		$this->set_date_prop( 'date_modified', $date );
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
	 * Checks the activity status against a passed in status.
	 *
	 * @param array|string $status Status to check.
	 * @return bool
	 */
	public function has_status( $status ) {
		return apply_filters( 'communityservice_activity_has_status', ( is_array( $status ) && in_array( $this->get_status(), $status, true ) ) || $this->get_status() === $status, $this, $status );
	}

}
