<?php
/**
 * WooCommerce Template
 *
 * Functions for the templating system.
 *
 * @package  WooCommerce\Functions
 * @version  2.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handle redirects before content is output - hooked into template_redirect so is_page works.
 */
function cs_template_redirect() {
	global $wp_query, $wp;

	if ( ! empty( $_GET['page_id'] ) && '' === get_option( 'permalink_structure' ) && cs_get_page_id( 'shop' ) === absint( $_GET['page_id'] ) && get_post_type_archive_link( 'task' ) ) { // WPCS: input var ok, CSRF ok.

		// When default permalinks are enabled, redirect shop page to post type archive url.
		wp_safe_redirect( get_post_type_archive_link( 'task' ) );
		exit;

	} elseif ( is_page( cs_get_page_id( 'checkout' ) ) && cs_get_page_id( 'checkout' ) !== cs_get_page_id( 'cart' ) && WC()->cart->is_empty() && empty( $wp->query_vars['order-pay'] ) && ! isset( $wp->query_vars['order-received'] ) && ! is_customize_preview() && apply_filters( 'communityservice_checkout_redirect_empty_cart', true ) ) {

		// When on the checkout with an empty cart, redirect to cart page.
		cs_add_notice( __( 'Checkout is not available whilst your cart is empty.', 'communityservice' ), 'notice' );
		wp_safe_redirect( cs_get_page_permalink( 'cart' ) );
		exit;

	} elseif ( isset( $wp->query_vars['customer-logout'] ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'customer-logout' ) ) { // WPCS: input var ok, CSRF ok.

		// Logout.
		wp_safe_redirect( str_replace( '&amp;', '&', wp_logout_url( cs_get_page_permalink( 'myaccount' ) ) ) );
		exit;

	} elseif ( isset( $wp->query_vars['customer-logout'] ) && 'true' === $wp->query_vars['customer-logout'] ) {
		// Redirect to the correct logout endpoint.
		wp_safe_redirect( esc_url_raw( cs_get_account_endpoint_url( 'customer-logout' ) ) );
		exit;

	} elseif ( is_search() && is_post_type_archive( 'task' ) && apply_filters( 'communityservice_redirect_single_search_result', true ) && 1 === absint( $wp_query->found_posts ) ) {

		// Redirect to the task page if we have a single task.
		$task = cs_get_task( $wp_query->post );

		if ( $task && $task->is_visible() ) {
			wp_safe_redirect( get_permalink( $task->get_id() ), 302 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'cs_template_redirect' );


/**
 * Show the gallery if JS is disabled.
 *
 * @since 3.0.6
 */
function cs_gallery_noscript() {
	?>
	<noscript><style>.communityservice-task-gallery{ opacity: 1 !important; }</style></noscript>
	<?php
}
add_action( 'wp_head', 'cs_gallery_noscript' );

/**
 * When the_post is called, put task data into a global.
 *
 * @param mixed $post Post Object.
 * @return CS_Task
 */
function cs_setup_task_data( $post ) {
	unset( $GLOBALS['task'] );

	if ( is_int( $post ) ) {
		$post = get_post( $post );
	}

	if ( empty( $post->post_type ) || ! in_array( $post->post_type, array( 'task', 'task_variation' ), true ) ) {
		return;
	}

	$GLOBALS['task'] = cs_get_task( $post );

	return $GLOBALS['task'];
}
add_action( 'the_post', 'cs_setup_task_data' );

/**
 * Sets up the communityservice_loop global from the passed args or from the main query.
 *
 * @since 3.3.0
 * @param array $args Args to pass into the global.
 */
function cs_setup_loop( $args = array() ) {
	$default_args = array(
		'loop'         => 0,
		'columns'      => cs_get_default_tasks_per_row(),
		'name'         => '',
		'is_shortcode' => false,
		'is_paginated' => true,
		'is_search'    => false,
		'is_filtered'  => false,
		'total'        => 0,
		'total_pages'  => 0,
		'per_page'     => 0,
		'current_page' => 1,
	);

	// If this is a main CS query, use global args as defaults.
	if ( $GLOBALS['wp_query']->get( 'cs_query' ) ) {
		$default_args = array_merge( $default_args, array(
			'is_search'    => $GLOBALS['wp_query']->is_search(),
			//'is_filtered'  => is_filtered(),
			'total'        => $GLOBALS['wp_query']->found_posts,
			'total_pages'  => $GLOBALS['wp_query']->max_num_pages,
			'per_page'     => $GLOBALS['wp_query']->get( 'posts_per_page' ),
			'current_page' => max( 1, $GLOBALS['wp_query']->get( 'paged', 1 ) ),
		) );
	}

	// Merge any existing values.
	if ( isset( $GLOBALS['communityservice_loop'] ) ) {
		$default_args = array_merge( $default_args, $GLOBALS['communityservice_loop'] );
	}

	$GLOBALS['communityservice_loop'] = wp_parse_args( $args, $default_args );
}
add_action( 'communityservice_before_shop_loop', 'cs_setup_loop' );

/**
 * Resets the communityservice_loop global.
 *
 * @since 3.3.0
 */
function cs_reset_loop() {
	unset( $GLOBALS['communityservice_loop'] );
}
add_action( 'communityservice_after_shop_loop', 'communityservice_reset_loop', 999 );

/**
 * Gets a property from the communityservice_loop global.
 *
 * @since 3.3.0
 * @param string $prop Prop to get.
 * @param string $default Default if the prop does not exist.
 * @return mixed
 */
function cs_get_loop_prop( $prop, $default = '' ) {
	cs_setup_loop(); // Ensure shop loop is setup.

	return isset( $GLOBALS['communityservice_loop'], $GLOBALS['communityservice_loop'][ $prop ] ) ? $GLOBALS['communityservice_loop'][ $prop ] : $default;
}

/**
 * Sets a property in the communityservice_loop global.
 *
 * @since 3.3.0
 * @param string $prop Prop to set.
 * @param string $value Value to set.
 */
function cs_set_loop_prop( $prop, $value = '' ) {
	if ( ! isset( $GLOBALS['communityservice_loop'] ) ) {
		cs_setup_loop();
	}
	$GLOBALS['communityservice_loop'][ $prop ] = $value;
}

/**
 * Should the WooCommerce loop be displayed?
 *
 * This will return true if we have posts (tasks) or if we have subcats to display.
 *
 * @since 3.4.0
 * @return bool
 */
function communityservice_task_loop() {
	return have_posts() || 'tasks' !== communityservice_get_loop_display_mode();
}

/**
 * Add body classes for WC pages.
 *
 * @param  array $classes Body Classes.
 * @return array
 */
function cs_body_class( $classes ) {
	$classes = (array) $classes;

	if ( is_communityservice() ) {

		$classes[] = 'communityservice';
		$classes[] = 'communityservice-page';

	} elseif ( is_checkout() ) {

		$classes[] = 'communityservice-checkout';
		$classes[] = 'communityservice-page';

	} elseif ( is_cart() ) {

		$classes[] = 'communityservice-cart';
		$classes[] = 'communityservice-page';

	} elseif ( is_account_page() ) {

		$classes[] = 'communityservice-account';
		$classes[] = 'communityservice-page';

	}

	if ( is_store_notice_showing() ) {
		$classes[] = 'communityservice-demo-store';
	}

	foreach ( WC()->query->get_query_vars() as $key => $value ) {
		if ( is_cs_endpoint_url( $key ) ) {
			$classes[] = 'communityservice-' . sanitize_html_class( $key );
		}
	}

	$classes[] = 'communityservice-no-js';

	add_action( 'wp_footer', 'cs_no_js' );

	return array_unique( $classes );
}

/**
 * NO JS handling.
 *
 * @since 3.4.0
 */
function cs_no_js() {
	?>
	<script type="text/javascript">
		var c = document.body.className;
		c = c.replace(/communityservice-no-js/, 'communityservice-js');
		document.body.className = c;
	</script>
	<?php
}


/**
 * Get the default columns setting - this is how many tasks will be shown per row in loops.
 *
 * @since 3.3.0
 * @return int
 */
function cs_get_default_tasks_per_row() {
	$columns      = get_option( 'communityservice_catalog_columns', 4 );
	$task_grid = cs_get_theme_support( 'task_grid' );
	$min_columns  = isset( $task_grid['min_columns'] ) ? absint( $task_grid['min_columns'] ) : 0;
	$max_columns  = isset( $task_grid['max_columns'] ) ? absint( $task_grid['max_columns'] ) : 0;

	if ( $min_columns && $columns < $min_columns ) {
		$columns = $min_columns;
		update_option( 'communityservice_catalog_columns', $columns );
	} elseif ( $max_columns && $columns > $max_columns ) {
		$columns = $max_columns;
		update_option( 'communityservice_catalog_columns', $columns );
	}

	if ( has_filter( 'loop_shop_columns' ) ) { // Legacy filter handling.
		$columns = apply_filters( 'loop_shop_columns', $columns );
	}

	$columns = absint( $columns );

	return max( 1, $columns );
}

/**
 * Get the default rows setting - this is how many task rows will be shown in loops.
 *
 * @since 3.3.0
 * @return int
 */
function cs_get_default_task_rows_per_page() {
	$rows         = absint( get_option( 'communityservice_catalog_rows', 4 ) );
	$task_grid 	  = cs_get_theme_support( 'task_grid' );
	$min_rows     = isset( $task_grid['min_rows'] ) ? absint( $task_grid['min_rows'] ) : 0;
	$max_rows     = isset( $task_grid['max_rows'] ) ? absint( $task_grid['max_rows'] ) : 0;

	if ( $min_rows && $rows < $min_rows ) {
		$rows = $min_rows;
		update_option( 'communityservice_catalog_rows', $rows );
	} elseif ( $max_rows && $rows > $max_rows ) {
		$rows = $max_rows;
		update_option( 'communityservice_catalog_rows', $rows );
	}

	return $rows;
}

/**
 * Reset the task grid settings when a new theme is activated.
 *
 * @since 3.3.0
 */
function cs_reset_task_grid_settings() {
	$task_grid = cs_get_theme_support( 'task_grid' );

	if ( ! empty( $task_grid['default_rows'] ) ) {
		update_option( 'communityservice_catalog_rows', absint( $task_grid['default_rows'] ) );
	}

	if ( ! empty( $task_grid['default_columns'] ) ) {
		update_option( 'communityservice_catalog_columns', absint( $task_grid['default_columns'] ) );
	}
}
add_action( 'after_switch_theme', 'cs_reset_task_grid_settings' );

/**
 * Get classname for communityservice loops.
 *
 * @since 2.6.0
 * @return string
 */
function cs_get_loop_class() {
	$loop_index = cs_get_loop_prop( 'loop', 0 );
	$columns    = absint( max( 1, cs_get_loop_prop( 'columns', cs_get_default_tasks_per_row() ) ) );

	$loop_index ++;
	cs_set_loop_prop( 'loop', $loop_index );

	if ( 0 === ( $loop_index - 1 ) % $columns || 1 === $columns ) {
		return 'first';
	}

	if ( 0 === $loop_index % $columns ) {
		return 'last';
	}

	return '';
}



/**
 * Adds extra post classes for tasks.
 *
 * @since 2.1.0
 * @param array        $classes Current classes.
 * @param string|array $class Additional class.
 * @param int          $post_id Post ID.
 * @return array
 */
function cs_task_post_class( $classes, $class = '', $post_id = 0 ) {
	if ( ! $post_id || ! in_array( get_post_type( $post_id ), array( 'task', 'task_variation' ), true ) ) {
		return $classes;
	}

	$task = cs_get_task( $post_id );

	if ( $task ) {
		$classes[] = 'task';
		$classes[] = cs_get_loop_class();
		$classes[] = $task->get_stock_status();

		if ( $task->is_on_sale() ) {
			$classes[] = 'sale';
		}
		if ( $task->is_featured() ) {
			$classes[] = 'featured';
		}
		if ( $task->is_downloadable() ) {
			$classes[] = 'downloadable';
		}
		if ( $task->is_virtual() ) {
			$classes[] = 'virtual';
		}
		if ( $task->is_sold_individually() ) {
			$classes[] = 'sold-individually';
		}
		if ( $task->is_taxable() ) {
			$classes[] = 'taxable';
		}
		if ( $task->is_shipping_taxable() ) {
			$classes[] = 'shipping-taxable';
		}
		if ( $task->is_purchasable() ) {
			$classes[] = 'purchasable';
		}
		if ( $task->get_type() ) {
			$classes[] = 'task-type-' . $task->get_type();
		}
		if ( $task->is_type( 'variable' ) ) {
			if ( ! $task->get_default_attributes() ) {
				$classes[] = 'has-default-attributes';
			}
		}
	}

	$key = array_search( 'hentry', $classes, true );
	if ( false !== $key ) {
		unset( $classes[ $key ] );
	}

	return $classes;
}

/**
 * Get task taxonomy HTML classes.
 *
 * @since 3.4.0
 * @param array  $term_ids Array of terms IDs or objects.
 * @param string $taxonomy Taxonomy.
 * @return array
 */
function cs_get_task_taxonomy_class( $term_ids, $taxonomy ) {
	$classes = array();

	foreach ( $term_ids as $term_id ) {
		$term = get_term( $term_id, $taxonomy );

		if ( empty( $term->slug ) ) {
			continue;
		}

		$term_class = sanitize_html_class( $term->slug, $term->term_id );
		if ( is_numeric( $term_class ) || ! trim( $term_class, '-' ) ) {
			$term_class = $term->term_id;
		}

		// 'post_tag' uses the 'tag' prefix for backward compatibility.
		if ( 'post_tag' === $taxonomy ) {
			$classes[] = 'tag-' . $term_class;
		} else {
			$classes[] = sanitize_html_class( $taxonomy . '-' . $term_class, $taxonomy . '-' . $term->term_id );
		}
	}

	return $classes;
}

/**
 * Retrieves the classes for the post div as an array.
 *
 * This method is clone from WordPress's get_post_class(), allowing removing taxonomies.
 *
 * @since 3.4.0
 * @param string|array           $class      One or more classes to add to the class list.
 * @param int|WP_Post|CS_Task $task_id Task ID or task object.
 * @return array
 */
function cs_get_task_class( $class = '', $task_id = null ) {
	if ( is_a( $task_id, 'CS_Task' ) ) {
		$task    = $task_id;
		$task_id = $task_id->get_id();
		$post       = get_post( $task_id );
	} else {
		$post    = get_post( $task_id );
		$task = cs_get_task( $post->ID );
	}

	$classes = array();

	if ( $class ) {
		if ( ! is_array( $class ) ) {
			$class = preg_split( '#\s+#', $class );
		}
		$classes = array_map( 'esc_attr', $class );
	} else {
		// Ensure that we always coerce class to being an array.
		$class = array();
	}

	if ( ! $post || ! $task ) {
		return $classes;
	}

	$classes[] = 'post-' . $post->ID;
	if ( ! is_admin() ) {
		$classes[] = $post->post_type;
	}
	$classes[] = 'type-' . $post->post_type;
	$classes[] = 'status-' . $post->post_status;

	// Post format.
	if ( post_type_supports( $post->post_type, 'post-formats' ) ) {
		$post_format = get_post_format( $post->ID );

		if ( $post_format && ! is_wp_error( $post_format ) ) {
			$classes[] = 'format-' . sanitize_html_class( $post_format );
		} else {
			$classes[] = 'format-standard';
		}
	}

	// Post requires password.
	$post_password_required = post_password_required( $post->ID );
	if ( $post_password_required ) {
		$classes[] = 'post-password-required';
	} elseif ( ! empty( $post->post_password ) ) {
		$classes[] = 'post-password-protected';
	}

	// Post thumbnails.
	if ( current_theme_supports( 'post-thumbnails' ) && $task->get_image_id() && ! is_attachment( $post ) && ! $post_password_required ) {
		$classes[] = 'has-post-thumbnail';
	}

	// Sticky for Sticky Posts.
	if ( is_sticky( $post->ID ) ) {
		if ( is_home() && ! is_paged() ) {
			$classes[] = 'sticky';
		} elseif ( is_admin() ) {
			$classes[] = 'status-sticky';
		}
	}

	// Hentry for hAtom compliance.
	$classes[] = 'hentry';

	// Include attributes and any extra taxonomy.
	if ( apply_filters( 'communityservice_get_task_class_include_taxonomies', false ) ) {
		$taxonomies = get_taxonomies( array( 'public' => true ) );
		foreach ( (array) $taxonomies as $taxonomy ) {
			if ( is_object_in_taxonomy( $post->post_type, $taxonomy ) && ! in_array( $taxonomy, array( 'task_cat', 'task_tag' ), true ) ) {
				$classes = array_merge( $classes, cs_get_task_taxonomy_class( (array) get_the_terms( $post->ID, $taxonomy ), $taxonomy ) );
			}
		}
	}
	// Categories.
	$classes = array_merge( $classes, cs_get_task_taxonomy_class( $task->get_category_ids(), 'task_cat' ) );

	// Tags.
	$classes = array_merge( $classes, cs_get_task_taxonomy_class( $task->get_tag_ids(), 'task_tag' ) );

	return array_filter( array_unique( apply_filters( 'post_class', $classes, $class, $post->ID ) ) );
}

/**
 * Display the classes for the task div.
 *
 * @since 3.4.0
 * @param string|array           $class      One or more classes to add to the class list.
 * @param int|WP_Post|CS_Task $task_id Task ID or task object.
 */
function cs_task_class( $class = '', $task_id = null ) {
	echo 'class="' . esc_attr( join( ' ', cs_get_task_class( $class, $task_id ) ) ) . '"';
}

/**
 * Outputs hidden form inputs for each query string variable.
 *
 * @since 3.0.0
 * @param string|array $values Name value pairs, or a URL to parse.
 * @param array        $exclude Keys to exclude.
 * @param string       $current_key Current key we are outputting.
 * @param bool         $return Whether to return.
 * @return string
 */
function cs_query_string_form_fields( $values = null, $exclude = array(), $current_key = '', $return = false ) {
	if ( is_null( $values ) ) {
		$values = $_GET; // WPCS: input var ok, CSRF ok.
	} elseif ( is_string( $values ) ) {
		$url_parts = wp_parse_url( $values );
		$values    = array();

		if ( ! empty( $url_parts['query'] ) ) {
			parse_str( $url_parts['query'], $values );
		}
	}
	$html = '';

	foreach ( $values as $key => $value ) {
		if ( in_array( $key, $exclude, true ) ) {
			continue;
		}
		if ( $current_key ) {
			$key = $current_key . '[' . $key . ']';
		}
		if ( is_array( $value ) ) {
			$html .= cs_query_string_form_fields( $value, $exclude, $key, true );
		} else {
			$html .= '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( wp_unslash( $value ) ) . '" />';
		}
	}

	if ( $return ) {
		return $html;
	}

	echo $html; // WPCS: XSS ok.
}

/**
 * Get the terms and conditons page ID.
 *
 * @since 3.4.0
 * @return int
 */
function cs_terms_and_conditions_page_id() {
	$page_id = cs_get_page_id( 'terms' );
	return apply_filters( 'communityservice_terms_and_conditions_page_id', 0 < $page_id ? absint( $page_id ) : 0 );
}

/**
 * Get the privacy policy page ID.
 *
 * @since 3.4.0
 * @return int
 */
function cs_privacy_policy_page_id() {
	$page_id = get_option( 'wp_page_for_privacy_policy', 0 );
	return apply_filters( 'communityservice_privacy_policy_page_id', 0 < $page_id ? absint( $page_id ) : 0 );
}

/**
 * See if the checkbox is enabled or not based on the existance of the terms page and checkbox text.
 *
 * @since 3.4.0
 * @return bool
 */
function cs_terms_and_conditions_checkbox_enabled() {
	$page_id = cs_terms_and_conditions_page_id();
	$page    = $page_id ? get_post( $page_id ) : false;
	return $page && cs_get_terms_and_conditions_checkbox_text();
}

/**
 * Get the terms and conditons checkbox text, if set.
 *
 * @since 3.4.0
 * @return string
 */
function cs_get_terms_and_conditions_checkbox_text() {
	/* translators: %s terms and conditions page name and link */
	return trim( apply_filters( 'communityservice_get_terms_and_conditions_checkbox_text', get_option( 'communityservice_checkout_terms_and_conditions_checkbox_text', sprintf( __( 'I have read and agree to the website %s', 'communityservice' ), '[terms]' ) ) ) );
}

/**
 * Get the privacy policy text, if set.
 *
 * @since 3.4.0
 * @param string $type Type of policy to load. Valid values include registration and checkout.
 * @return string
 */
function cs_get_privacy_policy_text( $type = '' ) {
	$text = '';

	switch ( $type ) {
		case 'checkout':
			/* translators: %s privacy policy page name and link */
			$text = get_option( 'communityservice_checkout_privacy_policy_text', sprintf( __( 'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our %s.', 'communityservice' ), '[privacy_policy]' ) );
			break;
		case 'registration':
			/* translators: %s privacy policy page name and link */
			$text = get_option( 'communityservice_registration_privacy_policy_text', sprintf( __( 'Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our %s.', 'communityservice' ), '[privacy_policy]' ) );
			break;
	}

	return trim( apply_filters( 'communityservice_get_privacy_policy_text', $text, $type ) );
}

/**
 * Output t&c checkbox text.
 *
 * @since 3.4.0
 */
function cs_terms_and_conditions_checkbox_text() {
	$text = cs_get_terms_and_conditions_checkbox_text();

	if ( ! $text ) {
		return;
	}

	echo wp_kses_post( cs_replace_policy_page_link_placeholders( $text ) );
}

/**
 * Output t&c page's content (if set). The page can be set from checkout settings.
 *
 * @since 3.4.0
 */
function cs_terms_and_conditions_page_content() {
	$terms_page_id = cs_terms_and_conditions_page_id();

	if ( ! $terms_page_id ) {
		return;
	}

	$page = get_post( $terms_page_id );

	if ( $page && 'publish' === $page->post_status && $page->post_content && ! has_shortcode( $page->post_content, 'communityservice_checkout' ) ) {
		echo '<div class="communityservice-terms-and-conditions" style="display: none; max-height: 200px; overflow: auto;">' . wp_kses_post( cs_format_content( $page->post_content ) ) . '</div>';
	}
}

/**
 * Render privacy policy text on the checkout.
 *
 * @since 3.4.0
 */
function cs_checkout_privacy_policy_text() {
	echo '<div class="communityservice-privacy-policy-text">';
	cs_privacy_policy_text( 'checkout' );
	echo '</div>';
}

/**
 * Render privacy policy text on the register forms.
 *
 * @since 3.4.0
 */
function cs_registration_privacy_policy_text() {
	echo '<div class="communityservice-privacy-policy-text">';
	cs_privacy_policy_text( 'registration' );
	echo '</div>';
}

/**
 * Output privacy policy text. This is custom text which can be added via the customizer/privacy settings section.
 *
 * Loads the relevant policy for the current page unless a specific policy text is required.
 *
 * @since 3.4.0
 * @param string $type Type of policy to load. Valid values include registration and checkout.
 */
function cs_privacy_policy_text( $type = 'checkout' ) {
	if ( ! cs_privacy_policy_page_id() ) {
		return;
	}
	echo wp_kses_post( wpautop( cs_replace_policy_page_link_placeholders( cs_get_privacy_policy_text( $type ) ) ) );
}

/**
 * Replaces placeholders with links to WooCommerce policy pages.
 *
 * @since 3.4.0
 * @param string $text Text to find/replace within.
 * @return string
 */
function cs_replace_policy_page_link_placeholders( $text ) {
	$privacy_page_id = cs_privacy_policy_page_id();
	$terms_page_id   = cs_terms_and_conditions_page_id();
	$privacy_link    = $privacy_page_id ? '<a href="' . esc_url( get_permalink( $privacy_page_id ) ) . '" class="communityservice-privacy-policy-link" target="_blank">' . __( 'privacy policy', 'communityservice' ) . '</a>' : __( 'privacy policy', 'communityservice' );
	$terms_link      = $terms_page_id ? '<a href="' . esc_url( get_permalink( $terms_page_id ) ) . '" class="communityservice-terms-and-conditions-link" target="_blank">' . __( 'terms and conditions', 'communityservice' ) . '</a>' : __( 'terms and conditions', 'communityservice' );

	$find_replace = array(
		'[terms]'          => $terms_link,
		'[privacy_policy]' => $privacy_link,
	);

	return str_replace( array_keys( $find_replace ), array_values( $find_replace ), $text );
}

/**
 * Template pages
 */

if ( ! function_exists( 'communityservice_content' ) ) {

	/**
	 * Output WooCommerce content.
	 *
	 * This function is only used in the optional 'communityservice.php' template.
	 * which people can add to their themes to add basic communityservice support.
	 * without hooks or modifying core templates.
	 */
	function communityservice_content() {

		if ( is_singular( 'task' ) ) {

			while ( have_posts() ) :
				the_post();
				cs_get_template_part( 'content', 'single-task' );
			endwhile;

		} else {
			?>

			<?php if ( apply_filters( 'communityservice_show_page_title', true ) ) : ?>

				<h1 class="page-title"><?php communityservice_page_title(); ?></h1>

			<?php endif; ?>

			<?php do_action( 'communityservice_archive_description' ); ?>

			<?php if ( communityservice_task_loop() ) : ?>

				<?php do_action( 'communityservice_before_shop_loop' ); ?>

				<?php communityservice_task_loop_start(); ?>

				<?php if ( cs_get_loop_prop( 'total' ) ) : ?>
					<?php while ( have_posts() ) : ?>
						<?php the_post(); ?>
						<?php cs_get_template_part( 'content', 'task' ); ?>
					<?php endwhile; ?>
				<?php endif; ?>

				<?php communityservice_task_loop_end(); ?>

				<?php do_action( 'communityservice_after_shop_loop' ); ?>

			<?php else : ?>

				<?php do_action( 'communityservice_no_tasks_found' ); ?>

			<?php
			endif;

		}
	}
}

/**
 * Global
 */

if ( ! function_exists( 'communityservice_output_content_wrapper' ) ) {

	/**
	 * Output the start of the page wrapper.
	 */
	function communityservice_output_content_wrapper() {
		cs_get_template( 'global/wrapper-start.php' );
	}
}
if ( ! function_exists( 'communityservice_output_content_wrapper_end' ) ) {

	/**
	 * Output the end of the page wrapper.
	 */
	function communityservice_output_content_wrapper_end() {
		cs_get_template( 'global/wrapper-end.php' );
	}
}

if ( ! function_exists( 'communityservice_get_sidebar' ) ) {

	/**
	 * Get the shop sidebar template.
	 */
	function communityservice_get_sidebar() {
		cs_get_template( 'global/sidebar.php' );
	}
}

if ( ! function_exists( 'communityservice_demo_store' ) ) {

	/**
	 * Adds a demo store banner to the site if enabled.
	 */
	function communityservice_demo_store() {
		if ( ! is_store_notice_showing() ) {
			return;
		}

		$notice = get_option( 'communityservice_demo_store_notice' );

		if ( empty( $notice ) ) {
			$notice = __( 'This is a demo store for testing purposes &mdash; no orders shall be fulfilled.', 'communityservice' );
		}

		echo apply_filters( 'communityservice_demo_store', '<p class="communityservice-store-notice demo_store">' . wp_kses_post( $notice ) . ' <a href="#" class="communityservice-store-notice__dismiss-link">' . esc_html__( 'Dismiss', 'communityservice' ) . '</a></p>', $notice ); // WPCS: XSS ok.
	}
}

/**
 * Loop
 */

if ( ! function_exists( 'communityservice_page_title' ) ) {

	/**
	 * Page Title function.
	 *
	 * @param  bool $echo Should echo title.
	 * @return string
	 */
	function communityservice_page_title( $echo = true ) {

		if ( is_search() ) {
			/* translators: %s: search query */
			$page_title = sprintf( __( 'Search results: &ldquo;%s&rdquo;', 'communityservice' ), get_search_query() );

			if ( get_query_var( 'paged' ) ) {
				/* translators: %s: page number */
				$page_title .= sprintf( __( '&nbsp;&ndash; Page %s', 'communityservice' ), get_query_var( 'paged' ) );
			}
		} elseif ( is_tax() ) {

			$page_title = single_term_title( '', false );

		} else {

			$shop_page_id = cs_get_page_id( 'shop' );
			$page_title   = get_the_title( $shop_page_id );

		}

		$page_title = apply_filters( 'communityservice_page_title', $page_title );

		if ( $echo ) {
			echo $page_title; // WPCS: XSS ok.
		} else {
			return $page_title;
		}
	}
}

if ( ! function_exists( 'communityservice_task_loop_start' ) ) {

	/**
	 * Output the start of a task loop. By default this is a UL.
	 *
	 * @param bool $echo Should echo?.
	 * @return string
	 */
	function communityservice_task_loop_start( $echo = true ) {
		ob_start();

		cs_set_loop_prop( 'loop', 0 );

		cs_get_template( 'loop/loop-start.php' );

		$loop_start = apply_filters( 'communityservice_task_loop_start', ob_get_clean() );

		if ( $echo ) {
			echo $loop_start; // WPCS: XSS ok.
		} else {
			return $loop_start;
		}
	}
}

if ( ! function_exists( 'communityservice_task_loop_end' ) ) {

	/**
	 * Output the end of a task loop. By default this is a UL.
	 *
	 * @param bool $echo Should echo?.
	 * @return string
	 */
	function communityservice_task_loop_end( $echo = true ) {
		ob_start();

		cs_get_template( 'loop/loop-end.php' );

		$loop_end = apply_filters( 'communityservice_task_loop_end', ob_get_clean() );

		if ( $echo ) {
			echo $loop_end; // WPCS: XSS ok.
		} else {
			return $loop_end;
		}
	}
}
if ( ! function_exists( 'communityservice_template_loop_task_title' ) ) {

	/**
	 * Show the task title in the task loop. By default this is an H2.
	 */
	function communityservice_template_loop_task_title() {
		echo '<h2 class="communityservice-loop-task__title">' . get_the_title() . '</h2>';
	}
}
if ( ! function_exists( 'communityservice_template_loop_category_title' ) ) {

	/**
	 * Show the subcategory title in the task loop.
	 *
	 * @param object $category Category object.
	 */
	function communityservice_template_loop_category_title( $category ) {
		?>
		<h2 class="communityservice-loop-category__title">
			<?php
			echo esc_html( $category->name );

			if ( $category->count > 0 ) {
				echo apply_filters( 'communityservice_subcategory_count_html', ' <mark class="count">(' . esc_html( $category->count ) . ')</mark>', $category ); // WPCS: XSS ok.
			}
			?>
		</h2>
		<?php
	}
}

if ( ! function_exists( 'communityservice_template_loop_task_link_open' ) ) {
	/**
	 * Insert the opening anchor tag for tasks in the loop.
	 */
	function communityservice_template_loop_task_link_open() {
		global $task;

		$link = apply_filters( 'communityservice_loop_task_link', get_the_permalink(), $task );

		echo '<a href="' . esc_url( $link ) . '" class="communityservice-LoopTask-link communityservice-loop-task__link">';
	}
}

if ( ! function_exists( 'communityservice_template_loop_task_link_close' ) ) {
	/**
	 * Insert the opening anchor tag for tasks in the loop.
	 */
	function communityservice_template_loop_task_link_close() {
		echo '</a>';
	}
}

if ( ! function_exists( 'communityservice_template_loop_category_link_open' ) ) {
	/**
	 * Insert the opening anchor tag for categories in the loop.
	 *
	 * @param int|object|string $category Category ID, Object or String.
	 */
	function communityservice_template_loop_category_link_open( $category ) {
		echo '<a href="' . esc_url( get_term_link( $category, 'task_cat' ) ) . '">';
	}
}

if ( ! function_exists( 'communityservice_template_loop_category_link_close' ) ) {
	/**
	 * Insert the closing anchor tag for categories in the loop.
	 */
	function communityservice_template_loop_category_link_close() {
		echo '</a>';
	}
}

if ( ! function_exists( 'communityservice_taxonomy_archive_description' ) ) {

	/**
	 * Show an archive description on taxonomy archives.
	 */
	function communityservice_taxonomy_archive_description() {
		if ( is_task_taxonomy() && 0 === absint( get_query_var( 'paged' ) ) ) {
			$term = get_queried_object();

			if ( $term && ! empty( $term->description ) ) {
				echo '<div class="term-description">' . cs_format_content( $term->description ) . '</div>'; // WPCS: XSS ok.
			}
		}
	}
}
if ( ! function_exists( 'communityservice_task_archive_description' ) ) {

	/**
	 * Show a shop page description on task archives.
	 */
	function communityservice_task_archive_description() {
		// Don't display the description on search results page.
		if ( is_search() ) {
			return;
		}

		if ( is_post_type_archive( 'task' ) && in_array( absint( get_query_var( 'paged' ) ), array( 0, 1 ), true ) ) {
			$shop_page = get_post( cs_get_page_id( 'shop' ) );
			if ( $shop_page ) {
				$description = cs_format_content( $shop_page->post_content );
				if ( $description ) {
					echo '<div class="page-description">' . $description . '</div>'; // WPCS: XSS ok.
				}
			}
		}
	}
}

if ( ! function_exists( 'communityservice_template_loop_add_to_cart' ) ) {

	/**
	 * Get the add to cart template for the loop.
	 *
	 * @param array $args Arguments.
	 */
	function communityservice_template_loop_add_to_cart( $args = array() ) {
		global $task;

		if ( $task ) {
			$defaults = array(
				'quantity'   => 1,
				'class'      => implode( ' ', array_filter( array(
					'button',
					'task_type_' . $task->get_type(),
					$task->is_purchasable() && $task->is_in_stock() ? 'add_to_cart_button' : '',
					$task->supports( 'ajax_add_to_cart' ) && $task->is_purchasable() && $task->is_in_stock() ? 'ajax_add_to_cart' : '',
				) ) ),
				'attributes' => array(
					'data-task_id'  => $task->get_id(),
					'data-task_sku' => $task->get_sku(),
					'aria-label'       => $task->add_to_cart_description(),
					'rel'              => 'nofollow',
				),
			);

			$args = apply_filters( 'communityservice_loop_add_to_cart_args', wp_parse_args( $args, $defaults ), $task );

			if ( isset( $args['attributes']['aria-label'] ) ) {
				$args['attributes']['aria-label'] = strip_tags( $args['attributes']['aria-label'] );
			}

			cs_get_template( 'loop/add-to-cart.php', $args );
		}
	}
}

if ( ! function_exists( 'communityservice_template_loop_task_thumbnail' ) ) {

	/**
	 * Get the task thumbnail for the loop.
	 */
	function communityservice_template_loop_task_thumbnail() {
		echo communityservice_get_task_thumbnail(); // WPCS: XSS ok.
	}
}
if ( ! function_exists( 'communityservice_template_loop_price' ) ) {

	/**
	 * Get the task price for the loop.
	 */
	function communityservice_template_loop_price() {
		cs_get_template( 'loop/price.php' );
	}
}
if ( ! function_exists( 'communityservice_template_loop_rating' ) ) {

	/**
	 * Display the average rating in the loop.
	 */
	function communityservice_template_loop_rating() {
		cs_get_template( 'loop/rating.php' );
	}
}
if ( ! function_exists( 'communityservice_show_task_loop_sale_flash' ) ) {

	/**
	 * Get the sale flash for the loop.
	 */
	function communityservice_show_task_loop_sale_flash() {
		cs_get_template( 'loop/sale-flash.php' );
	}
}

if ( ! function_exists( 'communityservice_get_task_thumbnail' ) ) {

	/**
	 * Get the task thumbnail, or the placeholder if not set.
	 *
	 * @param string $size (default: 'communityservice_thumbnail').
	 * @param int    $deprecated1 Deprecated since WooCommerce 2.0 (default: 0).
	 * @param int    $deprecated2 Deprecated since WooCommerce 2.0 (default: 0).
	 * @return string
	 */
	function communityservice_get_task_thumbnail( $size = 'communityservice_thumbnail', $deprecated1 = 0, $deprecated2 = 0 ) {
		global $task;

		$image_size = apply_filters( 'single_task_archive_thumbnail_size', $size );

		return $task ? $task->get_image( $image_size ) : '';
	}
}

if ( ! function_exists( 'communityservice_result_count' ) ) {

	/**
	 * Output the result count text (Showing x - x of x results).
	 */
	function communityservice_result_count() {
		if ( ! cs_get_loop_prop( 'is_paginated' ) || ! communityservice_tasks_will_display() ) {
			return;
		}
		$args = array(
			'total'    => cs_get_loop_prop( 'total' ),
			'per_page' => cs_get_loop_prop( 'per_page' ),
			'current'  => cs_get_loop_prop( 'current_page' ),
		);

		cs_get_template( 'loop/result-count.php', $args );
	}
}

if ( ! function_exists( 'communityservice_catalog_ordering' ) ) {

	/**
	 * Output the task sorting options.
	 */
	function communityservice_catalog_ordering() {
		if ( ! cs_get_loop_prop( 'is_paginated' ) || ! communityservice_tasks_will_display() ) {
			return;
		}
		$show_default_orderby    = 'menu_order' === apply_filters( 'communityservice_default_catalog_orderby', get_option( 'communityservice_default_catalog_orderby', 'menu_order' ) );
		$catalog_orderby_options = apply_filters( 'communityservice_catalog_orderby', array(
			'menu_order' => __( 'Default sorting', 'communityservice' ),
			'popularity' => __( 'Sort by popularity', 'communityservice' ),
			'rating'     => __( 'Sort by average rating', 'communityservice' ),
			'date'       => __( 'Sort by latest', 'communityservice' ),
			'price'      => __( 'Sort by price: low to high', 'communityservice' ),
			'price-desc' => __( 'Sort by price: high to low', 'communityservice' ),
		) );

		$default_orderby = cs_get_loop_prop( 'is_search' ) ? 'relevance' : apply_filters( 'communityservice_default_catalog_orderby', get_option( 'communityservice_default_catalog_orderby', '' ) );
		$orderby         = isset( $_GET['orderby'] ) ? cs_clean( wp_unslash( $_GET['orderby'] ) ) : $default_orderby; // WPCS: sanitization ok, input var ok, CSRF ok.

		if ( cs_get_loop_prop( 'is_search' ) ) {
			$catalog_orderby_options = array_merge( array( 'relevance' => __( 'Relevance', 'communityservice' ) ), $catalog_orderby_options );

			unset( $catalog_orderby_options['menu_order'] );
		}

		if ( ! $show_default_orderby ) {
			unset( $catalog_orderby_options['menu_order'] );
		}

		if ( 'no' === get_option( 'communityservice_enable_review_rating' ) ) {
			unset( $catalog_orderby_options['rating'] );
		}

		if ( ! array_key_exists( $orderby, $catalog_orderby_options ) ) {
			$orderby = current( array_keys( $catalog_orderby_options ) );
		}

		cs_get_template( 'loop/orderby.php', array(
			'catalog_orderby_options' => $catalog_orderby_options,
			'orderby'                 => $orderby,
			'show_default_orderby'    => $show_default_orderby,
		) );
	}
}

if ( ! function_exists( 'communityservice_pagination' ) ) {

	/**
	 * Output the pagination.
	 */
	function communityservice_pagination() {
		if ( ! true || ! communityservice_tasks_will_display() ) {
			return;
		}

		$args = array(
			'total'   => cs_get_loop_prop( 'total_pages' ),
			'current' => cs_get_loop_prop( 'current_page' ),
			'base'    => esc_url_raw( add_query_arg( 'task-page', '%#%', false ) ),
			'format'  => '?task-page=%#%',
		);

		if ( ! cs_get_loop_prop( 'is_shortcode' ) ) {
			$args['format'] = '';
			$args['base']   = esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) );
		}

		cs_get_template( 'loop/pagination.php', $args );
	}
}

/**
 * Single Task
 */

if ( ! function_exists( 'communityservice_show_task_images' ) ) {

	/**
	 * Output the task image before the single task summary.
	 */
	function communityservice_show_task_images() {
		cs_get_template( 'single-task/task-image.php' );
	}
}
if ( ! function_exists( 'communityservice_show_task_thumbnails' ) ) {

	/**
	 * Output the task thumbnails.
	 */
	function communityservice_show_task_thumbnails() {
		cs_get_template( 'single-task/task-thumbnails.php' );
	}
}

/**
 * Get HTML for a gallery image.
 *
 * Woocommerce_gallery_thumbnail_size, communityservice_gallery_image_size and communityservice_gallery_full_size accept name based image sizes, or an array of width/height values.
 *
 * @since 3.3.2
 * @param int  $attachment_id Attachment ID.
 * @param bool $main_image Is this the main image or a thumbnail?.
 * @return string
 */
function cs_get_gallery_image_html( $attachment_id, $main_image = false ) {
	$flexslider        = (bool) apply_filters( 'communityservice_single_task_flexslider_enabled', get_theme_support( 'wc-task-gallery-slider' ) );
	$gallery_thumbnail = cs_get_image_size( 'gallery_thumbnail' );
	$thumbnail_size    = apply_filters( 'communityservice_gallery_thumbnail_size', array( $gallery_thumbnail['width'], $gallery_thumbnail['height'] ) );
	$image_size        = apply_filters( 'communityservice_gallery_image_size', $flexslider || $main_image ? 'communityservice_single' : $thumbnail_size );
	$full_size         = apply_filters( 'communityservice_gallery_full_size', apply_filters( 'communityservice_task_thumbnails_large_size', 'full' ) );
	$thumbnail_src     = wp_get_attachment_image_src( $attachment_id, $thumbnail_size );
	$full_src          = wp_get_attachment_image_src( $attachment_id, $full_size );
	$image             = wp_get_attachment_image(
		$attachment_id,
		$image_size,
		false,
		apply_filters(
			'communityservice_gallery_image_html_attachment_image_params',
			array(
				'title'                   => _wp_specialchars( get_post_field( 'post_title', $attachment_id ), ENT_QUOTES, 'UTF-8', true ),
				'data-caption'            => _wp_specialchars( get_post_field( 'post_excerpt', $attachment_id ), ENT_QUOTES, 'UTF-8', true ),
				'data-src'                => esc_url( $full_src[0] ),
				'data-large_image'        => esc_url( $full_src[0] ),
				'data-large_image_width'  => esc_attr( $full_src[1] ),
				'data-large_image_height' => esc_attr( $full_src[2] ),
				'class'                   => esc_attr( $main_image ? 'wp-post-image' : '' ),
			),
			$attachment_id,
			$image_size,
			$main_image
		)
	);

	return '<div data-thumb="' . esc_url( $thumbnail_src[0] ) . '" class="communityservice-task-gallery__image"><a href="' . esc_url( $full_src[0] ) . '">' . $image . '</a></div>';
}

if ( ! function_exists( 'communityservice_output_task_data_tabs' ) ) {

	/**
	 * Output the task tabs.
	 */
	function communityservice_output_task_data_tabs() {
		cs_get_template( 'single-task/tabs/tabs.php' );
	}
}
if ( ! function_exists( 'communityservice_template_single_title' ) ) {

	/**
	 * Output the task title.
	 */
	function communityservice_template_single_title() {
		cs_get_template( 'single-task/title.php' );
	}
}
if ( ! function_exists( 'communityservice_template_single_rating' ) ) {

	/**
	 * Output the task rating.
	 */
	function communityservice_template_single_rating() {
		if ( post_type_supports( 'task', 'comments' ) ) {
			cs_get_template( 'single-task/rating.php' );
		}
	}
}
if ( ! function_exists( 'communityservice_template_single_price' ) ) {

	/**
	 * Output the task price.
	 */
	function communityservice_template_single_price() {
		cs_get_template( 'single-task/price.php' );
	}
}
if ( ! function_exists( 'communityservice_template_single_excerpt' ) ) {

	/**
	 * Output the task short description (excerpt).
	 */
	function communityservice_template_single_excerpt() {
		cs_get_template( 'single-task/short-description.php' );
	}
}
if ( ! function_exists( 'communityservice_template_single_meta' ) ) {

	/**
	 * Output the task meta.
	 */
	function communityservice_template_single_meta() {
		cs_get_template( 'single-task/meta.php' );
	}
}
if ( ! function_exists( 'communityservice_template_single_sharing' ) ) {

	/**
	 * Output the task sharing.
	 */
	function communityservice_template_single_sharing() {
		cs_get_template( 'single-task/share.php' );
	}
}
if ( ! function_exists( 'communityservice_show_task_sale_flash' ) ) {

	/**
	 * Output the task sale flash.
	 */
	function communityservice_show_task_sale_flash() {
		cs_get_template( 'single-task/sale-flash.php' );
	}
}

if ( ! function_exists( 'communityservice_template_single_add_to_cart' ) ) {

	/**
	 * Trigger the single task add to cart action.
	 */
	function communityservice_template_single_add_to_cart() {
		global $task;
		do_action( 'communityservice_' . $task->get_type() . '_add_to_cart' );
	}
}
if ( ! function_exists( 'communityservice_simple_add_to_cart' ) ) {

	/**
	 * Output the simple task add to cart area.
	 */
	function communityservice_simple_add_to_cart() {
		cs_get_template( 'single-task/add-to-cart/simple.php' );
	}
}
if ( ! function_exists( 'communityservice_grouped_add_to_cart' ) ) {

	/**
	 * Output the grouped task add to cart area.
	 */
	function communityservice_grouped_add_to_cart() {
		global $task;

		$tasks = array_filter( array_map( 'cs_get_task', $task->get_children() ), 'cs_tasks_array_filter_visible_grouped' );

		if ( $tasks ) {
			cs_get_template( 'single-task/add-to-cart/grouped.php', array(
				'grouped_task'    => $task,
				'grouped_tasks'   => $tasks,
				'quantites_required' => false,
			) );
		}
	}
}
if ( ! function_exists( 'communityservice_variable_add_to_cart' ) ) {

	/**
	 * Output the variable task add to cart area.
	 */
	function communityservice_variable_add_to_cart() {
		global $task;

		// Enqueue variation scripts.
		wp_enqueue_script( 'wc-add-to-cart-variation' );

		// Get Available variations?
		$get_variations = count( $task->get_children() ) <= apply_filters( 'communityservice_ajax_variation_threshold', 30, $task );

		// Load the template.
		cs_get_template( 'single-task/add-to-cart/variable.php', array(
			'available_variations' => $get_variations ? $task->get_available_variations() : false,
			'attributes'           => $task->get_variation_attributes(),
			'selected_attributes'  => $task->get_default_attributes(),
		) );
	}
}
if ( ! function_exists( 'communityservice_external_add_to_cart' ) ) {

	/**
	 * Output the external task add to cart area.
	 */
	function communityservice_external_add_to_cart() {
		global $task;

		if ( ! $task->add_to_cart_url() ) {
			return;
		}

		cs_get_template( 'single-task/add-to-cart/external.php', array(
			'task_url' => $task->add_to_cart_url(),
			'button_text' => $task->single_add_to_cart_text(),
		) );
	}
}

if ( ! function_exists( 'communityservice_quantity_input' ) ) {

	/**
	 * Output the quantity input for add to cart forms.
	 *
	 * @param  array           $args Args for the input.
	 * @param  CS_Task|null $task Task.
	 * @param  boolean         $echo Whether to return or echo|string.
	 *
	 * @return string
	 */
	function communityservice_quantity_input( $args = array(), $task = null, $echo = true ) {
		if ( is_null( $task ) ) {
			$task = $GLOBALS['task'];
		}

		$defaults = array(
			'input_id'     => uniqid( 'quantity_' ),
			'input_name'   => 'quantity',
			'input_value'  => '1',
			'max_value'    => apply_filters( 'communityservice_quantity_input_max', -1, $task ),
			'min_value'    => apply_filters( 'communityservice_quantity_input_min', 0, $task ),
			'step'         => apply_filters( 'communityservice_quantity_input_step', 1, $task ),
			'pattern'      => apply_filters( 'communityservice_quantity_input_pattern', has_filter( 'communityservice_stock_amount', 'intval' ) ? '[0-9]*' : '' ),
			'inputmode'    => apply_filters( 'communityservice_quantity_input_inputmode', has_filter( 'communityservice_stock_amount', 'intval' ) ? 'numeric' : '' ),
			'task_name' => $task ? $task->get_title() : '',
		);

		$args = apply_filters( 'communityservice_quantity_input_args', wp_parse_args( $args, $defaults ), $task );

		// Apply sanity to min/max args - min cannot be lower than 0.
		$args['min_value'] = max( $args['min_value'], 0 );
		$args['max_value'] = 0 < $args['max_value'] ? $args['max_value'] : '';

		// Max cannot be lower than min if defined.
		if ( '' !== $args['max_value'] && $args['max_value'] < $args['min_value'] ) {
			$args['max_value'] = $args['min_value'];
		}

		ob_start();

		cs_get_template( 'global/quantity-input.php', $args );

		if ( $echo ) {
			echo ob_get_clean(); // WPCS: XSS ok.
		} else {
			return ob_get_clean();
		}
	}
}

if ( ! function_exists( 'communityservice_task_description_tab' ) ) {

	/**
	 * Output the description tab content.
	 */
	function communityservice_task_description_tab() {
		cs_get_template( 'single-task/tabs/description.php' );
	}
}
if ( ! function_exists( 'communityservice_task_additional_information_tab' ) ) {

	/**
	 * Output the attributes tab content.
	 */
	function communityservice_task_additional_information_tab() {
		cs_get_template( 'single-task/tabs/additional-information.php' );
	}
}
if ( ! function_exists( 'communityservice_default_task_tabs' ) ) {

	/**
	 * Add default task tabs to task pages.
	 *
	 * @param array $tabs Array of tabs.
	 * @return array
	 */
	function communityservice_default_task_tabs( $tabs = array() ) {
		global $task, $post;

		// Description tab - shows task content.
		if ( $post->post_content ) {
			$tabs['description'] = array(
				'title'    => __( 'Description', 'communityservice' ),
				'priority' => 10,
				'callback' => 'communityservice_task_description_tab',
			);
		}

		// Additional information tab - shows attributes.
		if ( $task && ( $task->has_attributes() || apply_filters( 'cs_task_enable_dimensions_display', $task->has_weight() || $task->has_dimensions() ) ) ) {
			$tabs['additional_information'] = array(
				'title'    => __( 'Additional information', 'communityservice' ),
				'priority' => 20,
				'callback' => 'communityservice_task_additional_information_tab',
			);
		}

		// Reviews tab - shows comments.
		if ( comments_open() ) {
			$tabs['reviews'] = array(
				/* translators: %s: reviews count */
				'title'    => sprintf( __( 'Reviews (%d)', 'communityservice' ), $task->get_review_count() ),
				'priority' => 30,
				'callback' => 'comments_template',
			);
		}

		return $tabs;
	}
}

if ( ! function_exists( 'communityservice_sort_task_tabs' ) ) {

	/**
	 * Sort tabs by priority.
	 *
	 * @param array $tabs Array of tabs.
	 * @return array
	 */
	function communityservice_sort_task_tabs( $tabs = array() ) {

		// Make sure the $tabs parameter is an array.
		if ( ! is_array( $tabs ) ) {
			trigger_error( 'Function communityservice_sort_task_tabs() expects an array as the first parameter. Defaulting to empty array.' ); // @codingStandardsIgnoreLine
			$tabs = array();
		}

		// Re-order tabs by priority.
		if ( ! function_exists( '_sort_priority_callback' ) ) {
			/**
			 * Sort Priority Callback Function
			 *
			 * @param array $a Comparison A.
			 * @param array $b Comparison B.
			 * @return bool
			 */
			function _sort_priority_callback( $a, $b ) {
				if ( ! isset( $a['priority'], $b['priority'] ) || $a['priority'] === $b['priority'] ) {
					return 0;
				}
				return ( $a['priority'] < $b['priority'] ) ? -1 : 1;
			}
		}

		uasort( $tabs, '_sort_priority_callback' );

		return $tabs;
	}
}

if ( ! function_exists( 'communityservice_comments' ) ) {

	/**
	 * Output the Review comments template.
	 *
	 * @param WP_Comment $comment Comment object.
	 * @param array      $args Arguments.
	 * @param int        $depth Depth.
	 */
	function communityservice_comments( $comment, $args, $depth ) {
		$GLOBALS['comment'] = $comment; // WPCS: override ok.
		cs_get_template( 'single-task/review.php', array(
			'comment' => $comment,
			'args'    => $args,
			'depth'   => $depth,
		) );
	}
}

if ( ! function_exists( 'communityservice_review_display_gravatar' ) ) {
	/**
	 * Display the review authors gravatar
	 *
	 * @param array $comment WP_Comment.
	 * @return void
	 */
	function communityservice_review_display_gravatar( $comment ) {
		echo get_avatar( $comment, apply_filters( 'communityservice_review_gravatar_size', '60' ), '' );
	}
}

if ( ! function_exists( 'communityservice_review_display_rating' ) ) {
	/**
	 * Display the reviewers star rating
	 *
	 * @return void
	 */
	function communityservice_review_display_rating() {
		if ( post_type_supports( 'task', 'comments' ) ) {
			cs_get_template( 'single-task/review-rating.php' );
		}
	}
}

if ( ! function_exists( 'communityservice_review_display_meta' ) ) {
	/**
	 * Display the review authors meta (name, verified owner, review date)
	 *
	 * @return void
	 */
	function communityservice_review_display_meta() {
		cs_get_template( 'single-task/review-meta.php' );
	}
}

if ( ! function_exists( 'communityservice_review_display_comment_text' ) ) {

	/**
	 * Display the review content.
	 */
	function communityservice_review_display_comment_text() {
		echo '<div class="description">';
		comment_text();
		echo '</div>';
	}
}

if ( ! function_exists( 'communityservice_output_related_tasks' ) ) {

	/**
	 * Output the related tasks.
	 */
	function communityservice_output_related_tasks() {

		$args = array(
			'posts_per_page' => 4,
			'columns'        => 4,
			'orderby'        => 'rand', // @codingStandardsIgnoreLine.
		);

		communityservice_related_tasks( apply_filters( 'communityservice_output_related_tasks_args', $args ) );
	}
}

if ( ! function_exists( 'communityservice_related_tasks' ) ) {

	/**
	 * Output the related tasks.
	 *
	 * @param array $args Provided arguments.
	 */
	function communityservice_related_tasks( $args = array() ) {
		global $task;

		if ( ! $task ) {
			return;
		}

		$defaults = array(
			'posts_per_page' => 2,
			'columns'        => 2,
			'orderby'        => 'rand', // @codingStandardsIgnoreLine.
			'order'          => 'desc',
		);

		$args = wp_parse_args( $args, $defaults );

		// Get visible related tasks then sort them at random.
		$args['related_tasks'] = array_filter( array_map( 'cs_get_task', cs_get_related_tasks( $task->get_id(), $args['posts_per_page'], $task->get_upsell_ids() ) ), 'cs_tasks_array_filter_visible' );

		// Handle orderby.
		$args['related_tasks'] = cs_tasks_array_orderby( $args['related_tasks'], $args['orderby'], $args['order'] );

		// Set global loop values.
		cs_set_loop_prop( 'name', 'related' );
		cs_set_loop_prop( 'columns', apply_filters( 'communityservice_related_tasks_columns', $args['columns'] ) );

		cs_get_template( 'single-task/related.php', $args );
	}
}

if ( ! function_exists( 'communityservice_upsell_display' ) ) {

	/**
	 * Output task up sells.
	 *
	 * @param int    $limit (default: -1).
	 * @param int    $columns (default: 4).
	 * @param string $orderby Supported values - rand, title, ID, date, modified, menu_order, price.
	 * @param string $order Sort direction.
	 */
	function communityservice_upsell_display( $limit = '-1', $columns = 4, $orderby = 'rand', $order = 'desc' ) {
		global $task;

		if ( ! $task ) {
			return;
		}

		// Handle the legacy filter which controlled posts per page etc.
		$args = apply_filters( 'communityservice_upsell_display_args', array(
			'posts_per_page' => $limit,
			'orderby'        => $orderby,
			'columns'        => $columns,
		) );
		cs_set_loop_prop( 'name', 'up-sells' );
		cs_set_loop_prop( 'columns', apply_filters( 'communityservice_upsells_columns', isset( $args['columns'] ) ? $args['columns'] : $columns ) );

		$orderby = apply_filters( 'communityservice_upsells_orderby', isset( $args['orderby'] ) ? $args['orderby'] : $orderby );
		$limit   = apply_filters( 'communityservice_upsells_total', isset( $args['posts_per_page'] ) ? $args['posts_per_page'] : $limit );

		// Get visible upsells then sort them at random, then limit result set.
		$upsells = cs_tasks_array_orderby( array_filter( array_map( 'cs_get_task', $task->get_upsell_ids() ), 'cs_tasks_array_filter_visible' ), $orderby, $order );
		$upsells = $limit > 0 ? array_slice( $upsells, 0, $limit ) : $upsells;

		cs_get_template( 'single-task/up-sells.php', array(
			'upsells'        => $upsells,

			// Not used now, but used in previous version of up-sells.php.
			'posts_per_page' => $limit,
			'orderby'        => $orderby,
			'columns'        => $columns,
		) );
	}
}

/** Cart */

if ( ! function_exists( 'communityservice_shipping_calculator' ) ) {

	/**
	 * Output the cart shipping calculator.
	 *
	 * @param string $button_text Text for the shipping calculation toggle.
	 */
	function communityservice_shipping_calculator( $button_text = '' ) {
		if ( 'no' === get_option( 'communityservice_enable_shipping_calc' ) || ! WC()->cart->needs_shipping() ) {
			return;
		}
		wp_enqueue_script( 'wc-country-select' );
		cs_get_template( 'cart/shipping-calculator.php', array(
			'button_text' => $button_text,
		) );
	}
}

if ( ! function_exists( 'communityservice_cart_totals' ) ) {

	/**
	 * Output the cart totals.
	 */
	function communityservice_cart_totals() {
		if ( is_checkout() ) {
			return;
		}
		cs_get_template( 'cart/cart-totals.php' );
	}
}

if ( ! function_exists( 'communityservice_cross_sell_display' ) ) {

	/**
	 * Output the cart cross-sells.
	 *
	 * @param  int    $limit (default: 2).
	 * @param  int    $columns (default: 2).
	 * @param  string $orderby (default: 'rand').
	 * @param  string $order (default: 'desc').
	 */
	function communityservice_cross_sell_display( $limit = 2, $columns = 2, $orderby = 'rand', $order = 'desc' ) {
		if ( is_checkout() ) {
			return;
		}
		// Get visible cross sells then sort them at random.
		$cross_sells = array_filter( array_map( 'cs_get_task', WC()->cart->get_cross_sells() ), 'cs_tasks_array_filter_visible' );

		cs_set_loop_prop( 'name', 'cross-sells' );
		cs_set_loop_prop( 'columns', apply_filters( 'communityservice_cross_sells_columns', $columns ) );

		// Handle orderby and limit results.
		$orderby     = apply_filters( 'communityservice_cross_sells_orderby', $orderby );
		$order       = apply_filters( 'communityservice_cross_sells_order', $order );
		$cross_sells = cs_tasks_array_orderby( $cross_sells, $orderby, $order );
		$limit       = apply_filters( 'communityservice_cross_sells_total', $limit );
		$cross_sells = $limit > 0 ? array_slice( $cross_sells, 0, $limit ) : $cross_sells;

		cs_get_template( 'cart/cross-sells.php', array(
			'cross_sells'    => $cross_sells,

			// Not used now, but used in previous version of up-sells.php.
			'posts_per_page' => $limit,
			'orderby'        => $orderby,
			'columns'        => $columns,
		) );
	}
}

if ( ! function_exists( 'communityservice_button_proceed_to_checkout' ) ) {

	/**
	 * Output the proceed to checkout button.
	 */
	function communityservice_button_proceed_to_checkout() {
		cs_get_template( 'cart/proceed-to-checkout-button.php' );
	}
}

if ( ! function_exists( 'communityservice_widget_shopping_cart_button_view_cart' ) ) {

	/**
	 * Output the view cart button.
	 */
	function communityservice_widget_shopping_cart_button_view_cart() {
		echo '<a href="' . esc_url( cs_get_cart_url() ) . '" class="button wc-forward">' . esc_html__( 'View cart', 'communityservice' ) . '</a>';
	}
}

if ( ! function_exists( 'communityservice_widget_shopping_cart_proceed_to_checkout' ) ) {

	/**
	 * Output the proceed to checkout button.
	 */
	function communityservice_widget_shopping_cart_proceed_to_checkout() {
		echo '<a href="' . esc_url( cs_get_checkout_url() ) . '" class="button checkout wc-forward">' . esc_html__( 'Checkout', 'communityservice' ) . '</a>';
	}
}

/** Mini-Cart */

if ( ! function_exists( 'communityservice_mini_cart' ) ) {

	/**
	 * Output the Mini-cart - used by cart widget.
	 *
	 * @param array $args Arguments.
	 */
	function communityservice_mini_cart( $args = array() ) {

		$defaults = array(
			'list_class' => '',
		);

		$args = wp_parse_args( $args, $defaults );

		cs_get_template( 'cart/mini-cart.php', $args );
	}
}

/** Login */

if ( ! function_exists( 'communityservice_login_form' ) ) {

	/**
	 * Output the WooCommerce Login Form.
	 *
	 * @param array $args Arguments.
	 */
	function communityservice_login_form( $args = array() ) {

		$defaults = array(
			'message'  => '',
			'redirect' => '',
			'hidden'   => false,
		);

		$args = wp_parse_args( $args, $defaults );

		cs_get_template( 'global/form-login.php', $args );
	}
}

if ( ! function_exists( 'communityservice_checkout_login_form' ) ) {

	/**
	 * Output the WooCommerce Checkout Login Form.
	 */
	function communityservice_checkout_login_form() {
		cs_get_template( 'checkout/form-login.php', array(
			'checkout' => WC()->checkout(),
		) );
	}
}

if ( ! function_exists( 'communityservice_breadcrumb' ) ) {

	/**
	 * Output the WooCommerce Breadcrumb.
	 *
	 * @param array $args Arguments.
	 */
	function communityservice_breadcrumb( $args = array() ) {
		$args = wp_parse_args( $args, apply_filters( 'communityservice_breadcrumb_defaults', array(
			'delimiter'   => '&nbsp;&#47;&nbsp;',
			'wrap_before' => '<nav class="communityservice-breadcrumb">',
			'wrap_after'  => '</nav>',
			'before'      => '',
			'after'       => '',
			'home'        => _x( 'Home', 'breadcrumb', 'communityservice' ),
		) ) );

		$breadcrumbs = new CS_Breadcrumb();

		if ( ! empty( $args['home'] ) ) {
			$breadcrumbs->add_crumb( $args['home'], apply_filters( 'communityservice_breadcrumb_home_url', home_url() ) );
		}

		$args['breadcrumb'] = $breadcrumbs->generate();

		/**
		 * WooCommerce Breadcrumb hook
		 *
		 * @hooked CS_Structured_Data::generate_breadcrumblist_data() - 10
		 */
		do_action( 'communityservice_breadcrumb', $breadcrumbs, $args );

		cs_get_template( 'global/breadcrumb.php', $args );
	}
}

if ( ! function_exists( 'communityservice_order_review' ) ) {

	/**
	 * Output the Order review table for the checkout.
	 *
	 * @param bool $deprecated Deprecated param.
	 */
	function communityservice_order_review( $deprecated = false ) {
		cs_get_template( 'checkout/review-order.php', array(
			'checkout' => WC()->checkout(),
		) );
	}
}

if ( ! function_exists( 'communityservice_checkout_payment' ) ) {

	/**
	 * Output the Payment Methods on the checkout.
	 */
	function communityservice_checkout_payment() {
		if ( WC()->cart->needs_payment() ) {
			$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
			WC()->payment_gateways()->set_current_gateway( $available_gateways );
		} else {
			$available_gateways = array();
		}

		cs_get_template( 'checkout/payment.php', array(
			'checkout'           => WC()->checkout(),
			'available_gateways' => $available_gateways,
			'order_button_text'  => apply_filters( 'communityservice_order_button_text', __( 'Place order', 'communityservice' ) ),
		) );
	}
}

if ( ! function_exists( 'communityservice_checkout_coupon_form' ) ) {

	/**
	 * Output the Coupon form for the checkout.
	 */
	function communityservice_checkout_coupon_form() {
		cs_get_template( 'checkout/form-coupon.php', array(
			'checkout' => WC()->checkout(),
		) );
	}
}

if ( ! function_exists( 'communityservice_tasks_will_display' ) ) {

	/**
	 * Check if we will be showing tasks or not (and not sub-categories only).
	 *
	 * @return bool
	 */
	function communityservice_tasks_will_display() {
		$display_type = communityservice_get_loop_display_mode();

		return 0 < cs_get_loop_prop( 'total', 0 ) && 'subcategories' !== $display_type;
	}
}

if ( ! function_exists( 'communityservice_get_loop_display_mode' ) ) {

	/**
	 * See what is going to display in the loop.
	 *
	 * @since 3.3.0
	 * @return string Either tasks, subcategories, or both, based on current page.
	 */
	function communityservice_get_loop_display_mode() {
		// Only return tasks when filtering things.
		if ( 1 < cs_get_loop_prop( 'current_page' ) || cs_get_loop_prop( 'is_search' ) || cs_get_loop_prop( 'is_filtered' ) ) {
			return 'tasks';
		}

		$parent_id    = 0;
		$display_type = '';

		// Ensure valid value.
		if ( '' === $display_type || ! in_array( $display_type, array( 'tasks', 'subcategories', 'both' ), true ) ) {
			$display_type = 'tasks';
		}

		// If we're showing categories, ensure we actually have something to show.
		if ( in_array( $display_type, array( 'subcategories', 'both' ), true ) ) {
			$subcategories = communityservice_get_task_subcategories( $parent_id );

			if ( empty( $subcategories ) ) {
				$display_type = 'tasks';
			}
		}

		return $display_type;
	}
}

if ( ! function_exists( 'communityservice_maybe_show_task_subcategories' ) ) {

	/**
	 * Maybe display categories before, or instead of, a task loop.
	 *
	 * @since 3.3.0
	 * @param string $loop_html HTML.
	 * @return string
	 */
	function communityservice_maybe_show_task_subcategories( $loop_html = '' ) {
		if ( cs_get_loop_prop( 'is_shortcode' ) && ! CS_Template_Loader::in_content_filter() ) {
			return $loop_html;
		}

		$display_type = communityservice_get_loop_display_mode();

		// If displaying categories, append to the loop.
		if ( 'subcategories' === $display_type || 'both' === $display_type ) {
			ob_start();
			communityservice_output_task_categories( array(
				'parent_id' => is_task_category() ? get_queried_object_id() : 0,
			) );
			$loop_html .= ob_get_clean();

			if ( 'subcategories' === $display_type ) {
				cs_set_loop_prop( 'total', 0 );

				// This removes pagination and tasks from display for themes not using cs_get_loop_prop in their task loops.  @todo Remove in future major version.
				global $wp_query;

				if ( $wp_query->is_main_query() ) {
					$wp_query->post_count    = 0;
					$wp_query->max_num_pages = 0;
				}
			}
		}

		return $loop_html;
	}
}

if ( ! function_exists( 'communityservice_task_subcategories' ) ) {
	/**
	 * This is a legacy function which used to check if we needed to display subcats and then output them. It was called by templates.
	 *
	 * From 3.3 onwards this is all handled via hooks and the communityservice_maybe_show_task_subcategories function.
	 *
	 * Since some templates have not updated compatibility, to avoid showing incorrect categories this function has been deprecated and will
	 * return nothing. Replace usage with communityservice_output_task_categories to render the category list manually.
	 *
	 * This is a legacy function which also checks if things should display.
	 * Themes no longer need to call these functions. It's all done via hooks.
	 *
	 * @deprecated 3.3.1 @todo Add a notice in a future version.
	 * @param array $args Arguments.
	 * @return null|boolean
	 */
	function communityservice_task_subcategories( $args = array() ) {
		$defaults = array(
			'before'        => '',
			'after'         => '',
			'force_display' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['force_display'] ) {
			// We can still render if display is forced.
			communityservice_output_task_categories( array(
				'before'    => $args['before'],
				'after'     => $args['after'],
				'parent_id' => is_task_category() ? get_queried_object_id() : 0,
			) );
			return true;
		} else {
			// Output nothing. communityservice_maybe_show_task_subcategories will handle the output of cats.
			$display_type = communityservice_get_loop_display_mode();

			if ( 'subcategories' === $display_type ) {
				// This removes pagination and tasks from display for themes not using cs_get_loop_prop in their task loops. @todo Remove in future major version.
				global $wp_query;

				if ( $wp_query->is_main_query() ) {
					$wp_query->post_count    = 0;
					$wp_query->max_num_pages = 0;
				}
			}

			return 'subcategories' === $display_type || 'both' === $display_type;
		}
	}
}

if ( ! function_exists( 'communityservice_output_task_categories' ) ) {
	/**
	 * Display task sub categories as thumbnails.
	 *
	 * This is a replacement for communityservice_task_subcategories which also does some logic
	 * based on the loop. This function however just outputs when called.
	 *
	 * @since 3.3.1
	 * @param array $args Arguments.
	 * @return boolean
	 */
	function communityservice_output_task_categories( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'before'    => apply_filters( 'communityservice_before_output_task_categories', '' ),
			'after'     => apply_filters( 'communityservice_after_output_task_categories', '' ),
			'parent_id' => 0,
		) );

		$task_categories = communityservice_get_task_subcategories( $args['parent_id'] );

		if ( ! $task_categories ) {
			return false;
		}

		echo $args['before']; // WPCS: XSS ok.

		foreach ( $task_categories as $category ) {
			cs_get_template( 'content-task_cat.php', array(
				'category' => $category,
			) );
		}

		echo $args['after']; // WPCS: XSS ok.

		return true;
	}
}

