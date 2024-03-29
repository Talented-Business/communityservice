<?php
/**
 * CommunityService Message Functions
 *
 * Functions for error/message handling and display.
 *
 * @package CommunityService/Functions
 * @version 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the count of notices added, either for all notices (default) or for one.
 * particular notice type specified by $notice_type.
 *
 * @since  2.1
 * @param  string $notice_type Optional. The name of the notice type - either error, success or notice.
 * @return int
 */
function cs_notice_count( $notice_type = '' ) {
	if ( ! did_action( 'communityservice_init' ) ) {
		cs_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before communityservice_init.', 'communityservice' ), '2.3' );
		return;
	}

	$notice_count = 0;
	$all_notices  = CS()->session->get( 'cs_notices', array() );

	if ( isset( $all_notices[ $notice_type ] ) ) {

		$notice_count = count( $all_notices[ $notice_type ] );

	} elseif ( empty( $notice_type ) ) {

		foreach ( $all_notices as $notices ) {
			$notice_count += count( $notices );
		}
	}

	return $notice_count;
}

/**
 * Check if a notice has already been added.
 *
 * @since  2.1
 * @param  string $message The text to display in the notice.
 * @param  string $notice_type Optional. The name of the notice type - either error, success or notice.
 * @return bool
 */
function cs_has_notice( $message, $notice_type = 'success' ) {
	if ( ! did_action( 'communityservice_init' ) ) {
		cs_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before communityservice_init.', 'communityservice' ), '2.3' );
		return false;
	}

	$notices = CS()->session->get( 'cs_notices', array() );
	$notices = isset( $notices[ $notice_type ] ) ? $notices[ $notice_type ] : array();
	return array_search( $message, $notices, true ) !== false;
}

/**
 * Add and store a notice.
 *
 * @since 2.1
 * @param string $message The text to display in the notice.
 * @param string $notice_type Optional. The name of the notice type - either error, success or notice.
 */
function cs_add_notice( $message, $notice_type = 'success' ) {
	if ( ! did_action( 'communityservice_init' ) ) {
		cs_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before communityservice_init.', 'communityservice' ), '2.3' );
		return;
	}

	$notices = CS()->session->get( 'cs_notices', array() );

	// Backward compatibility.
	if ( 'success' === $notice_type ) {
		$message = apply_filters( 'communityservice_add_message', $message );
	}

	$notices[ $notice_type ][] = apply_filters( 'communityservice_add_' . $notice_type, $message );

	CS()->session->set( 'cs_notices', $notices );
}

/**
 * Set all notices at once.
 *
 * @since 2.6.0
 * @param mixed $notices Array of notices.
 */
function cs_set_notices( $notices ) {
	if ( ! did_action( 'communityservice_init' ) ) {
		cs_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before communityservice_init.', 'communityservice' ), '2.6' );
		return;
	}
	CS()->session->set( 'cs_notices', $notices );
}


/**
 * Unset all notices.
 *
 * @since 2.1
 */
function cs_clear_notices() {
	if ( ! did_action( 'communityservice_init' ) ) {
		cs_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before communityservice_init.', 'communityservice' ), '2.3' );
		return;
	}
	CS()->session->set( 'cs_notices', null );
}

/**
 * Prints messages and errors which are stored in the session, then clears them.
 *
 * @since 2.1
 * @param bool $return true to return rather than echo. @since 3.5.0.
 * @return string|null
 */
function cs_print_notices( $return = false ) {
	if ( ! did_action( 'communityservice_init' ) ) {
		cs_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before communityservice_init.', 'communityservice' ), '2.3' );
		return;
	}

	$all_notices  = CS()->session->get( 'cs_notices', array() );
	$notice_types = apply_filters( 'communityservice_notice_types', array( 'error', 'success', 'notice' ) );

	// Buffer output.
	ob_start();

	foreach ( $notice_types as $notice_type ) {
		if ( cs_notice_count( $notice_type ) > 0 ) {
			cs_get_template( "notices/{$notice_type}.php", array(
				'messages' => array_filter( $all_notices[ $notice_type ] ),
			) );
		}
	}

	cs_clear_notices();

	$notices = cs_kses_notice( ob_get_clean() );

	if ( $return ) {
		return $notices;
	}

	echo $notices; // WPCS: XSS ok.
}

/**
 * Print a single notice immediately.
 *
 * @since 2.1
 * @param string $message The text to display in the notice.
 * @param string $notice_type Optional. The singular name of the notice type - either error, success or notice.
 */
function cs_print_notice( $message, $notice_type = 'success' ) {
	if ( 'success' === $notice_type ) {
		$message = apply_filters( 'communityservice_add_message', $message );
	}

	cs_get_template( "notices/{$notice_type}.php", array(
		'messages' => array( apply_filters( 'communityservice_add_' . $notice_type, $message ) ),
	) );
}

/**
 * Returns all queued notices, optionally filtered by a notice type.
 *
 * @since  2.1
 * @param  string $notice_type Optional. The singular name of the notice type - either error, success or notice.
 * @return array|mixed
 */
function cs_get_notices( $notice_type = '' ) {
	if ( ! did_action( 'communityservice_init' ) ) {
		cs_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before communityservice_init.', 'communityservice' ), '2.3' );
		return;
	}

	$all_notices = CS()->session->get( 'cs_notices', array() );

	if ( empty( $notice_type ) ) {
		$notices = $all_notices;
	} elseif ( isset( $all_notices[ $notice_type ] ) ) {
		$notices = $all_notices[ $notice_type ];
	} else {
		$notices = array();
	}

	return $notices;
}

/**
 * Add notices for WP Errors.
 *
 * @param WP_Error $errors Errors.
 */
function cs_add_wp_error_notices( $errors ) {
	if ( is_wp_error( $errors ) && $errors->get_error_messages() ) {
		foreach ( $errors->get_error_messages() as $error ) {
			cs_add_notice( $error, 'error' );
		}
	}
}

/**
 * Filters out the same tags as wp_kses_post, but allows tabindex for <a> element.
 *
 * @since 3.5.0
 * @param string $message Content to filter through kses.
 * @return string
 */
function cs_kses_notice( $message ) {
	return wp_kses( $message,
		array_replace_recursive( // phpcs:ignore PHPCompatibility.PHP.NewFunctions.array_replace_recursiveFound
			wp_kses_allowed_html( 'post' ),
			array(
				'a' => array(
					'tabindex' => true,
				),
			)
		)
	);
}
