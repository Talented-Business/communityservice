<?php
/**
 * CommunityService Page Functions
 *
 * Functions related to pages and menus.
 *
 * @package  CommunityService\Functions
 * @version  1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Replace a page title with the endpoint title.
 *
 * @param  string $title Post title.
 * @return string
 */
function cs_page_endpoint_title( $title ) {
	global $wp_query;

	if ( ! is_null( $wp_query ) && ! is_admin() && is_main_query() && in_the_loop() && is_page() && is_cs_endpoint_url() ) {
		$endpoint       = CS()->query->get_current_endpoint();
		$endpoint_title = CS()->query->get_endpoint_title( $endpoint );
		$title          = $endpoint_title ? $endpoint_title : $title;

		remove_filter( 'the_title', 'cs_page_endpoint_title' );
	}

	return $title;
}

add_filter( 'the_title', 'cs_page_endpoint_title' );

/**
 * Retrieve page ids - used for myaccount, edit_address, shop, cart, checkout, pay, view_order, terms. returns -1 if no page is found.
 *
 * @param string $page Page slug.
 * @return int
 */
function cs_get_page_id( $page ) {
	if($page == 'myaccount') $page =1659;
	else $page = apply_filters( 'communityservice_get_' . $page . '_page_id', get_option( 'communityservice_' . $page . '_page_id' ) );
	return $page ? absint( $page ) : -1;
}

/**
 * Retrieve page permalink.
 *
 * @param string      $page page slug.
 * @param string|bool $fallback Fallback URL if page is not set. Defaults to home URL. @since 3.4.0.
 * @return string
 */
function cs_get_page_permalink( $page, $fallback = null ) {
	$page_id   = cs_get_page_id( $page );
	$permalink = 0 < $page_id ? get_permalink( $page_id ) : '';

	if ( ! $permalink ) {
		$permalink = is_null( $fallback ) ? get_home_url() : $fallback;
	}

	return apply_filters( 'communityservice_get_' . $page . '_page_permalink', $permalink );
}

/**
 * Get endpoint URL.
 *
 * Gets the URL for an endpoint, which varies depending on permalink settings.
 *
 * @param  string $endpoint  Endpoint slug.
 * @param  string $value     Query param value.
 * @param  string $permalink Permalink.
 *
 * @return string
 */
function cs_get_endpoint_url( $endpoint, $value = '', $permalink = '' ) {
	if ( ! $permalink ) {
		$permalink = get_permalink();
	}

	// Map endpoint to options.
	$query_vars = CS()->query->get_query_vars();
	$endpoint   = ! empty( $query_vars[ $endpoint ] ) ? $query_vars[ $endpoint ] : $endpoint;
	$value      = ( get_option( 'communityservice_myaccount_edit_address_endpoint', 'edit-address' ) === $endpoint ) ? cs_edit_address_i18n( $value ) : $value;

	if ( get_option( 'permalink_structure' ) ) {
		if ( strstr( $permalink, '?' ) ) {
			$query_string = '?' . wp_parse_url( $permalink, PHP_URL_QUERY );
			$permalink    = current( explode( '?', $permalink ) );
		} else {
			$query_string = '';
		}
		$url = trailingslashit( $permalink ) . trailingslashit( $endpoint );

		if ( $value ) {
			$url .= trailingslashit( $value );
		}

		$url .= $query_string;
	} else {
		$url = add_query_arg( $endpoint, $value, $permalink );
	}

	return apply_filters( 'communityservice_get_endpoint_url', $url, $endpoint, $value, $permalink );
}

/**
 * Hide menu items conditionally.
 *
 * @param array $items Navigation items.
 * @return array
 */