if ( ! function_exists( 'communityservice_get_task_subcategories' ) ) {
	/**
	 * Get (and cache) task subcategories.
	 *
	 * @param int $parent_id Get subcategories of this ID.
	 * @return array
	 */
	function communityservice_get_task_subcategories( $parent_id = 0 ) {
		$parent_id          = absint( $parent_id );
		$task_categories = wp_cache_get( 'task-category-hierarchy-' . $parent_id, 'task_cat' );

		if ( false === $task_categories ) {
			// NOTE: using child_of instead of parent - this is not ideal but due to a WP bug ( https://core.trac.wordpress.org/ticket/15626 ) pad_counts won't work.
			$task_categories = get_categories( apply_filters( 'communityservice_task_subcategories_args', array(
				'parent'       => $parent_id,
				'menu_order'   => 'ASC',
				'hide_empty'   => 0,
				'hierarchical' => 1,
				'taxonomy'     => 'task_cat',
				'pad_counts'   => 1,
			) ) );

			wp_cache_set( 'task-category-hierarchy-' . $parent_id, $task_categories, 'task_cat' );
		}

		if ( apply_filters( 'communityservice_task_subcategories_hide_empty', true ) ) {
			$task_categories = wp_list_filter( $task_categories, array( 'count' => 0 ), 'NOT' );
		}

		return $task_categories;
	}
}

