<?php
/**
 * CommunityService Task Functions
 *
 * Functions for task specific things.
 *
 * @package CommunityService/Functions
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Standard way of retrieving tasks based on certain parameters.
 *
 * This function should be used for task retrieval so that we have a data agnostic
 * way to get a list of tasks.
 *
 *
 * @since  1.0
 * @param  array $args Array of args (above).
 * @return array|stdClass Number of pages and an array of task objects if
 *                             paginate is true, or just an array of values.
 */
function cs_get_tasks( $args ) {
	// Handle some BW compatibility arg names where wp_query args differ in naming.
	$map_legacy = array(
		'numberposts'    => 'limit',
		'post_status'    => 'status',
		'post_parent'    => 'parent',
		'posts_per_page' => 'limit',
		'paged'          => 'page',
	);

	foreach ( $map_legacy as $from => $to ) {
		if ( isset( $args[ $from ] ) ) {
			$args[ $to ] = $args[ $from ];
		}
	}

	$query = new CS_Task_Query( $args );
	return $query->get_tasks();
}

/**
 * Main function for returning tasks, uses the CS_Task_Factory class.
 *
 * @since 2.2.0
 *
 * @param mixed $the_task Post object or post ID of the task.
 * @param array $deprecated Previously used to pass arguments to the factory, e.g. to force a type.
 * @return CS_Task|null|false
 */
function cs_get_task( $the_task = false) {
	if ( ! did_action( 'communityservice_init' )&&false ) {
		/* translators: 1: cs_get_task 2: communityservice_init */
		//wc_doing_it_wrong( __FUNCTION__, sprintf( __( '%1$s should not be called before the %2$s action.', 'woocommerce' ), 'cs_get_task', 'communityservice_init' ), '2.5' );
		return false;
	}
	
	return CS()->task_factory->get_task( $the_task);
}


/**
 * Clear all transients cache for task data.
 *
 * @param int $post_id (default: 0).
 */
function cs_delete_task_transients( $post_id = 0 ) {
	// Core transients.
	$transients_to_clear = array(
		'cs_featured_tasks',
	);

	// Transient names that include an ID.
	$post_transient_names = array(
		'cs_task_children_',
		'cs_related_',
	);

	if ( $post_id > 0 ) {
		foreach ( $post_transient_names as $transient ) {
			$transients_to_clear[] = $transient . $post_id;
		}

		// Does this task have a parent?
		$task = cs_get_task( $post_id );

		if ( $task ) {
			if ( $task->get_parent_id() > 0 ) {
				cs_delete_task_transients( $task->get_parent_id() );
			}
		}
	}

	// Delete transients.
	foreach ( $transients_to_clear as $transient ) {
		delete_transient( $transient );
	}

	// Increments the transient version to invalidate cache.
	//WC_Cache_Helper::get_transient_version( 'task', true );

	do_action( 'woocommerce_delete_task_transients', $post_id );
}


/**
 * Filter to allow task_cat in the permalinks for tasks.
 *
 * @param  string  $permalink The existing permalink URL.
 * @param  WP_Post $post WP_Post object.
 * @return string
 */
