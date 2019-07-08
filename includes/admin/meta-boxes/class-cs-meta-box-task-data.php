<?php
/**
 * Task Data
 *
 * Displays the task data box, tabbed, with several panels covering price, stock etc.
 *
 * @package  CommunityService/Admin/Meta Boxes
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CS_Meta_Box_Task_Data Class.
 */
class CS_Meta_Box_Task_Data {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function output( $post ) {
		global $thepostid, $task_object;

		$thepostid      = $post->ID;
		$task_object = $thepostid ? cs_get_task( $thepostid ) : new CS_Task();

		wp_nonce_field( 'communityservice_save_data', 'communityservice_meta_nonce' );

		include 'views/html-task-data-panel.php';
	}
	public static function task_duties($post){
		global $thepostid, $task_object;

		$thepostid      = $post->ID;
		$task_object = $thepostid ? cs_get_task( $thepostid ) : new CS_Task();
		
		wp_nonce_field( 'communityservice_save_data', 'communityservice_meta_nonce' );

		include 'views/html-cs-task-duties.php';
	}
	public static function task_years($post){
		global $thepostid, $task_object;

		$thepostid      = $post->ID;
		$task_object = $thepostid ? cs_get_task( $thepostid ) : new CS_Task();

		wp_nonce_field( 'communityservice_save_data', 'communityservice_meta_nonce' );

		include 'views/html-cs-task-years.php';
	}
	public static function students($post){
		global $thepostid, $task_object;

		$thepostid      = $post->ID;
		$task_object = $thepostid ? cs_get_task( $thepostid ) : new CS_Task();

		include 'views/html-cs-task-students.php';
	}
	/**
	 * Save meta box data.
	 *
	 * @param int     $post_id WP post id.
	 * @param WP_Post $post Post object.
	 */
	public static function save( $post_id, $post ) {
		// Process task type first so we have the correct class to run setters.
		$task_type = empty( $_POST['task-type'] ) ? CS_Task_Factory::get_task_type( $post_id ) : sanitize_title( wp_unslash( $_POST['task-type'] ) );
		$classname    = CS_Task_Factory::get_task_classname( $post_id, $task_type ? $task_type : 'simple' );
		$task      = new $classname( $post_id );
		$years = isset( $_POST['years'] ) ? (array)wp_unslash( $_POST['years'] ) : array();
		$years = array_keys($years);
//		$houses = isset( $_POST['houses'] ) ? (array)wp_unslash( $_POST['houses'] ) : array();
//		$houses = array_keys($houses);
		$errors = $task->set_props(
			array(
				'featured'           => isset( $_POST['_featured'] ),
				'task_url'        	 => isset($_POST['_task_url']) ? esc_url_raw( wp_unslash( $_POST['_task_url'] ) ):'',
				'duties'             => isset( $_POST['duties'] ) ? cs_clean( wp_unslash( $_POST['duties'] ) ) : null,
				'years'              => $years,
//				'houses'			 => $houses,
			)
		);
		if ( is_wp_error( $errors ) ) {
			CS_Admin_Meta_Boxes::add_error( $errors->get_error_message() );
		}

		/**
		 * Set props before save.
		 *
		 * @since 1.0
		 */
		do_action( 'communityservice_admin_process_task_object', $task );

		$task->save();

		do_action( 'communityservice_process_task_meta_' . $task_type, $post_id );
	}

}
