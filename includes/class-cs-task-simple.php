<?php
/**
 * Simple Task Class.
 *
 * The default task type kinda task.
 *
 * @package CommunityService/Classes/Tasks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Simple task class.
 */
class CS_Task_Simple extends CS_Task {

	/**
	 * Initialize simple task.
	 *
	 * @param CS_Task|int $task Task instance or ID.
	 */
	public function __construct( $task = 0 ) {
		parent::__construct( $task );
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'simple';
	}

}
