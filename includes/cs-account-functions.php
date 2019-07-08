<?php
/**
 * CommunityService Account Functions
 *
 * Functions for account specific things.
 *
 * @package CommunityService/Functions
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the url to the lost password endpoint url.
 *
 * @param  string $default_url Default lost password URL.
 * @return string
 */
function cs_lostpassword_url( $default_url = '' ) {
	// Avoid loading too early.
	if ( ! did_action( 'init' ) ) {
		return $default_url;
	}
	// Don't redirect to the communityservice endpoint on global network admin lost passwords.
	if ( is_multisite() && isset( $_GET['redirect_to'] ) && false !== strpos( wp_unslash( $_GET['redirect_to'] ), network_admin_url() ) ) { // WPCS: input var ok, sanitization ok, CSRF ok.
		return $default_url;
	}

	$cs_account_page_url    = cs_get_page_permalink( 'myaccount' );
	$cs_account_page_exists = cs_get_page_id( 'myaccount' ) > 0;
	$lost_password_endpoint = 'lost-password';//get_option( 'communityservice_myaccount_lost_password_endpoint' );

	if ( $cs_account_page_exists && ! empty( $lost_password_endpoint ) ) {
		return cs_get_endpoint_url( $lost_password_endpoint, '', $cs_account_page_url );
	} else {
		return $default_url;
	}
}

//add_filter( 'lostpassword_url', 'cs_lostpassword_url', 10, 100 );

/**
 * Get the link to the edit account details page.
 *
 * @return string
 */
function cs_customer_edit_account_url() {
	$edit_account_url = cs_get_endpoint_url( 'edit-account', '', cs_get_page_permalink( 'myaccount' ) );

	return apply_filters( 'communityservice_customer_edit_account_url', $edit_account_url );
}

/**
 * Get the edit address slug translation.
 *
 * @param  string $id   Address ID.
 * @param  bool   $flip Flip the array to make it possible to retrieve the values ​​from both sides.
 *
 * @return string       Address slug i18n.
 */
function cs_edit_address_i18n( $id, $flip = false ) {
	$slugs = apply_filters(
		'communityservice_edit_address_slugs', array(
			'billing'  => sanitize_title( _x( 'billing', 'edit-address-slug', 'communityservice' ) ),
			'shipping' => sanitize_title( _x( 'shipping', 'edit-address-slug', 'communityservice' ) ),
		)
	);

	if ( $flip ) {
		$slugs = array_flip( $slugs );
	}

	if ( ! isset( $slugs[ $id ] ) ) {
		return $id;
	}

	return $slugs[ $id ];
}

/**
 * Get My Account menu items.
 *
 * @since 1.0
 * @return array
 */
function cs_get_account_menu_items() {
	$endpoints = array(
		'activities'          => get_option( 'communityservice_myaccount_activities_endpoint', 'activities' ),
		'downloads'       => get_option( 'communityservice_myaccount_downloads_endpoint', 'downloads' ),
		'edit-address'    => get_option( 'communityservice_myaccount_edit_address_endpoint', 'edit-address' ),
		'payment-methods' => get_option( 'communityservice_myaccount_payment_methods_endpoint', 'payment-methods' ),
		'edit-account'    => get_option( 'communityservice_myaccount_edit_account_endpoint', 'edit-account' ),
		'customer-logout' => get_option( 'communityservice_logout_endpoint', 'customer-logout' ),
	);

	$items = array(
		'dashboard'       => __( 'Dashboard', 'communityservice' ),
		'activities'          => __( 'Activities', 'communityservice' ),
		'downloads'       => __( 'Downloads', 'communityservice' ),
		'edit-address'    => __( 'Addresses', 'communityservice' ),
		'payment-methods' => __( 'Payment methods', 'communityservice' ),
		'edit-account'    => __( 'Account details', 'communityservice' ),
		'customer-logout' => __( 'Logout', 'communityservice' ),
	);

	// Remove missing endpoints.
	foreach ( $endpoints as $endpoint_id => $endpoint ) {
		if ( empty( $endpoint ) ) {
			unset( $items[ $endpoint_id ] );
		}
	}

	// Check if payment gateways support add new payment methods.
	if ( isset( $items['payment-methods'] ) ) {
		$support_payment_methods = false;
		foreach ( CS()->payment_gateways->get_available_payment_gateways() as $gateway ) {
			if ( $gateway->supports( 'add_payment_method' ) || $gateway->supports( 'tokenization' ) ) {
				$support_payment_methods = true;
				break;
			}
		}

		if ( ! $support_payment_methods ) {
			unset( $items['payment-methods'] );
		}
	}

	return apply_filters( 'communityservice_account_menu_items', $items, $endpoints );
}

