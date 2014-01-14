<?php
/*
		 * @package   GFP_Stripe
		 * @copyright 2013 gravity+
		 * @license   GPL-2.0+
		 * @since     0.1.0
		 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * GFP_Stripe Class
 *
 * Controls everything
 *
 * @since 0.1.0
 * */
class GFP_Stripe {

	/**
	 * Instance of this class.
	 *
	 * @since    1.7.9.1
	 *
	 * @var      object
	 */
	private static $_this = null;

	/**
	 *
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	private static $slug = "gravity-forms-stripe";

	/**
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	public static $version = '1.8.1';

	/**
	 *
	 *
	 * @since
	 *
	 * @var string
	 */
	private static $min_gravityforms_version = '1.8.1';

	/**
	 *
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	private static $transaction_response = '';

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.7.9.1
	 *
	 * @uses      wp_die()
	 * @uses      __()
	 * @uses      register_activation_hook()
	 * @uses      add_action()
	 *
	 * @return void
	 */
	function __construct () {

		if ( isset( self::$_this ) )
			wp_die( sprintf( __( 'There is already an instance of %s.',
													 'gfp-stripe' ), get_class( $this ) ) );

		self::$_this = $this;

		register_activation_hook( GFP_STRIPE_FILE, array( 'GFP_Stripe', 'activate' ) );
		register_deactivation_hook( 'gravityforms/gravityforms.php', array( 'GFP_Stripe', 'deactivate_gravityforms' ) );

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'init', array( $this, 'init' ) );

	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone () {
	}

	/**
	 * Private unserialize method to prevent unserializing of the *Singleton*
	 * instance.
	 *
	 * @return void
	 */
	private function __wakeup () {
	}

	/**
	 * @return GFP_Stripe|null|object
	 */
	static function this () {
		return self::$_this;
	}

	//------------------------------------------------------
	//------------- SETUP --------------------------
	//------------------------------------------------------

	/**
	 * Activation
	 *
	 * @since 0.1.0
	 *
	 * @uses  GFP_Stripe::check_for_curl()
	 * @uses  GFP_Stripe::add_permissions()
	 * @uses  GFP_Stripe::redirect_to_settings_page()
	 *
	 * @return void
	 */
	public static function activate () {

		self::$_this->check_for_gravity_forms();

		$curl = self::$_this->check_for_curl();

		if ( $curl ) {
			self::$_this->add_permissions();
			self::$_this->set_settings_page_redirect();
		}

		delete_transient( 'gfp_stripe_currency' );
	}

	/**
	 * Make sure Gravity Forms is installed before activating this plugin
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  deactivate_plugins()
	 * @uses  __()
	 *
	 * @return void
	 */
	public function check_for_gravity_forms () {
		if ( ( array_key_exists( 'action', $_POST ) ) && ( 'activate-selected' == $_POST['action'] ) && ( in_array( 'gravityforms/gravityforms.php', $_POST['checked'] ) ) ) {
			return;
		}
		else {
			if ( ! class_exists( 'GFForms' ) ) {
				deactivate_plugins( basename( GFP_STRIPE_FILE ) );
				$message = __( 'You must install and activate Gravity Forms first.', 'gfp-stripe' );
				die( $message );
			}
		}
	}

	/**
	 *  Make sure curl is available on server
	 *
	 * The plugin will not work without curl enabled on the server
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  deactivate_plugins()
	 * @uses  __()
	 *
	 * @return bool
	 */
	public function check_for_curl () {
		if ( ! function_exists( 'curl_init' ) ) {

			deactivate_plugins( plugin_basename( trim( GFP_STRIPE_FILE ) ) );
			$html = '<div class="error">';
			$html .= '<p>';
			$html .= __( 'Gravity Forms + Stripe needs curl to be installed on your server. Please contact your host.', 'gfp-stripe' );
			$html .= '</p>';
			$html .= '</div>';
			echo $html;

			return false;
		}
		else {
			return true;
		}
	}

	/**
	 *  Add permissions
	 *
	 * @since 0.1.0
	 *
	 * @uses  add_cap()
	 *
	 * @return void
	 */
	public function add_permissions () {
		global $wp_roles;
		$wp_roles->add_cap( 'administrator', 'gfp_stripe' );
		$wp_roles->add_cap( 'administrator', 'gfp_stripe_uninstall' );
	}

	/**
	 * Set option to redirect to settings page
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  set_transient()
	 *
	 * @return void
	 */
	public static function set_settings_page_redirect () {
		set_transient( 'gfp_stripe_settings_page_redirect', true, HOUR_IN_SECONDS );
	}

	/**
	 * Plugin initialization
	 *
	 * @since 0.1.0
	 *
	 * @uses  add_action()
	 * @uses  add_filter()
	 * @uses  load_plugin_textdomain()
	 */
	public function init () {

		if ( ( ! $this->is_gravityforms_supported() ) && ( ! ( isset( $_GET['action'] ) && ( ( 'upgrade-plugin' == $_GET['action'] ) || ( 'update-selected' == $_GET['action'] ) ) ) ) ) {
			if ( isset( $_GET['action'] ) && ( ! ( 'activate' == $_GET['action'] ) ) ) {
				$message = __( 'Gravity Forms + Stripe requires Gravity Forms ' . self::$min_gravityforms_version . '.', 'gfp-stripe' );
				$this->set_admin_notice( $message );
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			}

			return;
		}

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		//add logging support
		add_filter( 'gform_logging_supported', array( $this, 'gform_logging_supported' ) );

		//limit currency to only those allowed by Stripe account
		add_filter( 'gform_currency', array( $this, 'gform_currency' ) );
		add_filter( 'gform_currencies', array( $this, 'gform_currencies' ) );

		//load translations
		load_plugin_textdomain( 'gfp-stripe', false, dirname( GFP_STRIPE_FILE ) . '/languages' );

		if ( ! is_admin() ) {

			//load Stripe JS
			add_action( 'gform_enqueue_scripts', array( $this, 'gform_enqueue_scripts' ), '', 2 );

			//remove input names from credit card field
			add_filter( 'gform_field_content', array( $this, 'gform_field_content' ), 10, 5 );

			//handle post submission
			add_filter( 'gform_field_validation', array( $this, 'gform_field_validation' ), 10, 4 );
			add_filter( 'gform_get_form_filter', array( $this, 'gform_get_form_filter' ), 10, 1 );
			add_filter( 'gform_validation', array( $this, 'gform_validation' ), 10, 4 );
			add_filter( 'gform_save_field_value', array( $this, 'gform_save_field_value' ), 10, 4 );
			add_action( 'gform_entry_created', array( $this, 'gform_entry_created' ), 10, 2 );

		}
	}

	/**
	 *
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  add_filter()
	 *
	 * @return void
	 */
	public function admin_menu () {
		//create the subnav left menu
		add_filter( 'gform_addon_navigation', array( self::$_this, 'gform_addon_navigation' ) );
	}

	/**
	 *
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  add_filter()
	 * @uses  GFP_Stripe::setup()
	 * @uses  GFP_Stripe::is_stripe_page()
	 * @uses  wp_enqueue_script()
	 * @uses  GFCommon::get_base_path()
	 * @uses  RGForms::get()
	 * @uses  RGForms::add_settings_page()
	 * @uses  GFP_Stripe::get_base_url()
	 * @uses  GFCommon::get_base_path()
	 *
	 * @return void
	 *
	 */
	public function admin_init () {

		if ( ( ! GFP_Stripe::is_gravityforms_supported() ) && ( ! ( isset( $_GET['action'] ) && ( ( 'upgrade-plugin' == $_GET['action'] ) || ( 'update-selected' == $_GET['action'] ) ) ) ) ) {
			if ( isset( $_GET['action'] ) && ( ! ( 'activate' == $_GET['action'] ) ) ) {
				$message = __( 'Gravity Forms + Stripe requires Gravity Forms ' . self::$min_gravityforms_version . '.', 'gfp-more-stripe' );
				self::set_admin_notice( $message );
				add_action( 'admin_notices', array( 'GFPMoreStripe', 'admin_notices' ) );
			}

			return;
		}

		add_filter( 'plugin_action_links_' . plugin_basename( GFP_STRIPE_FILE ), array( self::$_this, 'plugin_action_links' ) );

		//run the setup when version changes
		self::$_this->setup();
		self::$_this->redirect_to_settings_page();

		$settings       = get_option( 'gfp_stripe_settings' );
		$do_presstrends = ! empty( $settings['do_presstrends'] );
		if ( $do_presstrends ) {
			self::$_this->do_presstrends();
		}

		//integrate with Members plugin
		if ( function_exists( 'members_get_capabilities' ) )
			add_filter( 'members_get_capabilities', array( self::$_this, 'members_get_capabilities' ) );

		//enable credit card field
		add_filter( 'gform_enable_credit_card_field', '__return_true' );

		if ( self::$_this->is_stripe_page() ) {

			//enqueue sack for AJAX requests
			wp_enqueue_script( array( 'sack' ) );

			//load Gravity Forms tooltips
			require_once( GFCommon::get_base_path() . '/tooltips.php' );
			add_filter( 'gform_tooltips', array( self::$_this, 'gform_tooltips' ) );

		}
		else if ( in_array( RG_CURRENT_PAGE, array( 'admin-ajax.php' ) ) ) {

			add_action( 'wp_ajax_gfp_stripe_update_feed_active', array( self::$_this, 'gfp_stripe_update_feed_active' ) );
			add_action( 'wp_ajax_gfp_select_stripe_form', array( self::$_this, 'gfp_select_stripe_form' ) );

		}
		else if ( 'gf_settings' == RGForms::get( 'page' ) ) {
			RGForms::add_settings_page( 'Stripe', array( self::$_this, 'settings_page' ), self::get_base_url() . '/images/stripe_wordpress_icon_32.png' );
			add_action( 'gform_currency_setting_message', array( self::$_this, 'gform_currency_setting_message' ) );
			//add_filter( 'gform_currency_disabled', '__return_true' );

			//load Gravity Forms tooltips
			require_once( GFCommon::get_base_path() . '/tooltips.php' );
			add_filter( 'gform_tooltips', array( self::$_this, 'gform_tooltips' ) );
		}
		else if ( 'gf_entries' == RGForms::get( 'page' ) ) {

		}

	}

	/**
	 * Create or update database tables.
	 *
	 * Will only run when version changes.
	 *
	 * @since 0.1.0
	 *
	 * @uses  get_option()
	 * @uses  GFP_Stripe_Data::update_table()
	 * @uses  update_option()
	 *
	 * @return void
	 */
	private function setup () {
		if ( get_option( 'gfp_stripe_version' ) != self::$version ) {
			GFP_Stripe_Data::update_table();
		}

		update_option( 'gfp_stripe_version', self::$version );
	}

	/**
	 *  Redirect to settings page if not activating multiple plugins at once
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  get_transient()
	 * @uses  delete_transient()
	 * @uses  admin_url()
	 * @uses  wp_redirect()
	 *
	 * @return void
	 */
	public static function redirect_to_settings_page () {
		if ( true == get_transient( 'gfp_stripe_settings_page_redirect' ) ) {
			delete_transient( 'gfp_stripe_settings_page_redirect' );
			if ( ! isset( $_GET['activate-multi'] ) ) {
				wp_redirect( self_admin_url( 'admin.php?page=gf_settings&subview=Stripe' ) );
			}
		}
	}

	/**
	 * @return bool|mixed
	 */
	public static function is_gravityforms_supported () {
		if ( class_exists( 'GFCommon' ) ) {
			$is_correct_version = version_compare( GFCommon::$version, self::$min_gravityforms_version, '>=' );

			return $is_correct_version;
		}
		else {
			return false;
		}
	}

	//------------------------------------------------------
	//------------- GENERAL ADMIN --------------------------
	//------------------------------------------------------
	/**
	 *  Output admin notices
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  get_site_transient()
	 * @uses  get_transient()
	 * @uses  delete_site_transient()
	 * @uses  delete_transient()
	 *
	 * @return void
	 */
	public function admin_notices () {

		$notices = function_exists( 'get_site_transient' ) ? get_site_transient( 'gfp-stripe-admin_notices' ) : get_transient( 'gfp-stripe-admin_notices' );
		if ( $notices ) {
			foreach ( $notices as $notice ) {

				$message = '<div class="error"><p>' . $notice . '</p></div>';

				echo $message;
			}
			if ( function_exists( 'delete_site_transient' ) ) {
				delete_site_transient( 'gfp-stripe-admin_notices' );
			}
			else {
				delete_transient( 'gfp-stripe-admin_notices' );
			}
		}

	}

	/**
	 * Create an admin notice
	 *
	 * @since 1.7.11.1
	 *
	 * @uses  get_site_transient()
	 * @uses  get_transient()
	 * @uses  set_site_transient()
	 * @uses  set_transient()
	 *
	 * @param $notice
	 *
	 * @return void
	 */
	private function set_admin_notice ( $notice ) {
		if ( function_exists( 'get_site_transient' ) ) {
			$notices = get_site_transient( 'gfp-stripe-admin_notices' );
		}
		else {
			$notices = get_transient( 'gfp-stripe-admin_notices' );
		}
		if ( is_array( $notices ) ) {
			if ( ! in_array( $notice, $notices ) ) {
				$notices[] = $notice;
			}
		}
		else {
			$notices[] = $notice;
		}
		if ( function_exists( 'set_site_transient' ) ) {
			set_site_transient( 'gfp-stripe-admin_notices', $notices );
		}
		else {
			set_transient( 'gfp-stripe-admin_notices', $notices );
		}
	}

	/**
	 * Add a link to this plugin's settings page
	 *
	 * @uses self_admin_url()
	 * @uses __()
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function plugin_action_links ( $links ) {
		return array_merge(
			array(
					 'settings' => '<a href="' . self_admin_url( 'admin.php?page=gf_settings&subview=Stripe' ) . '">' . __( 'Settings', 'gfp-stripe' ) . '</a>'
			),
			$links
		);
	}

	/**
	 *  Disallow Gravity Forms deactivation if this plugin is still active
	 *
	 * Prevents a fatal error if this plugin is still active when user attempts to deactivate Gravity Forms
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  plugin_basename()
	 * @uses  is_plugin_active()
	 * @uses  __()
	 * @uses  get_site_transient()
	 * @uses  get_transient()
	 * @uses  set_site_transient()
	 * @uses  set_transient()
	 * @uses  self_admin_url()
	 * @uses  wp_redirect()
	 *
	 * @return void
	 */
	public static function deactivate_gravityforms () {
		$plugin = plugin_basename( trim( GFP_STRIPE_FILE ) );
		if ( ( array_key_exists( 'action', $_POST ) ) && ( 'deactivate-selected' == $_POST['action'] ) && ( in_array( $plugin, $_POST['checked'] ) ) ) {
			return;
		}
		else {
			if ( is_plugin_active( $plugin ) ) {
				$message = sprintf( __( "You must deactivate %s first.", 'gfp-stripe' ), basename( GFP_STRIPE_FILE ) );
				self::$_this->set_admin_notice( $message );
				wp_redirect( self_admin_url( 'plugins.php' ) );
				exit;
			}
		}
	}

	//------------------------------------------------------
	//------------- MEMBERS PLUGIN INTEGRATION -------------
	//------------------------------------------------------
	/**
	 * Provide the Members plugin with this plugin's list of capabilities
	 *
	 * @since 0.1.0
	 *
	 * @param $caps
	 *
	 * @return array
	 */
	public function members_get_capabilities ( $caps ) {
		return array_merge( $caps, array( 'gfp_stripe', 'gfp_stripe_uninstall' ) );
	}

	/**
	 * Check if user has the required permission
	 *
	 * @since 0.1.0
	 *
	 * @uses  current_user_can()
	 *
	 * @param $required_permission
	 *
	 * @return bool|string
	 */
	public static function has_access ( $required_permission ) {
		$has_members_plugin = function_exists( 'members_get_capabilities' );
		$has_access         = $has_members_plugin ? current_user_can( $required_permission ) : current_user_can( 'level_7' );
		if ( $has_access )
			return $has_members_plugin ? $required_permission : 'level_7';
		else
			return false;
	}

	//------------------------------------------------------
	//------------- CURRENCY --------------------------
	//------------------------------------------------------
	/**
	 * Get the currency or currencies supported by the Stripe account
	 *
	 * @since
	 *
	 * @uses get_transient()
	 * @uses GFP_Stripe::include_api()
	 * @uses GFP_Stripe::get_api_key()
	 * @uses Stripe_Account::retrieve()
	 * @uses set_transient()
	 *
	 * @param $currency
	 *
	 * @return mixed
	 */
	public function gform_currency ( $currency ) {
		$stripe_currency = get_transient( 'gfp_stripe_currency' );
		if ( false === $stripe_currency ) {
			self::$_this->include_api();
			$api_key = self::$_this->get_api_key( 'secret' );
			if ( ! empty( $api_key ) ) {
				Stripe::setApiKey( $api_key );
				$account                     = Stripe_Account::retrieve();
				$stripe_default_currency     = strtoupper( $account['default_currency'] );
				$stripe_currencies_supported = array_map( 'strtoupper', $account['currencies_supported'] );
				set_transient( 'gfp_stripe_currency',
											 array( 'default'   => $stripe_default_currency,
															'supported' => $stripe_currencies_supported ),
											 24 * HOUR_IN_SECONDS );
				if ( ( $stripe_default_currency !== $currency ) && ( ! in_array( $currency, $stripe_currencies_supported ) ) ) {
					$currency = $stripe_default_currency;
				}
			}
			else {
				update_option( 'rg_gforms_currency', 'USD' );
				$currency = 'USD'; //default Gravity Forms currency
			}
		}
		else {
			if ( ! in_array( $currency, $stripe_currency['supported'] ) ) {
				$currency = $stripe_currency['default'];
			}
		}

		return $currency;
	}

	/**
	 * Currencies supported by Stripe account
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  get_transient()
	 * @uses  GFP_Stripe::include_api()
	 * @uses  GFP_Stripe::get_api_key()
	 * @uses  Stripe::setApiKey()
	 * @uses  Stripe_Account::retrieve()
	 * @uses  set_transient()
	 *
	 * @param $currencies
	 *
	 * @return array
	 */
	public function gform_currencies ( $currencies ) {

		$current_currency = get_transient( 'gfp_stripe_currency' );
		if ( false === $current_currency ) {
			self::$_this->include_api();
			$api_key = self::$_this->get_api_key( 'secret' );
			if ( ! empty( $api_key ) ) {
				Stripe::setApiKey( $api_key );
				$account              = Stripe_Account::retrieve();
				$default_currency     = strtoupper( $account['default_currency'] );
				$currencies_supported = array_map( 'strtoupper', $account['currencies_supported'] );
				set_transient( 'gfp_stripe_currency',
											 array( 'default'   => $default_currency,
															'supported' => $currencies_supported ),
											 24 * HOUR_IN_SECONDS );
			}
		}

		if ( ( ! empty( $current_currency ) ) || ( ! empty( $default_currency ) ) && ( ! empty( $currencies_supported ) ) ) {
			$currencies = array_intersect_key( $currencies, ( $current_currency ) ? array_flip( $current_currency['supported'] ) : array_flip( $currencies_supported ) );
		}

		return $currencies;
	}

	/**
	 * Currency setting message
	 *
	 * @since
	 *
	 * @uses GFP_Stripe::get_api_key()
	 * @uses GFCommon::get_currency()
	 * @uses RGCurrency::get_currency()
	 * @uses __()
	 *
	 * @return void
	 */
	public function gform_currency_setting_message () {
		$api_key = self::$_this->get_api_key( 'secret' );
		if ( ! empty( $api_key ) ) {
			if ( ! class_exists( 'RGCurrency' ) )
				require_once( 'currency.php' );
			$currency_name = RGCurrency::get_currency( GFCommon::get_currency() );
			$currency_name = $currency_name['name'];
			echo '<div class=\'gform_currency_message\'>' . __( "Your Stripe account allows these currencies.", 'gfp-stripe' ) . '</div>';
		}
		else {
			echo '<div class=\'gform_currency_message\'>' . sprintf( __( "Your %sStripe settings%s are not filled in -- using default currency.", 'gfp-stripe' ), '<a href="admin.php?page=gf_settings&addon=Stripe">', '</a>' ) . '</div>';
		}
	}

