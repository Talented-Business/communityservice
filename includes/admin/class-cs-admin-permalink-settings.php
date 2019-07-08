<?php
/**
 * Adds settings to the permalinks admin settings page
 *
 * @class       CS_Admin_Permalink_Settings
 * @category    Admin
 * @package     CommunitService/Admin
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'CS_Admin_Permalink_Settings', false ) ) {
	return new CS_Admin_Permalink_Settings();
}

/**
 * CS_Admin_Permalink_Settings Class.
 */
class CS_Admin_Permalink_Settings {

	/**
	 * Permalink settings.
	 *
	 * @var array
	 */
	private $permalinks = array();

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		$this->settings_init();
		$this->settings_save();
	}

	/**
	 * Init our settings.
	 */
	public function settings_init() {
		add_settings_section( 'communitservice-permalink', __( 'Task permalinks', 'communitservice' ), array( $this, 'settings' ), 'permalink' );

		add_settings_field(
			'communitservice_task_category_slug',
			__( 'Task category base', 'communitservice' ),
			array( $this, 'task_category_slug_input' ),
			'permalink',
			'optional'
		);
		add_settings_field(
			'communitservice_task_tag_slug',
			__( 'Task tag base', 'communitservice' ),
			array( $this, 'task_tag_slug_input' ),
			'permalink',
			'optional'
		);
		add_settings_field(
			'communitservice_task_attribute_slug',
			__( 'Task attribute base', 'communitservice' ),
			array( $this, 'task_attribute_slug_input' ),
			'permalink',
			'optional'
		);

		$this->permalinks = cs_get_permalink_structure();
	}

	/**
	 * Show a slug input box.
	 */
	public function task_category_slug_input() {
		?>
		<input name="communitservice_task_category_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['category_base'] ); ?>" placeholder="<?php echo esc_attr_x( 'task-category', 'slug', 'communitservice' ); ?>" />
		<?php
	}

	/**
	 * Show a slug input box.
	 */
	public function task_tag_slug_input() {
		?>
		<input name="communitservice_task_tag_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['tag_base'] ); ?>" placeholder="<?php echo esc_attr_x( 'task-tag', 'slug', 'communitservice' ); ?>" />
		<?php
	}

	/**
	 * Show a slug input box.
	 */
	public function task_attribute_slug_input() {
		?>
		<input name="communitservice_task_attribute_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['attribute_base'] ); ?>" /><code>/attribute-name/attribute/</code>
		<?php
	}

	/**
	 * Show the settings.
	 */
	public function settings() {
		/* translators: %s: Home URL */
		echo wp_kses_post( wpautop( sprintf( __( 'If you like, you may enter custom structures for your task URLs here. For example, using <code>cservice</code> would make your task links like <code>%scservice/sample-task/</code>. This setting affects task URLs only, not things such as task categories.', 'communitservice' ), esc_url( home_url( '/' ) ) ) ) );

		$cservice_page_id = cs_get_page_id( 'cservice' );
		$base_slug    = urldecode( ( $cservice_page_id > 0 && get_post( $cservice_page_id ) ) ? get_page_uri( $cservice_page_id ) : _x( 'cservice', 'default-slug', 'communitservice' ) );
		$task_base = _x( 'task', 'default-slug', 'communitservice' );

		$structures = array(
			0 => '',
			1 => '/' . trailingslashit( $base_slug ),
			2 => '/' . trailingslashit( $base_slug ) . trailingslashit( '%task_cat%' ),
		);
		?>
		<table class="form-table cs-permalink-structure">
			<tbody>
				<tr>
					<th><label><input name="task_permalink" type="radio" value="<?php echo esc_attr( $structures[0] ); ?>" class="cstog" <?php checked( $structures[0], $this->permalinks['task_base'] ); ?> /> <?php esc_html_e( 'Default', 'communitservice' ); ?></label></th>
					<td><code class="default-example"><?php echo esc_html( home_url() ); ?>/?task=sample-task</code> <code class="non-default-example"><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $task_base ); ?>/sample-task/</code></td>
				</tr>
				<?php if ( $cservice_page_id ) : ?>
					<tr>
						<th><label><input name="task_permalink" type="radio" value="<?php echo esc_attr( $structures[1] ); ?>" class="cstog" <?php checked( $structures[1], $this->permalinks['task_base'] ); ?> /> <?php esc_html_e( 'Sservice base', 'communitservice' ); ?></label></th>
						<td><code><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $base_slug ); ?>/sample-task/</code></td>
					</tr>
					<tr>
						<th><label><input name="task_permalink" type="radio" value="<?php echo esc_attr( $structures[2] ); ?>" class="cstog" <?php checked( $structures[2], $this->permalinks['task_base'] ); ?> /> <?php esc_html_e( 'Sservice base with category', 'communitservice' ); ?></label></th>
						<td><code><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $base_slug ); ?>/task-category/sample-task/</code></td>
					</tr>
				<?php endif; ?>
				<tr>
					<th><label><input name="task_permalink" id="communitservice_custom_selection" type="radio" value="custom" class="tog" <?php checked( in_array( $this->permalinks['task_base'], $structures, true ), false ); ?> />
						<?php esc_html_e( 'Custom base', 'communitservice' ); ?></label></th>
					<td>
						<input name="task_permalink_structure" id="communitservice_permalink_structure" type="text" value="<?php echo esc_attr( $this->permalinks['task_base'] ? trailingslashit( $this->permalinks['task_base'] ) : '' ); ?>" class="regular-text code"> <span class="description"><?php esc_html_e( 'Enter a custom base to use. A base must be set or WordPress will use default instead.', 'communitservice' ); ?></span>
					</td>
				</tr>
			</tbody>
		</table>
		<?php wp_nonce_field( 'cs-permalinks', 'cs-permalinks-nonce' ); ?>
		<script type="text/javascript">
			jQuery( function() {
				jQuery('input.cstog').change(function() {
					jQuery('#communitservice_permalink_structure').val( jQuery( this ).val() );
				});
				jQuery('.permalink-structure input').change(function() {
					jQuery('.cs-permalink-structure').find('code.non-default-example, code.default-example').hide();
					if ( jQuery(this).val() ) {
						jQuery('.cs-permalink-structure code.non-default-example').show();
						jQuery('.cs-permalink-structure input').removeAttr('disabled');
					} else {
						jQuery('.cs-permalink-structure code.default-example').show();
						jQuery('.cs-permalink-structure input:eq(0)').click();
						jQuery('.cs-permalink-structure input').attr('disabled', 'disabled');
					}
				});
				jQuery('.permalink-structure input:checked').change();
				jQuery('#communitservice_permalink_structure').focus( function(){
					jQuery('#communitservice_custom_selection').click();
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Save the settings.
	 */
	public function settings_save() {
		if ( ! is_admin() ) {
			return;
		}

		// We need to save the options ourselves; settings api does not trigger save for the permalinks page.
		if ( isset( $_POST['permalink_structure'], $_POST['cs-permalinks-nonce'], $_POST['communitservice_task_category_slug'], $_POST['communitservice_task_tag_slug'], $_POST['communitservice_task_attribute_slug'] ) && wp_verify_nonce( wp_unslash( $_POST['cs-permalinks-nonce'] ), 'cs-permalinks' ) ) { // WPCS: input var ok, sanitization ok.
			cs_switch_to_site_locale();

			$permalinks                   = (array) get_option( 'communitservice_permalinks', array() );
			$permalinks['category_base']  = cs_sanitize_permalink( wp_unslash( $_POST['communitservice_task_category_slug'] ) ); // WPCS: input var ok, sanitization ok.
			$permalinks['tag_base']       = cs_sanitize_permalink( wp_unslash( $_POST['communitservice_task_tag_slug'] ) ); // WPCS: input var ok, sanitization ok.
			$permalinks['attribute_base'] = cs_sanitize_permalink( wp_unslash( $_POST['communitservice_task_attribute_slug'] ) ); // WPCS: input var ok, sanitization ok.

			// Generate task base.
			$task_base = isset( $_POST['task_permalink'] ) ? cs_clean( wp_unslash( $_POST['task_permalink'] ) ) : ''; // WPCS: input var ok, sanitization ok.

			if ( 'custom' === $task_base ) {
				if ( isset( $_POST['task_permalink_structure'] ) ) { // WPCS: input var ok.
					$task_base = preg_replace( '#/+#', '/', '/' . str_replace( '#', '', trim( wp_unslash( $_POST['task_permalink_structure'] ) ) ) ); // WPCS: input var ok, sanitization ok.
				} else {
					$task_base = '/';
				}

				// This is an invalid base structure and breaks pages.
				if ( '/%task_cat%/' === trailingslashit( $task_base ) ) {
					$task_base = '/' . _x( 'task', 'slug', 'communitservice' ) . $task_base;
				}
			} elseif ( empty( $task_base ) ) {
				$task_base = _x( 'task', 'slug', 'communitservice' );
			}

			$permalinks['task_base'] = cs_sanitize_permalink( $task_base );

			// Sservice base may require verbose page rules if nesting pages.
			$cservice_page_id   = cs_get_page_id( 'cservice' );
			$cservice_permalink = ( $cservice_page_id > 0 && get_post( $cservice_page_id ) ) ? get_page_uri( $cservice_page_id ) : _x( 'cservice', 'default-slug', 'communitservice' );

			if ( $cservice_page_id && stristr( trim( $permalinks['task_base'], '/' ), $cservice_permalink ) ) {
				$permalinks['use_verbose_page_rules'] = true;
			}
			
			update_option( 'communitservice_permalinks', $permalinks );
			cs_restore_locale();
		}
	}
}

return new CS_Admin_Permalink_Settings();
