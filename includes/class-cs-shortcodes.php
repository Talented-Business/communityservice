<?php
/**
 * Shortcodes
 *
 * @package CommunityService/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * CommunityService Shortcodes class.
 */
class CS_Shortcodes {

	/**
	 * Init shortcodes.
	 */
	public static function init() {
		$shortcodes = array(
			//'task'                    => __CLASS__ . '::task',
			//'task_page'               => __CLASS__ . '::task_page',
			//'task_category'           => __CLASS__ . '::task_category',
			//'task_categories'         => __CLASS__ . '::task_categories',
			//'tasks'                   => __CLASS__ . '::tasks',
			'activities'                   => __CLASS__ . '::activities',
			'submit_activity'                   => __CLASS__ . '::submit_activity',
			//'recent_tasks'            => __CLASS__ . '::recent_tasks',
			//'featured_tasks'          => __CLASS__ . '::featured_tasks',
			//'task_attribute'          => __CLASS__ . '::task_attribute',
			//'related_tasks'           => __CLASS__ . '::related_tasks',
			'service_messages'              => __CLASS__ . '::service_messages',
			'communityservice_my_account'     => __CLASS__ . '::my_account',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}

		// Alias for pre 2.1 compatibility.
		add_shortcode( 'communityservice_messages', __CLASS__ . '::service_messages' );
	}

	/**
	 * Shortcode Wrapper.
	 *
	 * @param string[] $function Callback function.
	 * @param array    $atts     Attributes. Default to empty array.
	 * @param array    $wrapper  Customer wrapper data.
	 *
	 * @return string
	 */
	public static function shortcode_wrapper(
		$function,
		$atts = array(),
		$wrapper = array(
			'class'  => 'communityservice',
			'before' => null,
			'after'  => null,
		)
	) {
		ob_start();

		// @codingStandardsIgnoreStart
		echo empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
		call_user_func( $function, $atts );
		echo empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];
		// @codingStandardsIgnoreEnd