function cs_task_post_type_link( $permalink, $post ) {
	// Abort if post is not a task.
	if ( 'task' !== $post->post_type ) {
		return $permalink;
	}

	// Abort early if the placeholder rewrite tag isn't in the generated URL.
	if ( false === strpos( $permalink, '%' ) ) {
		return $permalink;
	}

	// Get the custom taxonomy terms in use by this post.
	$terms = get_the_terms( $post->ID, 'task_cat' );

	if ( ! empty( $terms ) ) {
		if ( function_exists( 'wp_list_sort' ) ) {
			$terms = wp_list_sort( $terms, 'term_id', 'ASC' );
		} else {
			usort( $terms, '_usort_terms_by_ID' );
		}
		$category_object = apply_filters( 'cs_task_post_type_link_task_cat', $terms[0], $terms, $post );
		$category_object = get_term( $category_object, 'task_cat' );
		$task_cat     = $category_object->slug;

		if ( $category_object->parent ) {
			$ancestors = get_ancestors( $category_object->term_id, 'task_cat' );
			foreach ( $ancestors as $ancestor ) {
				$ancestor_object = get_term( $ancestor, 'task_cat' );
				$task_cat     = $ancestor_object->slug . '/' . $task_cat;
			}
		}
	} else {
		// If no terms are assigned to this post, use a string instead (can't leave the placeholder there).
		$task_cat = _x( 'uncategorized', 'slug', 'woocommerce' );
	}

	$find = array(
		'%year%',
		'%monthnum%',
		'%day%',
		'%hour%',
		'%minute%',
		'%second%',
		'%post_id%',
		'%category%',
		'%task_cat%',
	);

	$replace = array(
		date_i18n( 'Y', strtotime( $post->post_date ) ),
		date_i18n( 'm', strtotime( $post->post_date ) ),
		date_i18n( 'd', strtotime( $post->post_date ) ),
		date_i18n( 'H', strtotime( $post->post_date ) ),
		date_i18n( 'i', strtotime( $post->post_date ) ),
		date_i18n( 's', strtotime( $post->post_date ) ),
		$post->ID,
		$task_cat,
		$task_cat,
	);

	$permalink = str_replace( $find, $replace, $permalink );

	return $permalink;
}
add_filter( 'post_type_link', 'cs_task_post_type_link', 10, 2 );


/**
 * Get the placeholder image URL for tasks etc.
 *
 * @param string $size Image size.
 * @return string
 */
function wc_placeholder_img_src( $size = 'woocommerce_thumbnail' ) {
	$src               = WC()->plugin_url() . '/assets/images/placeholder.png';
	$placeholder_image = get_option( 'woocommerce_placeholder_image', 0 );

	if ( ! empty( $placeholder_image ) ) {
		if ( is_numeric( $placeholder_image ) ) {
			$image = wp_get_attachment_image_src( $placeholder_image, $size );

			if ( ! empty( $image[0] ) ) {
				$src = $image[0];
			}
		} else {
			$src = $placeholder_image;
		}
	}

	return apply_filters( 'woocommerce_placeholder_img_src', $src );
}

/**
 * Get the placeholder image.
 *
 * @param string $size Image size.
 * @return string
 */
function wc_placeholder_img( $size = 'woocommerce_thumbnail' ) {
	$dimensions = wc_get_image_size( $size );

	return apply_filters( 'woocommerce_placeholder_img', '<img src="' . wc_placeholder_img_src( $size ) . '" alt="' . esc_attr__( 'Placeholder', 'woocommerce' ) . '" width="' . esc_attr( $dimensions['width'] ) . '" class="woocommerce-placeholder wp-post-image" height="' . esc_attr( $dimensions['height'] ) . '" />', $size, $dimensions );
}

/**
 * Get attachment image attributes.
 *
 * @param array $attr Image attributes.
 * @return array
 */