/**
 * Get account menu item classes.
 *
 * @since 1.0
 * @param string $endpoint Endpoint.
 * @return string
 */
function cs_get_account_menu_item_classes( $endpoint ) {
	global $wp;

	$classes = array(
		'communityservice-MyAccount-navigation-link',
		'communityservice-MyAccount-navigation-link--' . $endpoint,
	);

	// Set current item class.
	$current = isset( $wp->query_vars[ $endpoint ] );
	if ( 'dashboard' === $endpoint && ( isset( $wp->query_vars['page'] ) || empty( $wp->query_vars ) ) ) {
		$current = true; // Dashboard is not an endpoint, so needs a custom check.
	}

	if ( $current ) {
		$classes[] = 'is-active';
	}

	$classes = apply_filters( 'communityservice_account_menu_item_classes', $classes, $endpoint );

	return implode( ' ', array_map( 'sanitize_html_class', $classes ) );
}

/**
 * Get account endpoint URL.
 *
 * @since 1.0
 * @param string $endpoint Endpoint.
 * @return string
 */
function cs_get_account_endpoint_url( $endpoint ) {
	if ( 'dashboard' === $endpoint ) {
		return cs_get_page_permalink( 'myaccount' );
	}

	if ( 'customer-logout' === $endpoint ) {
		return cs_logout_url();
	}

	return cs_get_endpoint_url( $endpoint, '', cs_get_page_permalink( 'myaccount' ) );
}

/**
 * Get My Account > Activities columns.
 *
 * @since 1.0
 * @return array
 */
function cs_get_account_activities_columns() {
	$columns = apply_filters(
		'communityservice_account_activities_columns', array(
			'activity-number'  => __( 'Activity', 'communityservice' ),
			'activity-date'    => __( 'Date', 'communityservice' ),
			'activity-status'  => __( 'Status', 'communityservice' ),
			'activity-total'   => __( 'Total', 'communityservice' ),
			'activity-actions' => __( 'Actions', 'communityservice' ),
		)
	);

	// Deprecated filter since 1.0.
	return apply_filters( 'communityservice_my_account_my_activities_columns', $columns );
}


/**
 * Get My Account > Payment methods columns.
 *
 * @since 1.0
 * @return array
 */
function cs_get_account_payment_methods_columns() {
	return apply_filters(
		'communityservice_account_payment_methods_columns', array(
			'method'  => __( 'Method', 'communityservice' ),
			'expires' => __( 'Expires', 'communityservice' ),
			'actions' => '&nbsp;',
		)
	);
}

/**
 * Get My Account > Payment methods types
 *
 * @since 1.0
 * @return array
 */
function cs_get_account_payment_methods_types() {
	return apply_filters(
		'communityservice_payment_methods_types', array(
			'cc'     => __( 'Credit card', 'communityservice' ),
			'echeck' => __( 'eCheck', 'communityservice' ),
		)
	);
}

/**
 * Get account activities actions.
 *
 * @since  3.2.0
 * @param  int|CS_Activity $activity Activity instance or ID.
 * @return array
 */
function cs_get_account_activities_actions( $activity ) {
	if ( ! is_object( $activity ) ) {
		$activity_id = absint( $activity );
		$activity    = cs_get_activity( $activity_id );
	}

	$actions = array(
		'pay'    => array(
			'url'  => $activity->get_checkout_payment_url(),
			'name' => __( 'Pay', 'communityservice' ),
		),
		'view'   => array(
			'url'  => $activity->get_view_activity_url(),
			'name' => __( 'View', 'communityservice' ),
		),
		'cancel' => array(
			'url'  => $activity->get_cancel_activity_url( cs_get_page_permalink( 'myaccount' ) ),
			'name' => __( 'Cancel', 'communityservice' ),
		),
	);

	if ( ! $activity->needs_payment() ) {
		unset( $actions['pay'] );
	}

	if ( ! in_array( $activity->get_status(), apply_filters( 'communityservice_valid_activity_statuses_for_cancel', array( 'pending', 'failed' ), $activity ), true ) ) {
		unset( $actions['cancel'] );
	}

	return apply_filters( 'communityservice_my_account_my_activities_actions', $actions, $activity );
}

/**
 * Get account formatted address.
 *
 * @since  3.2.0
 * @param  string $address_type Address type.
 *                              Accepts: 'billing' or 'shipping'.
 *                              Default to 'billing'.
 * @param  int    $customer_id  Customer ID.
 *                              Default to 0.
 * @return string
 */