if ( ! function_exists( 'communityservice_subcategory_thumbnail' ) ) {

	/**
	 * Show subcategory thumbnails.
	 *
	 * @param mixed $category Category.
	 */
	function communityservice_subcategory_thumbnail( $category ) {
		$small_thumbnail_size = apply_filters( 'subcategory_archive_thumbnail_size', 'communityservice_thumbnail' );
		$dimensions           = cs_get_image_size( $small_thumbnail_size );
		$thumbnail_id         = get_communityservice_term_meta( $category->term_id, 'thumbnail_id', true );

		if ( $thumbnail_id ) {
			$image        = wp_get_attachment_image_src( $thumbnail_id, $small_thumbnail_size );
			$image        = $image[0];
			$image_srcset = function_exists( 'wp_get_attachment_image_srcset' ) ? wp_get_attachment_image_srcset( $thumbnail_id, $small_thumbnail_size ) : false;
			$image_sizes  = function_exists( 'wp_get_attachment_image_sizes' ) ? wp_get_attachment_image_sizes( $thumbnail_id, $small_thumbnail_size ) : false;
		} else {
			$image        = cs_placeholder_img_src();
			$image_srcset = false;
			$image_sizes  = false;
		}

		if ( $image ) {
			// Prevent esc_url from breaking spaces in urls for image embeds.
			// Ref: https://core.trac.wordpress.org/ticket/23605.
			$image = str_replace( ' ', '%20', $image );

			// Add responsive image markup if available.
			if ( $image_srcset && $image_sizes ) {
				echo '<img src="' . esc_url( $image ) . '" alt="' . esc_attr( $category->name ) . '" width="' . esc_attr( $dimensions['width'] ) . '" height="' . esc_attr( $dimensions['height'] ) . '" srcset="' . esc_attr( $image_srcset ) . '" sizes="' . esc_attr( $image_sizes ) . '" />';
			} else {
				echo '<img src="' . esc_url( $image ) . '" alt="' . esc_attr( $category->name ) . '" width="' . esc_attr( $dimensions['width'] ) . '" height="' . esc_attr( $dimensions['height'] ) . '" />';
			}
		}
	}
}

