<?php
/**
 * Admin cancelled activity email
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/admin-cancelled-activity.php.
 *
 * HOWEVER, on occasion CommunityService will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked CS_Emails::email_header() Output the email header
*/
do_action( 'communityservice_email_header', $email_heading, $email ); ?>

<?php /* translators: %1$s: Student full name. %2$s: Activity numer */ ?>
<p><?php printf( esc_html__( 'Alas. Just to let you know &mdash; %1$s has not approved activity #%2$s:', 'communityservice' ), esc_html( $activity->get_student()->display_name ), esc_html( $activity->get_activity_number() ) ); ?></p>

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
<?php esc_html_e( 'Thanks for reading.', 'communityservice' ); ?>
</p>
<?php

/*
 * @hooked CS_Emails::email_footer() Output the email footer
 */
do_action( 'communityservice_email_footer', $email );
