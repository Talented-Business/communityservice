<?php
/**
 * List tables: activities.
 *
 * @package CommunityService\admin
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'CS_Admin_List_Table_Activities', false ) ) {
	return;
}

if ( ! class_exists( 'CS_Admin_List_Table', false ) ) {
	include_once 'abstract-class-cs-admin-list-table.php';
}

/**
 * CS_Admin_List_Table_Activities Class.
 */
class CS_Admin_List_Table_Activities extends CS_Admin_List_Table {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $list_table_type = 'cs-activity';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'admin_notices', array( $this, 'bulk_admin_notices' ) );
		add_action( 'admin_footer', array( $this, 'activity_preview_template' ) );
		add_filter( 'get_search_query', array( $this, 'search_label' ) );
		add_filter( 'query_vars', array( $this, 'add_custom_query_var' ) );
		add_action( 'parse_query', array( $this, 'search_custom_fields' ) );
	}

	/**
	 * Render blank state.
	 */
	protected function render_blank_state() {
		echo '<div class="communityservice-BlankState">';
		echo '<h2 class="communityservice-BlankState-message">' . esc_html__( 'When you receive a new activity, it will appear here.', 'communityservice' ) . '</h2>';
		echo '</div>';
	}

	/**
	 * Define primary column.
	 *
	 * @return string
	 */
	protected function get_primary_column() {
		return 'activity_number';
	}

	/**
	 * Get row actions to show in the list table.
	 *
	 * @param array   $actions Array of actions.
	 * @param WP_Post $post Current post object.
	 * @return array
	 */
	protected function get_row_actions( $actions, $post ) {
		return array();
	}

	/**
	 * Define hidden columns.
	 *
	 * @return array
	 */
	protected function define_hidden_columns() {
		return array(
			//'shipping_address',
			//'billing_address',
			'cs_actions',
		);
	}

	/**
	 * Define which columns are sortable.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function define_sortable_columns( $columns ) {
		$custom = array(
			'activity_number' => 'ID',
			'activity_title' => 'post_title',
			//'activity_total'  => 'activity_total',
			'activity_submitted_date'   => 'date',
		);
		unset( $columns['comments'] );

		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Define which columns to show on this screen.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function define_columns( $columns ) {
		$show_columns                     = array();
		$show_columns['cb']               = $columns['cb'];
		$show_columns['activity_number']     = __( 'Activity', 'communityservice' );
		$show_columns['activity_title']     = __( 'Activity Title', 'communityservice' );
		$show_columns['activity_date']     = __( 'Activity Date', 'communityservice' );
		$show_columns['activity_submitted_date']       = __( 'Submitted Date', 'communityservice' );
		$show_columns['activity_type']       = __( 'Type', 'communityservice' );
		$show_columns['activity_status']     = __( 'Status', 'communityservice' );
		//$show_columns['billing_address']  = __( 'Billing', 'communityservice' );
		//$show_columns['shipping_address'] = __( 'Ship to', 'communityservice' );
		//$show_columns['activity_total']      = __( 'Total', 'communityservice' );
		$show_columns['cs_actions']       = __( 'Actions', 'communityservice' );

		wp_enqueue_script( 'cs-activities' );

		return $show_columns;
	}

	/**
	 * Define bulk actions.
	 *
	 * @param array $actions Existing actions.
	 * @return array
	 */
	public function define_bulk_actions( $actions ) {
		if ( isset( $actions['edit'] ) ) {
			unset( $actions['edit'] );
		}

		$actions['mark_approved']       = __( 'Change status to approved', 'communityservice' );
		$actions['mark_cancelled']         = __( 'Change status to declined', 'communityservice' );
		$actions['mark_pending']      = __( 'Change status to pending', 'communityservice' );

		return $actions;
	}

	/**
	 * Pre-fetch any data for the row each column has access to it. the_activity global is there for bw compat.
	 *
	 * @param int $post_id Post ID being shown.
	 */
	protected function prepare_row_data( $post_id ) {
		global $the_activity;

		if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
			$this->object = cs_get_activity( $post_id );
			$the_activity    = $this->object;
		}
	}

	/**
	 * Render columm: activity_number.
	 */
	protected function render_activity_number_column() {
		$student = '';
		
		if ( false){//$this->object->get_first_name() || $this->object->get_last_name() ) {
			/* translators: 1: first name 2: last name */
			$student = trim( sprintf( _x( '%1$s %2$s', 'full name', 'communityservice' ), $this->object->get_billing_first_name(), $this->object->get_billing_last_name() ) );
		} elseif ( $this->object->get_student_id() ) {
			$user  = get_user_by( 'id', $this->object->get_student_id() );
			$student = ucwords( $user->display_name );
		}

		if ( $this->object->get_status() === 'trash' ) {
			echo '<strong>#' . esc_attr( $this->object->get_activity_number() ) . ' ' . esc_html( $student ) . '</strong>';
		} else {
			//echo '<a href="#" class="activity-preview" data-activity-id="' . absint( $this->object->get_id() ) . '" title="' . esc_attr( __( 'Preview', 'communityservice' ) ) . '">' . esc_html( __( 'Preview', 'communityservice' ) ) . '</a>';
			echo '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $this->object->get_id() ) ) . '&action=edit' ) . '" class="activity-view"><strong>#' . esc_attr( $this->object->get_activity_number() ) . ' ' . esc_html( $student ) . '</strong></a>';
		}
	}

	/**
	 * Render columm: activity_title.
	 */
	protected function render_activity_title_column() {
		$student = '';

		if ( $this->object->get_status() === 'trash' ) {
			echo '<strong>#' . esc_attr( $this->object->get_activity_number() ) . ' ' . esc_html( $student ) . '</strong>';
		} else {
			echo $this->object->get_name();
		}
	}

	/**
	 * Render columm: activity_type.
	 */
	protected function render_activity_type_column() {

		if ( $this->object->get_parent_id() === 0 ) {
			echo "External";
		} else {
			echo "Internal";
		}
	}

	/**
	 * Render columm: activity_status.
	 */
	protected function render_activity_status_column() {
		$tooltip                 = '';
		$comment_count           = get_comment_count( $this->object->get_id() );
		$approved_comments_count = absint( $comment_count['approved'] );

		if ( $approved_comments_count ) {
			$latest_notes = cs_get_activity_notes(
				array(
					'activity_id' => $this->object->get_id(),
					'limit'    => 1,
					'activityby'  => 'date_created_gmt',
				)
			);

			$latest_note = current( $latest_notes );

			if ( isset( $latest_note->content ) && 1 === $approved_comments_count ) {
				$tooltip = cs_sanitize_tooltip( $latest_note->content );
			} elseif ( isset( $latest_note->content ) ) {
				/* translators: %d: notes count */
				$tooltip = cs_sanitize_tooltip( $latest_note->content . '<br/><small style="display:block">' . sprintf( _n( 'Plus %d other note', 'Plus %d other notes', ( $approved_comments_count - 1 ), 'communityservice' ), $approved_comments_count - 1 ) . '</small>' );
			} else {
				/* translators: %d: notes count */
				$tooltip = cs_sanitize_tooltip( sprintf( _n( '%d note', '%d notes', $approved_comments_count, 'communityservice' ), $approved_comments_count ) );
			}
		}

		if ( $tooltip ) {
			printf( '<mark class="activity-status %s tips" data-tip="%s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $this->object->get_status() ) ), wp_kses_post( $tooltip ), esc_html( cs_get_activity_status_name( $this->object->get_status() ) ) );
		} else {
			printf( '<mark class="activity-status %s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $this->object->get_status() ) ), esc_html( cs_get_activity_status_name( $this->object->get_status() ) ) );
		}
	}

	/**
	 * Render columm: activity_date.
	 */
	protected function render_activity_date_column() {
		echo date_i18n( get_option( 'date_format' ), strtotime($this->object->get_activity_date() ));
	}

	/**
	 * Render columm: activity_submitted_date.
	 */
	protected function render_activity_submitted_date_column() {
		$activity_timestamp = $this->object->get_date_created() ? $this->object->get_date_created()->getTimestamp() : '';

		if ( ! $activity_timestamp ) {
			echo '&ndash;';
			return;
		}

		// Check if the activity was created within the last 24 hours, and not in the future.
		if ( $activity_timestamp > strtotime( '-1 day', current_time( 'timestamp', true ) ) && $activity_timestamp <= current_time( 'timestamp', true ) ) {
			$show_date = sprintf(
				/* translators: %s: human-readable time difference */
				_x( '%s ago', '%s = human-readable time difference', 'communityservice' ),
				human_time_diff( $this->object->get_date_created()->getTimestamp(), current_time( 'timestamp', true ) )
			);
		} else {
			$show_date = $this->object->get_date_created()->date_i18n( apply_filters( 'communityservice_admin_activity_submitted_date_format', __( 'M j, Y', 'communityservice' ) ) );
		}
		printf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( $this->object->get_date_created()->date( 'c' ) ),
			esc_html( $this->object->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
			esc_html( $show_date )
		);
	}

	/**
	 * Render columm: cs_actions.
	 */
	protected function render_cs_actions_column() {
		echo '<p>';

		do_action( 'communityservice_admin_activity_actions_start', $this->object );

		$actions = array();

		if ( $this->object->has_status( array( 'pending', 'on-hold' ) ) ) {
			$actions['processing'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=communityservice_mark_activity_status&status=processing&activity_id=' . $this->object->get_id() ), 'communityservice-mark-activity-status' ),
				'name'   => __( 'Processing', 'communityservice' ),
				'action' => 'processing',
			);
		}

		if ( $this->object->has_status( array( 'pending', 'on-hold', 'processing' ) ) ) {
			$actions['complete'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=communityservice_mark_activity_status&status=completed&activity_id=' . $this->object->get_id() ), 'communityservice-mark-activity-status' ),
				'name'   => __( 'Complete', 'communityservice' ),
				'action' => 'complete',
			);
		}

		$actions = apply_filters( 'communityservice_admin_activity_actions', $actions, $this->object );

		echo cs_render_action_buttons( $actions ); // WPCS: XSS ok.

		do_action( 'communityservice_admin_activity_actions_end', $this->object );

		echo '</p>';
	}

	/**
	 * Render columm: billing_address.
	 */
	protected function render_billing_address_column() {
		$address = $this->object->get_formatted_billing_address();

		if ( $address ) {
			echo esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) );

			if ( $this->object->get_payment_method() ) {
				/* translators: %s: payment method */
				echo '<span class="description">' . sprintf( __( 'via %s', 'communityservice' ), esc_html( $this->object->get_payment_method_title() ) ) . '</span>'; // WPCS: XSS ok.
			}
		} else {
			echo '&ndash;';
		}
	}

	/**
	 * Render columm: shipping_address.
	 */
	protected function render_shipping_address_column() {
		$address = $this->object->get_formatted_shipping_address();

		if ( $address ) {
			echo '<a target="_blank" href="' . esc_url( $this->object->get_shipping_address_map_url() ) . '">' . esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) ) . '</a>';
			if ( $this->object->get_shipping_method() ) {
				/* translators: %s: shipping method */
				echo '<span class="description">' . sprintf( __( 'via %s', 'communityservice' ), esc_html( $this->object->get_shipping_method() ) ) . '</span>'; // WPCS: XSS ok.
			}
		} else {
			echo '&ndash;';
		}
	}

	/**
	 * Template for activity preview.
	 *
	 * @since 1.0
	 */
	public function activity_preview_template() {
		?>
		<script type="text/template" id="tmpl-cs-modal-view-activity">
			<div class="cs-backbone-modal cs-activity-preview">
				<div class="cs-backbone-modal-content">
					<section class="cs-backbone-modal-main" role="main">
						<header class="cs-backbone-modal-header">
							<mark class="activity-status status-{{ data.status }}"><span>{{ data.status_name }}</span></mark>
							<?php /* translators: %s: activity ID */ ?>
							<h1><?php echo esc_html( sprintf( __( 'Activity #%s', 'communityservice' ), '{{ data.activity_number }}' ) ); ?></h1>
							<button class="modal-close modal-close-link dashicons dashicons-no-alt">
								<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'communityservice' ); ?></span>
							</button>
						</header>
						<article>
							<?php do_action( 'communityservice_admin_activity_preview_start' ); ?>

							<div class="cs-activity-preview-addresses">
								<div class="cs-activity-preview-address">
									<h2><?php esc_html_e( 'Billing details', 'communityservice' ); ?></h2>
									{{{ data.formatted_billing_address }}}
								</div>
							</div>

							{{{ data.item_html }}}

							<?php do_action( 'communityservice_admin_activity_preview_end' ); ?>
						</article>
						<footer>
							<div class="inner">
								{{{ data.actions_html }}}

								<a class="button button-primary button-large" aria-label="<?php esc_attr_e( 'Edit this activity', 'communityservice' ); ?>" href="<?php echo esc_url( admin_url( 'post.php?action=edit' ) ); ?>&post={{ data.data.id }}"><?php esc_html_e( 'Edit', 'communityservice' ); ?></a>
							</div>
						</footer>
					</section>
				</div>
			</div>
			<div class="cs-backbone-modal-backdrop modal-close"></div>
		</script>
		<?php
	}

	/**
	 * Get items to display in the preview as HTML.
	 *
	 * @param  CS_Activity $activity Activity object.
	 * @return string
	 */
	public static function get_activity_preview_item_html( $activity ) {
		$hidden_activity_itemmeta = apply_filters(
			'communityservice_hidden_activity_itemmeta', array(
				'_qty',
				'_tax_class',
				'_product_id',
				'_variation_id',
				'_line_subtotal',
				'_line_subtotal_tax',
				'_line_total',
				'_line_tax',
				'method_id',
				'cost',
				'_reduced_stock',
			)
		);

		$line_items = apply_filters( 'communityservice_admin_activity_preview_line_items', $activity->get_items(), $activity );
		$columns    = apply_filters(
			'communityservice_admin_activity_preview_line_item_columns', array(
				'product'  => __( 'Product', 'communityservice' ),
				'quantity' => __( 'Quantity', 'communityservice' ),
				'tax'      => __( 'Tax', 'communityservice' ),
				'total'    => __( 'Total', 'communityservice' ),
			), $activity
		);

		if ( ! cs_tax_enabled() ) {
			unset( $columns['tax'] );
		}

		$html = '
		<div class="cs-activity-preview-table-wrapper">
			<table cellspacing="0" class="cs-activity-preview-table">
				<thead>
					<tr>';

		foreach ( $columns as $column => $label ) {
			$html .= '<th class="cs-activity-preview-table__column--' . esc_attr( $column ) . '">' . esc_html( $label ) . '</th>';
		}

		$html .= '
					</tr>
				</thead>
				<tbody>';

		foreach ( $line_items as $item_id => $item ) {

			$product_object = is_callable( array( $item, 'get_product' ) ) ? $item->get_product() : null;
			$row_class      = apply_filters( 'communityservice_admin_html_activity_preview_item_class', '', $item, $activity );

			$html .= '<tr class="cs-activity-preview-table__item cs-activity-preview-table__item--' . esc_attr( $item_id ) . ( $row_class ? ' ' . esc_attr( $row_class ) : '' ) . '">';

			foreach ( $columns as $column => $label ) {
				$html .= '<td class="cs-activity-preview-table__column--' . esc_attr( $column ) . '">';
				switch ( $column ) {
					case 'product':
						$html .= wp_kses_post( $item->get_name() );

						if ( $product_object ) {
							$html .= '<div class="cs-activity-item-sku">' . esc_html( $product_object->get_sku() ) . '</div>';
						}

						$meta_data = $item->get_formatted_meta_data( '' );

						if ( $meta_data ) {
							$html .= '<table cellspacing="0" class="cs-activity-item-meta">';

							foreach ( $meta_data as $meta_id => $meta ) {
								if ( in_array( $meta->key, $hidden_activity_itemmeta, true ) ) {
									continue;
								}
								$html .= '<tr><th>' . wp_kses_post( $meta->display_key ) . ':</th><td>' . wp_kses_post( force_balance_tags( $meta->display_value ) ) . '</td></tr>';
							}
							$html .= '</table>';
						}
						break;
					case 'quantity':
						$html .= esc_html( $item->get_quantity() );
						break;
					case 'tax':
						$html .= cs_price( $item->get_total_tax(), array( 'currency' => $activity->get_currency() ) );
						break;
					case 'total':
						$html .= cs_price( $item->get_total(), array( 'currency' => $activity->get_currency() ) );
						break;
					default:
						$html .= apply_filters( 'communityservice_admin_activity_preview_line_item_column_' . sanitize_key( $column ), '', $item, $item_id, $activity );
						break;
				}
				$html .= '</td>';
			}

			$html .= '</tr>';
		}

		$html .= '
				</tbody>
			</table>
		</div>';

		return $html;
	}

	/**
	 * Get actions to display in the preview as HTML.
	 *
	 * @param  CS_Activity $activity Activity object.
	 * @return string
	 */
	public static function get_activity_preview_actions_html( $activity ) {
		$actions        = array();
		$status_actions = array();

		if ( $activity->has_status( array( 'pending' ) ) ) {
			$status_actions['on-hold'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=communityservice_mark_activity_status&status=on-hold&activity_id=' . $activity->get_id() ), 'communityservice-mark-activity-status' ),
				'name'   => __( 'On-hold', 'communityservice' ),
				'title'  => __( 'Change activity status to on-hold', 'communityservice' ),
				'action' => 'on-hold',
			);
		}

		if ( $activity->has_status( array( 'pending', 'on-hold' ) ) ) {
			$status_actions['processing'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=communityservice_mark_activity_status&status=processing&activity_id=' . $activity->get_id() ), 'communityservice-mark-activity-status' ),
				'name'   => __( 'Processing', 'communityservice' ),
				'title'  => __( 'Change activity status to processing', 'communityservice' ),
				'action' => 'processing',
			);
		}

		if ( $activity->has_status( array( 'pending', 'on-hold', 'processing' ) ) ) {
			$status_actions['complete'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=communityservice_mark_activity_status&status=completed&activity_id=' . $activity->get_id() ), 'communityservice-mark-activity-status' ),
				'name'   => __( 'Completed', 'communityservice' ),
				'title'  => __( 'Change activity status to completed', 'communityservice' ),
				'action' => 'complete',
			);
		}

		if ( $status_actions ) {
			$actions['status'] = array(
				'group'   => __( 'Change status: ', 'communityservice' ),
				'actions' => $status_actions,
			);
		}

		return cs_render_action_buttons( apply_filters( 'communityservice_admin_activity_preview_actions', $actions, $activity ) );
	}

	/**
	 * Get activity details to send to the ajax endpoint for previews.
	 *
	 * @param  CS_Activity $activity Activity object.
	 * @return array
	 */
	public static function activity_preview_get_activity_details( $activity ) {
		if ( ! $activity ) {
			return array();
		}

		$payment_via      = $activity->get_payment_method_title();
		$payment_method   = $activity->get_payment_method();
		$payment_gateways = CS()->payment_gateways() ? CS()->payment_gateways->payment_gateways() : array();
		$transaction_id   = $activity->get_transaction_id();

		if ( $transaction_id ) {

			$url = isset( $payment_gateways[ $payment_method ] ) ? $payment_gateways[ $payment_method ]->get_transaction_url( $activity ) : false;

			if ( $url ) {
				$payment_via .= ' (<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $transaction_id ) . '</a>)';
			} else {
				$payment_via .= ' (' . esc_html( $transaction_id ) . ')';
			}
		}

		$billing_address  = $activity->get_formatted_billing_address();
		$shipping_address = $activity->get_formatted_shipping_address();

		return apply_filters(
			'communityservice_admin_activity_preview_get_activity_details', array(
				'data'                       => $activity->get_data(),
				'activity_number'               => $activity->get_activity_number(),
				'activity_title'               => $activity->get_name(),
				'item_html'                  => CS_Admin_List_Table_Activities::get_activity_preview_item_html( $activity ),
				'actions_html'               => CS_Admin_List_Table_Activities::get_activity_preview_actions_html( $activity ),
				'ship_to_billing'            => cs_ship_to_billing_address_only(),
				'needs_shipping'             => $activity->needs_shipping_address(),
				'formatted_billing_address'  => $billing_address ? $billing_address : __( 'N/A', 'communityservice' ),
				'formatted_shipping_address' => $shipping_address ? $shipping_address : __( 'N/A', 'communityservice' ),
				'shipping_address_map_url'   => $activity->get_shipping_address_map_url(),
				'payment_via'                => $payment_via,
				'shipping_via'               => $activity->get_shipping_method(),
				'status'                     => $activity->get_status(),
				'status_name'                => cs_get_activity_status_name( $activity->get_status() ),
			), $activity
		);
	}

	/**
	 * Handle bulk actions.
	 *
	 * @param  string $redirect_to URL to redirect to.
	 * @param  string $action      Action name.
	 * @param  array  $ids         List of ids.
	 * @return string
	 */
	public function handle_bulk_actions( $redirect_to, $action, $ids ) {
		$ids     = apply_filters( 'communityservice_bulk_action_ids', array_reverse( array_map( 'absint', $ids ) ), $action, 'activity' );
		$changed = 0;

		if ( false !== strpos( $action, 'mark_' ) ) {
			$activity_statuses = cs_get_activity_statuses();
			$new_status     = substr( $action, 5 ); // Get the status name from action.
			$report_action  = 'marked_' . $new_status;

			// Sanity check: bail out if this is actually not a status, or is not a registered status.
			if ( isset( $activity_statuses[ 'cs-' . $new_status ] ) ) {
				// Initialize payment gateways in case activity has hooked status transition actions.
				foreach ( $ids as $id ) {
					$activity = cs_get_activity( $id );
					$activity->update_status( $new_status, __( 'Activity status changed by bulk edit:', 'communityservice' ), true );
					do_action( 'communityservice_activity_edit_status', $id, $new_status );
					$changed++;
				}
			}
		}

		if ( $changed ) {
			$redirect_to = add_query_arg(
				array(
					'post_type'   => $this->list_table_type,
					'bulk_action' => $report_action,
					'changed'     => $changed,
					'ids'         => join( ',', $ids ),
				), $redirect_to
			);
		}

		return esc_url_raw( $redirect_to );
	}

	/**
	 * Show confirmation message that activity status changed for number of activities.
	 */
	public function bulk_admin_notices() {
		global $post_type, $pagenow;

		// Bail out if not on shop activity list page.
		if ( 'edit.php' !== $pagenow || 'cs-activity' !== $post_type || ! isset( $_REQUEST['bulk_action'] ) ) { // WPCS: input var ok, CSRF ok.
			return;
		}

		$activity_statuses = cs_get_activity_statuses();
		$number         = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0; // WPCS: input var ok, CSRF ok.
		$bulk_action    = cs_clean( wp_unslash( $_REQUEST['bulk_action'] ) ); // WPCS: input var ok, CSRF ok.

		// Check if any status changes happened.
		foreach ( $activity_statuses as $slug => $name ) {
			if ( 'marked_' . str_replace( 'cs-', '', $slug ) === $bulk_action ) { // WPCS: input var ok, CSRF ok.
				/* translators: %d: activities count */
				$message = sprintf( _n( '%d activity status changed.', '%d activity statuses changed.', $number, 'communityservice' ), number_format_i18n( $number ) );
				echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';
				break;
			}
		}

		if ( 'removed_personal_data' === $bulk_action ) { // WPCS: input var ok, CSRF ok.
			/* translators: %d: activities count */
			$message = sprintf( _n( 'Removed personal data from %d activity.', 'Removed personal data from %d activities.', $number, 'communityservice' ), number_format_i18n( $number ) );
			echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';
		}
	}

	/**
	 * See if we should render search filters or not.
	 */
	public function restrict_manage_posts() {
		global $typenow;

		if ( in_array( $typenow, cs_get_activity_types( 'activity-meta-boxes' ), true ) ) {
			$this->render_filters();
		}
	}
	private function get_students(){
		$users = get_users(array('role'=>'subscriber'));
		$results = array();
		foreach($users as $user){
			$results[$user->ID] = $user->display_name;
		}
		return $results;
	}
	/**
	 * Render any custom filters and search inputs for the list table.
	 */
	protected function render_filters() {
		$first_name     = null;
		$last_name     = null;
		$_year = null;
		$_house = null;

		if ( ! empty( $_GET['first_name'] ) ) { 
			$first_name = $_GET['first_name'];
		}
		if ( ! empty( $_GET['last_name'] ) ) { 
			$last_name = $_GET['last_name']; 
		}
		?>
		<input type="text" name="first_name" placeholder="First Name" style="float:left;width:100px" value="<?=$first_name?>">
		<input type="text" name="last_name" placeholder="Last Name" style="float:left;width:100px" value="<?=$last_name?>">
		<?php
			if ( ! empty( $_GET['_year'] ) ) { 
				$_year = absint( $_GET['_year'] );
			}
			$years = get_cs_student_years_ids();
			arsort($years);
		?>
		<select class="cs-year-search" name="_year" data-placeholder="<?php esc_attr_e( 'Filter by Year of Graduation', 'communityservice' ); ?>" data-allow_clear="true">
			<option value=0>All Years</option>
			<optgroup Label="Current Year">
				<?php foreach($years as $id => $year){?>
					<?php if($year>date("Y")-9){?>
						<option value="<?php echo esc_attr( $id ); ?>" <?php if($id == $_year) echo 'selected="selected"'?>>
							<?php if($year>date("Y")-9)echo (date("Y")-$year+4);else {
								echo ($year+8)." Graduated";
							} ?>
						</option>
					<?php }?>
				<?php }?>
			</optgroup>
			<optgroup Label="Year of Graduation">
				<?php foreach($years as $id => $year){?>
					<?php if($year<=date("Y")-9){?>
						<option value="<?php echo esc_attr( $id ); ?>" <?php if($id == $_year) echo 'selected="selected"'?>>
							<?php if($year>date("Y")-9)echo (date("Y")-$year+4);else {
								echo ($year+8);
							} ?>
						</option>
					<?php }?>
				<?php }?>
			</optgroup>
		</select>
		<?php
			if ( ! empty( $_GET['_house'] ) ) { 
				$_house = absint( $_GET['_house'] );
			}
			$houses = get_cs_student_houses_ids();
		?>
		<select class="cs-house-search" name="_house" data-placeholder="<?php esc_attr_e( 'Filter by registered student', 'communityservice' ); ?>" data-allow_clear="true">
			<option value=0>All Houses</option>
			<?php foreach($houses as $id => $house){?>
				<option value="<?php echo esc_attr( $id ); ?>" <?php if($id == $_house) echo 'selected="selected"'?>><?php echo wp_kses_post( $house ); ?></option>
			<?php }?>
		</select>
		<input type="submit" name="export_action" id="post-query-submit" class="button" value="Export">		
		<?php
	}

	/**
	 * Handle any filters.
	 *
	 * @param array $query_vars Query vars.
	 * @return array
	 */
	public function request_query( $query_vars ) {
		global $typenow;
		
		if ( in_array( $typenow, cs_get_activity_types( 'activity-meta-boxes' ), true ) ) {
			return $this->query_filters( $query_vars );
		}

		return $query_vars;
	}

	/**
	 * Handle any custom filters.
	 *
	 * @param array $query_vars Query vars.
	 * @return array
	 */
	protected function query_filters( $query_vars ) {
		global $wp_post_statuses;

		// Filter the activities by the posted student.
		if ( ! empty( $_GET['_student_user'] ) ) { // WPCS: input var ok.
			// @codingStandardsIgnoreStart
			$query_vars['meta_query'] = array(
				array(
					'key'   => '_student_user',
					'value' => (int) $_GET['_student_user'], // WPCS: input var ok, sanitization ok.
					'compare' => '=',
				),
			);
			// @codingStandardsIgnoreEnd
		}


		// Status.
		if ( empty( $query_vars['post_status'] ) ) {
			$post_statuses = cs_get_activity_statuses();

			foreach ( $post_statuses as $status => $value ) {
				if ( isset( $wp_post_statuses[ $status ] ) && false === $wp_post_statuses[ $status ]->show_in_admin_all_list ) {
					unset( $post_statuses[ $status ] );
				}
			}

			$query_vars['post_status'] = array_keys( $post_statuses );
		}
		//$query_vars['author__in'] = array(2);
		if(function_exists('search_students_by')){
			$first_name = null;
			$last_name = null;
			$year_id = 0;
			$home_id = 0;
			if($_GET['first_name'])$first_name = $_GET['first_name'];
			if($_GET['last_name'])$last_name = $_GET['last_name'];
			if($_GET['_year'])$year_id = $_GET['_year'];
			if($_GET['_house'])$home_id = $_GET['_house'];
			$query_vars['author__in'] = search_students_by($first_name,$last_name,$year_id,$home_id);
		}
		return $query_vars;
	}
	/**
	 * Change the label when searching activities.
	 *
	 * @param mixed $query Current search query.
	 * @return string
	 */
	public function search_label( $query ) {
		global $pagenow, $typenow;

		if ( 'edit.php' !== $pagenow || 'cs-activity' !== $typenow || ! get_query_var( 'cs-activity_search' ) || ! isset( $_GET['s'] ) ) { // WPCS: input var ok.
			return $query;
		}

		return cs_clean( wp_unslash( $_GET['s'] ) ); // WPCS: input var ok, sanitization ok.
	}

	/**
	 * Query vars for custom searches.
	 *
	 * @param mixed $public_query_vars Array of query vars.
	 * @return array
	 */
	public function add_custom_query_var( $public_query_vars ) {
		$public_query_vars[] = 'cs-activity_search';
		return $public_query_vars;
	}

	/**
	 * Search custom fields as well as content.
	 *
	 * @param WP_Query $wp Query object.
	 */
	public function search_custom_fields( $wp ) {
		global $pagenow;

		if ( 'edit.php' !== $pagenow || empty( $wp->query_vars['s'] ) || 'cs-activity' !== $wp->query_vars['post_type'] || ! isset( $_GET['s'] ) ) { // WPCS: input var ok.
			return;
		}

		$post_ids = cs_activity_search( cs_clean( wp_unslash( $_GET['s'] ) ) ); // WPCS: input var ok, sanitization ok.

		if ( ! empty( $post_ids ) ) {
			// Remove "s" - we don't want to search activity name.
			unset( $wp->query_vars['s'] );

			// so we know we're doing this.
			$wp->query_vars['cs-activity_search'] = true;

			// Search by found posts.
			$wp->query_vars['post__in'] = array_merge( $post_ids, array( 0 ) );
		}
	}
}