function wc_get_attachment_image_attributes( $attr ) {
	if ( isset( $attr['src'] ) && strstr( $attr['src'], 'woocommerce_uploads/' ) ) {
		$attr['src'] = wc_placeholder_img_src();

		if ( isset( $attr['srcset'] ) ) {
			$attr['srcset'] = '';
		}
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'wc_get_attachment_image_attributes' );


/**
 * Prepare attachment for JavaScript.
 *
 * @param array $response JS version of a attachment post object.
 * @return array
 */
function wc_prepare_attachment_for_js( $response ) {

	if ( isset( $response['url'] ) && strstr( $response['url'], 'woocommerce_uploads/' ) ) {
		$response['full']['url'] = wc_placeholder_img_src();
		if ( isset( $response['sizes'] ) ) {
			foreach ( $response['sizes'] as $size => $value ) {
				$response['sizes'][ $size ]['url'] = wc_placeholder_img_src();
			}
		}
	}

	return $response;
}
add_filter( 'wp_prepare_attachment_for_js', 'wc_prepare_attachment_for_js' );

/**
 * Track task views.
 */
function wc_track_task_view() {
	if ( ! is_singular( 'task' ) || ! is_active_widget( false, false, 'woocommerce_recently_viewed_tasks', true ) ) {
		return;
	}

	global $post;

	if ( empty( $_COOKIE['woocommerce_recently_viewed'] ) ) { // @codingStandardsIgnoreLine.
		$viewed_tasks = array();
	} else {
		$viewed_tasks = wp_parse_id_list( (array) explode( '|', wp_unslash( $_COOKIE['woocommerce_recently_viewed'] ) ) ); // @codingStandardsIgnoreLine.
	}

	// Unset if already in viewed tasks list.
	$keys = array_flip( $viewed_tasks );

	if ( isset( $keys[ $post->ID ] ) ) {
		unset( $viewed_tasks[ $keys[ $post->ID ] ] );
	}

	$viewed_tasks[] = $post->ID;

	if ( count( $viewed_tasks ) > 15 ) {
		array_shift( $viewed_tasks );
	}

	// Store for session only.
	wc_setcookie( 'woocommerce_recently_viewed', implode( '|', $viewed_tasks ) );
}

add_action( 'template_redirect', 'wc_track_task_view', 20 );



/**
 * Gets data about an attachment, such as alt text and captions.
 *
 * @since 2.6.0
 *
 * @param int|null        $attachment_id Attachment ID.
 * @param CS_Task|bool $task CS_Task object.
 *
 * @return array
 */
function wc_get_task_attachment_props( $attachment_id = null, $task = false ) {
	$props      = array(
		'title'   => '',
		'caption' => '',
		'url'     => '',
		'alt'     => '',
		'src'     => '',
		'srcset'  => false,
		'sizes'   => false,
	);
	$attachment = get_post( $attachment_id );

	if ( $attachment ) {
		$props['title']   = wp_strip_all_tags( $attachment->post_title );
		$props['caption'] = wp_strip_all_tags( $attachment->post_excerpt );
		$props['url']     = wp_get_attachment_url( $attachment_id );

		// Alt text.
		$alt_text = array( wp_strip_all_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ), $props['caption'], wp_strip_all_tags( $attachment->post_title ) );

		if ( $task && $task instanceof CS_Task ) {
			$alt_text[] = wp_strip_all_tags( get_the_title( $task->get_id() ) );
		}

		$alt_text     = array_filter( $alt_text );
		$props['alt'] = isset( $alt_text[0] ) ? $alt_text[0] : '';

		// Large version.
		$full_size           = apply_filters( 'woocommerce_gallery_full_size', apply_filters( 'woocommerce_task_thumbnails_large_size', 'full' ) );
		$src                 = wp_get_attachment_image_src( $attachment_id, $full_size );
		$props['full_src']   = $src[0];
		$props['full_src_w'] = $src[1];
		$props['full_src_h'] = $src[2];

		// Gallery thumbnail.
		$gallery_thumbnail                = wc_get_image_size( 'gallery_thumbnail' );
		$gallery_thumbnail_size           = apply_filters( 'woocommerce_gallery_thumbnail_size', array( $gallery_thumbnail['width'], $gallery_thumbnail['height'] ) );
		$src                              = wp_get_attachment_image_src( $attachment_id, $gallery_thumbnail_size );
		$props['gallery_thumbnail_src']   = $src[0];
		$props['gallery_thumbnail_src_w'] = $src[1];
		$props['gallery_thumbnail_src_h'] = $src[2];

		// Thumbnail version.
		$thumbnail_size       = apply_filters( 'woocommerce_thumbnail_size', 'woocommerce_thumbnail' );
		$src                  = wp_get_attachment_image_src( $attachment_id, $thumbnail_size );
		$props['thumb_src']   = $src[0];
		$props['thumb_src_w'] = $src[1];
		$props['thumb_src_h'] = $src[2];

		// Image source.
		$image_size      = apply_filters( 'woocommerce_gallery_image_size', 'woocommerce_single' );
		$src             = wp_get_attachment_image_src( $attachment_id, $image_size );
		$props['src']    = $src[0];
		$props['src_w']  = $src[1];
		$props['src_h']  = $src[2];
		$props['srcset'] = function_exists( 'wp_get_attachment_image_srcset' ) ? wp_get_attachment_image_srcset( $attachment_id, $image_size ) : false;
		$props['sizes']  = function_exists( 'wp_get_attachment_image_sizes' ) ? wp_get_attachment_image_sizes( $attachment_id, $image_size ) : false;
	}
	return $props;
}

