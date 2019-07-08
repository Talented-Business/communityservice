<?php
/**
 * CommunityService task base class.
 *
 * @package CommunityService/Abstracts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy task contains all deprecated methods for this class and can be
 * removed in the future.
 */

/**
 * Abstract Task Class
 *
 * The CommunityService task class handles individual task data.
 *
 * @version  1.0
 * @package  CommunityService/Abstracts
 */
class CS_Task extends CS_Data {

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'task';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'cs-task';

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	protected $cache_group = 'tasks';

	/**
	 * Stores task data.
	 *
	 * @var array
	 */
	protected $data = array(
		'name'               => '',
		'slug'               => '',
		'date_created'       => null,
		'date_modified'      => null,
		'status'             => false,
		'featured'           => false,
		'catalog_visibility' => 'visible',
		'description'        => '',
		'short_description'  => '',
		'parent_id'          => 0,
		'category_ids'       => array(),
		'tag_ids'            => array(),
		'image_id'           => '',
		'duties'        	 => '',
		'years'				 => array(),
		'houses'			 => array(),
		'gallery_image_ids'  => array(),
	);

	/**
	 * Supported features such as ''.
	 *
	 * @var array
	 */
	protected $supports = array();

	/**
	 * Get the task if ID is passed, otherwise the task is new and empty.
	 * This class should NOT be instantiated, but the cs_get_task() function
	 * should be used. It is possible, but the cs_get_task() is preferred.
	 *
	 * @param int|CS_Task|object $task Task to init.
	 */
	public function __construct( $task = 0 ) {
		parent::__construct( $task );
		if ( is_numeric( $task ) && $task > 0 ) {
			$this->set_id( $task );
		} elseif ( $task instanceof self ) {
			$this->set_id( absint( $task->get_id() ) );
		} elseif ( ! empty( $task->ID ) ) {
			$this->set_id( absint( $task->ID ) );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = CS_Data_Store::load( 'task-' . $this->get_type() );
		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/**
	 * Get internal type. Should return string and *should be overridden* by child classes.
	 *
	 * The task_type property is deprecated but is used here for BW compatibility with child classes which may be defining task_type and not have a get_type method.
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_type() {
		return isset( $this->task_type ) ? $this->task_type : 'simple';
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	|
	| Methods for getting data from the task object.
	*/

	/**
	 * Get task name.
	 *
	 * @since 1.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	/**
	 * Get task slug.
	 *
	 * @since 1.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_slug( $context = 'view' ) {
		return $this->get_prop( 'slug', $context );
	}

	/**
	 * Get task created date.
	 *
	 * @since 1.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return CS_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get task modified date.
	 *
	 * @since 1.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return CS_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Get task status.
	 *
	 * @since 1.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * If the task is featured.
	 *
	 * @since 1.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return boolean
	 */
	public function get_featured( $context = 'view' ) {
		return $this->get_prop( 'featured', $context );
	}

	/**
	 * Get catalog visibility.
	 *
	 * @since 1.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_catalog_visibility( $context = 'view' ) {
		return $this->get_prop( 'catalog_visibility', $context );
	}

	/**
	 * Get task description.
	 *
	 * @since 1.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_description( $context = 'view' ) {
		return $this->get_prop( 'description', $context );
	}

	/**
	 * Get task short description.
	 *
	 * @since 1.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_short_description( $context = 'view' ) {
		return $this->get_prop( 'short_description', $context );
	}

	/**
	 * Get parent ID.
	 *
	 * @since 1.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_parent_id( $context = 'view' ) {
		return $this->get_prop( 'parent_id', $context );
	}

	/**
	 * Get category ids.
	 *
	 * @since 1.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return array
	 */
	public function get_category_ids( $context = 'view' ) {
		return $this->get_prop( 'category_ids', $context );
	}

	/**
	 * Get tag ids.
	 *
	 * @since 1.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return array
	 */
	public function get_tag_ids( $context = 'view' ) {
		return $this->get_prop( 'tag_ids', $context );
	}

	/**
	 * Returns the gallery attachment ids.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return array
	 */
	public function get_gallery_image_ids( $context = 'view' ) {
		return $this->get_prop( 'gallery_image_ids', $context );
	}

	/**
	 * Get main image ID.
	 *
	 * @since 1.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_image_id( $context = 'view' ) {
		return $this->get_prop( 'image_id', $context );
	}

	public function get_duties( $context = 'view' ) {
		return $this->get_prop( 'duties', $context );
	}
	public function get_years( $context = 'view' ) {
		return $this->get_prop( 'years', $context );
	}
	public function get_houses( $context = 'view' ) {
		return $this->get_prop( 'houses', $context );
	}
	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Functions for setting task data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object.
	*/

	/**
	 * Set task name.
	 *
	 * @since 1.0
	 * @param string $name Task name.
	 */
	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}

	/**
	 * Set task slug.
	 *
	 * @since 1.0
	 * @param string $slug Task slug.
	 */
	public function set_slug( $slug ) {
		$this->set_prop( 'slug', $slug );
	}

	/**
	 * Set task created date.
	 *
	 * @since 1.0
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set task modified date.
	 *
	 * @since 1.0
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_modified( $date = null ) {
		$this->set_date_prop( 'date_modified', $date );
	}

	/**
	 * Set task status.
	 *
	 * @since 1.0
	 * @param string $status Task status.
	 */
	public function set_status( $status ) {
		$this->set_prop( 'status', $status );
	}

	/**
	 * Set if the task is featured.
	 *
	 * @since 1.0
	 * @param bool|string $featured Whether the task is featured or not.
	 */
	public function set_featured( $featured ) {
		$this->set_prop( 'featured', cs_string_to_bool( $featured ) );
	}

	/**
	 * Set task description.
	 *
	 * @since 1.0
	 * @param string $description Task description.
	 */
	public function set_description( $description ) {
		$this->set_prop( 'description', $description );
	}

	/**
	 * Set task short description.
	 *
	 * @since 1.0
	 * @param string $short_description Task short description.
	 */
	public function set_short_description( $short_description ) {
		$this->set_prop( 'short_description', $short_description );
	}

	/**
	 * Set parent ID.
	 *
	 * @since 1.0
	 * @param int $parent_id Task parent ID.
	 */
	public function set_parent_id( $parent_id ) {
		$this->set_prop( 'parent_id', absint( $parent_id ) );
	}
	/**
	 * Set the task categories.
	 *
	 * @since 1.0
	 * @param array $term_ids List of terms IDs.
	 */
	public function set_category_ids( $term_ids ) {
		$this->set_prop( 'category_ids', array_unique( array_map( 'intval', $term_ids ) ) );
	}

	/**
	 * Set the task tags.
	 *
	 * @since 1.0
	 * @param array $term_ids List of terms IDs.
	 */
	public function set_tag_ids( $term_ids ) {
		$this->set_prop( 'tag_ids', array_unique( array_map( 'intval', $term_ids ) ) );
	}

	/**
	 * Set gallery attachment ids.
	 *
	 * @since 1.0
	 * @param array $image_ids List of image ids.
	 */
	public function set_gallery_image_ids( $image_ids ) {
		$image_ids = wp_parse_id_list( $image_ids );

		if ( $this->get_object_read() ) {
			$image_ids = array_filter( $image_ids, 'wp_attachment_is_image' );
		}

		$this->set_prop( 'gallery_image_ids', $image_ids );
	}

	/**
	 * Set main image ID.
	 *
	 * @since 1.0
	 * @param int|string $image_id Task image id.
	 */
	public function set_image_id( $image_id = '' ) {
		$this->set_prop( 'image_id', $image_id );
	}

	public function set_duties( $duties ) {
		$this->set_prop( 'duties', $duties );
	}

	public function set_years( $years ) {
		$this->set_prop( 'years', array_unique($years) );
	}

	public function set_houses( $houses ) {
		$this->set_prop( 'houses', array_unique($houses) );
	}

	/*
	|--------------------------------------------------------------------------
	| Other Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Ensure properties are set correctly before save.
	 *
	 * @since 1.0
	 */
	public function validate_props() {
		// Before updating, ensure stock props are all aligned. Qty, backorders and low stock amount are not needed if not stock managed.
	}

	/**
	 * Save data (either create or update depending on if we are working on an existing task).
	 *
	 * @since 1.0
	 * @return int
	 */
	public function save() {
		$this->validate_props();

		if ( $this->data_store ) {
			// Trigger action before saving to the DB. Use a pointer to adjust object props before save.
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
	| Conditionals
	|--------------------------------------------------------------------------
	*/

	/**
	 * Check if a task supports a given feature.
	 *
	 * Task classes should override this to declare support (or lack of support) for a feature.
	 *
	 * @param string $feature string The name of a feature to test support for.
	 * @return bool True if the task supports the feature, false otherwise.
	 * @since 2.5.0
	 */
	public function supports( $feature ) {
		return apply_filters( 'communityservice_task_supports', in_array( $feature, $this->supports, true ), $feature, $this );
	}

	/**
	 * Returns whether or not the task post exists.
	 *
	 * @return bool
	 */
	public function exists() {
		return false !== $this->get_status();
	}

	/**
	 * Checks the task type.
	 *
	 * Backwards compatibility with downloadable/virtual.
	 *
	 * @param string|array $type Array or string of types.
	 * @return bool
	 */
	public function is_type( $type ) {
		return ( $this->get_type() === $type || ( is_array( $type ) && in_array( $this->get_type(), $type, true ) ) );
	}

	/**
	 * Returns whether or not the task is featured.
	 *
	 * @return bool
	 */
	public function is_featured() {
		return true === $this->get_featured();
	}

	/**
	 * Returns whether or not the task has any child task.
	 *
	 * @return bool
	 */
	public function has_child() {
		return 0 < count( $this->get_children() );
	}

	/**
	 * Returns whether or not the task has additional options that need
	 * selecting before adding to cart.
	 *
	 * @since  1.0
	 * @return boolean
	 */
	public function has_options() {
		return false;
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the task's title. For tasks this is the task name.
	 *
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'communityservice_task_title', $this->get_name(), $this );
	}

	/**
	 * Task permalink.
	 *
	 * @return string
	 */
	public function get_permalink() {
		return get_permalink( $this->get_id() );
	}

	/**
	 * Returns the children IDs if applicable. Overridden by child classes.
	 *
	 * @return array of IDs
	 */
	public function get_children() {
		return array();
	}

	/**
	 * Returns the main task image.
	 *
	 * @param string $size (default: 'communityservice_thumbnail').
	 * @param array  $attr Image attributes.
	 * @param bool   $placeholder True to return $placeholder if no image is found, or false to return an empty string.
	 * @return string
	 */
	public function get_image( $size = 'communityservice_thumbnail', $attr = array(), $placeholder = true ) {
		$image = '';
		if ( $this->get_image_id() ) {
			$image = wp_get_attachment_image( $this->get_image_id(), $size, false, $attr );
		} elseif ( $this->get_parent_id() ) {
			$parent_task = cs_get_task( $this->get_parent_id() );
			if ( $parent_task ) {
				$image = $parent_task->get_image( $size, $attr, $placeholder );
			}
		}

		if ( ! $image && $placeholder ) {
			$image = cs_placeholder_img( $size );
		}

		return apply_filters( 'communityservice_task_get_image', $image, $this, $size, $attr, $placeholder, $image );
	}

}
