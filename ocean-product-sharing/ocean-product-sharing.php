<?php
/**
 * Plugin Name:         Ocean Product Sharing
 * Plugin URI:          https://oceanwp.org/extension/ocean-product-sharing/
 * Description:         A simple plugin to add social sharing buttons to your single product page. Compatible with WooCommerce and Easy Digital Downloads.
 * Version:             2.2.1
 * Author:              OceanWP
 * Author URI:          https://oceanwp.org/
 * Requires at least:   5.6
 * Tested up to:        6.8
 * WC requires at least:3.0
 * WC tested up to:     9.8.5
 *
 * Text Domain: ocean-product-sharing
 * Domain Path: /languages
 *
 * @package  Ocean_Product_Sharing
 * @category Core
 * @author   OceanWP
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the main instance of Ocean_Product_Sharing to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Ocean_Product_Sharing
 */
function Ocean_Product_Sharing()
{
	return Ocean_Product_Sharing::instance();
} // End Ocean_Product_Sharing()

Ocean_Product_Sharing();

/**
 * Main Ocean_Product_Sharing Class
 *
 * @class   Ocean_Product_Sharing
 * @version 1.0.0
 * @since   1.0.0
 * @package Ocean_Product_Sharing
 */
final class Ocean_Product_Sharing
{
	/**
	 * Ocean_Product_Sharing The single instance of Ocean_Product_Sharing.
	 *
	 * @var    object
	 * @access private
	 * @since  1.0.0
	 */
	private static $_instance = null;

	/**
	 * The token.
	 *
	 * @var    string
	 * @access public
	 * @since  1.0.0
	 */
	public $token;

	/**
	 * The version number.
	 *
	 * @var    string
	 * @access public
	 * @since  1.0.0
	 */
	public $version;

	/**
	 * The plugin url.
	 *
	 * @var     string
	 * @access  public
	 */
	public $plugin_url;

	/**
	 * The plugin path.
	 *
	 * @var     string
	 * @access  public
	 */
	public $plugin_path;

	/**
	 * The plugin data.
	 *
	 * @var     array
	 * @access  public
	 */
	public $plugin_data;

	// Admin - Start
	/**
	 * The admin object.
	 *
	 * @var    object
	 * @access public
	 * @since  1.0.0
	 */
	public $admin;

	/**
	 * Constructor function.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function __construct( $widget_areas = array() )
	{
		$this->token       = 'ocean-product-sharing';
		$this->plugin_url  = plugin_dir_url(__FILE__);
		$this->plugin_path = plugin_dir_path(__FILE__);
		$this->plugin_data = get_file_data( __FILE__, array( 'Version' => 'Version' ), false );
		$this->version     = $this->plugin_data['Version'];

		register_activation_hook(__FILE__, array( $this, 'install' ));

		add_action('init', array( $this, 'ops_load_plugin_textdomain' ));

		add_action('init', array( $this, 'ops_setup' ));

		// HPOS compatibility.
		add_action( 'before_woocommerce_init', array( $this, 'ops_hpos_compatibility' ) );
	}

	/**
	 * Main Ocean_Product_Sharing Instance
	 *
	 * Ensures only one instance of Ocean_Product_Sharing is loaded or can be loaded.
	 *
	 * @since  1.0.0
	 * @static
	 * @see    Ocean_Product_Sharing()
	 * @return Main Ocean_Product_Sharing instance
	 */
	public static function instance()
	{
		if (is_null(self::$_instance) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Load the localisation file.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function ops_load_plugin_textdomain()
	{
		load_plugin_textdomain('ocean-product-sharing', false, dirname(plugin_basename(__FILE__)) . '/languages');
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone()
	{
		_doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), '1.0.0');
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup()
	{
		_doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), '1.0.0');
	}

	/**
	 * Installation.
	 * Runs on activation. Logs the version number and assigns a notice message to a WordPress option.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function install()
	{
		$this->_log_version_number();
	}

	/**
	 * Log the plugin version number.
	 *
	 * @access private
	 * @since  1.0.0
	 * @return void
	 */
	private function _log_version_number()
	{
		// Log the version number.
		update_option($this->token . '-version', $this->version);
	}

	/**
	 * Setup all the things.
	 * Only executes if OceanWP or a child theme using OceanWP as a parent is active and the extension specific filter returns true.
	 *
	 * @return void
	 */
	public function ops_setup()
	{
		$theme = wp_get_theme();

		if ('OceanWP' == $theme->name || 'oceanwp' == $theme->template ) {
			include_once $this->plugin_path . '/includes/helpers.php';
			add_filter( 'ocean_customize_options_data', array( $this, 'register_customize_options') );
			//add_action('customize_preview_init', array( $this, 'ops_customize_preview_js' ));
			add_action('wp_enqueue_scripts', array( $this, 'ops_get_style' ), 999);
			add_action('woocommerce_after_single_product_summary', array( $this, 'ops_product_share' ));
			add_action('ocean_after_single_download_item', array( $this, 'ops_product_share' ));
			add_filter('ocean_head_css', array( $this, 'ops_head_css' ));
			add_filter( 'oe_theme_panels', array( $this, 'oe_theme_panels' ) );

			$theme_version = $theme->version;

			$current_theme_version = $theme_version;

			if ( get_template_directory() == get_stylesheet_directory() ) {
				$current_theme_version  = $theme_version;
			} else {
				$parent = wp_get_theme()->parent();
				if ( ! empty( $parent) ) {
					$current_theme_version = $parent->Version;
				}
			}

			if ( version_compare( $current_theme_version, '3.6.1', '<=' ) ) {

				$is_ocean_extra_active = class_exists( 'Ocean_Extra' );
				$is_ocean_extra_version_valid = defined( 'OE_VERSION' ) && version_compare( OE_VERSION, '2.3.1', '<=' );

				if ( ! $is_ocean_extra_active || $is_ocean_extra_version_valid ) {
					include_once $this->plugin_path . '/includes/update-message.php';
				}
			}
		}
	}

