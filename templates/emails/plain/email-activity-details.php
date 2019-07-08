<?php
/**
 * Activity details table shown in emails.
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/plain/email-activity-details.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'communityservice_email_before_activity_table', $activity, $sent_to_admin, $plain_text, $email );

/* translators: %1$s: Activity ID. %2$s: Activity date */
echo wp_kses_post( cs_strtoupper( sprintf( __( '[Activity #%1$s] (%2$s)', 'communityservice' ), $activity->get_activity_number(), cs_format_datetime( $activity->get_date_created() ) ) ) ) . "\n";
echo "\n" . cs_get_email_activity_items( $activity, array( // WPCS: XSS ok.
	'show_sku'      => $sent_to_admin,
	'show_image'    => false,
	'image_size'    => array( 32, 32 ),
	'plain_text'    => true,
	'sent_to_admin' => $sent_to_admin,
) );

echo "==========\n\n";

$totals = $activity->get_activity_item_totals();

if ( $totals ) {
	foreach ( $totals as $total ) {
		echo wp_kses_post( $total['label'] . "\t " . $total['value'] ) . "\n";
	}
}

if ( $activity->get_student_note() ) {
	echo esc_html__( 'Note:', 'communityservice' ) . "\t " . wp_kses_post( wptexturize( $activity->get_student_note() ) ) . "\n";
}

if ( $sent_to_admin ) {
	/* translators: %s: Activity link. */
	echo "\n" . sprintf( esc_html__( 'View activity: %s', 'communityservice' ), esc_url( $activity->get_edit_activity_url() ) ) . "\n";
}

do_action( 'communityservice_email_after_activity_table', $activity, $sent_to_admin, $plain_text, $email );
