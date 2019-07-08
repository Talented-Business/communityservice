<?php
/**
 * CommunityService setup
 *
 * @package CommunityService
 * @since   1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main CommunityService Class.
 *
 * @class CommunityService
 */
final class CommunityService {

	/**
	 * CommunityService version.
	 *
	 * @var string
	 */
	public $version = '1.0';

	/**
	 * The single instance of the class.
	 *
	 * @var CommunityService
	 * @since 1.0
	 */
	protected static $_instance = null;

	/**
	 * Session instance.
	 *
	 * @var CS_Session|CS_Session_Handler
	 */
	public $session = null;

	/**
	 * Query instance.
	 *
	 * @var CS_Query
	 */
	public $query = null;

	/**
	 * Task factory instance.
	 *
	 * @var CS_Task_Factory
	 */
	public $task_factory = null;


	/**
	 * Main CommunityService Instance.
	 *
	 * Ensures only one instance of CommunityService is loaded or can be loaded.
	 *
	 * @since 1.0
	 * @static
	 * @see CS()
	 * @return CommunityService - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		cs_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'communityservice' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		cs_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'communityservice' ), '1.0' );
	}

	/**
	 * Auto-load in-accessible properties on demand.
	 *
	 * @param mixed $key Key name.
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( in_array( $key, array('mailer'), true ) ) {
			return $this->$key();
		}
	}

	/**
	 * CommunityService Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();

		do_action( 'communityservice_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0
	 */
	private function init_hooks() {
		register_activation_hook( CS_PLUGIN_FILE, array( $this, 'install' ) );
		register_shutdown_function( array( $this, 'log_errors' ) );
//		add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( 'CS_Shortcodes', 'init' ) );
		add_action( 'init', array( 'CS_Emails', 'init_transactional_emails' ) );
		add_action( 'init', array( $this, 'wpdb_table_fix' ), 0 );
		add_action( 'init', array( $this, 'add_image_sizes' ) );
	}

