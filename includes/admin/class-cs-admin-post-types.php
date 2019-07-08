<?php
/**
 * Post Types Admin
 *
 * @package  CommuntiyService/admin
 * @version  3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'CS_Admin_Post_Types', false ) ) {
	new CS_Admin_Post_Types();
	return;
}

/**
 * CS_Admin_Post_Types Class.
 *
 * Handles the edit posts views and some functionality on the edit post screen for CS post types.
 */
class CS_Admin_Post_Types {

	/**
	 * Constructor.
	 */
	public function __construct() {
		include_once dirname( __FILE__ ) . '/class-cs-admin-meta-boxes.php';


		// Load correct list table classes for current screen.
		add_action( 'current_screen', array( $this, 'setup_screen' ) );
		add_action( 'check_ajax_referer', array( $this, 'setup_screen' ) );

		// Admin notices.
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_post_updated_messages' ), 10, 2 );

		// Disable Auto Save.
		add_action( 'admin_print_scripts', array( $this, 'disable_autosave' ) );

		// Extra post data and screen elements.
		add_action( 'edit_form_top', array( $this, 'edit_form_top' ) );
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 1, 2 );
		//add_action( 'edit_form_after_title', array( $this, 'edit_form_after_title' ) );
		add_filter( 'default_hidden_meta_boxes', array( $this, 'hidden_meta_boxes' ), 10, 2 );
		add_action( 'post_submitbox_misc_actions', array( $this, 'task_data_visibility' ) );

		// Uploads.
		add_filter( 'upload_dir', array( $this, 'upload_dir' ) );
		add_action( 'media_upload_downloadable_task', array( $this, 'media_upload_downloadable_task' ) );

		// Add a post display state for special CS pages.
		add_filter( 'display_post_states', array( $this, 'add_display_post_states' ), 10, 2 );

		// Bulk / quick edit.
		add_action( 'bulk_edit_custom_box', array( $this, 'bulk_edit' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( $this, 'quick_edit' ), 10, 2 );
		add_action( 'save_post', array( $this, 'bulk_and_quick_edit_hook' ), 10, 2 );
		add_action( 'communityservice_task_bulk_and_quick_edit', array( $this, 'bulk_and_quick_edit_save_post' ), 10, 2 );
	}

	/**
	 * Looks at the current screen and loads the correct list table handler.
	 *
	 * @since 3.3.0
	 */
	public function setup_screen() {
		global $cs_list_table;

		$screen_id = false;

		if ( function_exists( 'get_current_screen' ) ) {
			$screen    = get_current_screen();
			$screen_id = isset( $screen, $screen->id ) ? $screen->id : '';
		}

		if ( ! empty( $_REQUEST['screen'] ) ) { // WPCS: input var ok.
			$screen_id = cs_clean( wp_unslash( $_REQUEST['screen'] ) ); // WPCS: input var ok, sanitization ok.
		}
		
		switch ( $screen_id ) {
			case 'edit-cs-activity':
				include_once 'list-tables/class-cs-admin-list-table-activities.php';
				$cs_list_table = new CS_Admin_List_Table_Activities();
				break;
			case 'edit-cs-task':
				include_once 'list-tables/class-cs-admin-list-table-tasks.php';
				$cs_list_table = new CS_Admin_List_Table_Tasks();
				break;
		}

		// Ensure the table handler is only loaded once. Prevents multiple loads if a plugin calls check_ajax_referer many times.
		remove_action( 'current_screen', array( $this, 'setup_screen' ) );
		remove_action( 'check_ajax_referer', array( $this, 'setup_screen' ) );
	}