	//------------------------------------------------------
	//------------- SETTINGS PAGE --------------------------
	//------------------------------------------------------

	/**
	 * Create Stripe menu under Forms
	 *
	 * @since 0.1.0
	 *
	 * @uses  GFP_Stripe::has_access()
	 * @uses  __()
	 *
	 * @param $menus
	 *
	 * @return array
	 */
	public function gform_addon_navigation ( $menus ) {

		// Adding submenu if user has access
		$permission = $this->has_access( 'gfp_stripe' );
		if ( ! empty( $permission ) )
			$menus[] = array(
				'name'       => 'gfp_stripe',
				'label'      => __( 'Stripe', 'gfp-stripe' ),
				'callback'   => array( $this, 'stripe_page' ),
				'permission' => $permission );

		return $menus;
	}

	/**
	 * Menu page callback
	 *
	 * @since 0.1.0
	 *
	 * @uses  rgget()
	 * @uses  GFP_Stripe::edit_page()
	 * @uses  GFP_Stripe::stats_page()
	 * @uses  GFP_Stripe::list_page()
	 *
	 * @return void
	 */
	public function stripe_page () {
		$view = rgget( 'view' );
		if ( 'edit' == $view )
			$this->edit_page( rgget( 'id' ) );
		else if ( 'stats' == $view )
			$this->stats_page( rgget( 'id' ) );
		else
			$this->list_page();
	}

	/**
	 * Render settings page
	 *
	 * @since 0.1.0
	 *
	 * @uses  check_admin_referer()
	 * @uses  GFP_Stripe::uninstall()
	 * @uses  _e()
	 * @uses  rgpost()
	 * @uses  apply_filters()
	 * @uses  update_option()
	 * @uses  get_option()
	 * @uses  delete_option()
	 * @uses  has_filter()
	 * @uses  wp_nonce_field()
	 * @uses  gform_tooltip()
	 * @uses  GFPMoreStripe::get_slug()
	 * @uses  GFPMoreStripe::get_version()
	 * @uses  GFPMoreStripeUpgrade::get_version_info()
	 * @uses  GFCommon::get_base_url()
	 * @uses  rgar()
	 * @uses  esc_attr()
	 * @uses  GFP_Stripe::get_base_url()
	 * @uses  do_action()
	 * @uses  GFCommon::current_user_can_any()
	 * @uses  __()
	 *
	 * @return void
	 */
	public function settings_page () {

		if ( isset( $_POST["uninstall"] ) ) {
			check_admin_referer( 'uninstall', 'gfp_stripe_uninstall' );
			$this->uninstall();

			?>
			<div class="updated fade"
					 style="padding:20px;"><?php _e( sprintf( "Gravity Forms Stripe Add-On has been successfully uninstalled. It can be re-activated from the %splugins page%s.", "<a href='plugins.php'>", "</a>" ), 'gfp-stripe' ) ?></div>
			<?php
			return;
		}
		else if ( isset( $_POST["gfp_stripe_submit"] ) ) {
			check_admin_referer( 'update', 'gfp_stripe_update' );
			$settings = array(
				'test_secret_key'      => trim( rgpost( 'gfp_stripe_test_secret_key' ) ),
				'test_publishable_key' => trim( rgpost( 'gfp_stripe_test_publishable_key' ) ),
				'live_secret_key'      => trim( rgpost( 'gfp_stripe_live_secret_key' ) ),
				'live_publishable_key' => trim( rgpost( 'gfp_stripe_live_publishable_key' ) ),
				'mode'                 => rgpost( 'gfp_stripe_mode' ),
				'do_presstrends'       => rgpost( 'gfp_stripe_do_presstrends' )
			);
			$settings = apply_filters( 'gfp_stripe_save_settings', $settings );


			update_option( 'gfp_stripe_settings', $settings );

			$gfp_support_key = get_option( 'gfp_support_key' );
			$key             = rgpost( 'gfp_support_key' );
			if ( empty( $key ) ) {
				delete_option( 'gfp_support_key' );
			}
			else {
				if ( $gfp_support_key != $key ) {
					$key = md5( trim( $key ) );
					update_option( 'gfp_support_key', $key );
				}
			}

			delete_transient( 'gfp_stripe_currency' );
			if ( ! empty( $settings['test_secret_key'] ) ) {
				update_option( 'rg_gforms_currency', $this->gform_currency( get_option( 'rg_gforms_currency' ) ) );
			}
		}
		else if ( has_filter( 'gfp_stripe_settings_page_action' ) ) {
			$do_return = '';
			$do_return = apply_filters( 'gfp_stripe_settings_page_action', $do_return );
			if ( $do_return ) {
				return;
			}
		}

		$settings        = get_option( 'gfp_stripe_settings' );
		$gfp_support_key = get_option( 'gfp_support_key' );
		if ( ! empty( $settings ) ) {
			$is_valid = $this->is_valid_key();

			$message = array();
			if ( $is_valid[0] )
				$message[0] = 'Valid API key.';
			else {
				foreach ( $is_valid[1] as $key => $value ) {
					if ( ! empty( $settings[$key] ) ) {
						if ( ! $value ) {
							$message[1][$key] = 'Invalid API key. Please try again.';
						}
						else {
							$message[1][$key] = 'Valid API key.';
						}
					}
				}
			}
		}
		else {
			$is_valid = array();
		}


		?>
		<style>
			.valid_credentials {
				color: green;
			}

			.invalid_credentials {
				color: red;
			}

			.size-1 {
				width: 400px;
			}

			span.strong {
				font-weight: bold;
			}

			span.emphasis {
				font-style: italic;
			}
		</style>

		<form method="post" action="">
			<?php wp_nonce_field( 'update', 'gfp_stripe_update' ) ?>

			<h3><?php _e( 'Stripe Settings', 'gfp-stripe' ); ?></h3>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label
							for="gfp_support_key"><?php _e( "gravity+ Support License Key", "gfp-stripe" ); ?></label><?php gform_tooltip( 'stripe_support_license_key' ) ?>
					</th>
					<td>
						<?php

						$key_field = '<input type="password" ' . ( class_exists( 'GFPMoreStripeUpgrade' ) ? '' : 'disabled' ) . ' name="gfp_support_key" id="gfp_support_key" style="width:350px;" value="' . ( empty( $gfp_support_key ) ? '' : $gfp_support_key ) . '" />';
						if ( class_exists( 'GFPMoreStripeUpgrade' ) ) {
							$version_info = GFPMoreStripeUpgrade::get_version_info( GFPMoreStripe::get_slug(), $gfp_support_key, GFPMoreStripe::get_version(), ( isset( $_POST["gfp_stripe_submit"] ) ? false : true ) );
							if ( $version_info['is_valid_key'] )
								$key_field .= "&nbsp;<img src='" . GFCommon::get_base_url() . "/images/tick.png' class='gf_keystatus_valid' alt='valid key' title='valid key'/>";
							else if ( ! empty( $gfp_support_key ) )
								$key_field .= "&nbsp;<img src='" . GFCommon::get_base_url() . "/images/cross.png' class='gf_keystatus_invalid' alt='invalid key' title='invalid key'/>";
						}
						echo $key_field;
						?>
						<br/>
						<?php _e( sprintf( "The license key is used for access to %s+(More) Stripe%s automatic upgrades and support. Activate +(More) Stripe to enter your license key.", "<a href='https://gravityplus.pro' target='_blank'>", "</a>" ), 'gfp-stripe' ); ?>
					</td>
				</tr>
			</table>
			<div class="hr-divider"></div>
			<h3><?php _e( 'Stripe Account Information', 'gfp-stripe' ) ?></h3>

			<p style="text-align: left;">
				<?php _e( sprintf( "Stripe is a payment gateway for merchants. Use Gravity Forms to collect payment information and automatically integrate to your client's Stripe account. If you don't have a Stripe account, you can %ssign up for one here%s", "<a href='http://www.stripe.com' target='_blank'>", "</a>" ), 'gfp-stripe' ) ?>
			</p>
			<table class="form-table">

				<tr>
					<th scope="row" nowrap="nowrap"><label
							for="gfp_stripe_mode"><?php _e( 'API Mode', 'gfp-stripe' ); ?> <?php gform_tooltip( 'stripe_api' ) ?></label>
					</th>
					<td width="88%">
						<input type="radio" name="gfp_stripe_mode" id="gfp_stripe_mode_live"
									 value="live" <?php echo rgar( $settings, 'mode' ) != 'test' ? "checked='checked'" : '' ?>/> <label
							class="inline" for="gfp_stripe_mode_live"><?php _e( 'Live', 'gfp-stripe' ); ?></label> &nbsp;&nbsp;&nbsp;
						<input type="radio" name="gfp_stripe_mode" id="gfp_stripe_mode_test"
									 value="test" <?php echo 'test' == rgar( $settings, 'mode' ) ? "checked='checked'" : '' ?>/> <label
							class="inline" for="gfp_stripe_mode_test"><?php _e( 'Test', 'gfp-stripe' ); ?></label>
					</td>
				</tr>
				<tr>
					<td colspan='2'>
						<p><?php _e( sprintf( "You can find your <strong>Stripe API keys</strong> needed below in your Stripe dashboard 'Account Settings' %shere%s", "<a href='https://manage.stripe.com/account/apikeys' target='_blank'>", "</a>" ), 'gfp-stripe' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row" nowrap="nowrap"><label
							for="gfp_stripe_test_secret_key"><?php _e( 'Test Secret Key', 'gfp-stripe' ); ?> <?php gform_tooltip( 'stripe_test_secret_key' ) ?></label>
					</th>
					<td width="88%">
						<input class="size-1" id="gfp_stripe_test_secret_key" name="gfp_stripe_test_secret_key"
									 value="<?php echo trim( esc_attr( rgar( $settings, 'test_secret_key' ) ) ) ?>"/> <img
							src="<?php echo self::$_this->get_base_url() ?>/images/<?php echo $is_valid[1]['test_secret_key'] ? 'tick.png' : 'stop.png' ?>"
							border="0" alt="<?php array_key_exists( 0, $message ) ? $message[0] : $message[1]['test_secret_key'] ?>"
							title="<?php echo array_key_exists( 0, $message ) ? $message[0] : $message[1]['test_secret_key'] ?>"
							style="display:<?php echo ( empty( $message[0] ) && empty( $message[1]['test_secret_key'] ) ) ? 'none;' : 'inline;' ?>"/>
						<br/>
					</td>
				</tr>
				<tr>
					<th scope="row" nowrap="nowrap"><label
							for="gfp_stripe_test_publishable_key"><?php _e( 'Test Publishable Key', 'gfp-stripe' ); ?> <?php gform_tooltip( 'stripe_test_publishable_key' ) ?></label>
					</th>
					<td width="88%">
						<input class="size-1" id="gfp_stripe_test_publishable_key" name="gfp_stripe_test_publishable_key"
									 value="<?php echo trim( esc_attr( rgar( $settings, 'test_publishable_key' ) ) ) ?>"/> <img
							src="<?php echo self::$_this->get_base_url() ?>/images/<?php echo $is_valid[1]['test_publishable_key'] ? 'tick.png' : 'stop.png' ?>"
							border="0"
							alt="<?php array_key_exists( 0, $message ) ? $message[0] : $message[1]['test_publishable_key'] ?>"
							title="<?php echo array_key_exists( 0, $message ) ? $message[0] : $message[1]['test_publishable_key'] ?>"
							style="display:<?php echo ( empty( $message[0] ) && empty( $message[1]['test_publishable_key'] ) ) ? 'none;' : 'inline;' ?>"/>
						<br/>
					</td>
				</tr>
				<tr>
					<th scope="row" nowrap="nowrap"><label
							for="gfp_stripe_live_secret_key"><?php _e( 'Live Secret Key', 'gfp-stripe' ); ?> <?php gform_tooltip( 'stripe_live_secret_key' ) ?></label>
					</th>
					<td width="88%">
						<input class="size-1" id="gfp_stripe_live_secret_key" name="gfp_stripe_live_secret_key"
									 value="<?php echo trim( esc_attr( rgar( $settings, 'live_secret_key' ) ) ) ?>"/> <img
							src="<?php echo self::$_this->get_base_url() ?>/images/<?php echo $is_valid[1]['live_secret_key'] ? 'tick.png' : 'stop.png' ?>"
							border="0" alt="<?php array_key_exists( 0, $message ) ? $message[0] : $message[1]['live_secret_key'] ?>"
							title="<?php echo array_key_exists( 0, $message ) ? $message[0] : $message[1]['live_secret_key'] ?>"
							style="display:<?php echo ( empty( $message[0] ) && empty( $message[1]['live_secret_key'] ) ) ? 'none;' : 'inline;' ?>"/>
						<?php
						if ( array_key_exists( 2, $is_valid ) && ( 'Stripe_InvalidRequestError' == $is_valid[2]['live_secret_key'] ) ) {
							?>
							<span class="invalid_credentials">*You must activate your Stripe account to use this key</span>
						<?php
						}
						?>
						<br/>
					</td>
				</tr>
				<tr>
					<th scope="row" nowrap="nowrap"><label
							for="gfp_stripe_live_publishable_key"><?php _e( 'Live Publishable Key', 'gfp-stripe' ); ?> <?php gform_tooltip( 'stripe_live_publishable_key' ) ?></label>
					</th>
					<td width="88%">
						<input class="size-1" id="gfp_stripe_live_publishable_key" name="gfp_stripe_live_publishable_key"
									 value="<?php echo trim( esc_attr( rgar( $settings, 'live_publishable_key' ) ) ) ?>"/> <img
							src="<?php echo self::$_this->get_base_url() ?>/images/<?php echo $is_valid[1]['live_publishable_key'] ? 'tick.png' : 'stop.png' ?>"
							border="0"
							alt="<?php array_key_exists( 0, $message ) ? $message[0] : $message[1]['live_publishable_key'] ?>"
							title="<?php echo array_key_exists( 0, $message ) ? $message[0] : $message[1]['live_publishable_key'] ?>"
							style="display:<?php echo ( empty( $message[0] ) && empty( $message[1]['live_publishable_key'] ) ) ? 'none;' : 'inline;' ?>"/>
						<?php
						if ( array_key_exists( 2, $is_valid ) && ( 'Stripe_InvalidRequestError' == $is_valid[2]['live_publishable_key'] ) ) {
							?>
							<span class="invalid_credentials">*You must activate your Stripe account to use this key</span>
						<?php
						}
						?>
						<br/>
					</td>
				</tr>
			</table>
			<br/>

			<div class="push-alert-green" style="text-align: left; padding: 10px 26px;">
				<p>
					<span class="strong">
						<?php _e( "Broken and difficult to use plugins suck! ", 'gfp-stripe' );
						?>
					</span>
					<?php _e( "But that doesn't have to be your experience. ", 'gfp-stripe' );
					?>
				</p>

				<p>
					<span class="strong">
						<?php printf( __( 'Enable %1$sPressTrends%2$s,', 'gfp-stripe' ),
													'<a href="http://presstrends.io/" target="_blank">',
													'</a>' );
						?>
					</span>
					<?php _e( "a third-party analytics service just for WordPress sites,
				plugins, and themes, that aggregates completely anonymous usage stats so I know which themes, plugins,
				and configurations to test with to keep your site working!", 'gfp-stripe' );
					?>
				</p>

				<p>
					<?php _e( "(You can even access all of the data through benchmarks by creating your own PressTrends account on their website)", 'gfp-stripe' );
					?>
				</p>
			</div>
			<table class="form-table">
				<tr>
					<td width="88%">
						<input type="checkbox" name="gfp_stripe_do_presstrends" id="gfp_stripe_do_presstrends"
									 value="true" <?php checked( 'true', rgar( $settings, 'do_presstrends' ), true ) ?>/> <label
							class="inline"
							for="gfp_stripe_do_presstrends"><?php _e( 'Yes I want to make sure this plugin works on my site.', 'gfp-stripe' ); ?></label>
						&nbsp;&nbsp;&nbsp;
					</td>
				</tr>
			</table>

			<?php
			do_action( 'gfp_stripe_settings_page', $settings );
			?>



			<br/><br/>

			<p class="submit" style="text-align: left;">
				<input type="submit" name="gfp_stripe_submit" class="button-primary"
							 value="<?php _e( 'Save Settings', 'gfp-stripe' ) ?>"/>
			</p>

		</form>
		<?php
		do_action( 'gfp_stripe_before_uninstall_button' );

		if ( ! class_exists( 'GFPMoreStripe' ) ) {
			?>
			<form action="" method="post">
				<?php wp_nonce_field( 'uninstall', 'gfp_stripe_uninstall' ) ?>
				<?php if ( GFCommon::current_user_can_any( 'gfp_stripe_uninstall' ) ) { ?>
					<div class="hr-divider"></div>

					<h3><?php _e( 'Uninstall Stripe Add-On', 'gfp-stripe' ) ?></h3>
					<div class="delete-alert"><?php _e( 'Warning! This operation deletes ALL Stripe Feeds.', 'gfp-stripe' ) ?>
						<?php
						$uninstall_button = '<input type="submit" name="uninstall" value="' . __( 'Uninstall Stripe Add-On', 'gfp-stripe' ) . '" class="button" onclick="return confirm(\'' . __( "Warning! ALL Stripe Feeds will be deleted. This cannot be undone. \'OK\' to delete, \'Cancel\' to stop", 'gfp-stripe' ) . '\');"/>';
						echo apply_filters( 'gfp_stripe_uninstall_button', $uninstall_button );
						?>
					</div>
				<?php } ?>
			</form>
		<?php
		}
		do_action( 'gfp_stripe_after_uninstall_button' );
		?>

	<?php
	}

	/**
	 * Uninstall
	 *
	 * @since
	 *
	 * @uses GFP_Stripe::has_access()
	 * @uses do_action()
	 * @uses GFP_Stripe_Data::drop_tables()
	 * @uses delete_option()
	 * @uses delete_transient()
	 * @uses deactivate_plugins()
	 * @uses update_option()
	 * @uses get_option()
	 *
	 * @return void
	 */
	public function uninstall () {

		if ( ! self::$_this->has_access( 'gfp_stripe_uninstall' ) )
			die( __( 'You don\'t have adequate permission to uninstall the Stripe Add-On.', 'gfp-stripe' ) );

		do_action( 'gfp_stripe_uninstall_condition' );

		//dropping all tables
		GFP_Stripe_Data::drop_tables();

		//removing options
		delete_option( 'gfp_stripe_version' );
		delete_option( 'gfp_stripe_settings' );
		delete_option( 'gfp_support_key' );

		delete_transient( 'gfp_stripe_currency' );
		delete_transient( 'gfp_stripe_presstrends_cache_data' );

		//delete lead meta data
		//self::delete_stripe_meta();

		//Deactivating plugin
		$plugin = plugin_basename( trim( GFP_STRIPE_FILE ) );
		deactivate_plugins( $plugin );
		update_option( 'recently_activated', array( $plugin => time() ) + (array) get_option( 'recently_activated' ) );
	}

	/**
	 * Add feed & settings page tooltips to the list of tooltips
	 *
	 * @since 0.1.0
	 *
	 * @uses  __()
	 *
	 * @param $tooltips
	 *
	 * @return array
	 */
	public function gform_tooltips ( $tooltips ) {
		$stripe_tooltips = array(
			'stripe_transaction_type'     => '<h6>' . __( 'Transaction Type', 'gfp-stripe' ) . '</h6>' . __( 'Select which Stripe transaction type should be used. Products and Services, Subscription, or Billing Info Update.', 'gfp-stripe' ),
			'stripe_gravity_form'         => '<h6>' . __( 'Gravity Form', 'gfp-stripe' ) . '</h6>' . __( 'Select which Gravity Forms you would like to integrate with Stripe.', 'gfp-stripe' ),
			'stripe_customer'             => '<h6>' . __( 'Customer', 'gfp-stripe' ) . '</h6>' . __( 'Map your Form Fields to the available Stripe customer information fields.', 'gfp-stripe' ),
			'stripe_options'              => '<h6>' . __( 'Options', 'gfp-stripe' ) . '</h6>' . __( 'Turn on or off the available Stripe checkout options.', 'gfp-stripe' ),

			'stripe_support_license_key'  => '<h6>' . __( 'gravity+ Support License Key', 'gfp-stripe' ) . '</h6>' . __( 'Your gravity+ support license key is used to enable automatic updates for +(More) Stripe and receive support.', 'gfp-stripe' ),
			'stripe_api'                  => '<h6>' . __( 'API', 'gfp-stripe' ) . '</h6>' . __( 'Select the Stripe API you would like to use. Select \'Live\' to use your Live API keys. Select \'Test\' to use your Test API keys.', 'gfp-stripe' ),
			'stripe_test_secret_key'      => '<h6>' . __( 'API Test Secret Key', 'gfp-stripe' ) . '</h6>' . __( 'Enter the API Test Secret Key for your Stripe account.', 'gfp-stripe' ),
			'stripe_test_publishable_key' => '<h6>' . __( 'API Test Publishable Key', 'gfp-stripe' ) . '</h6>' . __( 'Enter the API Test Publishable Key for your Stripe account.', 'gfp-stripe' ),
			'stripe_live_secret_key'      => '<h6>' . __( 'API Live Secret Key', 'gfp-stripe' ) . '</h6>' . __( 'Enter the API Live Secret Key for your Stripe account.', 'gfp-stripe' ),
			'stripe_live_publishable_key' => '<h6>' . __( 'API Live Publishable Key', 'gfp-stripe' ) . '</h6>' . __( 'Enter the API Live Publishable Key for your Stripe account.', 'gfp-stripe' ),
			'stripe_conditional'          => '<h6>' . __( 'Stripe Condition', 'gfp-stripe' ) . '</h6>' . __( 'When the Stripe condition is enabled, form submissions will only be sent to Stripe when the condition is met. When disabled all form submissions will be sent to Stripe.', 'gfp-stripe' )

		);

		return array_merge( $tooltips, $stripe_tooltips );
	}

//------------------------------------------------------
//------------- STRIPE FEED LIST PAGE  -----------------
//------------------------------------------------------
	/**
	 *
	 *
	 * @since 0.1.0
	 */
	private function list_page () {
		if ( ! $this->is_gravityforms_supported() ) {
			die( __( sprintf( 'Stripe Add-On requires Gravity Forms %s. Upgrade automatically on the %sPlugin page%s.', self::$min_gravityforms_version, '<a href="plugins.php">', '</a>' ), 'gfp-stripe' ) );
		}

		if ( 'delete' == rgpost( 'action' ) ) {
			check_admin_referer( 'list_action', 'gfp_stripe_list' );

			$id = absint( $_POST["action_argument"] );
			GFP_Stripe_Data::delete_feed( $id );
			?>
			<div class="updated fade" style="padding:6px"><?php _e( 'Feed deleted.', 'gfp-stripe' ) ?></div>
		<?php
		}
		else if ( ! empty( $_POST["bulk_action"] ) ) {
			check_admin_referer( 'list_action', 'gfp_stripe_list' );
			$selected_feeds = $_POST["feed"];
			if ( is_array( $selected_feeds ) ) {
				foreach ( $selected_feeds as $feed_id ) {
					GFP_Stripe_Data::delete_feed( $feed_id );
				}
			}
			?>
			<div class="updated fade" style="padding:6px"><?php _e( 'Feeds deleted.', 'gfp-stripe' ) ?></div>
		<?php
		}

		?>
		<div class="wrap">
			<img alt="<?php _e( 'Stripe Transactions', 'gfp-stripe' ) ?>"
					 src="<?php echo self::$_this->get_base_url() ?>/images/stripe_wordpress_icon_32.png"
					 style="float:left; margin:15px 7px 0 0;"/>

			<h2><?php
				_e( 'Stripe Forms', 'gfp-stripe' );
				?>
				<a class="button add-new-h2"
					 href="admin.php?page=gfp_stripe&view=edit&id=0"><?php _e( 'Add New', 'gfp-stripe' ) ?></a>

			</h2>

			<form id="feed_form" method="post">
				<?php wp_nonce_field( 'list_action', 'gfp_stripe_list' ) ?>
				<input type="hidden" id="action" name="action"/> <input type="hidden" id="action_argument"
																																name="action_argument"/>

				<div class="tablenav">
					<div class="alignleft actions" style="padding:8px 0 7px 0;">
						<label class="hidden" for="bulk_action"><?php _e( 'Bulk action', 'gfp-stripe' ) ?></label> <select
							name="bulk_action" id="bulk_action">
							<option value=''> <?php _e( 'Bulk action', 'gfp-stripe' ) ?> </option>
							<option value='delete'><?php _e( 'Delete', 'gfp-stripe' ) ?></option>
						</select>
						<?php
						echo '<input type="submit" class="button" value="' . __( 'Apply', 'gfp-stripe' ) . '" onclick="if( jQuery(\'#bulk_action\').val() == \'delete\' && !confirm(\'' . __( 'Delete selected feeds? ', 'gfp-stripe' ) . __( '\'Cancel\' to stop, \'OK\' to delete.', 'gfp-stripe' ) . '\')) { return false; } return true;"/>';
						?>
					</div>
				</div>
				<table class="widefat fixed" cellspacing="0">
					<thead>
					<tr>
						<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox"/>
						</th>
						<th scope="col" id="active" class="manage-column check-column"></th>
						<th scope="col" class="manage-column"><?php _e( 'Form', 'gfp-stripe' ) ?></th>
						<th scope="col" class="manage-column"><?php _e( 'Transaction Type', 'gfp-stripe' ) ?></th>
					</tr>
					</thead>

					<tfoot>
					<tr>
						<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox"/>
						</th>
						<th scope="col" id="active" class="manage-column check-column"></th>
						<th scope="col" class="manage-column"><?php _e( 'Form', 'gfp-stripe' ) ?></th>
						<th scope="col" class="manage-column"><?php _e( 'Transaction Type', 'gfp-stripe' ) ?></th>
					</tr>
					</tfoot>

					<tbody class="list:user user-list">
					<?php


					$feeds = GFP_Stripe_Data::get_feeds();
					$settings = get_option( 'gfp_stripe_settings' );
					$mode = rgar( $settings, 'mode' );
					$is_valid = $this->is_valid_key();
					if ( ( ( ! $is_valid[0] ) && ( ( ! $is_valid[1]['test_secret_key'] ) || ( ! $is_valid[1]['test_publishable_key'] ) ) ) || ( ( ! $is_valid[0] ) && ( 'live' == $mode ) && ( array_key_exists( 2, $is_valid ) ) && ( ( 'Stripe_InvalidRequestError' == $is_valid[2]['live_secret_key'] ) || ( 'Stripe_InvalidRequestError' == $is_valid[2]['live_publishable_key'] ) ) ) ) {
						?>
						<tr>
							<td colspan="4" style="padding:20px;">
								<?php echo sprintf( __( "To get started, please configure your %sStripe Settings%s.", 'gfp-stripe' ), '<a href="admin.php?page=gf_settings&addon=Stripe">', '</a>' ); ?>
							</td>
						</tr>
					<?php
					}
					else if ( is_array( $feeds ) && sizeof( $feeds ) > 0 ) {
						foreach ( $feeds as $feed ) {
							?>
							<tr class='author-self status-inherit' valign="top">
								<th scope="row" class="check-column"><input type="checkbox" name="feed[]"
																														value="<?php echo $feed["id"] ?>"/></th>
								<td><img
										src="<?php echo self::$_this->get_base_url() ?>/images/active<?php echo intval( $feed["is_active"] ) ?>.png"
										alt="<?php echo $feed["is_active"] ? __( 'Active', 'gfp-stripe' ) : __( 'Inactive', 'gfp-stripe' ); ?>"
										title="<?php echo $feed["is_active"] ? __( 'Active', 'gfp-stripe' ) : __( 'Inactive', 'gfp-stripe' ); ?>"
										onclick="ToggleActive(this, <?php echo $feed['id'] ?>); "/></td>
								<td class="column-title">
									<a href="admin.php?page=gfp_stripe&view=edit&id=<?php echo $feed["id"] ?>"
										 title="<?php _e( 'Edit', 'gfp-stripe' ) ?>"><?php echo $feed["form_title"] ?></a>

									<div class="row-actions">
	                                            <span class="edit">
	                                            <a title="<?php _e( 'Edit', 'gfp-stripe' ) ?>"
																								 href="admin.php?page=gfp_stripe&view=edit&id=<?php echo $feed["id"] ?>"
																								 title="<?php _e( 'Edit', 'gfp-stripe' ) ?>"><?php _e( 'Edit', 'gfp-stripe' ) ?></a>
	                                            |
	                                            </span>
	                                            <span>
	                                            <a title="<?php _e( 'View Stats', 'gfp-stripe' ) ?>"
																								 href="admin.php?page=gfp_stripe&view=stats&id=<?php echo $feed["id"] ?>"
																								 title="<?php _e( 'View Stats', 'gfp-stripe' ) ?>"><?php _e( 'Stats', 'gfp-stripe' ) ?></a>
	                                            |
	                                            </span>
	                                            <span>
	                                            <a title="<?php _e( 'View Entries', 'gfp-stripe' ) ?>"
																								 href="admin.php?page=gf_entries&view=entries&id=<?php echo $feed["form_id"] ?>"
																								 title="<?php _e( 'View Entries', 'gfp-stripe' ) ?>"><?php _e( 'Entries', 'gfp-stripe' ) ?></a>
	                                            |
	                                            </span>
	                                            <span>
	                                            <a title="<?php _e( "Delete", "gfp-stripe" ) ?>"
																								 href="javascript: if(confirm('<?php _e( 'Delete this feed? ', 'gfp-stripe' ) ?> <?php _e( "\'Cancel\' to stop, \'OK\' to delete.", 'gfp-stripe' ) ?>')){ DeleteFeed(<?php echo $feed["id"] ?>);}"><?php _e( 'Delete', 'gfp-stripe' ) ?></a>
	                                            </span>
									</div>
								</td>
								<td class="column-date">
									<?php
									if ( has_action( 'gfp_stripe_list_feeds_product_type' ) ) {
										do_action( 'gfp_stripe_list_feeds_product_type', $feed );
									}
									else {
										switch ( $feed["meta"]["type"] ) {
											case 'product' :
												_e( 'Product and Services', 'gfp-stripe' );
												break;

											/*case 'subscription' :
															_e( 'Subscription', 'gfp-stripe' );
															break;*/
										}
									}
									?>
								</td>
							</tr>
						<?php
						}
					}
					else {
						?>
						<tr>
							<td colspan="4" style="padding:20px;">
								<?php echo sprintf( __( "You don't have any Stripe feeds configured. Let's go %screate one%s!", 'gfp-stripe' ), '<a href="admin.php?page=gfp_stripe&view=edit&id=0">', '</a>' ); ?>
							</td>
						</tr>
					<?php
					}
					?>
					</tbody>
				</table>
			</form>
		</div>
		<script type="text/javascript">
			function DeleteFeed( id ) {
				jQuery( "#action_argument" ).val( id );
				jQuery( "#action" ).val( "delete" );
				jQuery( "#feed_form" )[0].submit();
			}
			function ToggleActive( img, feed_id ) {
				var is_active = img.src.indexOf( "active1.png" ) >= 0
				if ( is_active ) {
					img.src = img.src.replace( "active1.png", "active0.png" );
					jQuery( img ).attr( 'title', '<?php _e( 'Inactive', 'gfp-stripe' ) ?>' ).attr( 'alt', '<?php _e( 'Inactive', 'gfp-stripe' ) ?>' );
				}
				else {
					img.src = img.src.replace( "active0.png", "active1.png" );
					jQuery( img ).attr( 'title', '<?php _e( 'Active', 'gfp-stripe' ) ?>' ).attr( 'alt', '<?php _e( 'Active', 'gfp-stripe' ) ?>' );
				}

				var mysack = new sack( "<?php echo admin_url( "admin-ajax.php" )?>" );
				mysack.execute = 1;
				mysack.method = 'POST';
				mysack.setVar( "action", "gfp_stripe_update_feed_active" );
				mysack.setVar( "gfp_stripe_update_feed_active", "<?php echo wp_create_nonce( 'gfp_stripe_update_feed_active' ) ?>" );
				mysack.setVar( "feed_id", feed_id );
				mysack.setVar( "is_active", is_active ? 0 : 1 );
				mysack.encVar( "cookie", document.cookie, false );
				mysack.onError = function () {
					alert( '<?php _e( 'Ajax error while updating feed', 'gfp-stripe' ) ?>' )
				};
				mysack.runAJAX();

				return true;
			}


		</script>
	<?php
	}

