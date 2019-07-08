<?php
/**
 * Additional Student Details
 *
 * This is extra student data which can be filtered by plugins. It outputs below the activity item table.
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/email-student-details.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<?php if ( ! empty( $fields ) ) : ?>
	<div style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;">
		<h2><?php _e( 'Student details', 'communityservice' ); ?></h2>
		<ul>
			<?php foreach ( $fields as $field ) : ?>
				<li><strong><?php echo wp_kses_post( $field['label'] ); ?>:</strong> <span class="text"><?php echo wp_kses_post( $field['value'] ); ?></span></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>
