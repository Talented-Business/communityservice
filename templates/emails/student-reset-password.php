<?php
/**
 * Student Reset Password email
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/student-reset-password.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<?php do_action( 'communityservice_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Student first name */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'communityservice' ), esc_html( $user_login ) ); ?>
<?php /* translators: %s: Store name */ ?>
<p><?php printf( esc_html__( 'Someone has requested a new password for the following account on %s:', 'communityservice' ), esc_html( wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ) ); ?></p>
<?php /* translators: %s Student username */ ?>
<p><?php printf( esc_html__( 'Username: %s', 'communityservice' ), esc_html( $user_login ) ); ?></p>
<p><?php esc_html_e( 'If you didn\'t make this request, just ignore this email. If you\'d like to proceed:', 'communityservice' ); ?></p>
<p>
	<a class="link" href="<?php echo esc_url( add_query_arg( array( 'key' => $reset_key, 'id' => $user_id ), cs_get_endpoint_url( 'lost-password', '', cs_get_page_permalink( 'myaccount' ) ) ) ); ?>"><?php // phpcs:ignore ?>
		<?php esc_html_e( 'Click here to reset your password', 'communityservice' ); ?>
	</a>
</p>
<p><?php esc_html_e( 'Thanks for reading.', 'communityservice' ); ?></p>

<?php do_action( 'communityservice_email_footer', $email ); ?>