	/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 3.2.0
	 */
	public function log_errors() {
		$error = error_get_last();
		if ( in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ) ) ) {
			$logger = cs_get_logger();
			$logger->critical(
				/* translators: 1: error message 2: file name and path 3: line number */
				sprintf( __( '%1$s in %2$s on line %3$s', 'communityservice' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL,
				array(
					'source' => 'fatal-errors',
				)
			);
			do_action( 'communityservice_shutdown_error', $error );
		}
	}

	/**
	 * Define CS Constants.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		$this->define( 'CS_ABSPATH', dirname( CS_PLUGIN_FILE ) . '/' );
		$this->define( 'CS_PLUGIN_BASENAME', plugin_basename( CS_PLUGIN_FILE ) );
		$this->define( 'CS_VERSION', $this->version );
		$this->define( 'COMMUNITYSERVICE_VERSION', $this->version );
		$this->define( 'CS_ROUNDING_PRECISION', 6 );
		$this->define( 'CS_DISCOUNT_ROUNDING_MODE', 2 );
		$this->define( 'CS_TAX_ROUNDING_MODE', 'yes' === get_option( 'communityservice_prices_include_tax', 'no' ) ? 2 : 1 );
		$this->define( 'CS_DELIMITER', '|' );
		$this->define( 'CS_LOG_DIR', $upload_dir['basedir'] . '/cs-logs/' );
		$this->define( 'CS_SESSION_CACHE_GROUP', 'cs_session_id' );
		$this->define( 'CS_TEMPLATE_DEBUG_MODE', false );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! defined( 'REST_REQUEST' );
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		/**
		 * Class autoloader.
		 */
		include_once CS_ABSPATH . 'includes/class-cs-autoloader.php';
		/**
		 * Interfaces.
		 */
		include_once CS_ABSPATH . 'includes/interfaces/class-cs-activity-data-store-interface.php';
		include_once CS_ABSPATH . 'includes/interfaces/class-cs-student-data-store-interface.php';
		include_once CS_ABSPATH . 'includes/interfaces/class-cs-object-data-store-interface.php';	
		include_once CS_ABSPATH . 'includes/interfaces/class-cs-task-data-store-interface.php';	
		
		/**
		 * Abstract classes.
		 */		
		include_once CS_ABSPATH . 'includes/abstracts/abstract-cs-data.php';
		include_once CS_ABSPATH . 'includes/abstracts/abstract-cs-session.php';
		include_once CS_ABSPATH . 'includes/abstracts/abstract-cs-task.php';
		include_once CS_ABSPATH . 'includes/abstracts/abstract-cs-activity.php';
		include_once CS_ABSPATH . 'includes/abstracts/abstract-cs-settings-api.php';
		include_once CS_ABSPATH . 'includes/abstracts/abstract-cs-object-query.php';
		/**
		 * Core classes.
		 */
		include_once CS_ABSPATH . 'includes/cs-core-functions.php';	
		include_once CS_ABSPATH . 'includes/class-cs-datetime.php';
    	include_once CS_ABSPATH . 'includes/class-cs-emails.php';
		include_once CS_ABSPATH . 'includes/class-cs-logger.php';
		include_once CS_ABSPATH . 'includes/class-cs-post-types.php';
		include_once CS_ABSPATH . 'includes/class-cs-query.php';
		include_once CS_ABSPATH . 'includes/class-cs-task-factory.php';
		include_once CS_ABSPATH . 'includes/class-cs-activity.php';	
		include_once CS_ABSPATH . 'includes/class-cs-cache-helper.php';	
		include_once CS_ABSPATH . 'includes/class-cs-meta-data.php';	
		include_once CS_ABSPATH . 'includes/class-cs-shortcodes.php';
		include_once CS_ABSPATH . 'includes/class-cs-activity-query.php';
		/**
		 * Data stores - used to store and retrieve CRUD object data from the database.
		 */
		include_once CS_ABSPATH . 'includes/class-cs-data-store.php';
		include_once CS_ABSPATH . 'includes/data-stores/class-cs-data-store-wp.php';
		include_once CS_ABSPATH . 'includes/data-stores/class-cs-task-data-store-cpt.php';
		include_once CS_ABSPATH . 'includes/data-stores/class-cs-student-data-store.php';
		include_once CS_ABSPATH . 'includes/data-stores/class-cs-student-data-store-session.php';
		include_once CS_ABSPATH . 'includes/data-stores/abstract-cs-activity-data-store-cpt.php';
		include_once CS_ABSPATH . 'includes/data-stores/class-cs-activity-data-store-cpt.php';

		if ( $this->is_request( 'admin' ) ) {
			include_once CS_ABSPATH . 'includes/admin/class-cs-admin.php';
		}

		if ( $this->is_request( 'frontend' ) ) {
			$this->frontend_includes();
		}

		if ( $this->is_request( 'cron' ) && 'yes' === get_option( 'communityservice_allow_tracking', 'no' ) ) {
			include_once CS_ABSPATH . 'includes/class-cs-tracker.php';
		}

		$this->theme_support_includes();
		$this->query = new CS_Query();
		//$this->api   = new CS_API();
	}

	/**
	 * Include classes for theme support.
	 *
	 * @since 3.3.0
	 */
	private function theme_support_includes() {
		if ( cs_is_active_theme( array( 'twentynineteen', 'twentyseventeen', 'twentysixteen', 'twentyfifteen', 'twentyfourteen', 'twentythirteen', 'twentyeleven', 'twentytwelve', 'twentyten' ) ) ) {
			switch ( get_template() ) {
				case 'twentyten':
					include_once CS_ABSPATH . 'includes/theme-support/class-wc-twenty-ten.php';
					break;
				case 'twentyeleven':
					include_once CS_ABSPATH . 'includes/theme-support/class-wc-twenty-eleven.php';
					break;
				case 'twentytwelve':
					include_once CS_ABSPATH . 'includes/theme-support/class-wc-twenty-twelve.php';
					break;
				case 'twentythirteen':
					include_once CS_ABSPATH . 'includes/theme-support/class-wc-twenty-thirteen.php';
					break;
				case 'twentyfourteen':
					include_once CS_ABSPATH . 'includes/theme-support/class-wc-twenty-fourteen.php';
					break;
				case 'twentyfifteen':
					include_once CS_ABSPATH . 'includes/theme-support/class-wc-twenty-fifteen.php';
					break;
				case 'twentysixteen':
					include_once CS_ABSPATH . 'includes/theme-support/class-wc-twenty-sixteen.php';
					break;
				case 'twentyseventeen':
					include_once CS_ABSPATH . 'includes/theme-support/class-wc-twenty-seventeen.php';
					break;
				case 'twentynineteen':
					include_once CS_ABSPATH . 'includes/theme-support/class-wc-twenty-nineteen.php';
					break;
			}
		}
	}

	/**
	 * Include required frontend files.
	 */
	public function frontend_includes() {
		include_once CS_ABSPATH . 'includes/cs-notice-functions.php';
		include_once CS_ABSPATH . 'includes/class-cs-form-handler.php';
		/*
		include_once CS_ABSPATH . 'includes/wc-template-hooks.php';
		include_once CS_ABSPATH . 'includes/class-wc-template-loader.php';
		include_once CS_ABSPATH . 'includes/class-wc-frontend-scripts.php';
		include_once CS_ABSPATH . 'includes/class-wc-cart.php';
		include_once CS_ABSPATH . 'includes/class-wc-tax.php';
		include_once CS_ABSPATH . 'includes/class-wc-shipping-zones.php';
		include_once CS_ABSPATH . 'includes/class-wc-embed.php';*/
		include_once CS_ABSPATH . 'includes/class-cs-student.php';
		include_once CS_ABSPATH . 'includes/class-cs-session-handler.php';
	}

	/**
	 * Function used to Init CommunityService Template Functions - This makes them pluggable by plugins and themes.
	 */
	public function include_template_functions() {
		include_once CS_ABSPATH . 'includes/cs-template-functions.php';
	}

	/**
	 * Init CommunityService when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'before_communityservice_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();

		// Load class instances.
		$this->task_factory                     = new CS_Task_Factory();
		//$this->order_factory                       = new CS_Order_Factory();
		//$this->countries                           = new CS_Countries();
		//$this->integrations                        = new CS_Integrations();
		//$this->structured_data                     = new CS_Structured_Data();

		// Classes/actions loaded for the frontend and for ajax requests.
		if ( $this->is_request( 'frontend' ) ) {
			// Session class, handles session data for users - can be overwritten if custom handler is needed.
			$session_class = apply_filters( 'communityservice_session_handler', 'CS_Session_Handler' );
			$this->session = new $session_class();
			$this->session->init();

			$this->student = new CS_Student( get_current_user_id(), true );
			// Student should be saved during shutdown.
			//add_action( 'shutdown', array( $this->student, 'save' ), 10 );
		}

		$this->load_webhooks();

		// Init action.
		do_action( 'communityservice_init' );
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/communityservice/communityservice-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/communityservice-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'communityservice' );

		unload_textdomain( 'communityservice' );
		load_textdomain( 'communityservice', WP_LANG_DIR . '/communityservice/communityservice-' . $locale . '.mo' );
		//load_plugin_textdomain( 'communityservice', false, plugin_basename( dirname( CS_PLUGIN_FILE ) ) . '/i18n/languages' );
	}

	/**
	 * Ensure theme and server variable compatibility and setup image sizes.
	 */
	public function setup_environment() {
		/* @deprecated 2.2 Use CS()->template_path() instead. */
		$this->define( 'CS_TEMPLATE_PATH', $this->template_path() );

		$this->add_thumbnail_support();
	}

	/**
	 * Ensure post thumbnail support is turned on.
	 */
	private function add_thumbnail_support() {
		if ( ! current_theme_supports( 'post-thumbnails' ) ) {
			add_theme_support( 'post-thumbnails' );
		}
	}

	/**
	 * Add CS Image sizes to WP.
	 *
	 * As of 3.3, image sizes can be registered via themes using add_theme_support for CommunityService
	 * and defining an array of args. If these are not defined, we will use defaults. This is
	 * handled in wc_get_image_size function.
	 *
	 * 3.3 sizes:
	 *
	 * communityservice_thumbnail - Used in task listings. We assume these work for a 3 column grid layout.
	 * communityservice_single - Used on single task pages for the main image.
	 *
	 * @since 1.0
	 */
	public function add_image_sizes() {
		$thumbnail         = wc_get_image_size( 'thumbnail' );
		$single            = wc_get_image_size( 'single' );
		$gallery_thumbnail = wc_get_image_size( 'gallery_thumbnail' );

		add_image_size( 'communityservice_thumbnail', $thumbnail['width'], $thumbnail['height'], $thumbnail['crop'] );
		add_image_size( 'communityservice_single', $single['width'], $single['height'], $single['crop'] );
		add_image_size( 'communityservice_gallery_thumbnail', $gallery_thumbnail['width'], $gallery_thumbnail['height'], $gallery_thumbnail['crop'] );

		// Registered for bw compat. @todo remove in 4.0.
		add_image_size( 'shop_catalog', $thumbnail['width'], $thumbnail['height'], $thumbnail['crop'] );
		add_image_size( 'shop_single', $single['width'], $single['height'], $single['crop'] );
		add_image_size( 'shop_thumbnail', $gallery_thumbnail['width'], $gallery_thumbnail['height'], $gallery_thumbnail['crop'] );
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', CS_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( CS_PLUGIN_FILE ) );
	}

	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	public function template_path() {
		return apply_filters( 'communityservice_template_path', 'communityservice/' );
	}

	/**
	 * Get Ajax URL.
	 *
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

	/**
	 * Return the CS API URL for a given request.
	 *
	 * @param string    $request Requested endpoint.
	 * @param bool|null $ssl     If should use SSL, null if should auto detect. Default: null.
	 * @return string
	 */
	public function api_request_url( $request, $ssl = null ) {
		if ( is_null( $ssl ) ) {
			$scheme = wp_parse_url( home_url(), PHP_URL_SCHEME );
		} elseif ( $ssl ) {
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}

		if ( strstr( get_option( 'permalink_structure' ), '/index.php/' ) ) {
			$api_request_url = trailingslashit( home_url( '/index.php/wc-api/' . $request, $scheme ) );
		} elseif ( get_option( 'permalink_structure' ) ) {
			$api_request_url = trailingslashit( home_url( '/wc-api/' . $request, $scheme ) );
		} else {
			$api_request_url = add_query_arg( 'wc-api', $request, trailingslashit( home_url( '', $scheme ) ) );
		}

		return esc_url_raw( apply_filters( 'communityservice_api_request_url', $api_request_url, $request, $ssl ) );
	}

	/**
	 * Load & enqueue active webhooks.
	 *
	 * @since 2.2
	 */
	private function load_webhooks() {

		if ( ! is_blog_installed() ) {
			return;
		}

		//wc_load_webhooks();
	}

	/**
	 * CommunityService Payment Token Meta API and Term/Order item Meta - set table names.
	 */
	public function wpdb_table_fix() {
		global $wpdb;
		//$wpdb->order_itemmeta    = $wpdb->prefix . 'communityservice_order_itemmeta';
	}

	/**
	 * Get queue instance.
	 *
	 * @return CS_Queue_Interface
	 */
	public function queue() {
		return CS_Queue::instance();
	}

	/**
	 * Email Class.
	 *
	 * @return CS_Emails
	 */
	public function mailer() {
		return CS_Emails::instance();
	}
	private function install(){
		global $wpdb;
		$wpdb->hide_errors();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( self::get_schema() );
	}
	private static function get_schema() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables = "
		CREATE TABLE {$wpdb->prefix}communityservice_sessions (
		session_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		session_key char(32) NOT NULL,
		session_value longtext NOT NULL,
		session_expiry BIGINT UNSIGNED NOT NULL,
		PRIMARY KEY  (session_id),
		UNIQUE KEY session_key (session_key)
		) $collate;";
		return $tables;
	}
}
