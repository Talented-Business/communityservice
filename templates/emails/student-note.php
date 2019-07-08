<?php
/**
 * Student note email
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/student-note.php.
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
<p><?php printf( esc_html__( 'Hi %s,', 'communityservice' ), esc_html( $activity->get_billing_first_name() ) ); ?></p>
<p><?php esc_html_e( 'The following note has been added to your activity:', 'communityservice' ); ?></p>

<blockquote><?php echo wpautop( wptexturize( $student_note ) ); ?></blockquote><?php // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>

<p><?php esc_html_e( 'As a reminder, here are your activity details:', 'communityservice' ); ?></p>

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
<p><?php esc_html_e( 'Thanks for reading.', 'communityservice' ); ?></p>
<?php

/*
 * @hooked CS_Emails::email_footer() Output the email footer
 */
do_action( 'communityservice_email_footer', $email );