if ( ! function_exists( 'communityservice_order_details_table' ) ) {

	/**
	 * Displays order details in a table.
	 *
	 * @param mixed $order_id Order ID.
	 */
	function communityservice_order_details_table( $order_id ) {
		if ( ! $order_id ) {
			return;
		}

		cs_get_template( 'order/order-details.php', array(
			'order_id' => $order_id,
		) );
	}
}

if ( ! function_exists( 'communityservice_order_downloads_table' ) ) {

	/**
	 * Displays order downloads in a table.
	 *
	 * @since 3.2.0
	 * @param array $downloads Downloads.
	 */
	function communityservice_order_downloads_table( $downloads ) {
		if ( ! $downloads ) {
			return;
		}
		cs_get_template( 'order/order-downloads.php', array(
			'downloads' => $downloads,
		) );
	}
}

if ( ! function_exists( 'communityservice_order_again_button' ) ) {

	/**
	 * Display an 'order again' button on the view order page.
	 *
	 * @param object $order Order.
	 */
	function communityservice_order_again_button( $order ) {
		if ( ! $order || ! $order->has_status( apply_filters( 'communityservice_valid_order_statuses_for_order_again', array( 'completed' ) ) ) || ! is_user_logged_in() ) {
			return;
		}

		cs_get_template( 'order/order-again.php', array(
			'order'           => $order,
			'order_again_url' => wp_nonce_url( add_query_arg( 'order_again', $order->get_id(), cs_get_cart_url() ), 'communityservice-order_again' ),
		) );
	}
}

