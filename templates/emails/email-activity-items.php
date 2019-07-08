<?php
/**
 * Email Activity Items
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/email-activity-items.php.
 *
 */

defined( 'ABSPATH' ) || exit;

$text_align  = is_rtl() ? 'right' : 'left';
$margin_side = is_rtl() ? 'left' : 'right';

foreach ( $items as $item_id => $item ) :
	$task       = $item->get_task();
	$sku           = '';
	$purchase_note = '';
	$image         = '';

	if ( ! apply_filters( 'communityservice_activity_item_visible', true, $item ) ) {
		continue;
	}

	if ( is_object( $task ) ) {
		$sku           = $task->get_sku();
		$purchase_note = $task->get_purchase_note();
		$image         = $task->get_image( $image_size );
	}

	?>
	<tr class="<?php echo esc_attr( apply_filters( 'communityservice_activity_item_class', 'activity_item', $item, $activity ) ); ?>">
		<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
		<?php

		// Show title/image etc.
		if ( $show_image ) {
			echo wp_kses_post( apply_filters( 'communityservice_activity_item_thumbnail', $image, $item ) );
		}

		// Task name.
		echo wp_kses_post( apply_filters( 'communityservice_activity_item_name', $item->get_name(), $item, false ) );

		// SKU.
		if ( $show_sku && $sku ) {
			echo wp_kses_post( ' (#' . $sku . ')' );
		}

		// allow other plugins to add additional task information here.
		do_action( 'communityservice_activity_item_meta_start', $item_id, $item, $activity, $plain_text );

		cs_display_item_meta(
			$item,
			array(
				'label_before' => '<strong class="cs-item-meta-label" style="float: ' . esc_attr( $text_align ) . '; margin-' . esc_attr( $margin_side ) . ': .25em; clear: both">',
			)
		);

		// allow other plugins to add additional task information here.
		do_action( 'communityservice_activity_item_meta_end', $item_id, $item, $activity, $plain_text );

		?>
		</td>
		<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
			<?php echo wp_kses_post( apply_filters( 'communityservice_email_activity_item_quantity', $item->get_quantity(), $item ) ); ?>
		</td>
		<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
			<?php echo wp_kses_post( $activity->get_formatted_line_subtotal( $item ) ); ?>
		</td>
	</tr>
	<?php

	if ( $show_purchase_note && $purchase_note ) {
		?>
		<tr>
			<td colspan="3" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
				<?php
				echo wp_kses_post( wpautop( do_shortcode( $purchase_note ) ) );
				?>
			</td>
		</tr>
		<?php
	}
	?>

<?php endforeach; ?>
