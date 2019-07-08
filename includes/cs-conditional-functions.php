<?php
/**
 * CommunityService Conditional Functions
 *
 * Functions for determining the current query/page.
 *
 * @package     CommunityService/Functions
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Is_communityservice - Returns true if on a page which uses CommunityService templates (cart and checkout are standard pages with shortcodes and thus are not included).
 *
 * @return bool
 */
function is_communityservice() {
	return apply_filters( 'is_communityservice', is_task() );
}


if ( ! function_exists( 'is_task' ) ) {

	/**
	 * is_task - Returns true when viewing a single product.
	 *
	 * @return bool
	 */
	function is_task() {
		return is_singular( array( 'cs-task' ) );
	}
}

if ( ! function_exists( 'is_cs_endpoint_url' ) ) {

	/**
	 * Is_cs_endpoint_url - Check if an endpoint is showing.
	 *
	 * @param string|false $endpoint Whether endpoint.
	 * @return bool
	 */
	function is_cs_endpoint_url( $endpoint = false ) {
		global $wp;

		$cs_endpoints = CS()->query->get_query_vars();

		if ( false !== $endpoint ) {
			if ( ! isset( $cs_endpoints[ $endpoint ] ) ) {
				return false;
			} else {
				$endpoint_var = $cs_endpoints[ $endpoint ];
			}

			return isset( $wp->query_vars[ $endpoint_var ] );
		} else {
			foreach ( $cs_endpoints as $key => $value ) {
				if ( isset( $wp->query_vars[ $key ] ) ) {
					return true;
				}
			}

			return false;
		}
	}
}

if ( ! function_exists( 'is_account_page' ) ) {

	/**
	 * Is_account_page - Returns true when viewing an account page.
	 *
	 * @return bool
	 */
	function is_account_page() {
		$page_id = cs_get_page_id( 'myaccount' );

		return ( $page_id && is_page( $page_id ) ) || wc_post_content_has_shortcode( 'woocommerce_my_account' ) || apply_filters( 'woocommerce_is_account_page', false );
	}
}

if ( ! function_exists( 'is_view_order_page' ) ) {

	/**
	 * Is_view_order_page - Returns true when on the view order page.
	 *
	 * @return bool
	 */
	function is_view_order_page() {
		global $wp;

		$page_id = cs_get_page_id( 'myaccount' );

		return ( $page_id && is_page( $page_id ) && isset( $wp->query_vars['view-order'] ) );
	}
}

if ( ! function_exists( 'is_edit_account_page' ) ) {

	/**
	 * Check for edit account page.
	 * Returns true when viewing the edit account page.
	 *
	 * @since 2.5.1
	 * @return bool
	 */
	function is_edit_account_page() {
		global $wp;

		$page_id = cs_get_page_id( 'myaccount' );

		return ( $page_id && is_page( $page_id ) && isset( $wp->query_vars['edit-account'] ) );
	}
}

if ( ! function_exists( 'is_order_received_page' ) ) {

	/**
	 * Is_order_received_page - Returns true when viewing the order received page.
	 *
	 * @return bool
	 */
	function is_order_received_page() {
		global $wp;

		$page_id = cs_get_page_id( 'checkout' );

		return apply_filters( 'woocommerce_is_order_received_page', ( $page_id && is_page( $page_id ) && isset( $wp->query_vars['order-received'] ) ) );
	}
}

if ( ! function_exists( 'is_lost_password_page' ) ) {

	/**
	 * Is_lost_password_page - Returns true when viewing the lost password page.
	 *
	 * @return bool
	 */
	function is_lost_password_page() {
		global $wp;

		$page_id = cs_get_page_id( 'myaccount' );

		return ( $page_id && is_page( $page_id ) && isset( $wp->query_vars['lost-password'] ) );
	}
}

if ( ! function_exists( 'is_ajax' ) ) {

	/**
	 * Is_ajax - Returns true when the page is loaded via ajax.
	 *
	 * @return bool
	 */
	function is_ajax() {
		return function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : defined( 'DOING_AJAX' );
	}
}


/**
 * Simple check for validating a URL, it must start with http:// or https://.
 * and pass FILTER_VALIDATE_URL validation.
 *
 * @param  string $url to check.
 * @return bool
 */
function wc_is_valid_url( $url ) {

	// Must start with http:// or https://.
	if ( 0 !== strpos( $url, 'http://' ) && 0 !== strpos( $url, 'https://' ) ) {
		return false;
	}

	// Must pass validation.
	if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		return false;
	}

	return true;
}


/**
 * Checks whether the content passed contains a specific short code.
 *
 * @param  string $tag Shortcode tag to check.
 * @return bool
 */
function wc_post_content_has_shortcode( $tag = '' ) {
	global $post;

	return is_singular() && is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $tag );
}
