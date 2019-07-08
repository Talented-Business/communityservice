<?php
/**
 * Activity Form Shortcodes
 *
 *
 * @package CommunityService/Shortcodes/Activity_Submit
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode Activity Submit class.
 */
class CS_Shortcode_Activity_Form {

	/**
	 * Get the shortcode content.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function get( $atts ) {
		return CS_Shortcodes::shortcode_wrapper( array( __CLASS__, 'output' ), $atts );
	}

	/**
	 * Output the shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 */
	public static function output( $atts ) {
		global $wp;
        // Start output buffer since the html may need discarding for BW compatibility.
        ob_start();

		cs_get_template(
			'activity/form.php'
		);

        // Send output buffer.
        ob_end_flush();
	}

}
