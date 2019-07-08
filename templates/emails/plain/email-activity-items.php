<?php
/**
 * Email Activity Items (plain)
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/plain/email-activity-items.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

foreach ( $items as $item_id => $item ) :
	if ( apply_filters( 'communityservice_activity_item_visible', true, $item ) ) {
		$task = $item->get_task();
		echo apply_filters( 'communityservice_activity_item_name', $item->get_name(), $item, false );
		if ( $show_sku && $task->get_sku() ) {
			echo ' (#' . $task->get_sku() . ')';
		}
		echo ' X ' . apply_filters( 'communityservice_email_activity_item_quantity', $item->get_quantity(), $item );
		echo ' = ' . $activity->get_formatted_line_subtotal( $item ) . "\n";

		// allow other plugins to add additional task information here
		do_action( 'communityservice_activity_item_meta_start', $item_id, $item, $activity, $plain_text );
		echo strip_tags( cs_display_item_meta( $item, array(
			'before'    => "\n- ",
			'separator' => "\n- ",
			'after'     => "",
			'echo'      => false,
			'autop'     => false,
		) ) );

		// allow other plugins to add additional task information here
		do_action( 'communityservice_activity_item_meta_end', $item_id, $item, $activity, $plain_text );
	}
	// Note
	if ( $show_purchase_note && is_object( $task ) && ( $purchase_note = $task->get_purchase_note() ) ) {
		echo "\n" . do_shortcode( wp_kses_post( $purchase_note ) );
	}
	echo "\n\n";
endforeach;
