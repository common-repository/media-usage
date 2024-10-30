<?php
/*
Plugin Name: Media Usage
Plugin URI: http://meowapps.com/media-usage
Description: This plugin tracks the real usage of your media and display this statistics in a simple way in the Media Library.
Version: 0.0.4
Author: Jordy Meow
Author URI: http://meowapps.com
Text Domain: media-usage
Domain Path: /languages

Dual licensed under the MIT and GPL licenses:
http://www.opensource.org/licenses/mit-license.php
http://www.gnu.org/licenses/gpl.html
*/

include "mmu_core.php";
global $Meow_MediaUsage_Core;
$Meow_MediaUsage_Core = new Meow_MediaUsage_Core;

if ( is_admin() ) {
  register_activation_hook( __FILE__, 'Meow_MediaUsage_Admin::create_db' );
  register_uninstall_hook( __FILE__, 'Meow_MediaUsage_Admin::drop_db' );

  include "mmu_admin.php";
  new Meow_MediaUsage_Admin;

  include "mmu_library.php";
  new Meow_MediaUsage_Library;
}
