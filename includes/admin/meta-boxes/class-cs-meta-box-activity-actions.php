<?php
/**
 * Activity Actions
 *
 * Functions for displaying the activity actions meta box.
 *
 * @author      Lazutina
 * @category    Admin
 * @package     CommunityService/Admin/Meta Boxes
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CS_Meta_Box_Activity_Actions Class.
 */
class CS_Meta_Box_Activity_Actions {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function output( $post ) {
		global $theactivity;

		// This is used by some callbacks attached to hooks such as communityservice_activity_actions which rely on the global to determine if actions should be displayed for certain activities.
		if ( ! is_object( $theactivity ) ) {
			$theactivity = cs_get_activity( $post->ID );
		}

		?>
		<ul class="activity_actions submitbox">

			<?php do_action( 'communityservice_activity_actions_start', $post->ID ); ?>
			<li class="wide" style="height:10px;">
				<div id="delete-action">
					<?php
					if ( current_user_can( 'delete_post', $post->ID ) ) {

						if ( ! EMPTY_TRASH_DAYS ) {
							$delete_text = __( 'Delete permanently', 'communityservice' );
						} else {
							$delete_text = __( 'Move to trash', 'communityservice' );
						}
						?>
						<a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php echo esc_html( $delete_text ); ?></a>
						<?php
					}
					?>
				</div>

				<button type="submit" style="float:right" class="button save_activity button-primary" name="save" value="<?php echo 'auto-draft' === $post->post_status ? esc_attr__( 'Create', 'communityservice' ) : esc_attr__( 'Update', 'communityservice' ); ?>"><?php echo 'auto-draft' === $post->post_status ? esc_html__( 'Create', 'communityservice' ) : esc_html__( 'Update', 'communityservice' ); ?></button>
			</li>

			<?php do_action( 'communityservice_activity_actions_end', $post->ID ); ?>

		</ul>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post Object.
	 */
	public static function save( $post_id, $post ) {
		// Activity data saved, now get it so we can manipulate status.
		$activity = cs_get_activity( $post_id );

		// Handle button actions.
		if ( ! empty( $_POST['post_status'] ) ) { // @codingStandardsIgnoreLine

			$status = cs_clean( wp_unslash( $_POST['post_status'] ) ); // @codingStandardsIgnoreLine
			

			if ( 'cs-cancelled' === $status ) {


				CS()->mailer()->emails['CS_Email_Cancelled_Activity']->trigger( $activity->get_id(), $activity );

			}
			if ( 'cs-approved' === $status ) {


				CS()->mailer()->emails['CS_Email_Student_Approved_Activity']->trigger( $post_id, $post );

			}
		}
	}

	/**
	 * Set the correct message ID.
	 *
	 * @param string $location Location.
	 * @since  2.3.0
	 * @static
	 * @return string
	 */
	public static function set_email_sent_message( $location ) {
		return add_query_arg( 'message', 11, $location );
	}
}
