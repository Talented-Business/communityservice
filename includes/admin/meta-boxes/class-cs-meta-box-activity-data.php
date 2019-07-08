<?php
/**
 * Activity Data
 *
 * Functions for displaying the activity data meta box.
 *
 * @author      Lazutina
 * @category    Admin
 * @package     CommunityService/Admin/Meta Boxes
 * @version     2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * CS_Meta_Box_Activity_Data Class.
 */
class CS_Meta_Box_Activity_Data {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {
		global $theactivity;

		if ( ! is_object( $theactivity ) ) {
			$theactivity = cs_get_activity( $post->ID );
		}

		$activity = $theactivity;

		$activity_type_object = get_post_type_object( $post->post_type );
		wp_nonce_field( 'communityservice_save_data', 'communityservice_meta_nonce' );
		?>
		<style>
		#activity_data .activity_data_column{width:45%;float:left;}
		</style>
		<div class="panel-wrap communityservice">
			<input name="post_status" type="hidden" value="<?php echo esc_attr( $post->post_status ); ?>" />
			<div id="activity_data" class="panel communityservice-activity-data">
				<h3 class="communityservice-activity-data__heading">
					<?php

					/* translators: 1: activity type 2: activity number */
					printf(
						esc_html__( '%1$s #%2$s ', 'communityservice' ),
						esc_html( $activity_type_object->labels->singular_name ),
						esc_html( $activity->get_activity_number() )
					);

					?>
				</h3>
				<p class="communityservice-activity-data__meta activity_number">
					<?php
					$meta_list = array();

					$meta_list[] = sprintf(
						__( 'Submitted Date: %s', 'communityservice' ),
						'<span class="communityservice-Activity-studentIP">' . esc_html( date_i18n( 'Y-m-d H:i:s', strtotime( $post->post_date ) ) ) . '</span>'
					);
					if ( $student_id = $activity->get_student_id() ) {
						$student = get_user_by('id',$student_id);
						/* translators: %s: IP address */
						$meta_list[] = sprintf(
							__( 'Student Name: %s', 'communityservice' ),
							'<span class="communityservice-Activity-studentIP">' . esc_html( $student->display_name ) . '</span>'
						);
					}

					echo wp_kses_post( implode( '. ', $meta_list ) );

					?>
				</p>
				<div class="activity_data_column_container">
					<div class="activity_data_column">
						<h4><?php esc_html_e( 'General', 'communityservice' ); ?></h4>

						<p class="form-field form-field-wide">
							<label for="activity_date"><?php _e( 'Activity Date:', 'communityservice' ); ?></label>
							<input type="text" class="date-picker" name="activity_date" maxlength="10" value="<?php echo esc_attr( date_i18n( 'Y-m-d', strtotime( $activity->get_activity_date() ) ) ); ?>" pattern="<?php echo esc_attr( apply_filters( 'communityservice_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" style="width:100px;"/>
						</p>

						<p class="form-field form-field-wide cs-activity-status">
							<label for="activity_status">
								<?php
								_e( 'Status:', 'communityservice' );
								?>
							</label>
							<select id="activity_status" name="activity_status" class="cs-enhanced-select">
								<?php
								$statuses = cs_get_activity_statuses();
								foreach ( $statuses as $status => $status_name ) {
									echo '<option value="' . esc_attr( $status ) . '" ' . selected( $status, 'cs-' . $activity->get_status( 'edit' ), false ) . '>' . esc_html( $status_name ) . '</option>';
								}
								?>
							</select>
						</p>

						<?php do_action( 'communityservice_admin_activity_data_after_activity_details', $activity ); ?>
					</div>
					<div class="activity_data_column">
						<h4><?php esc_html_e( 'Other', 'communityservice' ); ?></h4>
							<p class="form-field form-field-wide">
								<label for="activity_date"><?php _e( 'Type:', 'communityservice' ); ?></label>
								<?php if($activity->get_parent_id()>0){?>internal
									<?php $task_link = admin_url( 'post.php?post=' . $activity->get_parent_id() . '&action=edit' );?>
									<a href="<?=$task_link?>">Task #<?=$activity->get_parent_id()?></a>
								<?php }else{?>
									external
								<?php }?>
							</p>
							<?php if($activity->get_attachment()>0){?>
								<p class="form-field form-field-wide cs-activity-status">
									<label for="activity_status">
										<?php
										_e( 'Attachment:', 'communityservice' );
										?>
									</label>
									<a href="#"><?=get_the_title( $activity->get_attachment() );?></a>
								</p>
							<?php }?>	
							<?php do_action( 'communityservice_admin_activity_data_after_activity_details', $activity ); ?>
						</div>
					</div>
				</div>
				<div class="clear"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $activity_id Activity ID.
	 */
	public static function save( $activity_id ) {
		
		// Get activity object.
		$activity = cs_get_activity( $activity_id );
		$props = array();

		// Update date.
		if ( empty( $_POST['activity_date'] ) ) {
			$date = current_time( 'timestamp', true );
		} else {
			$date = date( 'Y-m-d', strtotime( $_POST['activity_date']));
		}

		$props['activity_date'] = $date;

		// Save activity data.
		$activity->set_props( $props );
		$activity->set_status( cs_clean( $_POST['activity_status'] ), '', true );
		$activity->save();
	}
}
