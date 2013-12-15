<?php
/*
Plugin Name: Better WordPress External Links
Plugin URI: http://betterwp.net/wordpress-plugins/bwp-external-links/
Description: Gives you total control over external links on your website. This plugin also comes with a comprehensive domain filtering feature. BWP External Links is based on the popular Prime Links phpBB mod by Ken F. Innes IV (Prime Halo).
Version: 1.1.2
Text Domain: bwp-ext
Domain Path: /languages/
Author: Khang Minh
Author URI: http://betterwp.net
License: GPLv3
*/

// Front end
require_once(dirname(__FILE__) . '/includes/class-bwp-external-links.php');
$bwp_ext = new BWP_EXTERNAL_LINKS();

// Back end
add_action('admin_menu', 'bwp_ext_init_admin', 1);

function bwp_ext_init_admin()
{
	global $bwp_ext;

	$bwp_ext->init_admin();
}