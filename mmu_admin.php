<?php

include "common/meow_admin.php";

class Meow_MediaUsage_Admin extends Meow_Admin {

	public function __construct() {
		parent::__construct();
		add_action( 'admin_menu', array( $this, 'app_menu' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'delete_post', array( $this, 'delete_post' ), 10 );
		//TODO: ON DELETE A MEDIA, CLEAN THE MMU DATABASE
	}

	function admin_notices() {
		global $wp_rewrite;
		$this->generate_rewrite_rules( $wp_rewrite, true );
		global $wpdb;
		$tbl_ref = $wpdb->prefix . "usage_ref";
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$tbl_ref'" ) != $tbl_ref ) {
			Meow_MediaUsage_Admin::create_db();
			echo '<div class="error"><p>The Usage DB was created.</p></div>';
		}
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'reset' ) {
			Meow_MediaUsage_Admin::reset_db();
			echo '<div class="error"><p>The Usage DB was reset.</p></div>';
		}
	}

	function delete_post( $id ) {
		//TODO: DELETE!
	}

	function common_url( $file ) {
		return trailingslashit( plugin_dir_url( __FILE__ ) ) . 'common/' . $file;
	}

	function app_menu() {

		// SUBMENU > Settings
		add_submenu_page( 'meowapps-main-menu', 'Media Usage', 'Media Usage', 'manage_options',
			'mmu_settings-menu', array( $this, 'admin_settings' ) );

			// SUBMENU > Settings > Settings
			add_settings_section( 'mmu_settings', null, null, 'mmu_settings-menu' );
			add_settings_field( 'mmu_logging', "Logging",
				array( $this, 'admin_logging_callback' ),
				'mmu_settings-menu', 'mmu_settings' );
			add_settings_field( 'mmu_logged_users', "Logged Users",
				array( $this, 'admin_logged_users_callback' ),
				'mmu_settings-menu', 'mmu_settings' );
			add_settings_field( 'mmu_include_admin', "WP Admin",
				array( $this, 'admin_include_admin_callback' ),
				'mmu_settings-menu', 'mmu_settings' );
			add_settings_field( 'mmu_x_sendfile', "X-Sendfile",
				array( $this, 'admin_x_sendfile_callback' ),
				'mmu_settings-menu', 'mmu_settings' );

		// SETTINGS
		register_setting( 'mmu_settings', 'mmu_x_sendfile' );
		register_setting( 'mmu_settings', 'mmu_logging' );
		register_setting( 'mmu_settings', 'mmu_logged_users' );
		register_setting( 'mmu_settings', 'mmu_include_admin' );
	}

	function admin_analyze_media( $media_id ) {
		global $Meow_MediaUsage_Core;
		$stats = $Meow_MediaUsage_Core->get_usage_stats_for_media( $media_id );
		?>
		<div class="wrap">
			<?php echo $this->display_title( "Media Usage" );  ?>
			<p>This page give you information about how your Media #<?php echo $media_id; ?> is used. For now, this is beta, I am waiting for your feedback to make this section awesome. For your information, the available data for each image is <b>who</b> accessed it (IP) and through <b>which page</b> it was accessed. For now, here is a simple data dump based on usage statistics by image size</p>
			<div class="meow-section meow-group">

				<div class="meow-box meow-col meow-span_2_of_2">
					<h3>Simple statistics by media size</h3>
					<div class="inside">
						<pre>
<?php print_r( $stats ); ?>
						</pre>
					</div>
				</div>

			</div>
		</div>
		<?php
	}

	function admin_settings() {
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'analyze_media' && isset( $_GET['id'] ) )
			return $this->admin_analyze_media( $_GET['id'] );
		$urltestfile = plugins_url( 'meowapps.png', __FILE__ );
		?>
		<div class="wrap">
			<?php echo $this->display_title( "Media Usage" );  ?>
			<p>This plugin tracks the actual usage of your media files (within your /wp-content/ directory).</p>
			<div class="meow-section meow-group">
				<div class="meow-box meow-col meow-span_2_of_2">
					<h3>How to use</h3>
					<div class="inside">
						<div style="float: right; padding: 5px; width: 40px; height: 40px; background: gray; margin-bottom: 10px; margin-left: 10px;">
							<img width="40" height="40" src="/index.php?meow_media_handler=<?php echo $urltestfile; ?>"></img>
						</div>
						<?php echo _e( "When <b>Logging</b> is enabled, the plugin modifies your .htaccess and use its own handler to deliver the images. This way, it can track the exact usage of your media files. <span style='color: #2b6eda;'>Your website will be much slower. <b>This is normal</b> and this is the only way to perform logging.</span> <b>Make sure the logo is displayed on the right of this text.</b> If it is only an empty gray square, there is an issue and so don't activate the logging. Disable CDN for your images if you use one. Keep this plugin running as much as you can, this data is valuable for you and you will be able to find out which media aren't in use at all, and how others are used and where.", 'media-usage' ); ?>
						<?php
						if ( strpos( $_SERVER["SERVER_SOFTWARE"], 'nginx' ) !== false )
							echo _e( "<b >It seems you are using Nginx as your web server. The rewrite rule needs to be set manually.</b>", 'media-usage' )
						?>
					</div>

				</div>
			</div>
			<div class="meow-section meow-group">

					<div class="meow-box meow-col meow-span_1_of_2">
						<h3>System</h3>
						<div class="inside">
							<form method="post" action="options.php">
							<?php settings_fields( 'mmu_settings' ); ?>
					    <?php do_settings_sections( 'mmu_settings-menu' ); ?>
					    <?php submit_button(); ?>
							</form>
						</div>
					</div>

					<div class="meow-box meow-col meow-span_1_of_2">
						<h3>Debug URL</h3>
						<div class="inside">

							<?php
								if ( isset( $_POST[ 'debug_url' ] ) ) {
									global $Meow_MediaUsage_Core;
									$Meow_MediaUsage_Core->handle_request( true, $_POST[ 'debug_url' ] );
								}
							?>

							<p>This section is for debug only. You can type here the URL of an image that is local and a debug will be performed.</p>
							<form method="POST" action="admin.php?page=mmu_settings-menu&action=analyze">
							<input style="width: 100%;" id="debug_url" name="debug_url" value="<?php echo isset( $_POST[ 'debug_url' ] ) ? $_POST[ 'debug_url' ] : $urltestfile; ?>"></input>
							<?php submit_button( "Debug URL" ); ?>
							</form>
						</div>
					</div>

					<div class="meow-box meow-col meow-span_1_of_2">
						<h3>Advanced</h3>
						<div class="inside">
							<a class="button button-primary meow-button" href="?page=mmu_settings-menu&amp;action=reset">Reset DB</a>
						</div>
					</div>

			</div>
		</div>
		<?php
	}

	function generate_rewrite_rules( $wp_rewrite, $flush = false ) {
		global $wp_rewrite;
		if ( get_option( 'mmu_logging', false ) ) {
			//$handlerurl = str_replace( trailingslashit( site_url()), '', plugins_url( 'mmu_handler.php', __FILE__ ) );
			//add_rewrite_rule( '(.+.(?:jpe?g|gif|png))', $handlerurl, 'top' );
			add_rewrite_rule( 'wp-content(.*)(.+.(?:jpe?g|gif|png))', 'index\.php?meow_media_handler=$0', 'top' );
			//RewriteRule ^(.*)uploads(.*)(.+.(?:jpe?g|gif|png)) index.php?meow_media_handler=$0 [QSA,L]
		}
		if ( $flush == true ) {
			$wp_rewrite->flush_rules();
		}
	}

	/*
		OPTIONS CALLBACKS
	*/

	function admin_logging_callback( $args ) {
		$html = '<input type="checkbox" id="mmu_logging" name="mmu_logging" value="1" ' .
			checked( 1, get_option( 'mmu_logging' ), false ) . '/>';
		$html .= '<label for="mmu_logging">Enabled</label><br>';
		$html .= '<small>The .htaccess will be modified to enable the handler and all the requests to the Media will be logged in realtime.</small>';
		echo $html;
	}

	function admin_logged_users_callback( $args ) {
		$html = '<input type="checkbox" id="mmu_logged_users" name="mmu_logged_users" value="1" ' .
			checked( 1, get_option( 'mmu_logged_users' ), false ) . '/>';
		$html .= '<label for="mmu_logged_users">Enabled</label><br>';
		$html .= '<small>By default, the logged users (such as administrators, authors, subscribers) are ignored by Media Usage. Enable this and a lot more activity will be logged, including all the media used in this admin.</small>';
		echo $html;
	}

	function admin_include_admin_callback( $args ) {
		$html = '<input type="checkbox" id="mmu_include_admin" name="mmu_include_admin" value="1" ' .
			checked( 1, get_option( 'mmu_include_admin' ), false ) . '/>';
		$html .= '<label for="mmu_include_admin">Enabled</label><br>';
		$html .= '<small>By default, access to the images from within the WP Admin is disabled. Switch this one and those access will be counted as well.</small>';
		echo $html;
	}

	function admin_x_sendfile_callback( $args ) {
		$html = '<input type="checkbox" id="mmu_x_sendfile" name="mmu_x_sendfile" value="1" ' .
			checked( 1, get_option( 'mmu_x_sendfile' ), false ) . '/>';
		$html .= '<label for="mmu_x_sendfile">Enabled</label><br>';
		$html .= '<small>If this option works for you, then the images will be delivered faster. Enable it and check if the logo displays in "How to use".</small>';
		echo $html;
	}

		/*
			INSTALL & UNINSTALL
		*/

		static function reset_db() {
			global $wpdb;
	    $tbl_ref = $wpdb->prefix . "usage_ref";
			$tbl_access = $wpdb->prefix . "usage_access";

			$sql = "DELETE FROM $tbl_ref";
			$wpdb->query( $sql );
			$sql = "DELETE FROM $tbl_access";
			$wpdb->query( $sql );
		}

	  static function create_db() {
	    global $wpdb;
	    $tbl_ref = $wpdb->prefix . "usage_ref";
			$tbl_access = $wpdb->prefix . "usage_access";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$charset_collate = $wpdb->get_charset_collate();

	    $sql = "CREATE TABLE $tbl_ref (
				path VARCHAR(128) NOT NULL,
	      media BIGINT(20) NULL,
				PRIMARY KEY  (path),
				KEY media_index (media)
	    ) " . $charset_collate . ";";
	    $result = dbDelta( $sql );

			$sql = "CREATE TABLE $tbl_access (
				ip VARBINARY(16) NULL,
				path VARCHAR(128) NOT NULL,
				url VARCHAR(128) NOT NULL,
	      views INT(9) NULL,
	      created_on DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
				updated_on DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY  (ip, path, url)
	    ) " . $charset_collate . ";";
	    $result = dbDelta( $sql );
	  }

	  static function drop_db() {
	    global $wpdb;
			$tbl_ref = $wpdb->prefix . "usage_ref";
			$tbl_access = $wpdb->prefix . "usage_access";
	  	$wpdb->query( "DROP TABLE IF EXISTS $tbl_ref" );
			$wpdb->query( "DROP TABLE IF EXISTS $tbl_access" );
	  }

}

?>