function cs_nav_menu_items( $items ) {
	if ( ! is_user_logged_in() ) {
		$customer_logout = get_option( 'communityservice_logout_endpoint', 'customer-logout' );

		if ( ! empty( $customer_logout ) && ! empty( $items ) && is_array( $items ) ) {
			foreach ( $items as $key => $item ) {
				if ( empty( $item->url ) ) {
					continue;
				}
				$path  = wp_parse_url( $item->url, PHP_URL_PATH );
				$query = wp_parse_url( $item->url, PHP_URL_QUERY );

				if ( strstr( $path, $customer_logout ) || strstr( $query, $customer_logout ) ) {
					unset( $items[ $key ] );
				}
			}
		}
	}

	return $items;
}
add_filter( 'wp_nav_menu_objects', 'cs_nav_menu_items', 10 );


/**
 * Fix active class in nav for shop page.
 *
 * @param array $menu_items Menu items.
 * @return array
 */
function cs_nav_menu_item_classes( $menu_items ) {
	if ( ! is_communityservice() ) {
		return $menu_items;
	}

	$shop_page      = cs_get_page_id( 'shop' );
	$page_for_posts = (int) get_option( 'page_for_posts' );

	if ( ! empty( $menu_items ) && is_array( $menu_items ) ) {
		foreach ( $menu_items as $key => $menu_item ) {
			$classes = (array) $menu_item->classes;
			$menu_id = (int) $menu_item->object_id;

			// Unset active class for blog page.
			if ( $page_for_posts === $menu_id ) {
				$menu_items[ $key ]->current = false;

				if ( in_array( 'current_page_parent', $classes, true ) ) {
					unset( $classes[ array_search( 'current_page_parent', $classes, true ) ] );
				}

				if ( in_array( 'current-menu-item', $classes, true ) ) {
					unset( $classes[ array_search( 'current-menu-item', $classes, true ) ] );
				}
			} elseif ( is_shop() && $shop_page === $menu_id && 'page' === $menu_item->object ) {
				// Set active state if this is the shop page link.
				$menu_items[ $key ]->current = true;
				$classes[]                   = 'current-menu-item';
				$classes[]                   = 'current_page_item';

			} elseif ( is_singular( 'product' ) && $shop_page === $menu_id ) {
				// Set parent state if this is a product page.
				$classes[] = 'current_page_parent';
			}

			$menu_items[ $key ]->classes = array_unique( $classes );
		}
	}

	return $menu_items;
}
add_filter( 'wp_nav_menu_objects', 'cs_nav_menu_item_classes', 2 );


/**
 * Fix active class in wp_list_pages for shop page.
 *
 * See details in https://github.com/communityservice/communityservice/issues/177.
 *
 * @param string $pages Pages list.
 * @return string
 */
function cs_list_pages( $pages ) {
	if ( ! is_communityservice() ) {
		return $pages;
	}

	// Remove current_page_parent class from any item.
	$pages = str_replace( 'current_page_parent', '', $pages );
	// Find shop_page_id through communityservice options.
	$shop_page = 'page-item-' . cs_get_page_id( 'shop' );

	if ( is_shop() ) {
		// Add current_page_item class to shop page.
		return str_replace( $shop_page, $shop_page . ' current_page_item', $pages );
	}

	// Add current_page_parent class to shop page.
	return str_replace( $shop_page, $shop_page . ' current_page_parent', $pages );
}
add_filter( 'wp_list_pages', 'cs_list_pages' );
/**
 * Retrieves unvalidated referer from '_wp_http_referer' or HTTP referer.
 *
 * Do not use for redirects, use {@see wp_get_referer()} instead.
 *
 * @since 2.6.1
 * @return string|false Referer URL on success, false on failure.
 */
function cs_get_raw_referer() {
	if ( function_exists( 'wp_get_raw_referer' ) ) {
		return wp_get_raw_referer();
	}

	if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) { // WPCS: input var ok, CSRF ok.
		return wp_unslash( $_REQUEST['_wp_http_referer'] ); // WPCS: input var ok, CSRF ok, sanitization ok.
	} elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) { // WPCS: input var ok, CSRF ok.
		return wp_unslash( $_SERVER['HTTP_REFERER'] ); // WPCS: input var ok, CSRF ok, sanitization ok.
	}

	return false;
}
