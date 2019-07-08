<?php
/**
 * Student invoice email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/plain/student-invoice.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo '= ' . esc_html( $email_heading ) . " =\n\n";

/* translators: %s: Student first name */
echo sprintf( esc_html__( 'Hi %s,', 'communityservice' ), esc_html( $activity->get_billing_first_name() ) ) . "\n\n";

if ( $activity->has_status( 'pending' ) ) {
	echo sprintf(
		/* translators: %1$s Site title, %2$s Activity pay link */
		__( 'An activity has been created for you on %1$s. Your invoice is below, with a link to make payment when you’re ready: %2$s', 'communityservice' ),
		esc_html( get_bloginfo( 'name', 'display' ) ),
		esc_url( $activity->get_checkout_payment_url() )
	) . "\n\n";

} else {
	/* translators: %s Activity date */
	echo sprintf( esc_html__( 'Here are the details of your activity placed on %s:', 'communityservice' ), esc_html( cs_format_datetime( $activity->get_date_created() ) ) ) . "\n\n";
}
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Hook for the communityservice_email_activity_details.
 *
 * @hooked CS_Emails::activity_details() Shows the activity details table.
 * @hooked CS_Structured_Data::generate_activity_data() Generates structured data.
 * @hooked CS_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'communityservice_email_activity_details', $activity, $sent_to_admin, $plain_text, $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Hook for the communityservice_email_activity_meta.
 *
 * @hooked CS_Emails::activity_meta() Shows activity meta data.
 */
do_action( 'communityservice_email_activity_meta', $activity, $sent_to_admin, $plain_text, $email );

/**
 * Hook for communityservice_email_student_details
 *
 * @hooked CS_Emails::student_details() Shows student details
 * @hooked CS_Emails::email_address() Shows email address
 */
do_action( 'communityservice_email_student_details', $activity, $sent_to_admin, $plain_text, $email );

echo esc_html__( 'Thanks for reading.', 'communityservice' ) . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html( apply_filters( 'communityservice_email_footer_text', get_option( 'communityservice_email_footer_text' ) ) );