	//------------------------------------------------------
	//------------- STRIPE FEED EDIT PAGE ------------------
	//------------------------------------------------------

	/**
	 *
	 */
	private function edit_page () {
		?>
		<style>
			#stripe_submit_container {
				clear: both;
			}

			.stripe_col_heading {
				padding-bottom: 2px;
				border-bottom: 1px solid #ccc;
				font-weight: bold;
				width: 120px;
			}

			.stripe_field_cell {
				padding: 6px 17px 0 0;
				margin-right: 15px;
			}

			.stripe_validation_error {
				background-color: #FFDFDF;
				margin-top: 4px;
				margin-bottom: 6px;
				padding-top: 6px;
				padding-bottom: 6px;
				border: 1px dotted #C89797;
			}

			.stripe_validation_error span {
				color: red;
			}

			.left_header {
				float: left;
				width: 200px;
			}

			.margin_vertical_10 {
				margin: 10px 0;
				padding-left: 5px;
				min-height: 17px;
			}

			.margin_vertical_30 {
				margin: 30px 0;
				padding-left: 5px;
			}

			.width-1 {
				width: 300px;
			}

			.gfp_stripe_invalid_form {
				margin-top: 30px;
				background-color: #FFEBE8;
				border: 1px solid #CC0000;
				padding: 10px;
				width: 600px;
			}
		</style>

		<script type="text/javascript" src="<?php echo GFCommon::get_base_url() ?>/js/gravityforms.js"></script>
		<script type="text/javascript">var form = Array();</script>

		<div class="wrap">
		<img alt="<?php _e( 'Stripe', 'gfp-stripe' ) ?>" style="margin: 15px 7px 0pt 0pt; float: left;"
				 src="<?php echo self::$_this->get_base_url() ?>/images/stripe_wordpress_icon_32.png"/>

		<h2><?php _e( 'Stripe Transaction Settings', 'gfp-stripe' ) ?></h2>

		<?php

		//getting setting id (0 when creating a new one)
		$id = ! empty( $_POST['stripe_setting_id'] ) ? $_POST['stripe_setting_id'] : absint( $_GET['id'] );
		$feed = empty( $id ) ? array(
			'meta'      => array(),
			'is_active' => true ) : GFP_Stripe_Data::get_feed( $id );
		$is_validation_error = false;

		//updating meta information
		if ( rgpost( 'gfp_stripe_submit' ) ) {

			$feed['form_id']                    = absint( rgpost( 'gfp_stripe_form' ) );
			$feed['meta']['type']               = rgpost( 'gfp_stripe_type' );
			$feed['meta']['update_post_action'] = rgpost( 'gfp_stripe_update_action' );

			// stripe conditional
			$feed['meta']['stripe_conditional_enabled']  = rgpost( 'gfp_stripe_conditional_enabled' );
			$feed['meta']['stripe_conditional_field_id'] = rgpost( 'gfp_stripe_conditional_field_id' );
			$feed['meta']['stripe_conditional_operator'] = rgpost( 'gfp_stripe_conditional_operator' );
			$feed['meta']['stripe_conditional_value']    = rgpost( 'gfp_stripe_conditional_value' );

			//-----------------

			$customer_fields                 = $this->get_customer_fields();
			$feed['meta']['customer_fields'] = array();
			foreach ( $customer_fields as $field ) {
				$feed['meta']['customer_fields'][$field['name']] = $_POST["stripe_customer_field_{$field["name"]}"];
			}

			$feed = apply_filters( 'gfp_stripe_save_feed', $feed );

			$is_validation_error = apply_filters( 'gfp_stripe_feed_validation', false, $feed );

			if ( ! $is_validation_error ) {
				$id = GFP_Stripe_Data::update_feed( $id, $feed["form_id"], $feed["is_active"], $feed["meta"] );
				?>
				<div class="updated fade"
						 style="padding:6px"><?php echo sprintf( __( "Feed Updated. %sback to list%s", 'gfp-stripe' ), "<a href='?page=gfp_stripe'>", '</a>' ) ?></div>
			<?php
			}
			else {
				$is_validation_error = true;
			}
		}

		$form = isset( $feed['form_id'] ) && $feed['form_id'] ? $form = RGFormsModel::get_form_meta( $feed['form_id'] ) : array();
		$settings = get_option( 'gfp_stripe_settings' );
		?>
		<form method="post" action="">
			<input type="hidden" name="stripe_setting_id" value="<?php echo $id ?>"/>

			<div class="margin_vertical_10 <?php echo $is_validation_error ? 'stripe_validation_error' : '' ?>">
				<?php
				if ( $is_validation_error ) {
					?>
					<span><?php _e( 'There was an issue saving your feed. Please address the errors below and try again.' ); ?></span>
				<?php
				}
				?>
			</div>
			<!-- / validation message -->



			<?php

			if ( has_action( 'gfp_stripe_feed_transaction_type' ) ) {
				do_action( 'gfp_stripe_feed_transaction_type', $settings, $feed );
			}
			else {
				$feed['meta']['type'] = 'product' ?>

				<input id="gfp_stripe_type" type="hidden" name="gfp_stripe_type" value="product">


			<?php } ?>


			<div id="stripe_form_container" valign="top"
					 class="margin_vertical_10" <?php echo empty( $feed['meta']['type'] ) ? "style='display:none;'" : '' ?>>
				<label for="gfp_stripe_form"
							 class="left_header"><?php _e( 'Gravity Form', 'gfp-stripe' ); ?> <?php gform_tooltip( 'stripe_gravity_form' ) ?></label>

				<select id="gfp_stripe_form" name="gfp_stripe_form"
								onchange="SelectForm(jQuery('#gfp_stripe_type').val(), jQuery(this).val(), '<?php echo rgar( $feed, 'id' ) ?>');">
					<option value=""><?php _e( 'Select a form', 'gfp-stripe' ); ?> </option>
					<?php

					$active_form = rgar( $feed, 'form_id' );
					$available_forms = GFP_Stripe_Data::get_available_forms( $active_form );

					foreach ( $available_forms as $current_form ) {
						$selected = absint( $current_form->id ) == rgar( $feed, 'form_id' ) ? 'selected="selected"' : '';
						?>

						<option
							value="<?php echo absint( $current_form->id ) ?>" <?php echo $selected; ?>><?php echo esc_html( $current_form->title ) ?></option>

					<?php
					}
					?>
				</select> &nbsp;&nbsp; <img src="<?php echo GFP_Stripe::get_base_url() ?>/images/loading.gif" id="stripe_wait"
																		style="display: none;"/>

				<div id="gfp_stripe_invalid_product_form" class="gfp_stripe_invalid_form" style="display:none;">
					<?php _e( 'The form selected does not have any Product fields. Please add a Product field to the form and try again.', 'gfp-stripe' ) ?>
				</div>
				<div id="gfp_stripe_invalid_creditcard_form" class="gfp_stripe_invalid_form" style="display:none;">
					<?php _e( 'The form selected does not have a credit card field. Please add a credit card field to the form and try again.', 'gfp-stripe' ) ?>
				</div>
			</div>
			<div id="stripe_field_group"
					 valign="top" <?php echo strlen( rgars( $feed, "meta/type" ) ) == 0 || empty( $feed["form_id"] ) ? "style='display:none;'" : '' ?>>


				<?php do_action( 'gfp_stripe_feed_before_billing', $feed, $form ); ?>
				<div class="margin_vertical_10"
						 id="gfp_stripe_billing_info" <?php echo ( false == apply_filters( 'gfp_stripe_display_billing_info', true, $feed ) ) ? "style='display:none;'" : '' ?>>
					<label
						class="left_header"><?php _e( 'Billing Information', 'gfp-stripe' ); ?> <?php gform_tooltip( 'stripe_customer' ) ?></label>

