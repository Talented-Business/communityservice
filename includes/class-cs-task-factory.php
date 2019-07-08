<?php
/**
 * Task Factory
 *
 * The CommunityService task factory creating the right task object.
 *
 * @package CommunityService/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Task factory class.
 */
class CS_Task_Factory {

	/**
	 * Get a task.
	 *
	 * @param mixed $task_id CS_Task|WP_Post|int|bool $task Task instance, post instance, numeric or false to use global $post.
	 * @param array $deprecated Previously used to pass arguments to the factory, e.g. to force a type.
	 * @return CS_Task|bool Task object or null if the task cannot be loaded.
	 */
	public function get_task( $task_id = false) {
		$task_id = $this->get_task_id( $task_id );

		if ( ! $task_id ) {
			return false;
		}

		$task_type = $this->get_task_type( $task_id );

		$classname = $this->get_task_classname( $task_id, $task_type );
		
		try {
			return new $classname( $task_id);
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Gets a task classname and allows filtering. Returns CS_Task_Simple if the class does not exist.
	 *
	 * @since  1.0
	 * @param  int    $task_id   Task ID.
	 * @param  string $task_type Task type.
	 * @return string
	 */
	public static function get_task_classname( $task_id, $task_type ) {
		$classname = apply_filters( 'communityservice_task_class', self::get_classname_from_task_type( $task_type ), $task_type, 'variation' === $task_type ? 'task_variation' : 'task', $task_id );

		if ( ! $classname || ! class_exists( $classname ) ) {
			$classname = 'CS_Task_Simple';
		}

		return $classname;
	}

	/**
	 * Get the task type for a task.
	 *
	 * @since 1.0
	 * @param  int $task_id Task ID.
	 * @return string|false
	 */
	public static function get_task_type( $task_id ) {
		// Allow the overriding of the lookup in this function. Return the task type here.
		$override = apply_filters( 'communityservice_task_type_query', false, $task_id );
		if ( ! $override ) {
			return CS_Data_Store::load( 'task' )->get_task_type( $task_id );
		} else {
			return $override;
		}
	}

	/**
	 * Create a CS coding standards compliant class name e.g. CS_Task_Type_Class instead of CS_Task_type-class.
	 *
	 * @param  string $task_type Task type.
	 * @return string|false
	 */
	public static function get_classname_from_task_type( $task_type ) {
		return $task_type ? 'CS_Task_' . implode( '_', array_map( 'ucfirst', explode( '-', $task_type ) ) ) : false;
	}

	/**
	 * Get the task ID depending on what was passed.
	 *
	 * @since  1.0
	 * @param  CS_Task|WP_Post|int|bool $task Task instance, post instance, numeric or false to use global $post.
	 * @return int|bool false on failure
	 */
	private function get_task_id( $task ) {
		global $post;

		if ( false === $task && isset( $post, $post->ID ) && 'task' === get_post_type( $post->ID ) ) {
			return absint( $post->ID );
		} elseif ( is_numeric( $task ) ) {
			return $task;
		} elseif ( $task instanceof CS_Task ) {
			return $task->get_id();
		} elseif ( ! empty( $task->ID ) ) {
			return $task->ID;
		} else {
			return false;
		}
	}
}
