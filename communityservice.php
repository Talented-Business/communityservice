<?php
/**
 * Plugin Name: Christian Brothers High School - Justice & Peace Passport
 * Plugin URI: https://#
 * Description: This solution encourages and awards students for completing tasks relating to community service 
 * and honorable mentions in the community.
 * Version: 1.0
 * Author: Lazutina
 * Author URI: https://#
 * Text Domain: Christian Brothers
 * Domain Path: /i18n/languages/
 *
 * @package jpp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define CS_PLUGIN_FILE.
if ( ! defined( 'CS_PLUGIN_FILE' ) ) {
	define( 'CS_PLUGIN_FILE', __FILE__ );
}
define( 'CS_STUDENT_YEAR', 8 );
// Include the main CommunityService class.
if ( ! class_exists( 'CommunityService' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-communityservice.php';
}

/**
 * Main instance of CommunityService.
 *
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @since  2.1
 * @return CommunityService
 */
function cs() {
	return CommunityService::instance();
}

// Global for backwards compatibility.
$GLOBALS['CommunityService'] = cs();