/**
 * Get task visibility options.
 *
 * @since 1.0
 * @return array
 */
function wc_get_task_visibility_options() {
	return apply_filters(
		'woocommerce_task_visibility_options',
		array(
			'visible' => __( 'Shop and search results', 'woocommerce' ),
			'catalog' => __( 'Shop only', 'woocommerce' ),
			'search'  => __( 'Search results only', 'woocommerce' ),
			'hidden'  => __( 'Hidden', 'woocommerce' ),
		)
	);
}


/**
 * Get related tasks based on task category and tags.
 *
 * @since  1.0
 * @param  int   $task_id  Task ID.
 * @param  int   $limit       Limit of results.
 * @param  array $exclude_ids Exclude IDs from the results.
 * @return array
 */
function wc_get_related_tasks( $task_id, $limit = 5, $exclude_ids = array() ) {

	$task_id     = absint( $task_id );
	$limit          = $limit >= -1 ? $limit : 5;
	$exclude_ids    = array_merge( array( 0, $task_id ), $exclude_ids );
	$transient_name = 'wc_related_' . $task_id;
	$query_args     = http_build_query(
		array(
			'limit'       => $limit,
			'exclude_ids' => $exclude_ids,
		)
	);

	$transient     = get_transient( $transient_name );
	$related_posts = $transient && isset( $transient[ $query_args ] ) ? $transient[ $query_args ] : false;

	// We want to query related posts if they are not cached, or we don't have enough.
	if ( false === $related_posts || count( $related_posts ) < $limit ) {

		$cats_array = apply_filters( 'woocommerce_task_related_posts_relate_by_category', true, $task_id ) ? apply_filters( 'woocommerce_get_related_task_cat_terms', wc_get_task_term_ids( $task_id, 'task_cat' ), $task_id ) : array();
		$tags_array = apply_filters( 'woocommerce_task_related_posts_relate_by_tag', true, $task_id ) ? apply_filters( 'woocommerce_get_related_task_tag_terms', wc_get_task_term_ids( $task_id, 'task_tag' ), $task_id ) : array();

		// Don't bother if none are set, unless woocommerce_task_related_posts_force_display is set to true in which case all tasks are related.
		if ( empty( $cats_array ) && empty( $tags_array ) && ! apply_filters( 'woocommerce_task_related_posts_force_display', false, $task_id ) ) {
			$related_posts = array();
		} else {
			$data_store    = WC_Data_Store::load( 'task' );
			$related_posts = $data_store->get_related_tasks( $cats_array, $tags_array, $exclude_ids, $limit + 10, $task_id );
		}

		if ( $transient ) {
			$transient[ $query_args ] = $related_posts;
		} else {
			$transient = array( $query_args => $related_posts );
		}

		set_transient( $transient_name, $transient, DAY_IN_SECONDS );
	}

	$related_posts = apply_filters(
		'woocommerce_related_tasks',
		$related_posts,
		$task_id,
		array(
			'limit'        => $limit,
			'excluded_ids' => $exclude_ids,
		)
	);

	shuffle( $related_posts );

	return array_slice( $related_posts, 0, $limit );
}