/** Forms */

if ( ! function_exists( 'communityservice_form_field' ) ) {

	/**
	 * Outputs a checkout/address form field.
	 *
	 * @param string $key Key.
	 * @param mixed  $args Arguments.
	 * @param string $value (default: null).
	 * @return string
	 */
	function communityservice_form_field( $key, $args, $value = null ) {
		$defaults = array(
			'type'              => 'text',
			'label'             => '',
			'description'       => '',
			'placeholder'       => '',
			'maxlength'         => false,
			'required'          => false,
			'autocomplete'      => false,
			'id'                => $key,
			'class'             => array(),
			'label_class'       => array(),
			'input_class'       => array(),
			'return'            => false,
			'options'           => array(),
			'custom_attributes' => array(),
			'validate'          => array(),
			'default'           => '',
			'autofocus'         => '',
			'priority'          => '',
		);

		$args = wp_parse_args( $args, $defaults );
		$args = apply_filters( 'communityservice_form_field_args', $args, $key, $value );

		if ( $args['required'] ) {
			$args['class'][] = 'validate-required';
			$required        = '&nbsp;<abbr class="required" title="' . esc_attr__( 'required', 'communityservice' ) . '">*</abbr>';
		} else {
			$required = '&nbsp;<span class="optional">(' . esc_html__( 'optional', 'communityservice' ) . ')</span>';
		}

		if ( is_string( $args['label_class'] ) ) {
			$args['label_class'] = array( $args['label_class'] );
		}

		if ( is_null( $value ) ) {
			$value = $args['default'];
		}

		// Custom attribute handling.
		$custom_attributes         = array();
		$args['custom_attributes'] = array_filter( (array) $args['custom_attributes'], 'strlen' );

		if ( $args['maxlength'] ) {
			$args['custom_attributes']['maxlength'] = absint( $args['maxlength'] );
		}

		if ( ! empty( $args['autocomplete'] ) ) {
			$args['custom_attributes']['autocomplete'] = $args['autocomplete'];
		}

		if ( true === $args['autofocus'] ) {
			$args['custom_attributes']['autofocus'] = 'autofocus';
		}

		if ( $args['description'] ) {
			$args['custom_attributes']['aria-describedby'] = $args['id'] . '-description';
		}

		if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
			foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		if ( ! empty( $args['validate'] ) ) {
			foreach ( $args['validate'] as $validate ) {
				$args['class'][] = 'validate-' . $validate;
			}
		}

		$field           = '';
		$label_id        = $args['id'];
		$sort            = $args['priority'] ? $args['priority'] : '';
		$field_container = '<p class="form-row %1$s" id="%2$s" data-priority="' . esc_attr( $sort ) . '">%3$s</p>';

		switch ( $args['type'] ) {
			case 'country':
				$countries = 'shipping_country' === $key ? WC()->countries->get_shipping_countries() : WC()->countries->get_allowed_countries();

				if ( 1 === count( $countries ) ) {

					$field .= '<strong>' . current( array_values( $countries ) ) . '</strong>';

					$field .= '<input type="hidden" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="' . current( array_keys( $countries ) ) . '" ' . implode( ' ', $custom_attributes ) . ' class="country_to_state" readonly="readonly" />';

				} else {

					$field = '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="country_to_state country_select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . '><option value="">' . esc_html__( 'Select a country&hellip;', 'communityservice' ) . '</option>';

					foreach ( $countries as $ckey => $cvalue ) {
						$field .= '<option value="' . esc_attr( $ckey ) . '" ' . selected( $value, $ckey, false ) . '>' . $cvalue . '</option>';
					}

					$field .= '</select>';

					$field .= '<noscript><button type="submit" name="communityservice_checkout_update_totals" value="' . esc_attr__( 'Update country', 'communityservice' ) . '">' . esc_html__( 'Update country', 'communityservice' ) . '</button></noscript>';

				}

				break;
			case 'state':
				/* Get country this state field is representing */
				$for_country = isset( $args['country'] ) ? $args['country'] : WC()->checkout->get_value( 'billing_state' === $key ? 'billing_country' : 'shipping_country' );
				$states      = WC()->countries->get_states( $for_country );

				if ( is_array( $states ) && empty( $states ) ) {

					$field_container = '<p class="form-row %1$s" id="%2$s" style="display: none">%3$s</p>';

					$field .= '<input type="hidden" class="hidden" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="" ' . implode( ' ', $custom_attributes ) . ' placeholder="' . esc_attr( $args['placeholder'] ) . '" readonly="readonly" />';

				} elseif ( ! is_null( $for_country ) && is_array( $states ) ) {

					$field .= '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="state_select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . ' data-placeholder="' . esc_attr( $args['placeholder'] ) . '">
						<option value="">' . esc_html__( 'Select an option&hellip;', 'communityservice' ) . '</option>';

					foreach ( $states as $ckey => $cvalue ) {
						$field .= '<option value="' . esc_attr( $ckey ) . '" ' . selected( $value, $ckey, false ) . '>' . $cvalue . '</option>';
					}

					$field .= '</select>';

				} else {

					$field .= '<input type="text" class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" value="' . esc_attr( $value ) . '"  placeholder="' . esc_attr( $args['placeholder'] ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" ' . implode( ' ', $custom_attributes ) . ' />';

				}

				break;
			case 'textarea':
				$field .= '<textarea name="' . esc_attr( $key ) . '" class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" id="' . esc_attr( $args['id'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" ' . ( empty( $args['custom_attributes']['rows'] ) ? ' rows="2"' : '' ) . ( empty( $args['custom_attributes']['cols'] ) ? ' cols="5"' : '' ) . implode( ' ', $custom_attributes ) . '>' . esc_textarea( $value ) . '</textarea>';

				break;
			case 'checkbox':
				$field = '<label class="checkbox ' . implode( ' ', $args['label_class'] ) . '" ' . implode( ' ', $custom_attributes ) . '>
						<input type="' . esc_attr( $args['type'] ) . '" class="input-checkbox ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="1" ' . checked( $value, 1, false ) . ' /> ' . $args['label'] . $required . '</label>';

				break;
			case 'text':
			case 'password':
			case 'datetime':
			case 'datetime-local':
			case 'date':
			case 'month':
			case 'time':
			case 'week':
			case 'number':
			case 'email':
			case 'url':
			case 'tel':
				$field .= '<input type="' . esc_attr( $args['type'] ) . '" class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '"  value="' . esc_attr( $value ) . '" ' . implode( ' ', $custom_attributes ) . ' />';

				break;
			case 'select':
				$field   = '';
				$options = '';

				if ( ! empty( $args['options'] ) ) {
					foreach ( $args['options'] as $option_key => $option_text ) {
						if ( '' === $option_key ) {
							// If we have a blank option, select2 needs a placeholder.
							if ( empty( $args['placeholder'] ) ) {
								$args['placeholder'] = $option_text ? $option_text : __( 'Choose an option', 'communityservice' );
							}
							$custom_attributes[] = 'data-allow_clear="true"';
						}
						$options .= '<option value="' . esc_attr( $option_key ) . '" ' . selected( $value, $option_key, false ) . '>' . esc_attr( $option_text ) . '</option>';
					}

					$field .= '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . ' data-placeholder="' . esc_attr( $args['placeholder'] ) . '">
							' . $options . '
						</select>';
				}

				break;
			case 'radio':
				$label_id .= '_' . current( array_keys( $args['options'] ) );

				if ( ! empty( $args['options'] ) ) {
					foreach ( $args['options'] as $option_key => $option_text ) {
						$field .= '<input type="radio" class="input-radio ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" value="' . esc_attr( $option_key ) . '" name="' . esc_attr( $key ) . '" ' . implode( ' ', $custom_attributes ) . ' id="' . esc_attr( $args['id'] ) . '_' . esc_attr( $option_key ) . '"' . checked( $value, $option_key, false ) . ' />';
						$field .= '<label for="' . esc_attr( $args['id'] ) . '_' . esc_attr( $option_key ) . '" class="radio ' . implode( ' ', $args['label_class'] ) . '">' . $option_text . '</label>';
					}
				}

				break;
		}

		if ( ! empty( $field ) ) {
			$field_html = '';

			if ( $args['label'] && 'checkbox' !== $args['type'] ) {
				$field_html .= '<label for="' . esc_attr( $label_id ) . '" class="' . esc_attr( implode( ' ', $args['label_class'] ) ) . '">' . $args['label'] . $required . '</label>';
			}

			$field_html .= '<span class="communityservice-input-wrapper">' . $field;

			if ( $args['description'] ) {
				$field_html .= '<span class="description" id="' . esc_attr( $args['id'] ) . '-description" aria-hidden="true">' . wp_kses_post( $args['description'] ) . '</span>';
			}

			$field_html .= '</span>';

			$container_class = esc_attr( implode( ' ', $args['class'] ) );
			$container_id    = esc_attr( $args['id'] ) . '_field';
			$field           = sprintf( $field_container, $container_class, $container_id, $field_html );
		}

		/**
		 * Filter by type.
		 */
		$field = apply_filters( 'communityservice_form_field_' . $args['type'], $field, $key, $args, $value );

		/**
		 * General filter on form fields.
		 *
		 * @since 3.4.0
		 */
		$field = apply_filters( 'communityservice_form_field', $field, $key, $args, $value );

		if ( $args['return'] ) {
			return $field;
		} else {
			echo $field; // WPCS: XSS ok.
		}
	}
}

if ( ! function_exists( 'get_task_search_form' ) ) {

	/**
	 * Display task search form.
	 *
	 * Will first attempt to locate the task-searchform.php file in either the child or.
	 * the parent, then load it. If it doesn't exist, then the default search form.
	 * will be displayed.
	 *
	 * The default searchform uses html5.
	 *
	 * @param bool $echo (default: true).
	 * @return string
	 */
	function get_task_search_form( $echo = true ) {
		global $task_search_form_index;

		ob_start();

		if ( empty( $task_search_form_index ) ) {
			$task_search_form_index = 0;
		}

		do_action( 'pre_get_task_search_form' );

		cs_get_template( 'task-searchform.php', array(
			'index' => $task_search_form_index++,
		) );

		$form = apply_filters( 'get_task_search_form', ob_get_clean() );

		if ( ! $echo ) {
			return $form;
		}

		echo $form; // WPCS: XSS ok.
	}
}

if ( ! function_exists( 'communityservice_output_auth_header' ) ) {

	/**
	 * Output the Auth header.
	 */
	function communityservice_output_auth_header() {
		cs_get_template( 'auth/header.php' );
	}
}

if ( ! function_exists( 'communityservice_output_auth_footer' ) ) {

	/**
	 * Output the Auth footer.
	 */
	function communityservice_output_auth_footer() {
		cs_get_template( 'auth/footer.php' );
	}
}

if ( ! function_exists( 'communityservice_single_variation' ) ) {

	/**
	 * Output placeholders for the single variation.
	 */
	function communityservice_single_variation() {
		echo '<div class="communityservice-variation single_variation"></div>';
	}
}

if ( ! function_exists( 'communityservice_single_variation_add_to_cart_button' ) ) {

	/**
	 * Output the add to cart button for variations.
	 */
	function communityservice_single_variation_add_to_cart_button() {
		cs_get_template( 'single-task/add-to-cart/variation-add-to-cart-button.php' );
	}
}

if ( ! function_exists( 'cs_dropdown_variation_attribute_options' ) ) {

	/**
	 * Output a list of variation attributes for use in the cart forms.
	 *
	 * @param array $args Arguments.
	 * @since 2.4.0
	 */
	function cs_dropdown_variation_attribute_options( $args = array() ) {
		$args = wp_parse_args( apply_filters( 'communityservice_dropdown_variation_attribute_options_args', $args ), array(
			'options'          => false,
			'attribute'        => false,
			'task'          => false,
			'selected'         => false,
			'name'             => '',
			'id'               => '',
			'class'            => '',
			'show_option_none' => __( 'Choose an option', 'communityservice' ),
		) );

		// Get selected value.
		if ( false === $args['selected'] && $args['attribute'] && $args['task'] instanceof CS_Task ) {
			$selected_key     = 'attribute_' . sanitize_title( $args['attribute'] );
			$args['selected'] = isset( $_REQUEST[ $selected_key ] ) ? cs_clean( wp_unslash( $_REQUEST[ $selected_key ] ) ) : $args['task']->get_variation_default_attribute( $args['attribute'] ); // WPCS: input var ok, CSRF ok, sanitization ok.
		}

		$options               = $args['options'];
		$task               = $args['task'];
		$attribute             = $args['attribute'];
		$name                  = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
		$id                    = $args['id'] ? $args['id'] : sanitize_title( $attribute );
		$class                 = $args['class'];
		$show_option_none      = (bool) $args['show_option_none'];
		$show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __( 'Choose an option', 'communityservice' ); // We'll do our best to hide the placeholder, but we'll need to show something when resetting options.

		if ( empty( $options ) && ! empty( $task ) && ! empty( $attribute ) ) {
			$attributes = $task->get_variation_attributes();
			$options    = $attributes[ $attribute ];
		}

		$html  = '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';
		$html .= '<option value="">' . esc_html( $show_option_none_text ) . '</option>';

		if ( ! empty( $options ) ) {
			if ( $task && taxonomy_exists( $attribute ) ) {
				// Get terms if this is a taxonomy - ordered. We need the names too.
				$terms = cs_get_task_terms( $task->get_id(), $attribute, array(
					'fields' => 'all',
				) );

				foreach ( $terms as $term ) {
					if ( in_array( $term->slug, $options, true ) ) {
						$html .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'communityservice_variation_option_name', $term->name ) ) . '</option>';
					}
				}
			} else {
				foreach ( $options as $option ) {
					// This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
					$selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
					$html    .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( apply_filters( 'communityservice_variation_option_name', $option ) ) . '</option>';
				}
			}
		}

		$html .= '</select>';

		echo apply_filters( 'communityservice_dropdown_variation_attribute_options_html', $html, $args ); // WPCS: XSS ok.
	}
}

if ( ! function_exists( 'communityservice_account_content' ) ) {

	/**
	 * My Account content output.
	 */
	function communityservice_account_content() {
		global $wp;

		if ( ! empty( $wp->query_vars ) ) {
			foreach ( $wp->query_vars as $key => $value ) {
				// Ignore pagename param.
				if ( 'pagename' === $key ) {
					continue;
				}

				if ( has_action( 'communityservice_account_' . $key . '_endpoint' ) ) {
					do_action( 'communityservice_account_' . $key . '_endpoint', $value );
					return;
				}
			}
		}

		// No endpoint found? Default to dashboard.
		cs_get_template( 'myaccount/form-edit-account.php', array(
			'user' => get_user_by( 'id', get_current_user_id() ),
		) );
	}
}

if ( ! function_exists( 'communityservice_account_navigation' ) ) {

	/**
	 * My Account navigation template.
	 */
	function communityservice_account_navigation() {
		cs_get_template( 'myaccount/navigation.php' );
	}
}

if ( ! function_exists( 'communityservice_account_orders' ) ) {

	/**
	 * My Account > Orders template.
	 *
	 * @param int $current_page Current page number.
	 */
	function communityservice_account_orders( $current_page ) {
		$current_page    = empty( $current_page ) ? 1 : absint( $current_page );
		$customer_orders = cs_get_orders( apply_filters( 'communityservice_my_account_my_orders_query', array(
			'customer' => get_current_user_id(),
			'page'     => $current_page,
			'paginate' => true,
		) ) );

		cs_get_template(
			'myaccount/orders.php',
			array(
				'current_page'    => absint( $current_page ),
				'customer_orders' => $customer_orders,
				'has_orders'      => 0 < $customer_orders->total,
			)
		);
	}
}

if ( ! function_exists( 'communityservice_account_view_order' ) ) {

	/**
	 * My Account > View order template.
	 *
	 * @param int $order_id Order ID.
	 */
	function communityservice_account_view_order( $order_id ) {
		CS_Shortcode_My_Account::view_order( absint( $order_id ) );
	}
}

if ( ! function_exists( 'communityservice_account_downloads' ) ) {

	/**
	 * My Account > Downloads template.
	 */
	function communityservice_account_downloads() {
		cs_get_template( 'myaccount/downloads.php' );
	}
}

if ( ! function_exists( 'communityservice_account_edit_address' ) ) {

	/**
	 * My Account > Edit address template.
	 *
	 * @param string $type Address type.
	 */
	function communityservice_account_edit_address( $type ) {
		$type = cs_edit_address_i18n( sanitize_title( $type ), true );

		CS_Shortcode_My_Account::edit_address( $type );
	}
}

if ( ! function_exists( 'communityservice_account_payment_methods' ) ) {

	/**
	 * My Account > Downloads template.
	 */
	function communityservice_account_payment_methods() {
		cs_get_template( 'myaccount/payment-methods.php' );
	}
}

if ( ! function_exists( 'communityservice_account_add_payment_method' ) ) {

	/**
	 * My Account > Add payment method template.
	 */
	function communityservice_account_add_payment_method() {
		CS_Shortcode_My_Account::add_payment_method();
	}
}
add_action( 'communityservice_account_page_endpoint', 'communityservice_account_edit_account' );
if ( ! function_exists( 'communityservice_account_edit_account' ) ) {

	/**
	 * My Account > Edit account template.
	 */
	function communityservice_account_edit_account() {
		CS_Shortcode_My_Account::edit_account();
	}
}

if ( ! function_exists( 'cs_no_tasks_found' ) ) {

	/**
	 * Handles the loop when no tasks were found/no task exist.
	 */
	function cs_no_tasks_found() {
		cs_get_template( 'loop/no-tasks-found.php' );
	}
}


if ( ! function_exists( 'cs_get_email_order_items' ) ) {
	/**
	 * Get HTML for the order items to be shown in emails.
	 *
	 * @param CS_Order $order Order object.
	 * @param array    $args Arguments.
	 *
	 * @since 3.0.0
	 * @return string
	 */
	function cs_get_email_order_items( $order, $args = array() ) {
		ob_start();

		$defaults = array(
			'show_sku'      => false,
			'show_image'    => false,
			'image_size'    => array( 32, 32 ),
			'plain_text'    => false,
			'sent_to_admin' => false,
		);

		$args     = wp_parse_args( $args, $defaults );
		$template = $args['plain_text'] ? 'emails/plain/email-order-items.php' : 'emails/email-order-items.php';

		cs_get_template( $template, apply_filters( 'communityservice_email_order_items_args', array(
			'order'               => $order,
			'items'               => $order->get_items(),
			'show_download_links' => $order->is_download_permitted() && ! $args['sent_to_admin'],
			'show_sku'            => $args['show_sku'],
			'show_purchase_note'  => $order->is_paid() && ! $args['sent_to_admin'],
			'show_image'          => $args['show_image'],
			'image_size'          => $args['image_size'],
			'plain_text'          => $args['plain_text'],
			'sent_to_admin'       => $args['sent_to_admin'],
		) ) );

		return apply_filters( 'communityservice_email_order_items_table', ob_get_clean(), $order );
	}
}

if ( ! function_exists( 'cs_display_item_meta' ) ) {
	/**
	 * Display item meta data.
	 *
	 * @since  3.0.0
	 * @param  CS_Order_Item $item Order Item.
	 * @param  array         $args Arguments.
	 * @return string|void
	 */
	function cs_display_item_meta( $item, $args = array() ) {
		$strings = array();
		$html    = '';
		$args    = wp_parse_args( $args, array(
			'before'    => '<ul class="wc-item-meta"><li>',
			'after'     => '</li></ul>',
			'separator' => '</li><li>',
			'echo'      => true,
			'autop'     => false,
			'label_before' => '<strong class="wc-item-meta-label">',
			'label_after' => ':</strong> ',
		) );

		foreach ( $item->get_formatted_meta_data() as $meta_id => $meta ) {
			$value     = $args['autop'] ? wp_kses_post( $meta->display_value ) : wp_kses_post( make_clickable( trim( $meta->display_value ) ) );
			$strings[] = $args['label_before'] . wp_kses_post( $meta->display_key ) . $args['label_after'] . $value;
		}

		if ( $strings ) {
			$html = $args['before'] . implode( $args['separator'], $strings ) . $args['after'];
		}

		$html = apply_filters( 'communityservice_display_item_meta', $html, $item, $args );

		if ( $args['echo'] ) {
			echo $html; // WPCS: XSS ok.
		} else {
			return $html;
		}
	}
}

if ( ! function_exists( 'cs_display_item_downloads' ) ) {
	/**
	 * Display item download links.
	 *
	 * @since  3.0.0
	 * @param  CS_Order_Item $item Order Item.
	 * @param  array         $args Arguments.
	 * @return string|void
	 */
	function cs_display_item_downloads( $item, $args = array() ) {
		$strings = array();
		$html    = '';
		$args    = wp_parse_args( $args, array(
			'before'    => '<ul class ="wc-item-downloads"><li>',
			'after'     => '</li></ul>',
			'separator' => '</li><li>',
			'echo'      => true,
			'show_url'  => false,
		) );

		$downloads = is_object( $item ) && $item->is_type( 'line_item' ) ? $item->get_item_downloads() : array();

		if ( $downloads ) {
			$i = 0;
			foreach ( $downloads as $file ) {
				$i ++;

				if ( $args['show_url'] ) {
					$strings[] = '<strong class="wc-item-download-label">' . esc_html( $file['name'] ) . ':</strong> ' . esc_html( $file['download_url'] );
				} else {
					/* translators: %d: downloads count */
					$prefix    = count( $downloads ) > 1 ? sprintf( __( 'Download %d', 'communityservice' ), $i ) : __( 'Download', 'communityservice' );
					$strings[] = '<strong class="wc-item-download-label">' . $prefix . ':</strong> <a href="' . esc_url( $file['download_url'] ) . '" target="_blank">' . esc_html( $file['name'] ) . '</a>';
				}
			}
		}

		if ( $strings ) {
			$html = $args['before'] . implode( $args['separator'], $strings ) . $args['after'];
		}

		$html = apply_filters( 'communityservice_display_item_downloads', $html, $item, $args );

		if ( $args['echo'] ) {
			echo $html; // WPCS: XSS ok.
		} else {
			return $html;
		}
	}
}

if ( ! function_exists( 'communityservice_photoswipe' ) ) {

	/**
	 * Get the shop sidebar template.
	 */
	function communityservice_photoswipe() {
		if ( current_theme_supports( 'wc-task-gallery-lightbox' ) ) {
			cs_get_template( 'single-task/photoswipe.php' );
		}
	}
}

/**
 * Outputs a list of task attributes for a task.
 *
 * @since  3.0.0
 * @param  CS_Task $task Task Object.
 */
function cs_display_task_attributes( $task ) {
	cs_get_template( 'single-task/task-attributes.php', array(
		'task'            => $task,
		'attributes'         => array_filter( $task->get_attributes(), 'cs_attributes_array_filter_visible' ),
		'display_dimensions' => apply_filters( 'cs_task_enable_dimensions_display', $task->has_weight() || $task->has_dimensions() ),
	) );
}

/**
 * Get HTML to show task stock.
 *
 * @since  3.0.0
 * @param  CS_Task $task Task Object.
 * @return string
 */
function cs_get_stock_html( $task ) {
	$html         = '';
	$availability = $task->get_availability();

	if ( ! empty( $availability['availability'] ) ) {
		ob_start();

		cs_get_template( 'single-task/stock.php', array(
			'task'      => $task,
			'class'        => $availability['class'],
			'availability' => $availability['availability'],
		) );

		$html = ob_get_clean();
	}

	if ( has_filter( 'communityservice_stock_html' ) ) {
		cs_deprecated_function( 'The communityservice_stock_html filter', '', 'communityservice_get_stock_html' );
		$html = apply_filters( 'communityservice_stock_html', $html, $availability['availability'], $task );
	}

	return apply_filters( 'communityservice_get_stock_html', $html, $task );
}

/**
 * Get HTML for ratings.
 *
 * @since  3.0.0
 * @param  float $rating Rating being shown.
 * @param  int   $count  Total number of ratings.
 * @return string
 */
function cs_get_rating_html( $rating, $count = 0 ) {
	$html = 0 < $rating ? '<div class="star-rating">' . cs_get_star_rating_html( $rating, $count ) . '</div>' : '';
	return apply_filters( 'communityservice_task_get_rating_html', $html, $rating, $count );
}

/**
 * Get HTML for star rating.
 *
 * @since  3.1.0
 * @param  float $rating Rating being shown.
 * @param  int   $count  Total number of ratings.
 * @return string
 */
function cs_get_star_rating_html( $rating, $count = 0 ) {
	$html = '<span style="width:' . ( ( $rating / 5 ) * 100 ) . '%">';

	if ( 0 < $count ) {
		/* translators: 1: rating 2: rating count */
		$html .= sprintf( _n( 'Rated %1$s out of 5 based on %2$s customer rating', 'Rated %1$s out of 5 based on %2$s customer ratings', $count, 'communityservice' ), '<strong class="rating">' . esc_html( $rating ) . '</strong>', '<span class="rating">' . esc_html( $count ) . '</span>' );
	} else {
		/* translators: %s: rating */
		$html .= sprintf( esc_html__( 'Rated %s out of 5', 'communityservice' ), '<strong class="rating">' . esc_html( $rating ) . '</strong>' );
	}

	$html .= '</span>';

	return apply_filters( 'communityservice_get_star_rating_html', $html, $rating, $count );
}

/**
 * Returns a 'from' prefix if you want to show where prices start at.
 *
 * @since  3.0.0
 * @return string
 */
function cs_get_price_html_from_text() {
	return apply_filters( 'communityservice_get_price_html_from_text', '<span class="from">' . _x( 'From:', 'min_price', 'communityservice' ) . ' </span>' );
}

/**
 * Get logout endpoint.
 *
 * @since  2.6.9
 *
 * @param string $redirect Redirect URL.
 *
 * @return string
 */
function cs_logout_url( $redirect = '' ) {
	$redirect = $redirect ? $redirect : cs_get_page_permalink( 'myaccount' );

	if ( get_option( 'communityservice_logout_endpoint' ) ) {
		return wp_nonce_url( cs_get_endpoint_url( 'customer-logout', '', $redirect ), 'customer-logout' );
	}

	return wp_logout_url( $redirect );
}

/**
 * Show notice if cart is empty.
 *
 * @since 3.1.0
 */
function cs_empty_cart_message() {
	echo '<p class="cart-empty">' . wp_kses_post( apply_filters( 'cs_empty_cart_message', __( 'Your cart is currently empty.', 'communityservice' ) ) ) . '</p>';
}

/**
 * Disable search engines indexing core, dynamic, cart/checkout pages.
 *
 * @since 3.2.0
 */
function cs_page_noindex() {
	if ( is_page( cs_get_page_id( 'cart' ) ) || is_page( cs_get_page_id( 'checkout' ) ) || is_page( cs_get_page_id( 'myaccount' ) ) ) {
		wp_no_robots();
	}
}
add_action( 'wp_head', 'cs_page_noindex' );

/**
 * Get a slug identifying the current theme.
 *
 * @since 3.3.0
 * @return string
 */
function cs_get_theme_slug_for_templates() {
	return apply_filters( 'communityservice_theme_slug_for_templates', get_option( 'template' ) );
}

/**
 * Gets and formats a list of cart item data + variations for display on the frontend.
 *
 * @since 3.3.0
 * @param array $cart_item Cart item object.
 * @param bool  $flat Should the data be returned flat or in a list.
 * @return string
 */
function cs_get_formatted_cart_item_data( $cart_item, $flat = false ) {
	$item_data = array();

	// Variation values are shown only if they are not found in the title as of 3.0.
	// This is because variation titles display the attributes.
	if ( $cart_item['data']->is_type( 'variation' ) && is_array( $cart_item['variation'] ) ) {
		foreach ( $cart_item['variation'] as $name => $value ) {
			$taxonomy = cs_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $name ) ) );

			if ( taxonomy_exists( $taxonomy ) ) {
				// If this is a term slug, get the term's nice name.
				$term = get_term_by( 'slug', $value, $taxonomy );
				if ( ! is_wp_error( $term ) && $term && $term->name ) {
					$value = $term->name;
				}
				$label = cs_attribute_label( $taxonomy );
			} else {
				// If this is a custom option slug, get the options name.
				$value = apply_filters( 'communityservice_variation_option_name', $value );
				$label = cs_attribute_label( str_replace( 'attribute_', '', $name ), $cart_item['data'] );
			}

			// Check the nicename against the title.
			if ( '' === $value || cs_is_attribute_in_task_name( $value, $cart_item['data']->get_name() ) ) {
				continue;
			}

			$item_data[] = array(
				'key'   => $label,
				'value' => $value,
			);
		}
	}

	// Filter item data to allow 3rd parties to add more to the array.
	$item_data = apply_filters( 'communityservice_get_item_data', $item_data, $cart_item );

	// Format item data ready to display.
	foreach ( $item_data as $key => $data ) {
		// Set hidden to true to not display meta on cart.
		if ( ! empty( $data['hidden'] ) ) {
			unset( $item_data[ $key ] );
			continue;
		}
		$item_data[ $key ]['key']     = ! empty( $data['key'] ) ? $data['key'] : $data['name'];
		$item_data[ $key ]['display'] = ! empty( $data['display'] ) ? $data['display'] : $data['value'];
	}

	// Output flat or in list format.
	if ( count( $item_data ) > 0 ) {
		ob_start();

		if ( $flat ) {
			foreach ( $item_data as $data ) {
				echo esc_html( $data['key'] ) . ': ' . wp_kses_post( $data['display'] ) . "\n";
			}
		} else {
			cs_get_template( 'cart/cart-item-data.php', array( 'item_data' => $item_data ) );
		}

		return ob_get_clean();
	}

	return '';
}

