<?php
/**
 * Email Styles
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/email-styles.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load colors.
$bg        = "#f7f7f7";//get_option( 'communityservice_email_background_color' );
$body      = "#ffffff";//get_option( 'communityservice_email_body_background_color' );
$base      = "#6cabdd";//get_option( 'communityservice_email_base_color' );
$base_text = cs_light_or_dark( $base, '#202020', '#ffffff' );
$text      = get_option( 'communityservice_email_text_color' );

// Pick a contrasting color for links.
$link_color = cs_hex_is_light( $base ) ? $base : $base_text;

if ( cs_hex_is_light( $body ) ) {
	$link_color = cs_hex_is_light( $base ) ? $base_text : $base;
}

$bg_darker_10    = cs_hex_darker( $bg, 10 );
$body_darker_10  = cs_hex_darker( $body, 10 );
$base_lighter_20 = cs_hex_lighter( $base, 20 );
$base_lighter_40 = cs_hex_lighter( $base, 40 );
$text_lighter_20 = cs_hex_lighter( $text, 20 );

// !important; is a gmail hack to prevent styles being stripped if it doesn't like something.
// body{padding: 0;} ensures proper scale/positioning of the email in the iOS native email app.
?>
body {
	padding: 0;
}

#wrapper {
	background-color: <?php echo esc_attr( $bg ); ?>;
	margin: 0;
	padding: 70px 0 70px 0;
	-webkit-text-size-adjust: none !important;
	width: 100%;
}

#template_container {
	box-shadow: 0 1px 4px rgba(0,0,0,0.1) !important;
	background-color: <?php echo esc_attr( $body ); ?>;
	bactivity: 1px solid <?php echo esc_attr( $bg_darker_10 ); ?>;
	bactivity-radius: 3px !important;
}

#template_header {
	background-color: <?php echo esc_attr( $base ); ?>;
	bactivity-radius: 3px 3px 0 0 !important;
	color: <?php echo esc_attr( $base_text ); ?>;
	bactivity-bottom: 0;
	font-weight: bold;
	line-height: 100%;
	vertical-align: middle;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
}

#template_header h1,
#template_header h1 a {
	color: <?php echo esc_attr( $base_text ); ?>;
}

#template_footer td {
	padding: 0;
	-webkit-bactivity-radius: 6px;
}

#template_footer #credit {
	bactivity:0;
	color: <?php echo esc_attr( $base_lighter_40 ); ?>;
	font-family: Arial;
	font-size:12px;
	line-height:125%;
	text-align:center;
	padding: 0 48px 48px 48px;
}

#body_content {
	background-color: <?php echo esc_attr( $body ); ?>;
}

#body_content table td {
	padding: 48px 48px 0;
}

#body_content table td td {
	padding: 12px;
}

#body_content table td th {
	padding: 12px;
}

#body_content td ul.cs-item-meta {
	font-size: small;
	margin: 1em 0 0;
	padding: 0;
	list-style: none;
}

#body_content td ul.cs-item-meta li {
	margin: 0.5em 0 0;
	padding: 0;
}

#body_content td ul.cs-item-meta li p {
	margin: 0;
}

#body_content p {
	margin: 0 0 16px;
}

#body_content_inner {
	color: <?php echo esc_attr( $text_lighter_20 ); ?>;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
	font-size: 14px;
	line-height: 150%;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

.td {
	color: <?php echo esc_attr( $text_lighter_20 ); ?>;
	bactivity: 1px solid <?php echo esc_attr( $body_darker_10 ); ?>;
	vertical-align: middle;
}

.address {
	padding: 12px;
	color: <?php echo esc_attr( $text_lighter_20 ); ?>;
	bactivity: 1px solid <?php echo esc_attr( $body_darker_10 ); ?>;
}

.text {
	color: <?php echo esc_attr( $text ); ?>;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
}

.link {
	color: <?php echo esc_attr( $base ); ?>;
}

#header_wrapper {
	padding: 36px 48px;
	display: block;
}

h1 {
	color: <?php echo esc_attr( $base ); ?>;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
	font-size: 30px;
	font-weight: 300;
	line-height: 150%;
	margin: 0;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
	text-shadow: 0 1px 0 <?php echo esc_attr( $base_lighter_20 ); ?>;
}

h2 {
	color: <?php echo esc_attr( $base ); ?>;
	display: block;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
	font-size: 18px;
	font-weight: bold;
	line-height: 130%;
	margin: 0 0 18px;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

h3 {
	color: <?php echo esc_attr( $base ); ?>;
	display: block;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
	font-size: 16px;
	font-weight: bold;
	line-height: 130%;
	margin: 16px 0 8px;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

a {
	color: <?php echo esc_attr( $link_color ); ?>;
	font-weight: normal;
	text-decoration: underline;
}

img {
	bactivity: none;
	display: inline-block;
	font-size: 14px;
	font-weight: bold;
	height: auto;
	outline: none;
	text-decoration: none;
	text-transform: capitalize;
	vertical-align: middle;
	margin-<?php echo is_rtl() ? 'left' : 'right'; ?>: 10px;
}
<?php
