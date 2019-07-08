<?php
/**
 * Activity details table shown in emails.
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/email-activity-details.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$text_align = is_rtl() ? 'right' : 'left';

do_action( 'communityservice_email_before_activity_table', $activity, $sent_to_admin, $plain_text, $email ); ?>

<h2>
	<?php
	if ( $sent_to_admin ) {
		$before = '<a class="link" href="' . esc_url( $activity->get_edit_activity_url() ) . '">';
		$after  = '</a>';
	} else {
		$before = '';
		$after  = '';
	}
	/* translators: %s: Activity ID. */
	echo wp_kses_post( $before . sprintf( __( '[Activity #%s]', 'communityservice' ) . $after . ' (<time datetime="%s">%s</time>)', $activity->get_activity_number(), $activity->get_date_created()->format( 'c' ), cs_format_datetime( $activity->get_date_created() ) ) );
	?>
</h2>

<?php do_action( 'communityservice_email_after_activity_table', $activity, $sent_to_admin, $plain_text, $email ); ?>
