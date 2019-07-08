<?php
/**
 * Edit account form
 *
 */

defined( 'ABSPATH' ) || exit;

do_action( 'communityservice_before_edit_account_form' ); ?>
<div class="vc_custom_1554393564526">
<form class="communityservice-EditAccountForm edit-account " action="" method="post" <?php do_action( 'communityservice_edit_account_form_tag' ); ?> >

	<?php do_action( 'communityservice_edit_account_form_start' ); ?>

	<p class="communityservice-form-row communityservice-form-row--first form-row form-row-first">
		<label for="account_first_name"><?php esc_html_e( 'First name', 'communityservice' ); ?>&nbsp;<span class="required">*</span></label>
		<input type="text" class="communityservice-Input communityservice-Input--text input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr( $user->first_name ); ?>" />
	</p>
	<p class="communityservice-form-row communityservice-form-row--last form-row form-row-last">
		<label for="account_last_name"><?php esc_html_e( 'Last name', 'communityservice' ); ?>&nbsp;<span class="required">*</span></label>
		<input type="text" class="communityservice-Input communityservice-Input--text input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr( $user->last_name ); ?>" />
	</p>
	<div class="clear"></div>

	<p class="communityservice-form-row communityservice-form-row--wide form-row form-row-wide">
		<label for="account_display_name"><?php esc_html_e( 'Display name', 'communityservice' ); ?>&nbsp;<span class="required">*</span></label>
		<input type="text" class="communityservice-Input communityservice-Input--text input-text" name="account_display_name" id="account_display_name" value="<?php echo esc_attr( $user->display_name ); ?>" /> <span><em><?php esc_html_e( 'This will be how your name will be displayed in the account section and in reviews', 'communityservice' ); ?></em></span>
	</p>
	<div class="clear"></div>

	<p class="communityservice-form-row communityservice-form-row--wide form-row form-row-wide">
		<label for="account_email"><?php esc_html_e( 'Email address', 'communityservice' ); ?>&nbsp;<span class="required">*</span></label>
		<input type="email" class="communityservice-Input communityservice-Input--email input-text" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr( $user->user_email ); ?>" />
	</p>

	<fieldset>
		<legend><?php esc_html_e( 'Password change', 'communityservice' ); ?></legend>

		<p class="communityservice-form-row communityservice-form-row--wide form-row form-row-wide">
			<label for="password_current"><?php esc_html_e( 'Current password (leave blank to leave unchanged)', 'communityservice' ); ?></label>
			<input type="password" class="communityservice-Input communityservice-Input--password input-text" name="password_current" id="password_current" autocomplete="off" />
		</p>
		<p class="communityservice-form-row communityservice-form-row--wide form-row form-row-wide">
			<label for="password_1"><?php esc_html_e( 'New password (leave blank to leave unchanged)', 'communityservice' ); ?></label>
			<input type="password" class="communityservice-Input communityservice-Input--password input-text" name="password_1" id="password_1" autocomplete="off" />
		</p>
		<p class="communityservice-form-row communityservice-form-row--wide form-row form-row-wide">
			<label for="password_2"><?php esc_html_e( 'Confirm new password', 'communityservice' ); ?></label>
			<input type="password" class="communityservice-Input communityservice-Input--password input-text" name="password_2" id="password_2" autocomplete="off" />
		</p>
	</fieldset>
	<div class="clear"></div>

	<?php do_action( 'communityservice_edit_account_form' ); ?>

	<p>
		<?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
		<button type="submit" class="communityservice-Button button" name="save_account_details" value="<?php esc_attr_e( 'Save changes', 'communityservice' ); ?>"><?php esc_html_e( 'Save changes', 'communityservice' ); ?></button>
		<input type="hidden" name="action" value="save_account_details" />
	</p>

	<?php do_action( 'communityservice_edit_account_form_end' ); ?>
</form>

<?php do_action( 'communityservice_after_edit_account_form' ); ?>
</div>