	/**
	 * WooCommerce HPOS compatibility.
	 *
	 * @since 2.0.8
	 */
	public function ops_hpos_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	/**
	 * Added localize in customizer js
	 */
	public function register_customize_options($options) {

		if ( OCEAN_EXTRA_ACTIVE
			&& class_exists( 'Ocean_Extra_Theme_Panel' ) ) {

			if ( empty( Ocean_Extra_Theme_Panel::get_setting( 'ocean_product_sharing_panel' ) ) ) {
				return $options;
			}

		}

		include_once $this->plugin_path . '/includes/options.php';

		$options['ocean_product_sharing_settings'] = ops_customizer_options();

		return $options;
	}

	/**
	 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
	 */
	public function ops_customize_preview_js()
	{
		wp_enqueue_script('ops-customizer', plugins_url('/assets/js/customizer.min.js', __FILE__), array( 'customize-preview' ), '1.0', true);
	}

	/**
	 * Enqueue style.
	 *
	 * @since 1.0.0
	 */
	public function ops_get_style()
	{

		// Load main stylesheet
		wp_enqueue_style('ops-product-share-style', plugins_url('/assets/css/style.min.css', __FILE__));

		// Load main script
		wp_enqueue_script('ops-product-share-script', plugins_url('/assets/js/social.js', __FILE__), array( 'jquery' ), $this->version, true);

		// If rtl
		if (is_RTL() ) {
			wp_enqueue_style('ops-product-share-rtl', plugins_url('/assets/css/rtl.css', __FILE__));
		}

	}

	/**
	 * Product sharing links
	 */
	public function ops_product_share()
	{

		$file       = $this->plugin_path . 'template/product-share.php';
		$theme_file = get_stylesheet_directory() . '/templates/extra/product-share.php';

		if (file_exists($theme_file) ) {
			$file = $theme_file;
		}

		if (file_exists($file) ) {
			include $file;
		}

	}

	/**
	 * Add css in head tag.
	 */
	public function ops_head_css( $output )
	{

		// Global vars
		$product_sharing_borders     = get_theme_mod('ops_product_sharing_borders_color', '#e9e9e9');
		$product_sharing_icons_bg    = get_theme_mod('ops_product_sharing_icons_bg', '#333333');
		$product_sharing_icons_color = get_theme_mod('ops_product_sharing_icons_color', '#ffffff');

		// Define css var
		$css = '';

		// Add borders color
		if (! empty($product_sharing_borders) && '#e9e9e9' != $product_sharing_borders ) {
			$css .= '.oew-product-share,.oew-product-share ul li{border-color:' . $product_sharing_borders . ';}';
		}

		// Add icon background
		if (! empty($product_sharing_icons_bg) && '#333333' != $product_sharing_icons_bg ) {
			$css .= '.oew-product-share ul li a .ops-icon-wrap{background-color:' . $product_sharing_icons_bg . ';}';
		}

		// Add icon color
		if (! empty($product_sharing_icons_color) && '#ffffff' != $product_sharing_icons_color ) {
			$css .= '.oew-product-share ul li a .ops-icon-wrap .ops-icon{fill:' . $product_sharing_icons_color . ';}';
		}

		// Return CSS
		if (! empty($css) ) {
			$output .= $css;
		}

		// Return output css
		return $output;

	}

	/**
	 * Add product sharing switcher.
	 *
	 * @since  1.0.0
	 */
	public function oe_theme_panels( $panels ) {

		$panels['ocean_product_sharing_panel'] = [
			'label' => esc_html__('Product Sharing', 'ocean-product-sharing'),
		];

		// Return panels list
		return $panels;
	}

	} // End Class

	// --------------------------------------------------------------------------------
	// region Freemius
	// --------------------------------------------------------------------------------

	if (! function_exists('ocean_product_sharing_fs') ) {
	// Create a helper function for easy SDK access.
	function ocean_product_sharing_fs()
	{
		global $ocean_product_sharing_fs;

		if (! isset($ocean_product_sharing_fs) ) {
			$ocean_product_sharing_fs = OceanWP_EDD_Addon_Migration::instance('ocean_product_sharing_fs')->init_sdk(
				array(
				'id'              => '3809',
				'slug'            => 'ocean-product-sharing',
				'public_key'      => 'pk_e8cc3b7980be98f86dc7286572cc0',
				'is_premium'      => false,
				'is_premium_only' => false,
				'has_paid_plans'  => false,
				)
			);
		}

		return $ocean_product_sharing_fs;
	}

	function ocean_product_sharing_fs_addon_init()
	{
		if (class_exists('Ocean_Extra') ) {
			OceanWP_EDD_Addon_Migration::instance('ocean_product_sharing_fs')->init();
		}
	}

	if (0 == did_action('owp_fs_loaded') ) {
		// Init add-on only after parent theme was loaded.
		add_action('owp_fs_loaded', 'ocean_product_sharing_fs_addon_init', 15);
	} else {
		if (class_exists('Ocean_Extra') ) {
			/**
			 * This makes sure that if the theme was already loaded
			 * before the plugin, it will run Freemius right away.
			 *
			 * This is crucial for the plugin's activation hook.
			 */
			ocean_product_sharing_fs_addon_init();
		}
	}
}

// endregion