	/**
	 * Change messages when a post type is updated.
	 *
	 * @param  array $messages Array of messages.
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		global $post;

		$messages['cs-task'] = array(
			0  => '', // Unused. Messages start at index 1.
			/* translators: %s: Task view URL. */
			1  => sprintf( __( 'Task updated. <a href="%s">View Task</a>', 'communityservice' ), esc_url( get_permalink( $post->ID ) ) ),
			2  => __( 'Custom field updated.', 'communityservice' ),
			3  => __( 'Custom field deleted.', 'communityservice' ),
			4  => __( 'Task updated.', 'communityservice' ),
			5  => __( 'Revision restored.', 'communityservice' ),
			/* translators: %s: task url */
			6  => sprintf( __( 'Task published. <a href="%s">View Task</a>', 'communityservice' ), esc_url( get_permalink( $post->ID ) ) ),
			7  => __( 'Task saved.', 'communityservice' ),
			/* translators: %s: task url */
			8  => sprintf( __( 'Task submitted. <a target="_blank" href="%s">Preview task</a>', 'communityservice' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) ),
			9  => sprintf(
				/* translators: 1: date 2: task url */
				__( 'Task scheduled for: %1$s. <a target="_blank" href="%2$s">Preview task</a>', 'communityservice' ),
				'<strong>' . date_i18n( __( 'M j, Y @ G:i', 'communityservice' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post->ID ) ) . '</strong>'
			),
			/* translators: %s: task url */
			10 => sprintf( __( 'Task draft updated. <a target="_blank" href="%s">Preview task</a>', 'communityservice' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) ),
		);

		$messages['cs-activity'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Activity updated.', 'communityservice' ),
			2  => __( 'Custom field updated.', 'communityservice' ),
			3  => __( 'Custom field deleted.', 'communityservice' ),
			4  => __( 'Activity updated.', 'communityservice' ),
			5  => __( 'Revision restored.', 'communityservice' ),
			6  => __( 'Activity updated.', 'communityservice' ),
			7  => __( 'Activity saved.', 'communityservice' ),
			8  => __( 'Activity submitted.', 'communityservice' ),
			9  => sprintf(
				/* translators: %s: date */
				__( 'Activity scheduled for: %s.', 'communityservice' ),
				'<strong>' . date_i18n( __( 'M j, Y @ G:i', 'communityservice' ), strtotime( $post->post_date ) ) . '</strong>'
			),
			10 => __( 'Activity draft updated.', 'communityservice' ),
			11 => __( 'Activity updated and sent.', 'communityservice' ),
		);

		return $messages;
	}

	/**
	 * Specify custom bulk actions messages for different post types.
	 *
	 * @param  array $bulk_messages Array of messages.
	 * @param  array $bulk_counts Array of how many objects were updated.
	 * @return array
	 */
	public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {
		$bulk_messages['cs-task'] = array(
			/* translators: %s: task count */
			'updated'   => _n( '%s task updated.', '%s tasks updated.', $bulk_counts['updated'], 'communityservice' ),
			/* translators: %s: task count */
			'locked'    => _n( '%s task not updated, somebody is editing it.', '%s tasks not updated, somebody is editing them.', $bulk_counts['locked'], 'communityservice' ),
			/* translators: %s: task count */
			'deleted'   => _n( '%s task permanently deleted.', '%s tasks permanently deleted.', $bulk_counts['deleted'], 'communityservice' ),
			/* translators: %s: task count */
			'trashed'   => _n( '%s task moved to the Trash.', '%s tasks moved to the Trash.', $bulk_counts['trashed'], 'communityservice' ),
			/* translators: %s: task count */
			'untrashed' => _n( '%s task restored from the Trash.', '%s tasks restored from the Trash.', $bulk_counts['untrashed'], 'communityservice' ),
		);

		$bulk_messages['cs-activity'] = array(
			/* translators: %s: activity count */
			'updated'   => _n( '%s activity updated.', '%s activities updated.', $bulk_counts['updated'], 'communityservice' ),
			/* translators: %s: activity count */
			'locked'    => _n( '%s activity not updated, somebody is editing it.', '%s activities not updated, somebody is editing them.', $bulk_counts['locked'], 'communityservice' ),
			/* translators: %s: activity count */
			'deleted'   => _n( '%s activity permanently deleted.', '%s activities permanently deleted.', $bulk_counts['deleted'], 'communityservice' ),
			/* translators: %s: activity count */
			'trashed'   => _n( '%s activity moved to the Trash.', '%s activities moved to the Trash.', $bulk_counts['trashed'], 'communityservice' ),
			/* translators: %s: activity count */
			'untrashed' => _n( '%s activity restored from the Trash.', '%s activities restored from the Trash.', $bulk_counts['untrashed'], 'communityservice' ),
		);

		return $bulk_messages;
	}

	/**
	 * Custom bulk edit - form.
	 *
	 * @param string $column_name Column being shown.
	 * @param string $post_type Post type being shown.
	 */
	public function bulk_edit( $column_name, $post_type ) {
		if ( 'price' !== $column_name || 'task' !== $post_type ) {
			return;
		}

		$shipping_class = get_terms(
			'task_shipping_class', array(
				'hide_empty' => false,
			)
		);

		//include CS()->plugin_path() . '/includes/admin/views/html-bulk-edit-task.php';
	}

	/**
	 * Custom quick edit - form.
	 *
	 * @param string $column_name Column being shown.
	 * @param string $post_type Post type being shown.
	 */
	public function quick_edit( $column_name, $post_type ) {
		if ( 'price' !== $column_name || 'task' !== $post_type ) {
			return;
		}

		$shipping_class = get_terms(
			'task_shipping_class', array(
				'hide_empty' => false,
			)
		);

		//include CS()->plugin_path() . '/includes/admin/views/html-quick-edit-task.php';
	}

	/**
	 * Offers a way to hook into save post without causing an infinite loop
	 * when quick/bulk saving task info.
	 *
	 * @since 3.0.0
	 * @param int    $post_id Post ID being saved.
	 * @param object $post Post object being saved.
	 */
	public function bulk_and_quick_edit_hook( $post_id, $post ) {
		//remove_action( 'save_post', array( $this, 'bulk_and_quick_edit_hook' ) );
		//do_action( 'communityservice_task_bulk_and_quick_edit', $post_id, $post );
		//add_action( 'save_post', array( $this, 'bulk_and_quick_edit_hook' ), 10, 2 );
	}

	/**
	 * Quick and bulk edit saving.
	 *
	 * @param int    $post_id Post ID being saved.
	 * @param object $post Post object being saved.
	 * @return int
	 */
	public function bulk_and_quick_edit_save_post( $post_id, $post ) {
		return $post_id;
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Don't save revisions and autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'task' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// Check nonce.
		if ( ! isset( $_REQUEST['communityservice_quick_edit_nonce'] ) || ! wp_verify_nonce( $_REQUEST['communityservice_quick_edit_nonce'], 'communityservice_quick_edit_nonce' ) ) { // WPCS: input var ok, sanitization ok.
			return $post_id;
		}

		// Get the task and save.
		$task = cs_get_task( $post );

		if ( ! empty( $_REQUEST['communityservice_quick_edit'] ) ) { // WPCS: input var ok.
			$this->quick_edit_save( $post_id, $task );
		} else {
			$this->bulk_edit_save( $post_id, $task );
		}

		return $post_id;
	}

	/**
	 * Quick edit.
	 *
	 * @param int        $post_id Post ID being saved.
	 * @param CS_Task $task Task object.
	 */
	private function quick_edit_save( $post_id, $task ) {

		//$task->save();

	}

	/**
	 * Bulk edit.
	 *
	 * @param int        $post_id Post ID being saved.
	 * @param CS_Task $task Task object.
	 */
	public function bulk_edit_save( $post_id, $task ) {

		//$task->save();
	}

	/**
	 * Disable the auto-save functionality for Activities.
	 */
	public function disable_autosave() {
		global $post;

		//if ( $post && in_array( get_post_type( $post->ID ), cs_get_activity_types( 'activity-meta-boxes' ), true ) ) {
			//wp_dequeue_script( 'autosave' );
		//}
	}

	/**
	 * Output extra data on post forms.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function edit_form_top( $post ) {
		echo '<input type="hidden" id="original_post_title" name="original_post_title" value="' . esc_attr( $post->post_title ) . '" />';
	}

	/**
	 * Change title boxes in admin.
	 *
	 * @param string  $text Text to shown.
	 * @param WP_Post $post Current post object.
	 * @return string
	 */
	public function enter_title_here( $text, $post ) {
		switch ( $post->post_type ) {
			case 'cs-task':
				$text = esc_html__( 'Service Task name', 'communityservice' );
				break;
		}
		return $text;
	}

	/**
	 * Hidden default Meta-Boxes.
	 *
	 * @param  array  $hidden Hidden boxes.
	 * @param  object $screen Current screen.
	 * @return array
	 */
	public function hidden_meta_boxes( $hidden, $screen ) {
		if ( 'task' === $screen->post_type && 'post' === $screen->base ) {
			$hidden = array_merge( $hidden, array( 'postcustom' ) );
		}

		return $hidden;
	}

	/**
	 * Output task visibility options.
	 */
	public function task_data_visibility() {
		global $post, $thepostid, $task_object;

		if ( 'task' !== $post->post_type ) {
			return;
		}
	}

	/**
	 * Change upload dir for downloadable files.
	 *
	 * @param array $pathdata Array of paths.
	 * @return array
	 */
	public function upload_dir( $pathdata ) {
		return $pathdata;
	}


	/**
	 * Add a post display state for special CS pages in the page list table.
	 *
	 * @param array   $post_states An array of post display states.
	 * @param WP_Post $post        The current post object.
	 */
	public function add_display_post_states( $post_states, $post ) {
		if ( cs_get_page_id( 'shop' ) === $post->ID ) {
			$post_states['cs_page_for_shop'] = __( 'Shop Page', 'communityservice' );
		}

		if ( cs_get_page_id( 'cart' ) === $post->ID ) {
			$post_states['cs_page_for_cart'] = __( 'Cart Page', 'communityservice' );
		}

		if ( cs_get_page_id( 'checkout' ) === $post->ID ) {
			$post_states['cs_page_for_checkout'] = __( 'Checkout Page', 'communityservice' );
		}

		if ( cs_get_page_id( 'myaccount' ) === $post->ID ) {
			$post_states['cs_page_for_myaccount'] = __( 'My Account Page', 'communityservice' );
		}

		if ( cs_get_page_id( 'terms' ) === $post->ID ) {
			$post_states['cs_page_for_terms'] = __( 'Terms and Conditions Page', 'communityservice' );
		}

		return $post_states;
	}
}

new CS_Admin_Post_Types();
