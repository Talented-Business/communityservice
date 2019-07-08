<?php
/**
 * List tables: tasks.
 *
 * @package  CommunityService/Admin
 * @version  3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'CS_Admin_List_Table_Tasks', false ) ) {
	return;
}

if ( ! class_exists( 'CS_Admin_List_Table', false ) ) {
	include_once 'abstract-class-cs-admin-list-table.php';
}

/**
 * CS_Admin_List_Table_Tasks Class.
 */
class CS_Admin_List_Table_Tasks extends CS_Admin_List_Table {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $list_table_type = 'cs-task';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_filter( 'disable_months_dropdown', '__return_true' );
		add_filter( 'query_vars', array( $this, 'add_custom_query_var' ) );
		add_filter( 'views_edit-task', array( $this, 'task_views' ) );
	}

	/**
	 * Render blank state.
	 */
	protected function render_blank_state() {
		echo '<div class="communityservice-BlankState">';
		echo '<h2 class="communityservice-BlankState-message">' . esc_html__( 'Ready to start selling something awesome?', 'communityservice' ) . '</h2>';
		echo '<a class="communityservice-BlankState-cta button-primary button" href="' . esc_url( admin_url( 'post-new.php?post_type=task&tutorial=true' ) ) . '">' . esc_html__( 'Create your first task!', 'communityservice' ) . '</a>';
		echo '<a class="communityservice-BlankState-cta button" href="' . esc_url( admin_url( 'edit.php?post_type=task&page=task_importer' ) ) . '">' . esc_html__( 'Import tasks from a CSV file', 'communityservice' ) . '</a>';
		echo '</div>';
	}

	/**
	 * Define primary column.
	 *
	 * @return string
	 */
	protected function get_primary_column() {
		return 'name';
	}

	/**
	 * Get row actions to show in the list table.
	 *
	 * @param array   $actions Array of actions.
	 * @param WP_Post $post Current post object.
	 * @return array
	 */
	protected function get_row_actions( $actions, $post ) {
		/* translators: %d: task ID. */
		return array_merge( array( 'id' => sprintf( __( 'ID: %d', 'communityservice' ), $post->ID ) ), $actions );
	}

	/**
	 * Define which columns are sortable.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function define_sortable_columns( $columns ) {
		$custom = array(
			'price' => 'price',
			'sku'   => 'sku',
			'name'  => 'title',
		);
		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Define which columns to show on this screen.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function define_columns( $columns ) {
		if ( empty( $columns ) && ! is_array( $columns ) ) {
			$columns = array();
		}

		unset( $columns['title'], $columns['comments'], $columns['date'] );

		$show_columns          = array();
		$show_columns['cb']    = '<input type="checkbox" />';
		$show_columns['name']  = __( 'Name', 'communityservice' );
		$show_columns['grade']  = __( 'Grade', 'communityservice' );

		$show_columns['date']         = __( 'Date', 'communityservice' );

		return array_merge( $show_columns, $columns );
	}

	/**
	 * Pre-fetch any data for the row each column has access to it. the_task global is there for bw compat.
	 *
	 * @param int $post_id Post ID being shown.
	 */
	protected function prepare_row_data( $post_id ) {
		global $the_task;

		if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
			$the_task  = cs_get_task( $post_id );
			$this->object = $the_task;
		}
	}

	/**
	 * Render columm: thumb.
	 */
	protected function render_thumb_column() {
		echo '<a href="' . esc_url( get_edit_post_link( $this->object->get_id() ) ) . '">' . $this->object->get_image( 'thumbnail' ) . '</a>'; // WPCS: XSS ok.
	}

	/**
	 * Render column: name.
	 */
	protected function render_name_column() {
		global $post;

		$edit_link = get_edit_post_link( $this->object->get_id() );
		$title     = _draft_or_post_title();

		echo '<strong><a class="row-title" href="' . esc_url( $edit_link ) . '">' . esc_html( $title ) . '</a>';

		_post_states( $post );

		echo '</strong>';

		if ( $this->object->get_parent_id() > 0 ) {
			echo '&nbsp;&nbsp;&larr; <a href="' . esc_url( get_edit_post_link( $this->object->get_parent_id() ) ) . '">' . get_the_title( $this->object->get_parent_id() ) . '</a>';
		}

		get_inline_data( $post );

		/* Custom inline data for communityservice. */
		echo '
			<div class="hidden" id="communityservice_inline_' . absint( $this->object->get_id() ) . '">
			</div>
		';
	}

	/**
	 * Render column: grade.
	 */
	protected function render_grade_column() {
		global $post;

		$edit_link = get_edit_post_link( $this->object->get_id() );
		$classname    = CS_Task_Factory::get_task_classname( $this->object->get_id(), 'simple' );
		$task      = new $classname( $this->object->get_id() );
		$years = $task->get_years();
		$year_names = array();
		foreach($years as $year){
			$term    = get_term( $year, 'user-group' );
			$year_names[] = date('Y')-$term->name+4;
		}
		echo implode(",",$year_names);
	}

	/**
	 * Query vars for custom searches.
	 *
	 * @param mixed $public_query_vars Array of query vars.
	 * @return array
	 */
	public function add_custom_query_var( $public_query_vars ) {
		return $public_query_vars;
	}

}