					<div id="stripe_customer_fields">
						<?php
						if ( ! empty( $form ) )
							echo $this->get_customer_information( $form, $feed );
						?>
					</div>
				</div>
				<?php do_action( 'gfp_stripe_feed_after_billing', $feed, $form ); ?>




				<div class="margin_vertical_10">
					<label
						class="left_header"><?php _e( 'Options', 'gfp-stripe' ); ?> <?php gform_tooltip( 'stripe_options' ) ?></label>

					<ul style="overflow:hidden;">

						<?php
						$display_post_fields = ! empty( $form ) ? GFCommon::has_post_field( $form['fields'] ) : false;
						?>
						<li
							id="stripe_post_update_action" <?php echo $display_post_fields && 'subscription' == $feed['meta']['type'] ? '' : "style='display:none;'" ?>>
							<input type="checkbox" name="gfp_stripe_update_post" id="gfp_stripe_update_post"
										 value="1" <?php echo rgar( $feed['meta'], 'update_post_action' ) ? "checked='checked'" : "" ?>
										 onclick="var action = this.checked ? 'draft' : ''; jQuery('#gfp_stripe_update_action').val(action);"/>
							<label class="inline"
										 for="gfp_stripe_update_post"><?php _e( 'Update Post when subscription is canceled.', 'gfp-stripe' ); ?> <?php gform_tooltip( 'stripe_update_post' ) ?></label>
							<select id="gfp_stripe_update_action" name="gfp_stripe_update_action"
											onchange="var checked = jQuery(this).val() ? 'checked' : false; jQuery('#gfp_stripe_update_post').attr('checked', checked);">
								<option value=""></option>
								<option
									value="draft" <?php echo 'draft' == rgar( $feed["meta"], 'update_post_action' ) ? "selected='selected'" : "" ?>><?php _e( 'Mark Post as Draft', 'gfp-stripe' ) ?></option>
								<option
									value="delete" <?php echo 'delete' == rgar( $feed["meta"], 'update_post_action' ) ? "selected='selected'" : "" ?>><?php _e( 'Delete Post', 'gfp-stripe' ) ?></option>
							</select>
						</li>

						<?php do_action( 'gfp_stripe_feed_options', $feed, $form ) ?>
					</ul>
				</div>

				<?php do_action( 'gfp_stripe_feed_setting', $feed, $form ); ?>

				<div id="gfp_stripe_conditional_section" valign="top" class="margin_vertical_10">
					<label for="gfp_stripe_conditional_optin"
								 class="left_header"><?php _e( 'Stripe Condition', 'gfp-stripe' ); ?> <?php gform_tooltip( 'stripe_conditional' ) ?></label>

					<div id="gfp_stripe_conditional_option">
						<table cellspacing="0" cellpadding="0">
							<tr>
								<td>
									<input type="checkbox" id="gfp_stripe_conditional_enabled" name="gfp_stripe_conditional_enabled"
												 value="1"
												 onclick="if(this.checked){jQuery('#gfp_stripe_conditional_container').fadeIn('fast');} else{ jQuery('#gfp_stripe_conditional_container').fadeOut('fast'); }" <?php echo rgar( $feed['meta'], 'stripe_conditional_enabled' ) ? "checked='checked'" : '' ?>/>
									<label for="gfp_stripe_conditional_enable"><?php _e( 'Enable', 'gfp-stripe' ); ?></label>
								</td>
							</tr>
							<tr>
								<td>
									<div
										id="gfp_stripe_conditional_container" <?php echo ! rgar( $feed['meta'], 'stripe_conditional_enabled' ) ? "style='display:none'" : '' ?>>

										<div id="gfp_stripe_conditional_fields" style="display:none">
											<?php _e( 'Send to Stripe if ', 'gfp-stripe' ) ?>

											<select id="gfp_stripe_conditional_field_id" name="gfp_stripe_conditional_field_id"
															class="optin_select"
															onchange='jQuery("#gfp_stripe_conditional_value_container").html(GetFieldValues(jQuery(this).val(), "", 20));'> </select>
											<select id="gfp_stripe_conditional_operator" name="gfp_stripe_conditional_operator">
												<option
													value="is" <?php selected( 'is', rgar( $feed['meta'], 'stripe_conditional_operator' ), true ); ?>>
													<?php _e( 'is', 'gfp-stripe' ) ?>
												</option>
												<option
													value="isnot" <?php selected( 'isnot', rgar( $feed['meta'], 'stripe_conditional_operator' ), true ); ?>>
													<?php _e( 'is not', 'gfp-stripe' ) ?>
												</option>
												<option
													value=">" <?php selected( '>', rgar( $feed['meta'], 'stripe_conditional_operator' ), true ); ?>>
													<?php _e( 'greater than', 'gfp-stripe' ) ?>
												</option>
												<option
													value="<" <?php selected( '<', rgar( $feed['meta'], 'stripe_conditional_operator' ), true ); ?>>
													<?php _e( 'less than', 'gfp-stripe' ) ?>
												</option>
												<option
													value="contains" <?php selected( 'contains', rgar( $feed['meta'], 'stripe_conditional_operator' ), true ); ?>>
													<?php _e( 'contains', 'gfp-stripe' ) ?>
												</option>
												<option
													value="starts_with" <?php selected( 'starts_with', rgar( $feed['meta'], 'stripe_conditional_operator' ), true ); ?>>
													<?php _e( 'starts with', 'gfp-stripe' ) ?>
												</option>
												<option
													value="ends_with" <?php selected( 'ends_with', rgar( $feed['meta'], 'stripe_conditional_operator' ), true ); ?>>
													<?php _e( 'ends with', 'gfp-stripe' ) ?>
												</option>
											</select>

											<div id="gfp_stripe_conditional_value_container" name="gfp_stripe_conditional_value_container"
													 style="display:inline"></div>

										</div>

										<div id="gfp_stripe_conditional_message" style="display:none">
											<?php _e( 'To create a registration condition, your form must have a field supported by conditional logic', 'gfp-stripe' ) ?>
										</div>

									</div>
								</td>
							</tr>
						</table>
					</div>

				</div>
				<!-- / stripe conditional -->

				<div id="stripe_submit_container" class="margin_vertical_30">
					<input type="submit" name="gfp_stripe_submit"
								 value="<?php echo empty( $id ) ? __( '  Save  ', 'gfp-stripe' ) : __( 'Update', 'gfp-stripe' ); ?>"
								 class="button-primary"/> <input type="button" value="<?php _e( 'Cancel', 'gfp-stripe' ); ?>"
																								 class="button"
																								 onclick="javascript:document.location='admin.php?page=gfp_stripe'"/>
				</div>
			</div>
		</form>
		</div>

		<script type="text/javascript">

			function SelectType( type ) {
				jQuery( "#stripe_field_group" ).slideUp();

				jQuery( "#stripe_field_group input[type=\"text\"], #stripe_field_group select" ).val( "" );

				jQuery( "#stripe_field_group input:checked" ).attr( "checked", false );

				if ( type ) {
					jQuery( "#stripe_form_container" ).slideDown();
					jQuery( "#gfp_stripe_form" ).val( "" );
				}
				else {
					jQuery( "#stripe_form_container" ).slideUp();
				}
			}

			function SelectForm( type, formId, settingId ) {
				if ( !formId ) {
					jQuery( "#stripe_field_group" ).slideUp();
					return;
				}

				jQuery( "#stripe_wait" ).show();
				jQuery( "#stripe_field_group" ).slideUp();

				var mysack = new sack( ajaxurl );
				mysack.execute = 1;
				mysack.method = 'POST';
				mysack.setVar( "action", "gfp_select_stripe_form" );
				mysack.setVar( "gfp_select_stripe_form", "<?php echo wp_create_nonce( 'gfp_select_stripe_form' ) ?>" );
				mysack.setVar( "type", type );
				mysack.setVar( "form_id", formId );
				mysack.setVar( "setting_id", settingId );
				mysack.encVar( "cookie", document.cookie, false );
				mysack.onError = function () {
					jQuery( "#stripe_wait" ).hide();
					alert( '<?php _e( 'Ajax error while selecting a form', 'gfp-stripe' ) ?>' )
				};
				mysack.runAJAX();

				return true;
			}

			function EndSelectForm( form_meta, customer_fields, additional_functions ) {
				//setting global form object
				form = form_meta;

				if ( !( typeof additional_functions === 'null' ) ) {
					var populate_field_options = additional_functions.populate_field_options;
					var post_update_action = additional_functions.post_update_action;
					var show_fields = additional_functions.show_fields;
				}
				else {
					var populate_field_options = '';
					var post_update_action = '';
					var show_fields = '';
				}

				var type = jQuery( "#gfp_stripe_type" ).val();

				jQuery( ".gfp_stripe_invalid_form" ).hide();
				if ( ( 'product' == type || 'subscription' == type || 'update-subscription' == type ) && GetFieldsByType( ['product'] ).length == 0 ) {
					jQuery( "#gfp_stripe_invalid_product_form" ).show();
					jQuery( "#stripe_wait" ).hide();
					return;
				}
				else if ( ( 'product' == type || 'subscription' == type || 'update-billing' == type ) && GetFieldsByType( ['creditcard'] ).length == 0 ) {
					jQuery( "#gfp_stripe_invalid_creditcard_form" ).show();
					jQuery( "#stripe_wait" ).hide();
					return;
				}

				jQuery( ".stripe_field_container" ).hide();
				jQuery( "#stripe_customer_fields" ).html( customer_fields );
				if ( populate_field_options.length > 0 ) {
					var func;
					for ( var i = 0; i < populate_field_options.length; i++ ) {
						func = new Function( populate_field_options[ i ] );
						func();
					}
				}

				var post_fields = GetFieldsByType( ["post_title", "post_content", "post_excerpt", "post_category", "post_custom_field", "post_image", "post_tag"] );
				if ( post_update_action.length > 0 ) {
					var func;
					for ( var i = 0; i < post_update_action.length; i++ ) {
						func = new Function( 'type', 'post_fields', post_update_action[ i ] );
						func( type, post_fields );
					}
				}
				else {
					jQuery( "#gfp_stripe_update_post" ).attr( "checked", false );
					jQuery( "#stripe_post_update_action" ).hide();
				}


				//Calling callback functions
				jQuery( document ).trigger( 'stripeFormSelected', [form] );

				jQuery( "#gfp_stripe_conditional_enabled" ).attr( 'checked', false );
				SetStripeCondition( "", "" );

				jQuery( "#stripe_field_container_" + type ).show();
				if ( show_fields.length > 0 ) {
					var func;
					for ( var i = 0; i < show_fields.length; i++ ) {
						func = new Function( 'type', show_fields[ i ] );
						func( type );
					}
				}

				jQuery( "#stripe_field_group" ).slideDown();
				jQuery( "#stripe_wait" ).hide();
			}


			function GetFieldsByType( types ) {
				var fields = new Array();
				for ( var i = 0; i < form["fields"].length; i++ ) {
					if ( IndexOf( types, form["fields"][i]["type"] ) >= 0 )
						fields.push( form["fields"][i] );
				}
				return fields;
			}

			function IndexOf( ary, item ) {
				for ( var i = 0; i < ary.length; i++ )
					if ( ary[i] == item )
						return i;

				return -1;
			}

		</script>

		<script type="text/javascript">

			// Stripe Conditional Functions

			<?php
				if ( ! empty( $feed['form_id'] ) ) {
					?>

			// initialize form object
			form = <?php echo GFCommon::json_encode( $form )?>;

			// initializing registration condition drop downs
			jQuery( document ).ready( function () {
				var selectedField = "<?php echo str_replace( '"', '\"', $feed[ 'meta' ][ 'stripe_conditional_field_id' ] )?>";
				var selectedValue = "<?php echo str_replace( '"', '\"', $feed[ 'meta' ][ 'stripe_conditional_value' ] )?>";
				SetStripeCondition( selectedField, selectedValue );
			} );

			<?php
			}
			?>

			function SetStripeCondition( selectedField, selectedValue ) {

				// load form fields
				jQuery( "#gfp_stripe_conditional_field_id" ).html( GetSelectableFields( selectedField, 20 ) );
				var optinConditionField = jQuery( "#gfp_stripe_conditional_field_id" ).val();
				var checked = jQuery( "#gfp_stripe_conditional_enabled" ).attr( 'checked' );

				if ( optinConditionField ) {
					jQuery( "#gfp_stripe_conditional_message" ).hide();
					jQuery( "#gfp_stripe_conditional_fields" ).show();
					jQuery( "#gfp_stripe_conditional_value_container" ).html( GetFieldValues( optinConditionField, selectedValue, 20 ) );
					jQuery( "#gfp_stripe_conditional_value" ).val( selectedValue );
				}
				else {
					jQuery( "#gfp_stripe_conditional_message" ).show();
					jQuery( "#gfp_stripe_conditional_fields" ).hide();
				}

				if ( !checked ) {
					jQuery( "#gfp_stripe_conditional_container" ).hide();
				}

			}