/**
 * Retrieves task term ids for a taxonomy.
 *
 * @since  1.0
 * @param  int    $task_id Task ID.
 * @param  string $taxonomy   Taxonomy slug.
 * @return array
 */
function wc_get_task_term_ids( $task_id, $taxonomy ) {
	$terms = get_the_terms( $task_id, $taxonomy );
	return ( empty( $terms ) || is_wp_error( $terms ) ) ? array() : wp_list_pluck( $terms, 'term_id' );
}


/**
 * Returns the task categories in a list.
 *
 * @param int    $task_id Task ID.
 * @param string $sep (default: ', ').
 * @param string $before (default: '').
 * @param string $after (default: '').
 * @return string
 */
function wc_get_task_category_list( $task_id, $sep = ', ', $before = '', $after = '' ) {
	return get_the_term_list( $task_id, 'task_cat', $before, $sep, $after );
}

/**
 * Returns the task tags in a list.
 *
 * @param int    $task_id Task ID.
 * @param string $sep (default: ', ').
 * @param string $before (default: '').
 * @param string $after (default: '').
 * @return string
 */
function wc_get_task_tag_list( $task_id, $sep = ', ', $before = '', $after = '' ) {
	return get_the_term_list( $task_id, 'task_tag', $before, $sep, $after );
}


/**
 * Sort by title.
 *
 * @since  1.0
 * @param  CS_Task $a First CS_Task object.
 * @param  CS_Task $b Second CS_Task object.
 * @return int
 */
function cs_tasks_array_orderby_title( $a, $b ) {
	return strcasecmp( $a->get_name(), $b->get_name() );
}

/**
 * Sort by id.
 *
 * @since  1.0
 * @param  CS_Task $a First CS_Task object.
 * @param  CS_Task $b Second CS_Task object.
 * @return int
 */
function cs_tasks_array_orderby_id( $a, $b ) {
	if ( $a->get_id() === $b->get_id() ) {
		return 0;
	}
	return ( $a->get_id() < $b->get_id() ) ? -1 : 1;
}

/**
 * Sort by date.
 *
 * @since  1.0
 * @param  CS_Task $a First CS_Task object.
 * @param  CS_Task $b Second CS_Task object.
 * @return int
 */
function cs_tasks_array_orderby_date( $a, $b ) {
	if ( $a->get_date_created() === $b->get_date_created() ) {
		return 0;
	}
	return ( $a->get_date_created() < $b->get_date_created() ) ? -1 : 1;
}

/**
 * Sort by modified.
 *
 * @since  1.0
 * @param  CS_Task $a First CS_Task object.
 * @param  CS_Task $b Second CS_Task object.
 * @return int
 */
function cs_tasks_array_orderby_modified( $a, $b ) {
	if ( $a->get_date_modified() === $b->get_date_modified() ) {
		return 0;
	}
	return ( $a->get_date_modified() < $b->get_date_modified() ) ? -1 : 1;
}

/**
 * Sort by menu order.
 *
 * @since  1.0
 * @param  CS_Task $a First CS_Task object.
 * @param  CS_Task $b Second CS_Task object.
 * @return int
 */
function cs_tasks_array_orderby_menu_order( $a, $b ) {
	if ( $a->get_menu_order() === $b->get_menu_order() ) {
		return 0;
	}
	return ( $a->get_menu_order() < $b->get_menu_order() ) ? -1 : 1;
}

function cs_activity_exists_by_task( $task_id, $student_id=null ) {
	global $wpdb;
	if(is_null($student_id))$student_id = get_current_user_id();
    $activity = $wpdb->get_row( $wpdb->prepare( "SELECT ID,post_status FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'cs-activity' AND post_author = '%d'", $task_id,$student_id ) );
    if ( $activity ) {
		$statuses = cs_get_activity_statuses();
        return $statuses[$activity->post_status];
    } else {
    	return 'Submit for Approval';
    }
}