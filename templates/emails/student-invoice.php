<?php
/**
 * Student invoice email
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/student-invoice.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Executes the e-mail header.
 *
 * @hooked CS_Emails::email_header() Output the email header
 */
do_action( 'communityservice_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Student first name */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'communityservice' ), esc_html( $activity->get_billing_first_name() ) ); ?></p>

<?php if ( $activity->has_status( 'pending' ) ) { ?>
	<p>
	<?php
	printf(
		wp_kses(
			/* translators: %1$s Site title, %2$s Activity pay link */
			__( 'An activity has been created for you on %1$s. Your invoice is below, with a link to make payment when you’re ready: %2$s', 'communityservice' ),
			array(
				'a' => array(
					'href' => array(),
				),
			)
		),
		esc_html( get_bloginfo( 'name', 'display' ) ),
		'<a href="' . esc_url( $activity->get_checkout_payment_url() ) . '">' . esc_html__( 'Pay for this activity', 'communityservice' ) . '</a>'
	);
	?>
	</p>

<?php } else { ?>
	<p>
	<?php
		/* translators: %s Activity date */
		printf( esc_html__( 'Here are the details of your activity placed on %s:', 'communityservice' ), esc_html( cs_format_datetime( $activity->get_date_created() ) ) );
	?>
	</p>
<?php
}

/**
 * Hook for the communityservice_email_activity_details.
 *
 * @hooked CS_Emails::activity_details() Shows the activity details table.
 * @hooked CS_Structured_Data::generate_activity_data() Generates structured data.
 * @hooked CS_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'communityservice_email_activity_details', $activity, $sent_to_admin, $plain_text, $email );

/**
 * Hook for the communityservice_email_activity_meta.
 *
 * @hooked CS_Emails::activity_meta() Shows activity meta data.
 */
do_action( 'communityservice_email_activity_meta', $activity, $sent_to_admin, $plain_text, $email );

/**
 * Hook for communityservice_email_student_details.
 *
 * @hooked CS_Emails::student_details() Shows student details
 * @hooked CS_Emails::email_address() Shows email address
 */
do_action( 'communityservice_email_student_details', $activity, $sent_to_admin, $plain_text, $email );

?>
<p>
<?php esc_html_e( 'Thanks for reading.', 'communityservice' ); ?>
</p>
<?php

/**
 * Executes the email footer.
 *
 * @hooked CS_Emails::email_footer() Output the email footer
 */
do_action( 'communityservice_email_footer', $email );
