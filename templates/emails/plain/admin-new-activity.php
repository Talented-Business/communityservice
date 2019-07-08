<?php
/**
 * Admin new activity email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/plain/admin-new-activity.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo '= ' . esc_html( $email_heading ) . " =\n\n";

/* translators: %s: Student billing full name */
echo sprintf( esc_html__( 'You’ve received the following activity from %s:', 'communityservice' ), esc_html( $activity->get_student()->display_name ) ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/*
 * @hooked CS_Emails::activity_details() Shows the activity details table.
 * @hooked CS_Structured_Data::generate_activity_data() Generates structured data.
 * @hooked CS_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'communityservice_email_activity_details', $activity, $sent_to_admin, $plain_text, $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/*
 * @hooked CS_Emails::activity_meta() Shows activity meta data.
 */
do_action( 'communityservice_email_activity_meta', $activity, $sent_to_admin, $plain_text, $email );

/*
 * @hooked CS_Emails::student_details() Shows student details
 * @hooked CS_Emails::email_address() Shows email address
 */
do_action( 'communityservice_email_student_details', $activity, $sent_to_admin, $plain_text, $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html( apply_filters( 'communityservice_email_footer_text', get_option( 'communityservice_email_footer_text' ) ) );
