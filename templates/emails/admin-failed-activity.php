<?php
/**
 * Admin failed activity email
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/admin-failed-activity.php
 *
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked CS_Emails::email_header() Output the email header
 */
do_action( 'communityservice_email_header', $email_heading, $email ); ?>

<?php /* translators: %1$s: Activity number. %2$s: Student full name. */ ?>
<p><?php printf( esc_html__( 'Payment for activity #%1$s from %2$s has failed. The activity was as follows:', 'communityservice' ), esc_html( $activity->get_activity_number() ), esc_html( $activity->get_student()->display_name ) ); ?></p>

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

</p>
<?php

/*
 * @hooked CS_Emails::email_footer() Output the email footer
*/
do_action( 'communityservice_email_footer', $email );
