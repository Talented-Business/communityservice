<?php
/**
 * Post Types
 *
 * Registers post types and taxonomies.
 *
 * @package CommunityService/Classes/Products
 * @version 2.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types Class.
 */
class WC_Post_Types {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 5 );
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
		add_action( 'init', array( __CLASS__, 'register_post_status' ), 9 );
		add_filter( 'rest_api_allowed_post_types', array( __CLASS__, 'rest_api_allowed_post_types' ) );
		add_action( 'communityservice_after_register_post_type', array( __CLASS__, 'maybe_flush_rewrite_rules' ) );
		add_action( 'communityservice_flush_rewrite_rules', array( __CLASS__, 'flush_rewrite_rules' ) );
		add_filter( 'gutenberg_can_edit_post_type', array( __CLASS__, 'gutenberg_can_edit_post_type' ), 10, 2 );
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'gutenberg_can_edit_post_type' ), 10, 2 );
	}

	/**
	 * Register core taxonomies.
	 */
	public static function register_taxonomies() {

	}

	/**
	 * Register core post types.
	 */
	public static function register_post_types() {
		if ( ! is_blog_installed() || post_type_exists( 'cs-task' ) ) {
			return;
		}

		do_action( 'communityservice_register_post_type' );

		$permalinks = cs_get_permalink_structure();
		$supports   = array( 'title', 'editor', 'thumbnail');

		// If theme support changes, we may need to flush permalinks since some are changed based on this flag.
		if ( update_option( 'current_theme_supports_communityservice', current_theme_supports( 'communityservice' ) ? 'yes' : 'no' ) ) {
			update_option( 'communityservice_queue_flush_rewrite_rules', 'yes' );
		}
		register_post_type( 'cs-task',
    array(
      'labels' => array(
        'name' => __( 'Internal Tasks' ),
				'singular_name' => __( 'Internal Task' ),
				'add_new'               => __( 'Add New', 'communityservice' ),
				'add_new_item'          => __( 'Add new task', 'communityservice' ),
				'edit'                  => __( 'Edit', 'communityservice' ),
				'edit_item'             => __( 'Edit task', 'communityservice' ),				
				'featured_image'        => __( 'Badge image', 'communityservice' ),
				'set_featured_image'    => __( 'Set Badge image', 'communityservice' ),
				'remove_featured_image' => __( 'Remove Badge image', 'communityservice' ),
				'use_featured_image'    => __( 'Use as Badge image', 'communityservice' ),
      ),
			'public' => true,
			'supports' => $supports,
			'rewrite'             => array(
				'slug'       => 'tasks',
				'with_front' => false,
				'feeds'      => true,
			),
			'show_in_rest'        => true,
    )
  );
	cs_register_activity_type(
		'cs-activity',
		array(
			'labels'              => array(
				'name'                  => __( 'Activities', 'communityservice' ),
				'singular_name'         => __( 'Activity', 'communityservice' ),
				'all_items'             => __( 'All Activities', 'communityservice' ),
				'menu_name'             => _x( 'Activities', 'Admin menu name', 'communityservice' ),
				'add_new'               => __( 'Add New', 'communityservice' ),
				'add_new_item'          => __( 'Add new activity', 'communityservice' ),
				'edit'                  => __( 'Edit', 'communityservice' ),
				'edit_item'             => __( 'Edit activity', 'communityservice' ),
				'new_item'              => __( 'New activity', 'communityservice' ),
				'view_item'             => __( 'View activity', 'communityservice' ),
				'view_items'            => __( 'View activities', 'communityservice' ),
				'search_items'          => __( 'Search activities', 'communityservice' ),
				'not_found'             => __( 'No activities found', 'communityservice' ),
				'not_found_in_trash'    => __( 'No activities found in trash', 'communityservice' ),
				'parent'                => __( 'Parent activity', 'communityservice' ),
				'featured_image'        => __( 'activity image', 'communityservice' ),
				'set_featured_image'    => __( 'Set activity image', 'communityservice' ),
				'remove_featured_image' => __( 'Remove activity image', 'communityservice' ),
				'use_featured_image'    => __( 'Use as activity image', 'communityservice' ),
				'insert_into_item'      => __( 'Insert into activity', 'communityservice' ),
				'uploaded_to_this_item' => __( 'Uploaded to this activity', 'communityservice' ),
				'filter_items_list'     => __( 'Filter activities', 'communityservice' ),
				'items_list_navigation' => __( 'activities navigation', 'communityservice' ),
				'items_list'            => __( 'activities list', 'communityservice' ),
			),
			'description'         => __( 'This is where you can add new activities to your system.', 'communityservice' ),
			'public'              => true,
			'show_ui'             => true,
			'capabilities' => array(
				'publish_posts' => 'publish_activities',
				'edit_posts' => 'edit_activities',
				'create_posts' =>'do_not_allow',
				'edit_others_posts' => 'edit_others_activities',
				'delete_posts' => 'delete_activities',
				'delete_others_posts' => 'delete_others_activities',
				'read_private_posts' => 'read_private_activities',
				'edit_post' => 'edit_activity',
				'delete_post' => 'delete_activity',
				'read_post' => 'read_activity'
			),
			'map_meta_cap'        => true,
			'publicly_queryable'  => false,
			'exclude_from_search' => false,
			'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
			'rewrite'             => array(
				'slug'       => true,
				'with_front' => false,
				'feeds'      => true,
			),
			'query_var'           => true,
			'supports'            => array( 'title', 'editor'),
			//'has_archive'         => $has_archive,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			//'show_in_rest'        => true,
		)
	);

		do_action( 'communityservice_after_register_post_type' );
	}

	/**
	 * Register our custom post statuses, used for activity status.
	 */
	public static function register_post_status() {

		$activity_statuses = apply_filters(
			'communityservice_register_shop_activity_post_statuses',
			array(
				'cs-pending' => array(
					'label'                     => _x( 'Pending', 'Activity status', 'communityservice' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of activities */
					'label_count'               => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', 'communityservice' ),
				),
				'cs-approved'  => array(
					'label'                     => _x( 'Approved', 'Activity status', 'communityservice' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of activities */
					'label_count'               => _n_noop( 'Approved <span class="count">(%s)</span>', 'Approved <span class="count">(%s)</span>', 'communityservice' ),
				),
				'cs-cancelled'  => array(
					'label'                     => _x( 'Declined', 'Activity status', 'communityservice' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of activities */
					'label_count'               => _n_noop( 'Declined <span class="count">(%s)</span>', 'Declined <span class="count">(%s)</span>', 'communityservice' ),
				),
			)
		);

		foreach ( $activity_statuses as $activity_status => $values ) {
			register_post_status( $activity_status, $values );
		}
	}

	/**
	 * Flush rules if the event is queued.
	 *
	 * @since 3.3.0
	 */
	public static function maybe_flush_rewrite_rules() {
		if ( 'yes' === get_option( 'communityservice_queue_flush_rewrite_rules' ) ) {
			update_option( 'communityservice_queue_flush_rewrite_rules', 'no' );
			self::flush_rewrite_rules();
		}
	}

	/**
	 * Flush rewrite rules.
	 */
	public static function flush_rewrite_rules() {
		flush_rewrite_rules();
	}

	/**
	 * Disable Gutenberg for products.
	 *
	 * @param bool   $can_edit Whether the post type can be edited or not.
	 * @param string $post_type The post type being checked.
	 * @return bool
	 */
	public static function gutenberg_can_edit_post_type( $can_edit, $post_type ) {
		return 'cs-task' === $post_type ? false : $can_edit;
	}


	/**
	 * Added product for Jetpack related posts.
	 *
	 * @param  array $post_types Post types.
	 * @return array
	 */
	public static function rest_api_allowed_post_types( $post_types ) {
		$post_types[] = 'cs-task';

		return $post_types;
	}
}

WC_Post_types::init();