			function GetFieldValues( fieldId, selectedValue, labelMaxCharacters ) {
				if ( !fieldId )
					return "";

				var str = "";
				var field = GetFieldById( fieldId );
				if ( !field )
					return "";

				var isAnySelected = false;

				if ( ( 'post_category' == field['type'] ) && field['displayAllCategories'] ) {
					str += '<?php $select = wp_dropdown_categories( array(
					'orderby' => 'name',
					'hide_empty' => 0,
					'echo' => false,
					'hierarchical' => true,
					'name' => 'gfp_stripe_conditional_value',
					'id' => 'gfp_stripe_conditional_value',
					'class' => 'optin_select'
					));
					echo str_replace( "\n","", str_replace( "'","\\'",$select ) ); ?>';
				}
				else if ( field.choices ) {
					str += '<select id="gfp_stripe_conditional_value" name="gfp_stripe_conditional_value" class="optin_select">'

					for ( var i = 0; i < field.choices.length; i++ ) {
						var fieldValue = field.choices[i].value ? field.choices[i].value : field.choices[i].text;
						var isSelected = fieldValue == selectedValue;
						var selected = isSelected ? "selected='selected'" : "";
						if ( isSelected )
							isAnySelected = true;

						str += "<option value='" + fieldValue.replace( /'/g, "&#039;" ) + "' " + selected + ">" + TruncateMiddle( field.choices[i].text, labelMaxCharacters ) + "</option>";
					}

					if ( !isAnySelected && selectedValue ) {
						str += "<option value='" + selectedValue.replace( /'/g, "&#039;" ) + "' selected='selected'>" + TruncateMiddle( selectedValue, labelMaxCharacters ) + "</option>";
					}
					str += "</select>";
				}
				else {
					selectedValue = selectedValue ? selectedValue.replace( /'/g, "&#039;" ) : "";
					str += "<input type='text' placeholder='<?php _e( 'Enter value', 'gfp-stripe' ); ?>' id='gfp_stripe_conditional_value' name='gfp_stripe_conditional_value' value='" + selectedValue.replace( /'/g, "&#039;" ) + "'>";
				}

				return str;
			}

			function GetFieldById( fieldId ) {
				for ( var i = 0; i < form.fields.length; i++ ) {
					if ( form.fields[i].id == fieldId )
						return form.fields[i];
				}
				return null;
			}

			function TruncateMiddle( text, maxCharacters ) {
				if ( text.length <= maxCharacters )
					return text;
				var middle = parseInt( maxCharacters / 2 );
				return text.substr( 0, middle ) + "..." + text.substr( text.length - middle, middle );
			}

			function GetSelectableFields( selectedFieldId, labelMaxCharacters ) {
				var str = "";
				var inputType;
				for ( var i = 0; i < form.fields.length; i++ ) {
					fieldLabel = form.fields[i].adminLabel ? form.fields[i].adminLabel : form.fields[i].label;
					fieldLabel = typeof fieldLabel == 'undefined' ? '' : fieldLabel;
					inputType = form.fields[i].inputType ? form.fields[i].inputType : form.fields[i].type;
					if ( IsConditionalLogicField( form.fields[i] ) ) {
						var selected = form.fields[i].id == selectedFieldId ? "selected='selected'" : "";
						str += "<option value='" + form.fields[i].id + "' " + selected + ">" + TruncateMiddle( fieldLabel, labelMaxCharacters ) + "</option>";
					}
				}
				return str;
			}

			function IsConditionalLogicField( field ) {
				inputType = field.inputType ? field.inputType : field.type;
				var supported_fields = ["checkbox", "radio", "select", "text", "website", "textarea", "email", "hidden", "number", "phone", "multiselect", "post_title",
																"post_tags", "post_custom_field", "post_content", "post_excerpt", "total"];

				var index = jQuery.inArray( inputType, supported_fields );

				return index >= 0;
			}

		</script>

	<?php

	}

	/**
	 *
	 */
	public function gfp_select_stripe_form () {

		check_ajax_referer( 'gfp_select_stripe_form', 'gfp_select_stripe_form' );

		$type       = $_POST["type"];
		$form_id    = intval( $_POST["form_id"] );
		$setting_id = intval( $_POST["setting_id"] );

		//fields meta
		$form = RGFormsModel::get_form_meta( $form_id );

		$customer_fields         = $this->get_customer_information( $form );
		$more_endselectform_args = array( 'populate_field_options' => array(),
																			'post_update_action'     => array(),
																			'show_fields'            => array()
		);
		$more_endselectform_args = apply_filters( 'gfp_stripe_feed_endselectform_args', $more_endselectform_args, $form );

		die( "EndSelectForm(" . GFCommon::json_encode( $form ) . ", '" . str_replace( "'", "\'", $customer_fields ) . "', " . GFCommon::json_encode( $more_endselectform_args ) . ");" );
	}

	/**
	 *
	 */
	public function gfp_stripe_update_feed_active () {
		check_ajax_referer( 'gfp_stripe_update_feed_active', 'gfp_stripe_update_feed_active' );
		$id   = $_POST["feed_id"];
		$feed = GFP_Stripe_Data::get_feed( $id );
		GFP_Stripe_Data::update_feed( $id, $feed["form_id"], $_POST["is_active"], $feed["meta"] );
	}

	/**
	 * @param      $form
	 * @param null $feed
	 *
	 * @return string
	 */
	private function get_customer_information ( $form, $feed = null ) {

		//getting list of all fields for the selected form
		$form_fields = $this->get_form_fields( $form );

		$str             = "<table cellpadding='0' cellspacing='0'><tr><td class='stripe_col_heading'>" . __( 'Stripe Fields', 'gfp-stripe' ) . "</td><td class='stripe_col_heading'>" . __( 'Form Fields', 'gfp-stripe' ) . '</td></tr>';
		$customer_fields = $this->get_customer_fields();
		foreach ( $customer_fields as $field ) {
			$selected_field = $feed ? $feed["meta"]["customer_fields"][$field["name"]] : "";
			$str .= "<tr><td class='stripe_field_cell'>" . $field["label"] . "</td><td class='stripe_field_cell'>" . $this->get_mapped_field_list( $field["name"], $selected_field, $form_fields ) . '</td></tr>';
		}
		$str .= '</table>';

		return $str;
	}

	/**
	 * @return array
	 */
	private function get_customer_fields () {
		return
			array(
				array(
					'name'  => 'first_name',
					'label' => __( 'First Name', 'gfp-stripe' ) ),
				array(
					'name'  => 'last_name',
					'label' => __( 'Last Name', 'gfp-stripe' ) ),
				array(
					'name'  => 'email',
					'label' => __( 'Email', 'gfp-stripe' ) ),
				array(
					'name'  => 'address1',
					'label' => __( 'Address', 'gfp-stripe' ) ),
				array(
					'name'  => 'address2',
					'label' => __( 'Address 2', 'gfp-stripe' ) ),
				array(
					'name'  => 'city',
					'label' => __( 'City', 'gfp-stripe' ) ),
				array(
					'name'  => 'state',
					'label' => __( 'State', 'gfp-stripe' ) ),
				array(
					'name'  => 'zip',
					'label' => __( 'Zip', 'gfp-stripe' ) ),
				array(
					'name'  => 'country',
					'label' => __( 'Country', 'gfp-stripe' ) ) );
	}

	/**
	 * @param $variable_name
	 * @param $selected_field
	 * @param $fields
	 *
	 * @return string
	 */
	private function get_mapped_field_list ( $variable_name, $selected_field, $fields ) {
		$field_name = 'stripe_customer_field_' . $variable_name;
		$str        = "<select name='$field_name' id='$field_name'><option value=''></option>";
		foreach ( $fields as $field ) {
			$field_id    = $field[0];
			$field_label = esc_html( GFCommon::truncate_middle( $field[1], 40 ) );

			$selected = $field_id == $selected_field ? "selected='selected'" : "";
			$str .= "<option value='" . $field_id . "' " . $selected . ">" . $field_label . '</option>';
		}
		$str .= '</select>';

		return $str;
	}

	/**
	 * @param $form
	 * @param $selected_field
	 * @param $form_total
	 *
	 * @return string
	 */
	public static function get_product_options ( $form, $selected_field, $form_total ) {
		$str    = "<option value=''>" . __( 'Select a field', 'gfp-stripe' ) . '</option>';
		$fields = GFCommon::get_fields_by_type( $form, array( 'product' ) );
		foreach ( $fields as $field ) {
			$field_id    = $field["id"];
			$field_label = RGFormsModel::get_label( $field );

			$selected = $field_id == $selected_field ? "selected='selected'" : "";
			$str .= "<option value='" . $field_id . "' " . $selected . ">" . $field_label . '</option>';
		}

		if ( $form_total ) {
			$selected = $selected_field == 'all' ? "selected='selected'" : "";
			$str .= "<option value='all' " . $selected . ">" . __( 'Form Total', 'gfp-stripe' ) . "</option>";
		}


		return $str;
	}

	/**
	 * @param $form
	 *
	 * @return array
	 */
	private function get_form_fields ( $form ) {
		$fields = array();

		if ( is_array( $form["fields"] ) ) {
			foreach ( $form["fields"] as $field ) {
				if ( is_array( rgar( $field, 'inputs' ) ) ) {

					foreach ( $field["inputs"] as $input ) {
						$fields[] = array( $input["id"], GFCommon::get_label( $field, $input["id"] ) );
					}
				}
				else if ( ! rgar( $field, 'displayOnly' ) ) {
					$fields[] = array( $field["id"], GFCommon::get_label( $field ) );
				}
			}
		}

		return $fields;
	}

//------------------------------------------------------
//------------- STATS PAGE ---------------------------
//------------------------------------------------------
	/**
	 *
	 */
	private function stats_page () {
		?>
		<style>
			.stripe_graph_container {
				clear: both;
				padding-left: 5px;
				min-width: 789px;
				margin-right: 50px;
			}

			.stripe_message_container {
				clear: both;
				padding-left: 5px;
				text-align: center;
				padding-top: 120px;
				border: 1px solid #CCC;
				background-color: #FFF;
				width: 100%;
				height: 160px;
			}

			.stripe_summary_container {
				margin: 30px 60px;
				text-align: center;
				min-width: 740px;
				margin-left: 50px;
			}

			.stripe_summary_item {
				width: 160px;
				background-color: #FFF;
				border: 1px solid #CCC;
				padding: 14px 8px;
				margin: 6px 3px 6px 0;
				display: -moz-inline-stack;
				display: inline-block;
				zoom: 1;
				*display: inline;
				text-align: center;
			}

			.stripe_summary_value {
				font-size: 20px;
				margin: 5px 0;
				font-family: Georgia, "Times New Roman", "Bitstream Charter", Times, serif
			}

			.stripe_summary_title {
			}

			#stripe_graph_tooltip {
				border: 4px solid #b9b9b9;
				padding: 11px 0 0 0;
				background-color: #f4f4f4;
				text-align: center;
				-moz-border-radius: 4px;
				-webkit-border-radius: 4px;
				border-radius: 4px;
				-khtml-border-radius: 4px;
			}

			#stripe_graph_tooltip .tooltip_tip {
				width: 14px;
				height: 14px;
				background-image: url(<?php echo self::$_this->get_base_url() ?>/images/tooltip_tip.png);
				background-repeat: no-repeat;
				position: absolute;
				bottom: -14px;
				left: 68px;
			}

			.stripe_tooltip_date {
				line-height: 130%;
				font-weight: bold;
				font-size: 13px;
				color: #21759B;
			}

			.stripe_tooltip_sales {
				line-height: 130%;
			}

			.stripe_tooltip_revenue {
				line-height: 130%;
			}

			.stripe_tooltip_revenue .stripe_tooltip_heading {
			}

			.stripe_tooltip_revenue .stripe_tooltip_value {
			}

			.stripe_trial_disclaimer {
				clear: both;
				padding-top: 20px;
				font-size: 10px;
			}
		</style>
		<script type="text/javascript" src="<?php echo self::$_this->get_base_url() ?>/js/jquery.flot.min.js"></script>
		<script type="text/javascript" src="<?php echo self::$_this->get_base_url() ?>/js/currency.js"></script>

		<div class="wrap">
			<img alt="<?php _e( 'Stripe', 'gfp-stripe' ) ?>" style="margin: 15px 7px 0pt 0pt; float: left;"
					 src="<?php echo self::$_this->get_base_url() ?>/images/stripe_wordpress_icon_32.png"/>

			<h2><?php _e( 'Stripe Stats', 'gfp-stripe' ) ?></h2>

			<form method="post" action="">
				<ul class="subsubsub">
					<li><a class="<?php echo ( ! RGForms::get( 'tab' ) || 'daily' == RGForms::get( 'tab' ) ) ? 'current' : '' ?>"
								 href="?page=gfp_stripe&view=stats&id=<?php echo $_GET["id"] ?>"><?php _e( 'Daily', 'gravityforms' ); ?></a>
						|
					</li>
					<li><a class="<?php echo 'weekly' == RGForms::get( 'tab' ) ? 'current' : '' ?>"
								 href="?page=gfp_stripe&view=stats&id=<?php echo $_GET["id"] ?>&tab=weekly"><?php _e( 'Weekly', 'gravityforms' ); ?></a>
						|
					</li>
					<li><a class="<?php echo 'monthly' == RGForms::get( 'tab' ) ? 'current' : '' ?>"
								 href="?page=gfp_stripe&view=stats&id=<?php echo $_GET["id"] ?>&tab=monthly"><?php _e( 'Monthly', 'gravityforms' ); ?></a>
					</li>
				</ul>
				<?php
				$feed = GFP_Stripe_Data::get_feed( RGForms::get( 'id' ) );

				switch ( RGForms::get( 'tab' ) ) {
					case 'monthly' :
						$chart_info = $this->monthly_chart_info( $feed );
						break;

					case 'weekly' :
						$chart_info = $this->weekly_chart_info( $feed );
						break;

					default :
						$chart_info = $this->daily_chart_info( $feed );
						break;
				}

				if ( ! $chart_info["series"] ) {
					?>
					<div
						class="stripe_message_container"><?php _e( 'No payments have been made yet.', 'gfp-stripe' ) ?> <?php echo $feed["meta"]["trial_period_enabled"] && empty( $feed["meta"]["trial_amount"] ) ? " **" : "" ?></div>
				<?php
				}
				else {
				?>
					<div class="stripe_graph_container">
						<div id="graph_placeholder" style="width:100%;height:300px;"></div>
					</div>

					<script type="text/javascript">
						var stripe_graph_tooltips = <?php echo $chart_info[ "tooltips" ]?>;
						jQuery.plot( jQuery( "#graph_placeholder" ), <?php echo $chart_info[ "series" ] ?>, <?php echo $chart_info[ "options" ] ?> );
						jQuery( window ).resize( function () {
							jQuery.plot( jQuery( "#graph_placeholder" ), <?php echo $chart_info[ "series" ] ?>, <?php echo $chart_info[ "options" ] ?> );
						} );

						var previousPoint = null;
						jQuery( "#graph_placeholder" ).bind( "plothover", function ( event, pos, item ) {
							startShowTooltip( item );
						} );

						jQuery( "#graph_placeholder" ).bind( "plotclick", function ( event, pos, item ) {
							startShowTooltip( item );
						} );

						function startShowTooltip( item ) {
							if ( item ) {
								if ( !previousPoint || previousPoint[0] != item.datapoint[0] ) {
									previousPoint = item.datapoint;

									jQuery( "#stripe_graph_tooltip" ).remove();
									var x = item.datapoint[0].toFixed( 2 ),
										y = item.datapoint[1].toFixed( 2 );

									showTooltip( item.pageX, item.pageY, stripe_graph_tooltips[item.dataIndex] );
								}
							}
							else {
								jQuery( "#stripe_graph_tooltip" ).remove();
								previousPoint = null;
							}
						}
						function showTooltip( x, y, contents ) {
							jQuery( '<div id="stripe_graph_tooltip">' + contents + '<div class="tooltip_tip"></div></div>' ).css( {
																																																											position: 'absolute',
																																																											display: 'none',
																																																											opacity: 0.90,
																																																											width: '150px',
																																																											height: '<?php echo "subscription" == $feed[ "meta" ][ "type" ] ? "75px" : "60px";?>',
																																																											top: y - <?php echo "subscription" == $feed[ "meta" ][ "type" ] ? "100" : "89";?>,
																																																											left: x - 79
																																																										} ).appendTo( "body" ).fadeIn( 200 );
						}
						function convertToMoney( number ) {
							var currency = getCurrentCurrency();
							return currency.toMoney( number );
						}
						function formatWeeks( number ) {
							number = number + "";
							return "<?php _e( "Week ", "gfp-stripe" ) ?>" + number.substring( number.length - 2 );
						}
						function getCurrentCurrency() {
							<?php
									if ( ! class_exists( 'RGCurrency' ) )
										require_once( ABSPATH . '/' . PLUGINDIR . '/gravityforms/currency.php' );

									$current_currency = RGCurrency::get_currency( GFCommon::get_currency() );
									?>
							var currency = new Currency( <?php echo GFCommon::json_encode( $current_currency )?> );
							return currency;
						}
					</script>
				<?php
				}
				$payment_totals = RGFormsModel::get_form_payment_totals( $feed["form_id"] );
				$transaction_totals = GFP_Stripe_Data::get_transaction_totals( $feed["form_id"] );

				switch ( $feed["meta"]["type"] ) {
					case 'product' :
						$total_sales = $payment_totals["orders"];
						$sales_label = __( 'Total Orders', 'gfp-stripe' );
						break;

					case 'donation' :
						$total_sales = $payment_totals["orders"];
						$sales_label = __( 'Total Donations', 'gfp-stripe' );
						break;

					case 'subscription' :
						$total_sales = $payment_totals["active"];
						$sales_label = __( 'Active Subscriptions', 'gfp-stripe' );
						break;
				}

				$total_revenue = empty( $transaction_totals["payment"]["revenue"] ) ? 0 : $transaction_totals["payment"]["revenue"];
				?>
				<div class="stripe_summary_container">
					<div class="stripe_summary_item">
						<div class="stripe_summary_title"><?php _e( 'Total Revenue', 'gfp-stripe' ) ?></div>
						<div class="stripe_summary_value"><?php echo GFCommon::to_money( $total_revenue ) ?></div>
					</div>
					<div class="stripe_summary_item">
						<div class="stripe_summary_title"><?php echo $chart_info["revenue_label"] ?></div>
						<div class="stripe_summary_value"><?php echo $chart_info["revenue"] ?></div>
					</div>
					<div class="stripe_summary_item">
						<div class="stripe_summary_title"><?php echo $sales_label ?></div>
						<div class="stripe_summary_value"><?php echo $total_sales ?></div>
					</div>
					<div class="stripe_summary_item">
						<div class="stripe_summary_title"><?php echo $chart_info["sales_label"] ?></div>
						<div class="stripe_summary_value"><?php echo $chart_info["sales"] ?></div>
					</div>
				</div>
				<?php
				if ( ! $chart_info["series"] && $feed["meta"]["trial_period_enabled"] && empty( $feed["meta"]["trial_amount"] ) ) {
					?>
					<div
						class="stripe_trial_disclaimer"><?php _e( '** Free trial transactions will only be reflected in the graph after the first payment is made (i.e. after trial period ends)', 'gfp-stripe' ) ?></div>
				<?php
				}
				?>
			</form>
		</div>
	<?php
	}

	/**
	 * @param $local_datetime
	 *
	 * @return int|string
	 */
	private function get_graph_timestamp ( $local_datetime ) {
		$local_timestamp      = mysql2date( 'G', $local_datetime ); //getting timestamp with timezone adjusted
		$local_date_timestamp = mysql2date( 'G', gmdate( 'Y-m-d 23:59:59', $local_timestamp ) ); //setting time portion of date to midnight (to match the way Javascript handles dates)
		$timestamp            = ( $local_date_timestamp - ( 24 * 60 * 60 ) + 1 ) * 1000; //adjusting timestamp for Javascript (subtracting a day and transforming it to milliseconds
		$date                 = gmdate( 'Y-m-d', $timestamp );

		return $timestamp;
	}

	/**
	 * @param $format
	 * @param $js_timestamp
	 *
	 * @return bool
	 */
	private function matches_current_date ( $format, $js_timestamp ) {
		$target_date = 'YW' == $format ? $js_timestamp : date( $format, $js_timestamp / 1000 );

		$current_date = gmdate( $format, GFCommon::get_local_timestamp( time() ) );

		return $target_date == $current_date;
	}

	/**
	 * @param $feed
	 *
	 * @return array
	 */
	private function daily_chart_info ( $feed ) {
		global $wpdb;

		$tz_offset = $this->get_mysql_tz_offset();

		$results = $wpdb->get_results( "SELECT CONVERT_TZ(t.date_created, '+00:00', '" . $tz_offset . "') as date, sum(t.amount) as amount_sold, sum(is_renewal) as renewals, sum(is_renewal=0) as new_sales
                                        FROM {$wpdb->prefix}rg_lead l
                                        INNER JOIN {$wpdb->prefix}rg_stripe_transaction t ON l.id = t.entry_id
                                        WHERE form_id={$feed["form_id"]} AND t.transaction_type='payment'
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc
                                        LIMIT 30" );

		$sales_today   = 0;
		$revenue_today = 0;
		$tooltips      = '';
		$series        = '';
		$options       = '';
		if ( ! empty( $results ) ) {

			$data = '[';

			foreach ( $results as $result ) {
				$timestamp = $this->get_graph_timestamp( $result->date );
				if ( $this->matches_current_date( 'Y-m-d', $timestamp ) ) {
					$sales_today += $result->new_sales;
					$revenue_today += $result->amount_sold;
				}
				$data .= "[{$timestamp},{$result->amount_sold}],";

				if ( 'subscription' == $feed["meta"]["type"] ) {
					$sales_line = " <div class='stripe_tooltip_subscription'><span class='stripe_tooltip_heading'>" . __( "New Subscriptions", "gfp-stripe" ) . ": </span><span class='stripe_tooltip_value'>" . $result->new_sales . "</span></div><div class='stripe_tooltip_subscription'><span class='stripe_tooltip_heading'>" . __( "Renewals", "gfp-stripe" ) . ": </span><span class='stripe_tooltip_value'>" . $result->renewals . "</span></div>";
				}
				else {
					$sales_line = "<div class='stripe_tooltip_sales'><span class='stripe_tooltip_heading'>" . __( "Orders", "gfp-stripe" ) . ": </span><span class='stripe_tooltip_value'>" . $result->new_sales . "</span></div>";
				}

				$tooltips .= "\"<div class='stripe_tooltip_date'>" . GFCommon::format_date( $result->date, false, "", false ) . "</div>{$sales_line}<div class='stripe_tooltip_revenue'><span class='stripe_tooltip_heading'>" . __( "Revenue", "gfp-stripe" ) . ": </span><span class='stripe_tooltip_value'>" . GFCommon::to_money( $result->amount_sold ) . "</span></div>\",";
			}
			$data     = substr( $data, 0, strlen( $data ) - 1 );
			$tooltips = substr( $tooltips, 0, strlen( $tooltips ) - 1 );
			$data .= "]";

			$series      = "[{data:" . $data . "}]";
			$month_names = $this->get_chart_month_names();
			$options     = "
            {
                xaxis: {mode: 'time', monthnames: $month_names, timeformat: '%b %d', minTickSize:[1, 'day']},
                yaxis: {tickFormatter: convertToMoney},
                bars: {show:true, align:'right', barWidth: (24 * 60 * 60 * 1000) - 10000000},
                colors: ['#a3bcd3', '#14568a'],
                grid: {hoverable: true, clickable: true, tickColor: '#F1F1F1', backgroundColor:'#FFF', borderWidth: 1, borderColor: '#CCC'}
            }";
		}
		switch ( $feed["meta"]["type"] ) {
			case 'product' :
				$sales_label = __( 'Orders Today', 'gfp-stripe' );
				break;

			case 'donation' :
				$sales_label = __( 'Donations Today', 'gfp-stripe' );
				break;

			case 'subscription' :
				$sales_label = __( 'Subscriptions Today', 'gfp-stripe' );
				break;
		}
		$revenue_today = GFCommon::to_money( $revenue_today );

		return array(
			'series'        => $series,
			'options'       => $options,
			'tooltips'      => "[$tooltips]",
			'revenue_label' => __( 'Revenue Today', 'gfp-stripe' ),
			'revenue'       => $revenue_today,
			'sales_label'   => $sales_label,
			'sales'         => $sales_today );
	}

	/**
	 * @param $feed
	 *
	 * @return array
	 */
	private function weekly_chart_info ( $feed ) {
		global $wpdb;

		$tz_offset = $this->get_mysql_tz_offset();

		$results      = $wpdb->get_results( "SELECT yearweek(CONVERT_TZ(t.date_created, '+00:00', '" . $tz_offset . "')) week_number, sum(t.amount) as amount_sold, sum(is_renewal) as renewals, sum(is_renewal=0) as new_sales
                                            FROM {$wpdb->prefix}rg_lead l
                                            INNER JOIN {$wpdb->prefix}rg_stripe_transaction t ON l.id = t.entry_id
                                            WHERE form_id={$feed["form_id"]} AND t.transaction_type='payment'
                                            GROUP BY week_number
                                            ORDER BY week_number desc
                                            LIMIT 30" );
		$sales_week   = 0;
		$revenue_week = 0;
		$tooltips     = '';
		if ( ! empty( $results ) ) {
			$data = '[';

			foreach ( $results as $result ) {
				if ( $this->matches_current_date( 'YW', $result->week_number ) ) {
					$sales_week += $result->new_sales;
					$revenue_week += $result->amount_sold;
				}
				$data .= "[{$result->week_number},{$result->amount_sold}],";

				if ( "subscription" == $feed["meta"]["type"] ) {
					$sales_line = " <div class='stripe_tooltip_subscription'><span class='stripe_tooltip_heading'>" . __( "New Subscriptions", "gfp-stripe" ) . ": </span><span class='stripe_tooltip_value'>" . $result->new_sales . "</span></div><div class='stripe_tooltip_subscription'><span class='stripe_tooltip_heading'>" . __( "Renewals", "gfp-stripe" ) . ": </span><span class='stripe_tooltip_value'>" . $result->renewals . "</span></div>";
				}
				else {
					$sales_line = "<div class='stripe_tooltip_sales'><span class='stripe_tooltip_heading'>" . __( "Orders", "gfp-stripe" ) . ": </span><span class='stripe_tooltip_value'>" . $result->new_sales . "</span></div>";
				}

				$tooltips .= "\"<div class='stripe_tooltip_date'>" . substr( $result->week_number, 0, 4 ) . ", " . __( "Week", "gfp-stripe" ) . " " . substr( $result->week_number, strlen( $result->week_number ) - 2, 2 ) . "</div>{$sales_line}<div class='stripe_tooltip_revenue'><span class='stripe_tooltip_heading'>" . __( "Revenue", "gfp-stripe" ) . ": </span><span class='stripe_tooltip_value'>" . GFCommon::to_money( $result->amount_sold ) . "</span></div>\",";
			}
			$data     = substr( $data, 0, strlen( $data ) - 1 );
			$tooltips = substr( $tooltips, 0, strlen( $tooltips ) - 1 );
			$data .= "]";

			$series      = "[{data:" . $data . "}]";
			$month_names = $this->get_chart_month_names();
			$options     = "
                {
                    xaxis: {tickFormatter: formatWeeks, tickDecimals: 0},
                    yaxis: {tickFormatter: convertToMoney},
                    bars: {show:true, align:'center', barWidth:0.95},
                    colors: ['#a3bcd3', '#14568a'],
                    grid: {hoverable: true, clickable: true, tickColor: '#F1F1F1', backgroundColor:'#FFF', borderWidth: 1, borderColor: '#CCC'}
                }";
		}

		switch ( $feed["meta"]["type"] ) {
			case 'product' :
				$sales_label = __( 'Orders this Week', 'gfp-stripe' );
				break;

			case 'donation' :
				$sales_label = __( 'Donations this Week', 'gfp-stripe' );
				break;

			case 'subscription' :
				$sales_label = __( 'Subscriptions this Week', 'gfp-stripe' );
				break;
		}
		$revenue_week = GFCommon::to_money( $revenue_week );

		return array(
			'series'        => $series,
			'options'       => $options,
			'tooltips'      => "[$tooltips]",
			'revenue_label' => __( 'Revenue this Week', 'gfp-stripe' ),
			'revenue'       => $revenue_week,
			'sales_label'   => $sales_label,
			'sales'         => $sales_week );
	}

	/**
	 * @param $feed
	 *
	 * @return array
	 */
	private function monthly_chart_info ( $feed ) {
		global $wpdb;
		$tz_offset = $this->get_mysql_tz_offset();

		$results = $wpdb->get_results( "SELECT date_format(CONVERT_TZ(t.date_created, '+00:00', '" . $tz_offset . "'), '%Y-%m-02') date, sum(t.amount) as amount_sold, sum(is_renewal) as renewals, sum(is_renewal=0) as new_sales
                                            FROM {$wpdb->prefix}rg_lead l
                                            INNER JOIN {$wpdb->prefix}rg_stripe_transaction t ON l.id = t.entry_id
                                            WHERE form_id={$feed["form_id"]} AND t.transaction_type='payment'
                                            group by date
                                            order by date desc
                                            LIMIT 30" );

		$sales_month   = 0;
		$revenue_month = 0;
		$tooltips      = '';
		if ( ! empty( $results ) ) {

			$data = '[';

			foreach ( $results as $result ) {
				$timestamp = $this->get_graph_timestamp( $result->date );
				if ( $this->matches_current_date( 'Y-m', $timestamp ) ) {
					$sales_month += $result->new_sales;
					$revenue_month += $result->amount_sold;
				}
				$data .= "[{$timestamp},{$result->amount_sold}],";

				if ( "subscription" == $feed["meta"]["type"] ) {
					$sales_line = " <div class='stripe_tooltip_subscription'><span class='stripe_tooltip_heading'>" . __( "New Subscriptions", "gfp-stripe" ) . ": </span><span class='stripe_tooltip_value'>" . $result->new_sales . "</span></div><div class='stripe_tooltip_subscription'><span class='stripe_tooltip_heading'>" . __( "Renewals", "gfp-stripe" ) . ": </span><span class='stripe_tooltip_value'>" . $result->renewals . "</span></div>";
				}
				else {
					$sales_line = "<div class='stripe_tooltip_sales'><span class='stripe_tooltip_heading'>" . __( "Orders", "gfp-stripe" ) . ": </span><span class='stripe_tooltip_value'>" . $result->new_sales . "</span></div>";
				}

				$tooltips .= "\"<div class='stripe_tooltip_date'>" . GFCommon::format_date( $result->date, false, "F, Y", false ) . "</div>{$sales_line}<div class='stripe_tooltip_revenue'><span class='stripe_tooltip_heading'>" . __( "Revenue", "gfp-stripe" ) . ": </span><span class='stripe_tooltip_value'>" . GFCommon::to_money( $result->amount_sold ) . "</span></div>\",";
			}
			$data     = substr( $data, 0, strlen( $data ) - 1 );
			$tooltips = substr( $tooltips, 0, strlen( $tooltips ) - 1 );
			$data .= "]";

			$series      = "[{data:" . $data . "}]";
			$month_names = $this->get_chart_month_names();
			$options     = "
                {
                    xaxis: {mode: 'time', monthnames: $month_names, timeformat: '%b %y', minTickSize: [1, 'month']},
                    yaxis: {tickFormatter: convertToMoney},
                    bars: {show:true, align:'center', barWidth: (24 * 60 * 60 * 30 * 1000) - 130000000},
                    colors: ['#a3bcd3', '#14568a'],
                    grid: {hoverable: true, clickable: true, tickColor: '#F1F1F1', backgroundColor:'#FFF', borderWidth: 1, borderColor: '#CCC'}
                }";
		}
		switch ( $feed["meta"]["type"] ) {
			case 'product' :
				$sales_label = __( 'Orders this Month', 'gfp-stripe' );
				break;

			case 'donation' :
				$sales_label = __( 'Donations this Month', 'gfp-stripe' );
				break;

			case 'subscription' :
				$sales_label = __( 'Subscriptions this Month', 'gfp-stripe' );
				break;
		}
		$revenue_month = GFCommon::to_money( $revenue_month );

		return array(
			'series'        => $series,
			'options'       => $options,
			'tooltips'      => "[$tooltips]",
			'revenue_label' => __( 'Revenue this Month', 'gfp-stripe' ),
			'revenue'       => $revenue_month,
			'sales_label'   => $sales_label,
			'sales'         => $sales_month );
	}

	/**
	 * @return string
	 */
	private function get_mysql_tz_offset () {
		$tz_offset = get_option( 'gmt_offset' );

		//add + if offset starts with a number
		if ( is_numeric( substr( $tz_offset, 0, 1 ) ) )
			$tz_offset = '+' . $tz_offset;

		return $tz_offset . ':00';
	}

	/**
	 * @return string
	 */
	private function get_chart_month_names () {
		return "['" . __( "Jan", "gfp-stripe" ) . "','" . __( "Feb", "gfp-stripe" ) . "','" . __( "Mar", "gfp-stripe" ) . "','" . __( "Apr", "gfp-stripe" ) . "','" . __( "May", "gfp-stripe" ) . "','" . __( "Jun", "gfp-stripe" ) . "','" . __( "Jul", "gfp-stripe" ) . "','" . __( "Aug", "gfp-stripe" ) . "','" . __( "Sep", "gfp-stripe" ) . "','" . __( "Oct", "gfp-stripe" ) . "','" . __( "Nov", "gfp-stripe" ) . "','" . __( "Dec", "gfp-stripe" ) . "']";
	}

	/**
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  get_transient()
	 * @uses  wp_count_posts()
	 * @uses  wp_count_comments()
	 * @uses  wp_get_theme()
	 * @uses  get_stylesheet_directory()
	 * @uses  get_plugins()
	 * @uses  get_plugin_data
	 * @uses  site_url()
	 * @uses  get_bloginfo()
	 * @uses  get_option()
	 * @uses  wp_remote_get()
	 * @uses  set_transient
	 *
	 * @return void
	 */
	private function do_presstrends () {
		// PressTrends Account API Key
		$api_key = 'pa079pxk8dqtqb92pukvs60ryxcuxjil627k';
		$auth    = 'fawbd412dhgvzqc06pc3qb9onlbdy8vmv';
		// Start of Metrics
		global $wpdb;
		$data = get_transient( 'gfp_stripe_presstrends_cache_data' );
		if ( ! $data || $data == '' ) {
			$api_base       = 'http://api.presstrends.io/index.php/api/pluginsites/update/auth/';
			$url            = $api_base . $auth . '/api/' . $api_key . '/';
			$count_posts    = wp_count_posts();
			$count_pages    = wp_count_posts( 'page' );
			$comments_count = wp_count_comments();
			if ( function_exists( 'wp_get_theme' ) ) {
				$theme_data = wp_get_theme();
				$theme_name = urlencode( $theme_data->Name );
			}
			else {
				$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
				$theme_name = $theme_data['Name'];
			}
			$plugin_name = '&';
			foreach ( get_plugins() as $plugin_info ) {
				$plugin_name .= $plugin_info['Name'] . '&';
			}

			$plugin_data         = get_plugin_data( GFP_STRIPE_FILE );
			$posts_with_comments = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type='post' AND comment_count > 0" );
			$data                = array(
				'url'             => stripslashes( str_replace( array( 'http://', '/', ':' ), '', site_url() ) ),
				'posts'           => $count_posts->publish,
				'pages'           => $count_pages->publish,
				'comments'        => $comments_count->total_comments,
				'approved'        => $comments_count->approved,
				'spam'            => $comments_count->spam,
				'pingbacks'       => $wpdb->get_var( "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_type = 'pingback'" ),
				'post_conversion' => ( $count_posts->publish > 0 && $posts_with_comments > 0 ) ? number_format( ( $posts_with_comments / $count_posts->publish ) * 100, 0, '.', '' ) : 0,
				'theme_version'   => $plugin_data['Version'],
				'theme_name'      => $theme_name,
				'site_name'       => str_replace( ' ', '', get_bloginfo( 'name' ) ),
				'plugins'         => count( get_option( 'active_plugins' ) ),
				'plugin'          => urlencode( $plugin_name ),
				'wpversion'       => get_bloginfo( 'version' ),
			);
			foreach ( $data as $k => $v ) {
				$url .= $k . '/' . $v . '/';
			}
			wp_remote_get( $url );
			set_transient( 'gfp_stripe_presstrends_cache_data', $data, 60 * 60 * 24 );
		}
	}

//------------------------------------------------------
//------------- FORM ---------------------------
//------------------------------------------------------
	/**
	 * Add Stripe.js
	 *
	 * @since 0.1.0
	 *
	 * @uses  GFCommon::has_credit_card_field()
	 * @uses  GFP_Stripe_Data::get_feed_by_form()
	 * @uses  wp_enqueue_script()
	 * @uses  GFCommon::get_base_url()
	 *
	 * @param null $form
	 * @param null $ajax
	 *
	 * @return void
	 */
	public function gform_enqueue_scripts ( $form = null, $ajax = null ) {

		if ( ! $form == null ) {

			if ( GFCommon::has_credit_card_field( $form ) ) {

				$form_feeds = GFP_Stripe_Data::get_feed_by_form( $form['id'] );

				if ( ! empty( $form_feeds ) ) {
					wp_enqueue_script( 'stripe-js', 'https://js.stripe.com/v1/', array( 'jquery' ) );
				}

				if ( 1 <= count( $form_feeds ) ) {
					$has_condition = $form_feeds[0]['meta']['stripe_conditional_enabled'] && $form_feeds[0]['meta']['stripe_conditional_field_id'];
					if ( $has_condition ) {
						wp_enqueue_script( 'gforms_conditional_logic_lib', GFCommon::get_base_url() . '/js/conditional_logic.js', array( 'jquery', 'gforms_gravityforms' ), GFCommon::$version );
					}
				}
			}

		}

	}

	/**
	 * Remove input field name attribute so credit card information is not sent to server
	 *
	 * @since 0.1.0
	 *
	 * @uses  GFP_Stripe_Data::get_feed_by_form()
	 *
	 * @param $field_content
	 * @param $field
	 * @param $default_value
	 * @param $lead_id
	 * @param $form_id
	 *
	 * @return mixed
	 */
	public function gform_field_content ( $field_content, $field, $default_value, $lead_id, $form_id ) {

		$form_feeds = GFP_Stripe_Data::get_feed_by_form( $form_id );

		if ( ! empty( $form_feeds ) ) {

			if ( 'creditcard' == $field['type'] ) {
				$search          = array();
				$exp_date_input  = $field['id'] . '.2';
				$card_type_input = $field['id'] . '.4';
				foreach ( $field['inputs'] as $input ) {
					if ( $card_type_input == $input['id'] ) {
						continue;
					}
					else {
						( $input['id'] == $exp_date_input ) ? ( $search[] = "name='input_" . $input['id'] . "[]'" ) : ( $search[] = "name='input_" . $input['id'] . "'" );
					}
				}
				$field_content = str_ireplace( $search, '', $field_content );
			}

		}

		return $field_content;

	}

	/**
	 * Check to see if ID is an input ID
	 *
	 * @since 1.7.9.1
	 *
	 * @param $id
	 *
	 * @return int
	 */
	private function is_input_id ( $id ) {
		$is_input_id = stripos( $id, '.' );

		return $is_input_id;
	}

	/**
	 * Get field ID from the ID saved in Stripe feed
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  GFP_Stripe::is_input_id()
	 *
	 * @param $field_id
	 *
	 * @return mixed
	 */
	private function get_field_id ( $id ) {
		$input_id = $this->is_input_id( $id );
		if ( $input_id ) {
			$id = substr( $id, 0, $input_id );
		}

		return $id;
	}

	/**
	 * Get form ID from form_string
	 *
	 * @since 1.7.9.1
	 *
	 * @param $form_string
	 *
	 * @return string
	 */
	private function get_form_id_from_form_string ( $form_string ) {
		$form_id = stristr( $form_string, 'gform_wrapper_' );
		$form_id = str_ireplace( 'gform_wrapper_', '', $form_id );
		//$form_id = stristr( $form_id, "'", true );
		$form_id = strtok( $form_id, "'" );

		return $form_id;
	}

	/**
	 * Get feed fields
	 *
	 * @since 1.7.9.1
	 *
	 * @param $form_feed
	 *
	 * @return array
	 */
	private function get_feed_fields ( $form_feed ) {
		return array( 'feed_field_address1' => $form_feed['meta']['customer_fields']['address1'],
									'feed_field_city'     => $form_feed['meta']['customer_fields']['city'],
									'feed_field_state'    => $form_feed['meta']['customer_fields']['state'],
									'feed_field_zip'      => $form_feed['meta']['customer_fields']['zip'],
									'feed_field_country'  => $form_feed['meta']['customer_fields']['country']
		);
	}

	/**
	 * Get field IDs
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  GFP_Stripe::get_field_id()
	 *
	 * @param $feed_fields
	 *
	 * @return array
	 */
	private function get_field_ids ( $feed_fields ) {
		$feed_field_address1 = $feed_field_city = $feed_field_state = $feed_field_zip = $feed_field_country = '';
		extract( $feed_fields );

		return array(
			'address1_field_id' => $this->get_field_id( $feed_field_address1 ),
			'city_field_id'     => $this->get_field_id( $feed_field_city ),
			'state_field_id'    => $this->get_field_id( $feed_field_state ),
			'zip_field_id'      => $this->get_field_id( $feed_field_zip ),
			'country_field_id'  => $this->get_field_id( $feed_field_country )
		);
	}

	/**
	 * Get field input ID
	 *
	 * @since 1.7.9.1
	 *
	 * @param $field_input_id
	 *
	 * @return string
	 */
	private function get_field_input_id ( $field_input_id ) {
		$separator_position = stripos( $field_input_id, '.' );
		$input_id           = substr( $field_input_id, $separator_position + 1 );

		return $input_id;
	}

	/**
	 * Get form input IDs
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  GFP_Stripe::get_field_input_id()
	 *
	 * @param $form
	 * @param $feed_fields
	 * @param $feed_field_ids
	 *
	 * @return array
	 */
	private function get_form_input_ids ( $form, $feed_fields, $feed_field_ids ) {
		$form_input_ids      = array( 'street_input_id'  => '',
																	'city_input_id'    => '',
																	'state_input_id'   => '',
																	'zip_input_id'     => '',
																	'country_input_id' => '' );
		$feed_field_address1 = $feed_field_city = $feed_field_state = $feed_field_zip = $feed_field_country = '';
		extract( $feed_fields );
		$address1_field_id = $city_field_id = $state_field_id = $zip_field_id = $country_field_id = '';
		extract( $feed_field_ids );

		foreach ( $form['fields'] as $field ) {
			if ( 'creditcard' == $field['type'] ) {
				$form_input_ids['creditcard_field_id'] = $field['id'];
			}
			else {
				if ( ! empty( $field['inputs'] ) ) {
					foreach ( $field['inputs'] as $input ) {
						switch ( $input['id'] ) {
							case $feed_field_address1:
								$input_id                          = $this->get_field_input_id( $input['id'] );
								$street_input_id                   = $form['id'] . '_' . $field['id'] . '_' . $input_id;
								$form_input_ids['street_input_id'] = $street_input_id;
								break;
							case $feed_field_city:
								$input_id                        = $this->get_field_input_id( $input['id'] );
								$city_input_id                   = $form['id'] . '_' . $field['id'] . '_' . $input_id;
								$form_input_ids['city_input_id'] = $city_input_id;
								break;
							case $feed_field_state:
								$input_id                         = $this->get_field_input_id( $input['id'] );
								$state_input_id                   = $form['id'] . '_' . $field['id'] . '_' . $input_id;
								$form_input_ids['state_input_id'] = $state_input_id;
								break;
							case $feed_field_zip:
								$input_id                       = $this->get_field_input_id( $input['id'] );
								$zip_input_id                   = $form['id'] . '_' . $field['id'] . '_' . $input_id;
								$form_input_ids['zip_input_id'] = $zip_input_id;
								break;
							case $feed_field_country:
								$input_id                           = $this->get_field_input_id( $input['id'] );
								$country_input_id                   = $form['id'] . '_' . $field['id'] . '_' . $input_id;
								$form_input_ids['country_input_id'] = $country_input_id;
								break;
						}
					}
				}
				else {
					switch ( $field['id'] ) { //assumes field IDs are unique e.g. city and state aren't equal to same field
						case $address1_field_id:
							$form_input_ids['street_input_id'] = $form['id'] . '_' . $field['id'];
							break;
						case $city_field_id:
							$form_input_ids['city_input_id'] = $form['id'] . '_' . $field['id'];
							break;
						case $state_field_id:
							$form_input_ids['state_input_id'] = $form['id'] . '_' . $field['id'];
							break;
						case $zip_field_id:
							$form_input_ids['zip_input_id'] = $form['id'] . '_' . $field['id'];
							break;
						case $country_field_id:
							$form_input_ids['country_input_id'] = $form['id'] . '_' . $field['id'];
							break;
					}
				}
			}
		}

		return $form_input_ids;
	}

	/**
	 * Does feed have conditional logic
	 *
	 * @since 1.7.9.1
	 *
	 * @param $feed
	 * @param $conditional_field_id
	 *
	 * @return bool
	 */
	private function feed_has_condition ( $feed, $conditional_field_id ) {
		$has_condition = ( ( '1' == $feed['meta']['stripe_conditional_enabled'] ) && ( $conditional_field_id == $feed['meta']['stripe_conditional_field_id'] ) );

		return $has_condition;
	}

	/**
	 * @param $feed
	 *
	 * @return array
	 */
	private function get_feed_condition ( $feed ) {
		$feed_condition = array();

		$feed_condition['operator'] = $feed['meta']['stripe_conditional_operator'];
		$feed_condition['value']    = $feed['meta']['stripe_conditional_value'];

		return $feed_condition;
	}

	/**
	 * Add Stripe JS
	 *
	 * @since 0.1.0
	 *
	 * @uses  GFP_Stripe::get_form_id_from_form_string()
	 * @uses  RGFormsModel::get_form_meta()
	 * @uses  GFCommon::has_credit_card_field()
	 * @uses  GFP_Stripe_Data::get_feed_by_form()
	 * @uses  GFP_Stripe::feed_has_condition()
	 * @uses  GFP_Stripe::get_api_key()
	 * @uses  GFP_Stripe::get_feed_fields()
	 * @uses  GFP_Stripe::get_field_ids()
	 * @uses  GFP_Stripe::get_form_input_ids()
	 * @uses  GFP_Stripe::get_feed_condition()
	 * @uses  apply_filters()
	 * @uses  GFCommon::json_encode()
	 *
	 * @param $form_string
	 *
	 * @return string
	 */
	public function gform_get_form_filter ( $form_string ) {
		//Get form ID
		$form_id = $this->get_form_id_from_form_string( $form_string );

		//Check for credit card field
		$form = RGFormsModel::get_form_meta( $form_id );
		if ( GFCommon::has_credit_card_field( $form ) ) {

			//Check for Stripe feed
			$form_feeds = GFP_Stripe_Data::get_feed_by_form( $form_id );

			//if there is more than one feed, check if there is a conditional, otherwise use the 1st feed
			//assumes the conditional field is the same for multiple feeds
			$conditional_field_id = 0;
			if ( 1 == count( $form_feeds ) ) {
				$form_feeds           = $form_feeds[0];
				$conditional_field_id = $form_feeds['meta']['stripe_conditional_field_id'];
			}
			else if ( 1 < count( $form_feeds ) ) {
				$valid_feeds          = 0;
				$conditional_field_id = $form_feeds[0]['meta']['stripe_conditional_field_id'];
				foreach ( $form_feeds as $feed ) {
					if ( $this->feed_has_condition( $feed, $conditional_field_id ) ) {
						$valid_feeds ++;
					}
				}
				//if all feeds don't match, use first feed — ideally return an error instead
				if ( $valid_feeds !== count( $form_feeds ) ) {
					$form_feeds           = $form_feeds[0];
					$conditional_field_id = $form_feeds['meta']['stripe_conditional_field_id'];
				}
			}

			if ( ! empty( $form_feeds ) ) {

				$stripe_form_id = $form_id;
				//Get Stripe API key
				$publishable_key = self::$_this->get_api_key( 'publishable' );

				//if more than one feed, find out if conditional logic affects Stripe token fields (address)
				$multiple_feeds = isset( $valid_feeds ) && ( 1 < $valid_feeds );
				if ( $multiple_feeds ) {
					$field_info = array();

					foreach ( $form_feeds as $feed ) {
						$feed_fields    = $this->get_feed_fields( $feed );
						$feed_field_ids = $this->get_field_ids( $feed_fields );
						$form_input_ids = $this->get_form_input_ids( $form, $feed_fields, $feed_field_ids );
						$feed_condition = $this->get_feed_condition( $feed );
						$field_info[]   = array_merge( $form_input_ids, $feed_condition );
					}
					$creditcard_field_id = $field_info[0]['creditcard_field_id'];

				}
				else {
					//insert JS
					$field_info          = array();
					$feed_fields         = $this->get_feed_fields( $form_feeds );
					$feed_field_ids      = $this->get_field_ids( $feed_fields );
					$creditcard_field_id = $street_input_id = $city_input_id = $state_input_id = $zip_input_id = $country_input_id = '';

					//Get credit card field ID and address fields if they exist
					extract( $this->get_form_input_ids( $form, $feed_fields, $feed_field_ids ) );

					$field_info['creditcard_field_id'] = $creditcard_field_id;
					$field_info['street_input_id']     = $street_input_id;
					$field_info['city_input_id']       = $city_input_id;
					$field_info['state_input_id']      = $state_input_id;
					$field_info['zip_input_id']        = $zip_input_id;
					$field_info['country_input_id']    = $country_input_id;
				}

				$field_info = apply_filters( 'gfp_stripe_gform_get_form_filter', $field_info, $form_feeds );

				//Make sure JS gets added for multi-page forms
				$is_postback     = false;
				$submission_info = isset( GFFormDisplay::$submission[$stripe_form_id] ) ? GFFormDisplay::$submission[$stripe_form_id] : false;
				if ( $submission_info ) {
					if ( $submission_info['is_valid'] ) {
						$is_postback = true;
					}
				}

				$is_ajax = stristr( $form_string, 'GF_AJAX_POSTBACK' );

				if ( ( ! $is_postback ) || ( $is_postback && ! $is_ajax ) ) {
					$js = $js_token = $js_condition = $js_start = $js_end = '';

					//add JS to create token
					$js_start .= "<script type='text/javascript'>" .
						"function stripeResponseHandler(status, response) {" .
						"var form$ = jQuery('#gform_{$stripe_form_id}');" .
						"if (response.error) {" .
						"var param = response.error.param;" .
						"form$.append(\"<input type='hidden' name='create_token_error' value='\" + response.error.message + \"' />\");" .
						"} else {" .
						"var token = response['id'];" .
						"var fingerprint = response['card']['fingerprint'];" .
						"var card_type = response['card']['type'];" .
						"form$.append(\"<input type='hidden' name='stripeToken' value='\" + token + \"' />\");" .
						"form$.append(\"<input type='hidden' name='input_{$creditcard_field_id}.1' value='\" + fingerprint + \"' />\");" .
						"form$.append(\"<input type='hidden' name='input_{$creditcard_field_id}.4' value='\" + card_type + \"' />\");" .
						"}" .
						"form$.get(0).submit();" .
						"}";
					$js_start .= "function gfp_stripe_set_stripe_info( stripe_feed ) {" .
						"Stripe.setPublishableKey('{$publishable_key}');" .
						"var card_number = jQuery('#gform_{$stripe_form_id} #input_{$stripe_form_id}_{$creditcard_field_id}_1').val();" .
						"var exp_month = jQuery('#gform_{$stripe_form_id} .ginput_card_expiration_month').val();" .
						"var exp_year = jQuery('#gform_{$stripe_form_id} .ginput_card_expiration_year').val();" .
						"var cvc = jQuery('#gform_{$stripe_form_id} .ginput_card_security_code').val();" .
						"var cardholder_name = jQuery('#gform_{$stripe_form_id} #input_{$stripe_form_id}_{$creditcard_field_id}_5').val();" .
						"if ( ! ( 'undefined' === typeof stripe_feed ) ) {" .
						"var address_line1 = ( ! ( 'undefined' === typeof stripe_feed['street'] ) ) ? jQuery('#gform_{$stripe_form_id} #input_' + stripe_feed['street']).val() : '';" .
						"var address_city = ( ! ( 'undefined' === typeof stripe_feed['city'] ) ) ? jQuery('#gform_{$stripe_form_id} #input_' + stripe_feed['city']).val() : '';" .
						"var address_state = ( ! ( 'undefined' === typeof stripe_feed['state'] ) ) ? jQuery('#gform_{$stripe_form_id} #input_' + stripe_feed['state']).val() : '';" .
						"var address_zip = ( ! ( 'undefined' === typeof stripe_feed['zip'] ) ) ? jQuery('#gform_{$stripe_form_id} #input_' + stripe_feed['zip']).val() : '';" .
						"var address_country = ( ! ( 'undefined' === typeof stripe_feed['country'] ) ) ? jQuery('#gform_{$stripe_form_id} #input_' + stripe_feed['country']).val() : '';" .
						"}" .
						"return { card_number: card_number, exp_month: exp_month, exp_year: exp_year, cvc: cvc, cardholder_name: cardholder_name, address_line1: address_line1, address_city: address_city, address_state: address_state, address_zip: address_zip, address_country: address_country };" .
						"}";
					$js_start .= "function gfp_stripe_validate_card( card_info ) {" .
						"var card_number_valid = Stripe.validateCardNumber( card_info.card_number );" .
						"var exp_date_valid = Stripe.validateExpiry(card_info.exp_month, card_info.exp_year);" .
						"var cvc_valid = Stripe.validateCVC(card_info.cvc);" .
						"var cardholder_name_valid = (card_info.cardholder_name.length > 0 ) ? true : false;" .
						"return { card_number: card_number_valid, exp_date: exp_date_valid, cvc: cvc_valid, cardholder_name: cardholder_name_valid};" .
						"}";
					$js_start .= "jQuery(document).bind('gform_post_render', function(event, formId, currentPage) {" .
						"if( formId !== {$stripe_form_id} ) { return; }" .
						"jQuery('#gform_{$stripe_form_id}').submit(function(){" .
						"var last_page = jQuery('#gform_target_page_number_{$stripe_form_id}').val();" .
						"if ( last_page === '0' ){" .
						"var form$ = jQuery('#gform_{$stripe_form_id}');" .
						"var card_info = '';" .
						"var card_valid = '';";
					$js_token = "if ( card_info ) {" .
						"if ( !card_valid.card_number || !card_valid.exp_date || !card_valid.cvc || !card_valid.cardholder_name ) {" .
						"form$.append(\"<input type='hidden' name='card_number_valid' value='\" + card_valid.card_number + \"' /><input type='hidden' name='exp_date_valid' value='\" + card_valid.exp_date + \"' /><input type='hidden' name='cvc_valid' value='\" + card_valid.cvc + \"' /><input type='hidden' name='cardholder_name_valid' value='\" + card_valid.cardholder_name + \"' />\");" .
						"} else {" .
						"var token = Stripe.createToken({" .
						"number: card_info.card_number," .
						"exp_month: card_info.exp_month," .
						"exp_year: card_info.exp_year," .
						"cvc: card_info.cvc," .
						"name: card_info.cardholder_name," .
						"address_line1: ( ! ( typeof card_info.address_line1 === 'undefined' ) ) ? card_info.address_line1 : ''," .
						"address_city: ( ! ( typeof card_info.address_city === 'undefined' ) ) ? card_info.address_city : ''," .
						"address_zip: ( ! ( typeof card_info.address_zip === 'undefined' ) ) ? card_info.address_zip : ''," .
						"address_state: ( ! ( typeof card_info.address_state === 'undefined' ) ) ? card_info.address_state : ''," .
						"address_country: ( ! ( typeof card_info.address_country === 'undefined' ) ) ? card_info.address_country : ''," .
						"}, stripeResponseHandler);" .
						"return false;" .
						"}" .
						"}";
					$js_end   =
						"}" .

						"});" .
						"});</script>";

					$js .= $js_start;

					if ( array_key_exists( 0, $field_info ) && ( is_array( $field_info[0] ) ) ) {
						$js_condition .= "var stripe_condition = [];";
						foreach ( $field_info as $info ) {
							$js_condition .= "stripe_condition.push(" . GFCommon::json_encode( array( 'operator' => $info['operator'],
																																												'fieldId'  => $conditional_field_id,
																																												'value'    => $info['value'],
																																												'street'   => $info['street_input_id'],
																																												'city'     => $info['city_input_id'],
																																												'state'    => $info['state_input_id'],
																																												'zip'      => $info['zip_input_id'],
																																												'country'  => $info['country_input_id']
																																								 ) ) . ");";
						}
						$js_condition .= 'for( var i = 0; i < stripe_condition.length; i++ ) {' .
							'var rule = stripe_condition[i];' .
							'if ( gf_is_match( formId, rule ) ) {' .
							"card_info = gfp_stripe_set_stripe_info( rule );" .
							"card_valid = gfp_stripe_validate_card( card_info );" .
							'}' .
							'}';

						$js .= $js_condition . $js_token;
					}
					else if ( ( $conditional_field_id ) && ( $this->feed_has_condition( $form_feeds, $conditional_field_id ) ) ) {
						$field_info = array_merge( $field_info, $this->get_feed_condition( $form_feeds ) );

						if ( array_key_exists( 'operator', $field_info ) ) {
							$js_condition .= "var stripe_condition = [];" .
								"stripe_condition.push(" . GFCommon::json_encode( array( 'operator' => $field_info['operator'],
																																				 'fieldId'  => $conditional_field_id,
																																				 'value'    => $field_info['value'],
																																				 'street'   => $field_info['street_input_id'],
																																				 'city'     => $field_info['city_input_id'],
																																				 'state'    => $field_info['state_input_id'],
																																				 'zip'      => $field_info['zip_input_id'],
																																				 'country'  => $field_info['country_input_id'] ) ) . ");";
							$js_condition .= 'for( var i = 0; i < stripe_condition.length; i++ ) {' .
								'var rule = stripe_condition[i];' .
								'if ( gf_is_match( formId, rule ) ) {' .
								"card_info = gfp_stripe_set_stripe_info( rule );" .
								"card_valid = gfp_stripe_validate_card( card_info );" .
								'}' .
								'}';

							$js .= $js_condition . $js_token;

						}

					}
					else {
						$js .= "var stripe_feed = " .
							GFCommon::json_encode( array( 'street'  => $field_info['street_input_id'],
																						'city'    => $field_info['city_input_id'],
																						'state'   => $field_info['state_input_id'],
																						'zip'     => $field_info['zip_input_id'],
																						'country' => $field_info['country_input_id']
																		 ) ) . ";";
						$js .= "card_info = gfp_stripe_set_stripe_info( stripe_feed );";
						$js .= "card_valid = gfp_stripe_validate_card( card_info );";
						$js .= $js_token;
					}

					$js .= $js_end;

					$js = apply_filters( 'gfp_stripe_gform_get_form_filter_js', $js, $form, $stripe_form_id, $field_info );
					$form_string .= $js;
				}
			}
		}

		return $form_string;
	}

	//------------------------------------------------------
	//------------- PROCESSING ---------------------------
	//------------------------------------------------------

	/**
	 * @param $validation_result
	 * @param $value
	 * @param $form
	 * @param $field
	 *
	 * @return mixed
	 */
	public function gform_field_validation ( $validation_result, $value, $form, $field ) {
		$form_feeds = GFP_Stripe_Data::get_feed_by_form( $form['id'] );
		//TODO do I need to check for Stripe condition first?
		if ( ! empty( $form_feeds ) ) {
			if ( 'creditcard' == $field['type'] ) {
				$card_number_valid     = rgpost( 'card_number_valid' );
				$exp_date_valid        = rgpost( 'exp_date_valid' );
				$cvc_valid             = rgpost( 'cvc_valid' );
				$cardholder_name_valid = rgpost( 'cardholder_name_valid' );
				$create_token_error    = rgpost( 'create_token_error' );
				if ( ( 'false' == $card_number_valid ) || ( 'false' == $exp_date_valid ) || ( 'false' == $cvc_valid ) || ( 'false' == $cardholder_name_valid ) ) {
					$validation_result['is_valid'] = false;
					$message                       = ( 'false' == $card_number_valid ) ? __( 'Invalid credit card number.', 'gfp-stripe' ) : '';
					$message .= ( 'false' == $exp_date_valid ) ? __( ' Invalid expiration date.', 'gfp-stripe' ) : '';
					$message .= ( 'false' == $cvc_valid ) ? __( ' Invalid security code.', 'gfp-stripe' ) : '';
					$message .= ( 'false' == $cardholder_name_valid ) ? __( ' Invalid cardholder name.', 'gfp-stripe' ) : '';
					$validation_result['message'] = sprintf( __( '%s', 'gfp-stripe' ), $message );
				}
				else if ( ! empty( $create_token_error ) ) {
					$validation_result['is_valid'] = false;
					$validation_result['message']  = sprintf( __( '%s', 'gfp-stripe' ), $create_token_error );
				}
				else {
					$validation_result['is_valid'] = true;
					unset( $validation_result['message'] );
				}
			}
		}

		return $validation_result;
	}

	/**
	 * @param $validation_result
	 *
	 * @return mixed|void
	 */
	public function gform_validation ( $validation_result ) {

		$feed = $this->is_ready_for_capture( $validation_result );
		if ( ! $feed )
			return $validation_result;

		if ( 'product' == $feed['meta']['type'] ) {
			//making one time payment
			$validation_result = $this->make_product_payment( $feed, $validation_result );

		}
		else {
			$validation_result = apply_filters( 'gfp_stripe_gform_validation', $validation_result, $feed );
		}

		return $validation_result;
	}

	/**
	 * @param $validation_result
	 *
	 * @return bool
	 */
	private function is_ready_for_capture ( $validation_result ) {

		//if form has already failed validation or this is not the last page, abort
		if ( false == $validation_result["is_valid"] || ! $this->is_last_page( $validation_result["form"] ) )
			return false;

		//getting feed that matches condition (if conditions are enabled)
		$feed = self::$_this->get_feed( $validation_result["form"] );
		if ( ! $feed )
			return false;

		//making sure credit card field is visible TODO: check to see if this will actually work since there are no credit card fields submitted with the form
		$creditcard_field = $this->get_creditcard_field( $validation_result["form"] );
		if ( RGFormsModel::is_field_hidden( $validation_result["form"], $creditcard_field, array() ) )
			return false;

		return $feed;
	}

	/**
	 * @param $form
	 *
	 * @return bool
	 */
	private function is_last_page ( $form ) {
		$current_page = GFFormDisplay::get_source_page( $form["id"] );
		$target_page  = GFFormDisplay::get_target_page( $form, $current_page, rgpost( 'gform_field_values' ) );

		return ( $target_page == 0 );
	}

	/**
	 * @param $form
	 *
	 * @return bool
	 */
	public static function get_feed ( $form ) {

		//Getting stripe settings associated with this transaction
		$feeds = GFP_Stripe_Data::get_feed_by_form( $form["id"] );
		if ( ! $feeds )
			return false;

		foreach ( $feeds as $feed ) {
			if ( self::$_this->has_stripe_condition( $form, $feed ) )
				return $feed;
		}

		return false;
	}

	/**
	 * @param $form
	 * @param $feed
	 *
	 * @return bool
	 */
	public function has_stripe_condition ( $form, $feed ) {

		$feed = $feed['meta'];

		$operator = $feed['stripe_conditional_operator'];
		$field    = RGFormsModel::get_field( $form, $feed['stripe_conditional_field_id'] );

		if ( empty( $field ) || ! $feed['stripe_conditional_enabled'] )
			return true;

		// if conditional is enabled, but the field is hidden, ignore conditional
		$is_visible = ! RGFormsModel::is_field_hidden( $form, $field, array() );

		//TODO: if !is_visible then skip field_value stuff
		$field_value = RGFormsModel::get_field_value( $field, array() );

		$is_value_match = RGFormsModel::is_value_match( $field_value, $feed['stripe_conditional_value'], $operator );
		$do_stripe      = $is_value_match && $is_visible;

		return $do_stripe;
	}

	/**
	 * Get credit card field
	 *
	 * @since
	 *
	 * @uses GFCommon::get_fields_by_type()
	 *
	 * @param $form
	 *
	 * @return bool
	 */
	public function get_creditcard_field ( $form ) {
		$fields = GFCommon::get_fields_by_type( $form, array( 'creditcard' ) );

		return empty( $fields ) ? false : $fields[0];
	}

	/**
	 * Process payment
	 *
	 * @since 0.1.0
	 *
	 * @uses  GFP_Stripe::log_debug()
	 * @uses  GFP_Stripe::get_form_data()
	 * @uses  GFP_Stripe::is_last_page()
	 * @uses  GFP_Stripe::get_creditcard_field()
	 * @uses  GFP_Stripe::has_visible_products()
	 * @uses  GFP_Stripe::include_api()
	 * @uses  GFP_Stripe::get_api_key()
	 * @uses  Stripe::setApiKey()
	 * @uses  Stripe_Customer::create()
	 * @uses  apply_filters()
	 * @uses  GFP_Stripe::log_error()
	 * @uses  GFP_Stripe::gfp_stripe_create_error_message()
	 * @uses  GFP_Stripe::set_validation_result()
	 * @uses  Stripe_Charge::create(
	 * @uses  GFCommon::get_currency()
	 *
	 * @param $feed
	 * @param $validation_result
	 *
	 * @return mixed
	 */
	private function make_product_payment ( $feed, $validation_result ) {
		$form = $validation_result["form"];

		self::$_this->log_debug( "Starting to make a product payment for form: {$form["id"]}" );

		$form_data = self::$_this->get_form_data( $form, $feed );

		//don't process payment if total less than $0.50, but act as if the transaction was successful
		if ( $form_data["amount"] < 0.5 ) {
			self::$_this->log_debug( 'Amount is less than $0.50. No need to process payment, but act as if transaction was successful' );

			//blank out credit card field if this is the last page
			if ( $this->is_last_page( $form ) ) {
				$card_field                           = $this->get_creditcard_field( $form );
				$_POST["input_{$card_field["id"]}_1"] = '';
			}
			//creating dummy transaction response
			if ( $this->has_visible_products( $form ) ) {
				self::$transaction_response = array(
					'transaction_id'   => 'N/A',
					'amount'           => $form_data["amount"],
					'transaction_type' => 1 );
			}

			return $validation_result;
		}

		//create charge
		self::$_this->include_api();
		$secret_api_key = self::$_this->get_api_key( 'secret' );
		self::$_this->log_debug( 'Creating the customer' );
		try {
			$customer = Stripe_Customer::create( array(
																								'description' => apply_filters( 'gfp_stripe_customer_description', $form_data['name'], $form_data, $form ),
																								'card'        => $form_data['credit_card'],
																								'email'       => $form_data['email']
																					 ), $secret_api_key );
		} catch ( Exception $e ) {

			self::$_this->log_error( 'Customer creation failed' );
			$error_message = self::$_this->gfp_stripe_create_error_message( $e );

			return self::$_this->set_validation_result( $validation_result, $_POST, $error_message );

		}

		$use_stripe_connect = apply_filters( 'gfp_stripe_use_stripe_connect', false, $feed );
		if ( $use_stripe_connect ) {
			$access_token    = $use_stripe_connect['access_token'];
			$application_fee = $use_stripe_connect['application_fee'];
			try {
				$token = Stripe_Token::create(
					array( 'customer' => $customer['id'] ),
					$access_token
				);
			} catch ( Exception $e ) {
				self::$_this->log_error( 'Token creation failed' );
				$error_message = self::$_this->gfp_stripe_create_error_message( $e );

				return self::$_this->set_validation_result( $validation_result, $_POST, $error_message );
			}
			try {
				$customer = Stripe_Customer::create( array(
																									'description' => apply_filters( 'gfp_stripe_customer_description', $form_data['name'], $form_data, $form ),
																									'card'        => $token['id'],
																									'email'       => $form_data['email']
																						 ), $access_token );
			} catch ( Exception $e ) {

				self::$_this->log_error( 'Customer creation failed' );
				$error_message = self::$_this->gfp_stripe_create_error_message( $e );

				return self::$_this->set_validation_result( $validation_result, $_POST, $error_message );

			}
		}

		//Allows users to cancel charge
		$cancel = apply_filters( 'gfp_stripe_cancel_charge', false, $feed, $form );
		if ( $cancel ) {

			self::$_this->log_debug( "Charge creation canceled. Consider transaction successful with customer ID: {$customer['id']}" );

			self::$transaction_response = array(
				'transaction_id'   => $customer['id'],
				'amount'           => $form_data['amount'],
				'transaction_type' => 3 );

			$validation_result["is_valid"] = true;

			return $validation_result;
		}
		else {
			try {
				self::$_this->log_debug( 'Creating the charge, using the customer ID' );
				$response = Stripe_Charge::create( array(
																								'amount'          => ( $form_data['amount'] * 100 ),
																								'currency'        => GFCommon::get_currency(),
																								'customer'        => $customer['id'],
																								'description'     => apply_filters( 'gfp_stripe_customer_charge_description', implode( '\n', $form_data['line_items'] ), $form ),
																								'application_fee' => ( $use_stripe_connect && ( ! empty( $application_fee ) ) ) ? intval( $application_fee ) : null
																					 ), ( $use_stripe_connect ) ? $access_token : $secret_api_key );

				self::$_this->log_debug( "Charge successful. ID: {$response['id']} - Amount: {$response['amount']}" );

				self::$transaction_response = array(
					'transaction_id'   => $response['id'],
					'amount'           => $response['amount'] / 100,
					'transaction_type' => 1 );

				$validation_result["is_valid"] = true;

				return $validation_result;
			} catch ( Exception $e ) {
				self::$_this->log_error( 'Charge failed' );
				$error_message = self::$_this->gfp_stripe_create_error_message( $e );

				// Payment for single transaction was not successful
				return self::$_this->set_validation_result( $validation_result, $_POST, $error_message );
			}
		}

	}

	/**
	 * Get form data
	 *
	 * @since
	 *
	 * @uses RGFormsModel::create_lead()
	 * @uses GFCommon::get_product_fields()
	 * @uses rgpost()
	 * @uses apply_filters()
	 * @uses GFP_Stripe::get_order_info()
	 *
	 * @param $form
	 * @param $feed
	 *
	 * @return mixed|void
	 */
	public static function get_form_data ( $form, $feed ) {

		// get products
		$tmp_lead  = RGFormsModel::create_lead( $form );
		$products  = GFCommon::get_product_fields( $form, $tmp_lead );
		$form_data = array();

		// getting billing information
		$form_data['form_title']  = $form['title'];
		$form_data['name']        = rgpost( 'input_' . str_replace( '.', '_', $feed['meta']['customer_fields']['first_name'] ) ) . ' ' . rgpost( 'input_' . str_replace( '.', '_', $feed['meta']['customer_fields']['last_name'] ) );
		$form_data['email']       = rgpost( 'input_' . str_replace( '.', '_', $feed['meta']['customer_fields']['email'] ) );
		$form_data['address1']    = rgpost( 'input_' . str_replace( '.', '_', $feed['meta']['customer_fields']['address1'] ) );
		$form_data['address2']    = rgpost( 'input_' . str_replace( '.', '_', $feed['meta']['customer_fields']['address2'] ) );
		$form_data['city']        = rgpost( 'input_' . str_replace( '.', '_', $feed['meta']['customer_fields']['city'] ) );
		$form_data['state']       = rgpost( 'input_' . str_replace( '.', '_', $feed['meta']['customer_fields']['state'] ) );
		$form_data['zip']         = rgpost( 'input_' . str_replace( '.', '_', $feed['meta']['customer_fields']['zip'] ) );
		$form_data['country']     = rgpost( 'input_' . str_replace( '.', '_', $feed['meta']['customer_fields']['country'] ) );
		$form_data["credit_card"] = rgpost( 'stripeToken' );

		$form_data       = apply_filters( 'gfp_stripe_get_form_data', $form_data, $feed, $products );
		$order_info_args = '';
		$order_info      = self::$_this->get_order_info( $products, apply_filters( 'gfp_stripe_get_form_data_order_info', $order_info_args, $feed ) );

		$form_data["line_items"] = $order_info["line_items"];
		$form_data["amount"]     = $order_info["amount"];

		return $form_data;
	}

	/**
	 * Get order info
	 *
	 * @since
	 *
	 * @uses apply_filters()
	 * @uses GFCommon::to_number()
	 * @uses __()
	 * @uses has_action()
	 *
	 * @param $products
	 * @param $additional_fields
	 *
	 * @return array
	 */
	private function get_order_info ( $products, $additional_fields ) {
		$amount        = 0;
		$line_items    = array();
		$item          = 1;
		$continue_flag = 0;
		$new_line_item = '';
		foreach ( $products["products"] as $field_id => $product ) {
			$continue_flag = apply_filters( 'gfp_stripe_get_order_info', $continue_flag, $field_id, $additional_fields );
			if ( $continue_flag )
				continue;

			$quantity      = $product['quantity'] ? $product['quantity'] : 1;
			$product_price = GFCommon::to_number( $product['price'] );

			$options = array();
			if ( isset( $product['options'] ) && is_array( $product['options'] ) ) {
				foreach ( $product['options'] as $option ) {
					$options[] = $option['option_label'];
					$product_price += $option['price'];
				}
			}

			$amount += $product_price * $quantity;

			$description = '';
			if ( ! empty( $options ) )
				$description = __( 'options: ', 'gfp-stripe' ) . ' ' . implode( ', ', $options );

			if ( has_action( 'gfp_stripe_get_order_info_line_items' ) ) {
				$new_line_item = apply_filters( 'gfp_stripe_get_order_info_line_items', $line_items, $product_price, $field_id, $quantity, $product, $description, $item );
				if ( ! empty( $new_line_item ) ) {
					$line_items[]  = $new_line_item;
					$new_line_item = '';
					$item ++;
				}
			}
			else {
				if ( ( $product_price >= 0 ) ) {
					$line_items[] = "(" . $quantity . ")\t" . $product["name"] . "\t" . $description . "\tx\t$" . $product_price;
					$item ++;
				}
			}

		}

		if ( has_action( 'gfp_stripe_get_order_info_shipping' ) ) {
			$shipping_info = apply_filters( 'gfp_stripe_get_order_info_shipping', $line_items, $products, $amount, $item, $additional_fields );
			if ( ! empty( $shipping_info ) ) {
				$line_items    = $shipping_info['line_items'];
				$amount        = $shipping_info['amount'];
				$shipping_info = '';
			}
		}
		else {
			if ( ! empty( $products["shipping"]["name"] ) ) {
				$line_items[] = $item . "\t" . $products["shipping"]["name"] . "\t" . "1" . "\t" . $products["shipping"]["price"];
				$amount += $products["shipping"]["price"];
			}
		}

		return array(
			'amount'     => $amount,
			'line_items' => $line_items );
	}

	/**
	 * @param $product
	 *
	 * @return mixed
	 */
	public static function get_product_unit_price ( $product ) {

		$product_total = $product["price"];

		foreach ( $product["options"] as $option ) {
			$options[] = $option["option_label"];
			$product_total += $option["price"];
		}

		return $product_total;
	}

	/**
	 * Has visible products
	 *
	 * @since
	 *
	 * @uses RGFormsModel::is_field_hidden()
	 *
	 * @param $form
	 *
	 * @return bool
	 */
	private function has_visible_products ( $form ) {
		foreach ( $form["fields"] as $field ) {
			if ( $field["type"] == "product" && ! RGFormsModel::is_field_hidden( $form, $field, "" ) )
				return true;
		}

		return false;
	}

	/**
	 * @param $validation_result
	 * @param $post
	 * @param $error_message
	 *
	 * @return mixed
	 */
	public static function set_validation_result ( $validation_result, $post, $error_message ) {

		$credit_card_page = 0;
		foreach ( $validation_result['form']['fields'] as &$field ) {
			if ( 'creditcard' == $field["type"] ) {
				$field['failed_validation']  = true;
				$field['validation_message'] = $error_message;
				$credit_card_page            = $field['pageNumber'];
				break;
			}

		}
		$validation_result['is_valid'] = false;

		GFFormDisplay::set_current_page( $validation_result['form']['id'], $credit_card_page );

		$validation_result = apply_filters( 'gfp_stripe_set_validation_result', $validation_result, $post, $error_message );

		return $validation_result;
	}

	/**
	 * @param $e
	 *
	 * @return mixed|void
	 */
	public static function gfp_stripe_create_error_message ( $e ) {
		$error_class   = get_class( $e );
		$error_message = $e->getMessage();
		$response      = $error_class . ': ' . $error_message;
		self::$_this->log_error( print_r( $response, true ) );

		$settings = get_option( 'gfp_stripe_settings' );
		$mode     = rgar( $settings, 'mode' );
		if ( 'live' == $mode ) {
			switch ( $error_class ) {
				case 'Stripe_InvalidRequestError':
					$error_message = 'This form cannot process your payment. Please contact site owner.';
					break;
				case 'Stripe_ApiConnectionError':
					$error_message = 'There was a temporary network communication error and while we try to make sure these never happen, sometimes they do. Please try your payment again in a few minutes and if this continues, please contact site owner.';
					break;
				case 'Stripe_CardError':
					//"For card errors, these messages can be shown to your users."
					break;
			}
		}

		return apply_filters( 'gfp_stripe_error_message', $error_message, $e );
	}

//------------------------------------------------------
//------------- ENTRY ---------------------------
//------------------------------------------------------

	/**
	 * @param $value
	 * @param $lead
	 * @param $field
	 * @param $form
	 *
	 * @return mixed|string
	 */
	public function gform_save_field_value ( $value, $lead, $field, $form ) {
		$input_type = RGFormsModel::get_input_type( $field );

		if ( 'creditcard' == $input_type ) {

			if ( ! array_key_exists( strval( $field['inputs'][0]['id'] ), $lead ) ) {
				$input_name             = "input_" . str_replace( '.', '_', $field['inputs'][0]['id'] );
				$original_value_changed = $original_value = rgpost( $input_name );
				$original_value_changed = str_replace( ' ', '', $original_value_changed );
				$card_number_length     = strlen( $original_value_changed );
				$original_value_changed = substr( $original_value_changed, - 4, 4 );
				$original_value_changed = str_pad( $original_value_changed, $card_number_length, "X", STR_PAD_LEFT );
				if ( $original_value_changed == $value ) {
					$value = $original_value;
				}
			}
		}

		return $value;
	}

	/**
	 * Save payment information to DB
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  rgar()
	 * @uses  GFCommon::get_currency()
	 * @uses  rgpost()
	 * @uses  RGFormsModel::get_lead_details_table_name()
	 * @uses  wpdb->prepare()
	 * @uses  wpdb->get_results()
	 * @uses  RGFormsModel::get_lead_detail_id()
	 * @uses  wpdb->update()
	 * @uses  wpdb->insert()
	 * @uses  RGFormsModel::update_lead()
	 * @uses  apply_filters()
	 * @uses  GFP_Stripe::get_feed()
	 * @uses  gform_update_meta()
	 * @uses  GFP_Stripe_Data::insert_transaction()
	 *
	 * @param $entry
	 * @param $form
	 *
	 * @return void
	 */
	public function gform_entry_created ( $entry, $form ) {
		global $wpdb;

		$entry_id = rgar( $entry, 'id' );

		if ( ! empty( self::$transaction_response ) ) {
			//Current Currency
			$currency          = GFCommon::get_currency();
			$transaction_id    = self::$transaction_response['transaction_id'];
			$transaction_type  = self::$transaction_response['transaction_type'];
			$amount            = array_key_exists( 'amount', self::$transaction_response ) ? self::$transaction_response['amount'] : null;
			$payment_date      = gmdate( 'Y-m-d H:i:s' );
			$entry['currency'] = $currency;
			if ( '1' == $transaction_type ) {
				$entry['payment_status'] = 'Approved';
			}
			else {
				$entry['payment_status'] = 'Active';
			}
			$entry['payment_amount']   = $amount;
			$entry['payment_date']     = $payment_date;
			$entry['transaction_id']   = $transaction_id;
			$entry['transaction_type'] = $transaction_type;
			$entry['is_fulfilled']     = true;

			//save card type since it gets stripped
			$form_id = $entry['form_id'];
			foreach ( $form['fields'] as $field ) {
				if ( 'creditcard' == $field['type'] ) {
					$creditcard_field_id = $field['id'];
				}
			}
			$card_type_name    = "input_" . $creditcard_field_id . "_4";
			$card_type_id      = $creditcard_field_id . ".4";
			$card_type_value   = rgpost( $card_type_name );
			$card_type_value   = substr( $card_type_value, 0, GFORMS_MAX_FIELD_LENGTH );
			$lead_detail_table = RGFormsModel::get_lead_details_table_name();
			$current_fields    = $wpdb->get_results( $wpdb->prepare( "SELECT id, field_number FROM $lead_detail_table WHERE lead_id=%d", $entry_id ) );
			$lead_detail_id    = RGFormsModel::get_lead_detail_id( $current_fields, $card_type_id );
			if ( $lead_detail_id > 0 ) {
				$wpdb->update( $lead_detail_table, array( 'value' => $card_type_value ), array( 'id' => $lead_detail_id ), array( "%s" ), array( "%d" ) );
			}
			else {
				$wpdb->insert( $lead_detail_table, array( 'lead_id' => $entry_id, 'form_id' => $form['id'], 'field_number' => $card_type_id, 'value' => $card_type_value ), array( "%d", "%d", "%f", "%s" ) );
			}

			$entry = apply_filters( 'gfp_stripe_entry_created_update_lead', $entry, self::$transaction_response );
			RGFormsModel::update_lead( $entry );

			//saving feed id
			$feed = self::$_this->get_feed( $form );
			gform_update_meta( $entry_id, 'Stripe_feed_id', $feed['id'] );
			//updating form meta with current payment gateway
			gform_update_meta( $entry_id, 'payment_gateway', 'stripe' );

			$subscriber_id = apply_filters( 'gfp_stripe_entry_created_subscriber_id', '', self::$transaction_response, $entry );

			GFP_Stripe_Data::insert_transaction( $entry['id'], apply_filters( 'gfp_stripe_entry_created_insert_transaction_type', 'payment', $transaction_type ), $subscriber_id, $transaction_id, $amount );
		}

	}

	//------------------------------------------------------
	//------------- HELPERS --------------------------
	//------------------------------------------------------

	/**
	 * Validate Stripe API keys
	 *
	 * @since 0.1.0
	 *
	 * @uses  GFP_Stripe::include_api()
	 * @uses  get_option()
	 * @uses  Stripe::setApiKey()
	 * @uses  Stripe_Token::create()
	 *
	 * @return array
	 */
	private function is_valid_key () {
		self::$_this->include_api();
		$settings = get_option( 'gfp_stripe_settings' );

		$year = date( 'Y' ) + 1;

		$valid_keys = array(
			'test_secret_key'      => false,
			'test_publishable_key' => false,
			'live_secret_key'      => false,
			'live_publishable_key' => false
		);
		$valid      = false;
		$flag_false = 0;
		foreach ( $valid_keys as $key => $value ) {
			if ( ! empty( $settings[$key] ) ) {
				try {
					Stripe::setApiKey( $settings[$key] );
					Stripe_Token::create( array(
																		 'card' => array(
																			 'number'    => '4242424242424242',
																			 'exp_month' => 3,
																			 'exp_year'  => $year,
																			 'cvc'       => 314
																		 ),
																) );
					$valid_keys[$key] = true;
				} catch ( Exception $e ) {
					$class = get_class( $e );
					if ( 'Stripe_CardError' == $class ) {
						$valid_keys[$key] = true;
					}
					else {
						$flag_false ++;
					}
					$errors[$key] = $class;
				}
			}
			else {
				$flag_false ++;
			}
		}

		if ( 0 == $flag_false ) {
			$valid = true;

			return array( $valid, $valid_keys );
		}
		else {
			return array( $valid, $valid_keys, isset( $errors ) ? $errors : null );
		}

	}

	/**
	 * Return the desired API key from the database
	 *
	 * @since
	 *
	 * @uses get_option()
	 * @uses rgar()
	 * @uses esc_attr()
	 *
	 * @param $type
	 *
	 * @return string
	 */
	public static function get_api_key ( $type ) {
		$settings = get_option( 'gfp_stripe_settings' );
		$mode     = rgar( $settings, 'mode' );
		$key      = $mode . '_' . $type . '_key';

		return trim( esc_attr( rgar( $settings, $key ) ) );

	}


	/**
	 * Include the Stripe library
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function include_api () {
		if ( ! class_exists( 'Stripe' ) )
			require_once( GFP_STRIPE_PATH . '/includes/api/lib/Stripe.php' );
	}

	/**
	 * Return the url of the plugin's root folder
	 *
	 * @since
	 *
	 * @uses plugins_url()
	 *
	 * @return string
	 */
	public static function get_base_url () {
		return plugins_url( null, GFP_STRIPE_FILE );
	}

	/**
	 * Return the physical path of the plugin's root folder
	 *
	 * @since
	 *
	 * @return string
	 */
	private static function get_base_path () {
		$folder = basename( dirname( GFP_STRIPE_FILE ) );

		return WP_PLUGIN_DIR . '/' . $folder;
	}

	/**
	 *
	 * @return bool
	 */
	public static function is_stripe_page () {
		$current_page = trim( strtolower( RGForms::get( 'page' ) ) );

		return in_array( $current_page, array( 'gfp_stripe' ) );
	}

	/**
	 * Set transaction response
	 *
	 * @since
	 *
	 * @param $response
	 *
	 * @return void
	 */
	public static function set_transaction_response ( $response ) {
		if ( ( 2 == $response['transaction_type'] ) || ( 4 == $response['transaction_type'] ) || ( 5 == $response['transaction_type'] ) || ( 6 == $response['transaction_type'] ) ) {
			self::$transaction_response = $response;
		}
	}

	/**
	 * @return string
	 */
	public static function get_transaction_response () {
		return self::$transaction_response;
	}

	//------------------------------------------------------
	//------------- LOGGING --------------------------
	//------------------------------------------------------

	/**
	 * Add this plugin to Gravity Forms Logging Add-On
	 *
	 * @since
	 *
	 * @param $plugins
	 *
	 * @return mixed
	 */
	function gform_logging_supported ( $plugins ) {
		$plugins[self::$slug] = 'Gravity Forms + Stripe';

		return $plugins;
	}

	/**
	 * Log an error message
	 *
	 * @since
	 *
	 * @uses GFLogging::include_logger()
	 * @uses GFLogging::log_message
	 *
	 * @param $message
	 *
	 * @return void
	 */
	public static function log_error ( $message ) {
		if ( class_exists( 'GFLogging' ) ) {
			GFLogging::include_logger();
			GFLogging::log_message( self::$slug, $message, KLogger::ERROR );
		}
	}

	/**
	 * Log a debug message
	 *
	 * @since
	 *
	 * @uses GFLogging::include_logger()
	 * @uses GFLogging::log_message
	 *
	 * @param $message
	 *
	 * @return void
	 */
	public static function log_debug ( $message ) {
		if ( class_exists( 'GFLogging' ) ) {
			GFLogging::include_logger();
			GFLogging::log_message( self::$slug, $message, KLogger::DEBUG );
		}
	}
}