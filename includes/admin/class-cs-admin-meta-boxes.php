<?php
/**
 * WooCommerce Meta Boxes
 *
 * Sets up the write panels used by tasks and activities (custom post types).
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin/Meta Boxes
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * CS_Admin_Meta_Boxes.
 */
class CS_Admin_Meta_Boxes {

	/**
	 * Is meta boxes saved once?
	 *
	 * @var boolean
	 */
	private static $saved_meta_boxes = false;

	/**
	 * Meta box error messages.
	 *
	 * @var array
	 */
	public static $meta_box_errors = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 10 );
		add_action( 'add_meta_boxes', array( $this, 'rename_meta_boxes' ), 20 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 30 );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 1, 2 );
		add_action( 'publish_post', array( $this,'post_published_notification'), 10, 2 );
		/**
		 * Save Activity Meta Boxes.
		 *
		 * In activity:
		 *      Save the activity items.
		 *      Save the activity totals.
		 *      Save the activity downloads.
		 *      Save activity data - also updates status and sends out admin emails if needed. Last to show latest data.
		 *      Save actions - sends out other emails. Last to show latest data.
		 */
		/*add_action( 'communityservice_process_activity_meta', 'CS_Meta_Box_Activity_Items::save', 10, 2 );
		add_action( 'communityservice_process_activity_meta', 'CS_Meta_Box_Activity_Downloads::save', 30, 2 );*/
		add_action( 'communityservice_process_activity_meta', 'CS_Meta_Box_Activity_Data::save', 40, 2 );
		add_action( 'communityservice_process_activity_meta', 'CS_Meta_Box_Activity_Actions::save', 50, 2 );

		// Save Task Meta Boxes.
		add_action( 'communityservice_process_cs-task_meta', 'CS_Meta_Box_Task_Data::save', 10, 2 );
		//add_action( 'communityservice_process_cs-task_meta', 'CS_Meta_Box_Task_Images::save', 20, 2 );


		// Error handling (for showing errors from meta boxes on next page load).
		add_action( 'admin_notices', array( $this, 'output_errors' ) );
		add_action( 'shutdown', array( $this, 'save_errors' ) );
	}
	public static function post_published_notification( $ID, $post ) {
		CS()->mailer()->emails['CS_Email_New_Blog']->trigger( $ID, $post );
	}
	/**
	 * Add an error message.
	 *
	 * @param string $text
	 */
	public static function add_error( $text ) {
		self::$meta_box_errors[] = $text;
	}

	/**
	 * Save errors to an option.
	 */
	public function save_errors() {
		update_option( 'communityservice_meta_box_errors', self::$meta_box_errors );
	}

	/**
	 * Show any stored error messages.
	 */
	public function output_errors() {
		$errors = array_filter( (array) get_option( 'communityservice_meta_box_errors' ) );

		if ( ! empty( $errors ) ) {

			echo '<div id="communityservice_errors" class="error notice is-dismissible">';

			foreach ( $errors as $error ) {
				echo '<p>' . wp_kses_post( $error ) . '</p>';
			}

			echo '</div>';

			// Clear
			delete_option( 'communityservice_meta_box_errors' );
		}
	}

	/**
	 * Add CS Meta boxes.
	 */
	public function add_meta_boxes() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Tasks.
		add_meta_box( 'communityservice-task-duties', __( 'Duties', 'communityservice' ), 'CS_Meta_Box_Task_Data::task_duties', 'cs-task', 'normal', 'high' );
		add_meta_box( 'communityservice-task-years', __( 'Years and Houses', 'communityservice' ), 'CS_Meta_Box_Task_Data::task_years', 'cs-task', 'normal', 'high' );
		add_meta_box( 'communityservice-task-students', __( 'Activities', 'communityservice' ), 'CS_Meta_Box_Task_Data::students', 'cs-task', 'normal', 'high' );

		// Activities.
		foreach ( cs_get_activity_types( 'activity-meta-boxes' ) as $type ) {
			$activity_type_object = get_post_type_object( $type );
			add_meta_box( 'communityservice-activity-data', sprintf( __( '%s data', 'communityservice' ), $activity_type_object->labels->singular_name ), 'CS_Meta_Box_Activity_Data::output', $type, 'normal', 'high' );
			add_meta_box( 'communityservice-activity-actions', sprintf( __( '%s actions', 'communityservice' ), $activity_type_object->labels->singular_name ), 'CS_Meta_Box_Activity_Actions::output', $type, 'side', 'high' );
			/*add_meta_box( 'communityservice-activity-items', __( 'Items', 'communityservice' ), 'CS_Meta_Box_Activity_Items::output', $type, 'normal', 'high' );
			add_meta_box( 'communityservice-activity-notes', sprintf( __( '%s notes', 'communityservice' ), $activity_type_object->labels->singular_name ), 'CS_Meta_Box_Activity_Notes::output', $type, 'side', 'default' );
			add_meta_box( 'communityservice-activity-downloads', __( 'Downloadable task permissions', 'communityservice' ) . cs_help_tip( __( 'Note: Permissions for activity items will automatically be granted when the activity status changes to processing/completed.', 'communityservice' ) ), 'CS_Meta_Box_Activity_Downloads::output', $type, 'normal', 'default' );*/
		}

	}

	/**
	 * Remove bloat.
	 */
	public function remove_meta_boxes() {
		remove_meta_box( 'postexcerpt', 'cs-task', 'normal' );
		remove_meta_box( 'task_shipping_classdiv', 'cs-task', 'side' );
		remove_meta_box( 'commentsdiv', 'cs-task', 'normal' );
		remove_meta_box( 'commentstatusdiv', 'cs-task', 'side' );
		remove_meta_box( 'commentstatusdiv', 'cs-task', 'normal' );

		foreach ( cs_get_activity_types( 'activity-meta-boxes' ) as $type ) {
			remove_meta_box( 'commentsdiv', $type, 'normal' );
			remove_meta_box( 'woothemes-settings', $type, 'normal' );
			remove_meta_box( 'commentstatusdiv', $type, 'normal' );
			remove_meta_box( 'slugdiv', $type, 'normal' );
			remove_meta_box( 'submitdiv', $type, 'side' );
		}
	}

	/**
	 * Rename core meta boxes.
	 */
	public function rename_meta_boxes() {
		global $post;

		// Comments/Reviews
		if ( isset( $post ) && ( 'publish' == $post->post_status || 'private' == $post->post_status ) && post_type_supports( 'cs-task', 'comments' ) ) {
			remove_meta_box( 'commentsdiv', 'cs-task', 'normal' );
			//add_meta_box( 'commentsdiv', __( 'Reviews', 'communityservice' ), 'post_comment_meta_box', 'cs-task', 'normal' );
		}
	}

	/**
	 * Check if we're saving, the trigger an action based on the post type.
	 *
	 * @param  int    $post_id
	 * @param  object $post
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// $post_id and $post are required
		if ( empty( $post_id ) || empty( $post ) || self::$saved_meta_boxes ) {
			return;
		}

		// Dont' save meta boxes for revisions or autosaves
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}
		// Check the nonce
		if ( empty( $_POST['communityservice_meta_nonce'] ) || ! wp_verify_nonce( $_POST['communityservice_meta_nonce'], 'communityservice_save_data' ) ) {
			return;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events
		if ( empty( $_POST['post_ID'] ) || $_POST['post_ID'] != $post_id ) {
			return;
		}

		// Check user has permission to edit
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// We need this save event to run once to avoid potential endless loops. This would have been perfect:
		// remove_action( current_filter(), __METHOD__ );
		// But cannot be used due to https://github.com/communityservice/communityservice/issues/6485
		// When that is patched in core we can use the above. For now:
		self::$saved_meta_boxes = true;

		// Check the post type
		if ( in_array( $post->post_type, cs_get_activity_types( 'activity-meta-boxes' ) ) ) {
			do_action( 'communityservice_process_activity_meta', $post_id, $post );
		} elseif ( in_array( $post->post_type, array( 'cs-task') ) ) {
			do_action( 'communityservice_process_' . $post->post_type . '_meta', $post_id, $post );
		}
	}
}

new CS_Admin_Meta_Boxes();