/**
 * Gets the url to remove an item from the cart.
 *
 * @since 3.3.0
 * @param string $cart_item_key contains the id of the cart item.
 * @return string url to page
 */
function cs_get_cart_remove_url( $cart_item_key ) {
	$cart_page_url = cs_get_page_permalink( 'cart' );
	return apply_filters( 'communityservice_get_remove_url', $cart_page_url ? wp_nonce_url( add_query_arg( 'remove_item', $cart_item_key, $cart_page_url ), 'communityservice-cart' ) : '' );
}

/**
 * Gets the url to re-add an item into the cart.
 *
 * @since 3.3.0
 * @param  string $cart_item_key Cart item key to undo.
 * @return string url to page
 */
function cs_get_cart_undo_url( $cart_item_key ) {
	$cart_page_url = cs_get_page_permalink( 'cart' );

	$query_args = array(
		'undo_item' => $cart_item_key,
	);

	return apply_filters( 'communityservice_get_undo_url', $cart_page_url ? wp_nonce_url( add_query_arg( $query_args, $cart_page_url ), 'communityservice-cart' ) : '', $cart_item_key );
}

/**
 * Outputs all queued notices on WC pages.
 *
 * @since 3.5.0
 */
function communityservice_output_all_notices() {
	echo '<div class="communityservice-notices-wrapper">';
	cs_print_notices();
	echo '</div>';
}

/**
 * Tasks RSS Feed.
 *
 * @deprecated 2.6
 */
function cs_tasks_rss_feed() {
	cs_deprecated_function( 'cs_tasks_rss_feed', '2.6' );
}

if ( ! function_exists( 'communityservice_reset_loop' ) ) {

	/**
	 * Reset the loop's index and columns when we're done outputting a task loop.
	 *
	 * @deprecated 3.3
	 */
	function communityservice_reset_loop() {
		cs_reset_loop();
	}
}

if ( ! function_exists( 'communityservice_task_reviews_tab' ) ) {
	/**
	 * Output the reviews tab content.
	 *
	 * @deprecated 2.4.0 Unused.
	 */
	function communityservice_task_reviews_tab() {
		cs_deprecated_function( 'communityservice_task_reviews_tab', '2.4' );
	}
}
