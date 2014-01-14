<?php
/*
		 * @wordpress-plugin
		 * Plugin Name: Gravity Forms + Stripe
		 * Plugin URI: https://gravityplus.pro/gravity-forms-stripe
		 * Description: Use Stripe to process credit card payments on your site, easily and securely, with Gravity Forms
		 * Version: 1.8.1
		 * Author: gravity+
		 * Author URI: https://gravityplus.pro
		 * Text Domain: gfp-stripe
		 * Domain Path: /languages
		 * License:     GPL-2.0+
		 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
	 *
	 * This program is free software; you can redistribute it and/or modify
		 it under the terms of the GNU General Public License as published by
		 the Free Software Foundation; either version 2 of the License, or
		 (at your option) any later version.

		 This program is distributed in the hope that it will be useful,
		 but WITHOUT ANY WARRANTY; without even the implied warranty of
		 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
		 GNU General Public License for more details.

		 You should have received a copy of the GNU General Public License
		 along with this program; if not, write to the Free Software
		 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
		 *
		 * @package   GFP_Stripe
		 * @version   1.8.1
		 * @author    gravity+ <support@gravityplus.pro>
		 * @license   GPL-2.0+
		 * @link      https://gravityplus.pro
		 * @copyright 2013 gravity+
		 *
		 * last updated: January 14, 2013
		 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

//Find proper file path, due to version control & symlinks
$gfp_stripe_file = __FILE__;

if ( isset( $plugin ) ) {
	$gfp_stripe_file = $plugin;
}
else if ( isset( $mu_plugin ) ) {
	$gfp_stripe_file = $mu_plugin;
}
else if ( isset( $network_plugin ) ) {
	$gfp_stripe_file = $network_plugin;
}

//Setup Constants

/**
 *
 */
define( 'GFP_STRIPE_FILE', $gfp_stripe_file );

/**
 *
 */
define( 'GFP_STRIPE_PATH', WP_PLUGIN_DIR . '/' . basename( dirname( $gfp_stripe_file ) ) );

//Let's get it started! Load all of the necessary class files for the plugin
require_once( 'includes/class-gfp-stripe-loader.php' );
$gfp_stripe_loader = new GFP_Stripe_Loader( null, GFP_STRIPE_PATH . DIRECTORY_SEPARATOR . 'includes' );
$gfp_stripe_loader->register();

require_once( 'includes/gf-utility-functions.php' );

new GFP_Stripe();