function cs_get_account_formatted_address( $address_type = 'billing', $customer_id = 0 ) {
	$getter  = "get_{$address_type}";
	$address = array();

	if ( 0 === $customer_id ) {
		$customer_id = get_current_user_id();
	}

	$customer = new CS_Customer( $customer_id );

	if ( is_callable( array( $customer, $getter ) ) ) {
		$address = $customer->$getter();
		unset( $address['email'], $address['tel'] );
	}

	return CS()->countries->get_formatted_address( apply_filters( 'communityservice_my_account_my_address_formatted_address', $address, $customer->get_id(), $address_type ) );
}

/**
 * Returns an array of a user's saved payments list for output on the account tab.
 *
 * @since  2.6
 * @param  array $list         List of payment methods passed from cs_get_customer_saved_methods_list().
 * @param  int   $customer_id  The customer to fetch payment methods for.
 * @return array               Filtered list of customers payment methods.
 */
function cs_get_account_saved_payment_methods_list( $list, $customer_id ) {
	$payment_tokens = CS_Payment_Tokens::get_customer_tokens( $customer_id );
	foreach ( $payment_tokens as $payment_token ) {
		$delete_url      = cs_get_endpoint_url( 'delete-payment-method', $payment_token->get_id() );
		$delete_url      = wp_nonce_url( $delete_url, 'delete-payment-method-' . $payment_token->get_id() );
		$set_default_url = cs_get_endpoint_url( 'set-default-payment-method', $payment_token->get_id() );
		$set_default_url = wp_nonce_url( $set_default_url, 'set-default-payment-method-' . $payment_token->get_id() );

		$type            = strtolower( $payment_token->get_type() );
		$list[ $type ][] = array(
			'method'     => array(
				'gateway' => $payment_token->get_gateway_id(),
			),
			'expires'    => esc_html__( 'N/A', 'communityservice' ),
			'is_default' => $payment_token->is_default(),
			'actions'    => array(
				'delete' => array(
					'url'  => $delete_url,
					'name' => esc_html__( 'Delete', 'communityservice' ),
				),
			),
		);
		$key             = key( array_slice( $list[ $type ], -1, 1, true ) );

		if ( ! $payment_token->is_default() ) {
			$list[ $type ][ $key ]['actions']['default'] = array(
				'url'  => $set_default_url,
				'name' => esc_html__( 'Make default', 'communityservice' ),
			);
		}

		$list[ $type ][ $key ] = apply_filters( 'communityservice_payment_methods_list_item', $list[ $type ][ $key ], $payment_token );
	}
	return $list;
}

add_filter( 'communityservice_saved_payment_methods_list', 'cs_get_account_saved_payment_methods_list', 10, 2 );

/**
 * Controls the output for credit cards on the my account page.
 *
 * @since 2.6
 * @param  array            $item         Individual list item from communityservice_saved_payment_methods_list.
 * @param  CS_Payment_Token $payment_token The payment token associated with this method entry.
 * @return array                           Filtered item.
 */
function cs_get_account_saved_payment_methods_list_item_cc( $item, $payment_token ) {
	if ( 'cc' !== strtolower( $payment_token->get_type() ) ) {
		return $item;
	}

	$card_type               = $payment_token->get_card_type();
	$item['method']['last4'] = $payment_token->get_last4();
	$item['method']['brand'] = ( ! empty( $card_type ) ? ucfirst( $card_type ) : esc_html__( 'Credit card', 'communityservice' ) );
	$item['expires']         = $payment_token->get_expiry_month() . '/' . substr( $payment_token->get_expiry_year(), -2 );

	return $item;
}

add_filter( 'communityservice_payment_methods_list_item', 'cs_get_account_saved_payment_methods_list_item_cc', 10, 2 );

/**
 * Controls the output for eChecks on the my account page.
 *
 * @since 2.6
 * @param  array            $item         Individual list item from communityservice_saved_payment_methods_list.
 * @param  CS_Payment_Token $payment_token The payment token associated with this method entry.
 * @return array                           Filtered item.
 */
function cs_get_account_saved_payment_methods_list_item_echeck( $item, $payment_token ) {
	if ( 'echeck' !== strtolower( $payment_token->get_type() ) ) {
		return $item;
	}

	$item['method']['last4'] = $payment_token->get_last4();
	$item['method']['brand'] = esc_html__( 'eCheck', 'communityservice' );

	return $item;
}

add_filter( 'communityservice_payment_methods_list_item', 'cs_get_account_saved_payment_methods_list_item_echeck', 10, 2 );