		return ob_get_clean();
	}

	/**
	 * My account page shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function my_account( $atts ) {
		return self::shortcode_wrapper( array( 'CS_Shortcode_My_Account', 'output' ), $atts );
	}

	/**
	 * List multiple activities shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function activities( $atts ) {
		$atts = (array) $atts;
		$type = 'activities';
		
		$shortcode = new CS_Shortcode_Activities( $atts, $type );
		
		return $shortcode->get_content();
	}

	public static function submit_activity( $atts ) {
		return self::shortcode_wrapper( array( 'CS_Shortcode_Activity_Form', 'output' ), $atts );
	}


	/**
	 * Show a single task page.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function task_page( $atts ) {
		if ( empty( $atts ) ) {
			return '';
		}

		if ( ! isset( $atts['id'] ) && ! isset( $atts['sku'] ) ) {
			return '';
		}

		$args = array(
			'posts_per_page'      => 1,
			'post_type'           => 'task',
			'post_status'         => ( ! empty( $atts['status'] ) ) ? $atts['status'] : 'publish',
			'ignore_sticky_posts' => 1,
			'no_found_rows'       => 1,
		);

		if ( isset( $atts['sku'] ) ) {
			$args['meta_query'][] = array(
				'key'     => '_sku',
				'value'   => sanitize_text_field( $atts['sku'] ),
				'compare' => '=',
			);

			$args['post_type'] = array( 'task', 'task_variation' );
		}

		if ( isset( $atts['id'] ) ) {
			$args['p'] = absint( $atts['id'] );
		}

		// Don't render titles if desired.
		if ( isset( $atts['show_title'] ) && ! $atts['show_title'] ) {
			remove_action( 'communityservice_single_task_summary', 'communityservice_template_single_title', 5 );
		}

		$single_task = new WP_Query( $args );

		$preselected_id = '0';

		// Check if sku is a variation.
		if ( isset( $atts['sku'] ) && $single_task->have_posts() && 'task_variation' === $single_task->post->post_type ) {

			$variation  = new CS_Task_Variation( $single_task->post->ID );
			$attributes = $variation->get_attributes();

			// Set preselected id to be used by JS to provide context.
			$preselected_id = $single_task->post->ID;

			// Get the parent task object.
			$args = array(
				'posts_per_page'      => 1,
				'post_type'           => 'task',
				'post_status'         => 'publish',
				'ignore_sticky_posts' => 1,
				'no_found_rows'       => 1,
				'p'                   => $single_task->post->post_parent,
			);

			$single_task = new WP_Query( $args );
		?>
			<script type="text/javascript">
				jQuery( document ).ready( function( $ ) {
					var $variations_form = $( '[data-task-page-preselected-id="<?php echo esc_attr( $preselected_id ); ?>"]' ).find( 'form.variations_form' );

					<?php foreach ( $attributes as $attr => $value ) { ?>
						$variations_form.find( 'select[name="<?php echo esc_attr( $attr ); ?>"]' ).val( '<?php echo esc_js( $value ); ?>' );
					<?php } ?>
				});
			</script>
		<?php
		}

		// For "is_single" to always make load comments_template() for reviews.
		$single_task->is_single = true;

		ob_start();

		global $wp_query;

		// Backup query object so following loops think this is a task page.
		$previous_wp_query = $wp_query;
		// @codingStandardsIgnoreStart
		$wp_query          = $single_task;
		// @codingStandardsIgnoreEnd

		wp_enqueue_script( 'cs-single-task' );

		while ( $single_task->have_posts() ) {
			$single_task->the_post()
			?>
			<div class="single-task" data-task-page-preselected-id="<?php echo esc_attr( $preselected_id ); ?>">
				<?php cs_get_template_part( 'content', 'single-task' ); ?>
			</div>
			<?php
		}

		// Restore $previous_wp_query and reset post data.
		// @codingStandardsIgnoreStart
		$wp_query = $previous_wp_query;
		// @codingStandardsIgnoreEnd
		wp_reset_postdata();

		// Re-enable titles if they were removed.
		if ( isset( $atts['show_title'] ) && ! $atts['show_title'] ) {
			add_action( 'communityservice_single_task_summary', 'communityservice_template_single_title', 5 );
		}

		return '<div class="communityservice">' . ob_get_clean() . '</div>';
	}

	/**
	 * Show messages.
	 *
	 * @return string
	 */
	public static function service_messages() {
		return '<div class="communityservice">' . cs_print_notices( true ) . '</div>';
	}

	/**
	 * Order by rating.
	 *
	 * @param      array $args Query args.
	 * @return     array
	 */
	public static function order_by_rating_post_clauses( $args ) {
		return CS_Shortcode_Tasks::order_by_rating_post_clauses( $args );
	}

	/**
	 * List tasks with an attribute shortcode.
	 * Example [task_attribute attribute="color" filter="black"].
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function task_attribute( $atts ) {
		$atts = array_merge( array(
			'limit'     => '12',
			'columns'   => '4',
			'orderby'   => 'title',
			'order'     => 'ASC',
			'attribute' => '',
			'terms'     => '',
		), (array) $atts );

		if ( empty( $atts['attribute'] ) ) {
			return '';
		}

		$shortcode = new CS_Shortcode_Tasks( $atts, 'task_attribute' );

		return $shortcode->get_content();
	}

	/**
	 * List related tasks.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function related_tasks( $atts ) {
		if ( isset( $atts['per_page'] ) ) {
			$atts['limit'] = $atts['per_page'];
		}

		// @codingStandardsIgnoreStart
		$atts = shortcode_atts( array(
			'limit'    => '4',
			'columns'  => '4',
			'orderby'  => 'rand',
		), $atts, 'related_tasks' );
		// @codingStandardsIgnoreEnd

		ob_start();

		// Rename arg.
		$atts['posts_per_page'] = absint( $atts['limit'] );

		communityservice_related_tasks( $atts );

		return ob_get_clean();
	}
}