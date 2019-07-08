<?php
/**
 * Student refunded activity email
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/student-refunded-activity.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked CS_Emails::email_header() Output the email header
 */
do_action( 'communityservice_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Student first name */ ?>
<p><?php printf( __( 'Hi %s,', 'communityservice' ), $activity->get_billing_first_name() ); ?></p><?php // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>

<p>
<?php
if ( $partial_refund ) {
	/* translators: %s: Site title */
	printf( __( 'Your activity on %s has been partially refunded. There are more details below for your reference:', 'communityservice' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
} else {
	/* translators: %s: Site title */
	printf( __( 'Your activity on %s has been refunded. There are more details below for your reference:', 'communityservice' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
}
?>
</p>
<?php

/*
 * @hooked CS_Emails::activity_details() Shows the activity details table.
 * @hooked CS_Structured_Data::generate_activity_data() Generates structured data.
 * @hooked CS_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'communityservice_email_activity_details', $activity, $sent_to_admin, $plain_text, $email );

/*
 * @hooked CS_Emails::activity_meta() Shows activity meta data.
 */
do_action( 'communityservice_email_activity_meta', $activity, $sent_to_admin, $plain_text, $email );

/*
 * @hooked CS_Emails::student_details() Shows student details
 * @hooked CS_Emails::email_address() Shows email address
 */
do_action( 'communityservice_email_student_details', $activity, $sent_to_admin, $plain_text, $email );

?>
<p>
<?php _e( 'We hope to see you again soon.', 'communityservice' ); // phpcs:ignore WordPress.XSS.EscapeOutput ?>
</p>
<?php

/*
 * @hooked CS_Emails::email_footer() Output the email footer
 */
do_action( 'communityservice_email_footer', $email );
