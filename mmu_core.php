<?php

class Meow_MediaUsage_Core {

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 10 );
	}

	function plugins_loaded() {
		if ( isset( $_GET[ 'meow_media_handler' ] ) ) {
			$this->handle_request();
		}
	}

	function handle_request( $admin_debug = false, $url = null ) {

		// WP System
		$document_root = $_SERVER[ 'DOCUMENT_ROOT' ];

		// Main variables
		if ( !empty( $url ) )
			$requested_uri = parse_url( $url, PHP_URL_PATH );
		else if ( isset( $_GET['meow_media_handler'] ) )
			$requested_uri = parse_url( urldecode( $_GET['meow_media_handler'] ), PHP_URL_PATH );
		else
			$requested_uri = parse_url( urldecode( $_SERVER[ 'REQUEST_URI' ] ), PHP_URL_PATH );

		if ( $admin_debug ) {
			echo "document_root = $document_root<br />";
			echo "requested_uri = $requested_uri<br />";
		}

		$source_file = rtrim( $document_root, '/' ) . '/' . ltrim( $requested_uri, '/' );
		$source_ext = pathinfo( $source_file, PATHINFO_EXTENSION );

		// Check if the image to send exists
		// echo $source_file;
		// exit;
	  if ( !file_exists( $source_file ) ) {

			if ( $admin_debug ) {
				echo "Cannot found source_file: $source_file<br />";
				return;
			}
			else {
		    header( 'HTTP/1.1 404 Not Found', true );
		    exit();
			}
	  }

		// Settings
		$cache_time = 24 * 60 * 60;
		$send_expires = true;
		$send_cache_control = true;
		$use_x_sendfile = get_option( 'mmu_x_sendfile', false );;
		$cache_directive = 'must-revalidate';
		$ip = isset( $_SERVER[' REMOTE_ADDR' ] ) ? $_SERVER[' REMOTE_ADDR' ] : '';
		$url = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';

		$user = wp_get_current_user();
		$is_visitor = $user->ID <= 0;
		$log_users = get_option( 'mmu_logged_users', false );
		$include_admin = get_option( 'mmu_include_admin', false );
		if ( empty( $ip ) )
			$ip = "127.0.0.1";

		$is_admin = strpos( $url, 'wp-admin' ) !== false;

		if ( !$admin_debug ) {
			// Send cache headers
		  if ( $send_cache_control )
		  	header( "Cache-Control: private, {$cache_directive}, max-age=" . $cache_time, true );
		  if ( $send_expires ) {
		    date_default_timezone_set( 'GMT' );
		    header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $cache_time ) . ' GMT', true );
		  }
		  if ( in_array( $source_ext, array( 'png', 'gif', 'jpeg', 'bmp' ) ) )
		  	header( "Content-Type: image/" . $source_ext, true );
		  else {
		  	header( "Content-Type: image/jpeg", true );
			}
			header( 'Content-Length: ' . filesize( $source_file ), true );
		}



		if ( $admin_debug || ( ( $log_users || $is_visitor ) && ( !$is_admin || $include_admin ) ) ) {

			// Get Info
			$path = Meow_MediaUsage_Core::get_pathinfo_from_image_src( $requested_uri, $admin_debug );
			$media = Meow_MediaUsage_Core::find_attachment_id_by( $path, $admin_debug );

			// Not linked to a media, let's check if it's an sized image
			if ( is_null( $media ) ) {

				// In case of Retina, remove @2x
				// Look for base in the case it is Retina
				$is_retina = strpos( $path, '@2x' ) !== false;
				$path = str_replace( '@2x', '', $path );

				// Remove the size info
				$image_size_regex = "/([_-]\\d+x\\d+(?=\\.[a-z]{3,4}$))/i";
				$potential_filepath = preg_replace( $image_size_regex, '', $path );
				$media = Meow_MediaUsage_Core::find_attachment_id_by( $potential_filepath );
				if ( !is_null( $media ) ) {
					// It was a sized image but we got the original one!
				}
			}

			if ( $admin_debug ) {
				echo "path = $path<br />";
				echo "file = " . ( empty( $source_file ) ? "N/A" : $source_file ) . "<br />";
				echo "media = " . ( empty( $media ) ? "N/A" : $media ) . "<br />";
			}

			// Add info to the DB
			global $wpdb;
	    $tbl_ref = $wpdb->prefix . "usage_ref";
			$tbl_access = $wpdb->prefix . "usage_access";
			$current_time = current_time( 'mysql' );

			// This reference/image is new -> create the reference
			$sql = "INSERT INTO $tbl_ref (path, media) VALUES (%s, %d) ON DUPLICATE KEY UPDATE path = %s";
			$sql = $wpdb->prepare( $sql, $path, $media, $path );
			$wpdb->query( $sql );

			// Get the views on this reference
			$sql = "SELECT views FROM $tbl_access WHERE ip = inet6_aton(%s) AND path = %s AND url = %s";
			$sql = $wpdb->prepare( $sql, $ip, $path, $url );
			$views = $wpdb->get_var( $sql );

			if ( is_null( $views )  ) {

				// Create the first IP/URI view for this reference
				$sql = "INSERT INTO $tbl_access (ip, path, views, url, created_on, updated_on) VALUES (inet6_aton(%s), %s, %d, %s, %s, %s)";
				$sql = $wpdb->prepare( $sql, $ip, $path, 1, $url, $current_time, $current_time );
				$wpdb->query( $sql );
			}
			else {

				// This reference was accessed by this IP/URI already, update counter
				$sql = "UPDATE $tbl_access SET views = %d, updated_on = %s WHERE ip = inet6_aton(%s) AND path = %s AND url = %s";
				$sql = $wpdb->prepare( $sql, $views + 1, $current_time, $ip, $path, $url );
				$wpdb->query( $sql );
			}
		}


		if ( $admin_debug )
			return;

	  // Send file
	  if ( $use_x_sendfile )
	      header( 'X-Sendfile: '. $source_file );
	  else
	    readfile( $source_file );
		exit;
	}

	function time_elapsed_string( $datetime, $full = false ) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'Now';
	}

	function get_usage_stats_for_media( $media_id ) {
		global $wpdb;
    $tbl_ref = $wpdb->prefix . "usage_ref";
		$tbl_access = $wpdb->prefix . "usage_access";
		$current_time = current_time( 'mysql' );
		$sql = "
			SELECT a.path, COUNT(*) as visits, SUM(views) views, MIN(created_on) first, MAX(updated_on) last
			FROM $tbl_access a, $tbl_ref r
			WHERE a.path = r.path
			AND media = %d
			GROUP BY a.path
			ORDER BY visits, views DESC
		";
		$sql = $wpdb->prepare( $sql, $media_id );
		$results = $wpdb->get_results( $sql );
		$meta = wp_get_attachment_metadata( $media_id );
		$info = array();

		if ( isset( $meta['sizes'] ) ) {
			$dir = trailingslashit( dirname( $meta['file'] ) );
			$meta['sizes']['full-size'] = array( 'file' => basename( $meta['file'] ) );
			foreach ( $meta['sizes'] as $key => $metadata ) {
				$data = null;
				foreach ( $results as $v ) {
			    if ( $this->from_relative_to_wp_url( $v->path ) == $dir . $metadata['file'] ) {
		        $data = $v;
		        break;
			    }
				}
				$info[ $dir . $metadata['file'] ] = array(
					'size' => $key,
					'visits' => isset( $data ) ? $data->visits : 0,
					'views' => isset( $data ) ? $data->views : 0,
					'first' => isset( $data ) ? $data->first : null,
					'last' => isset( $data ) ? $data->last : null,
				);
			}
		}

		return $info;
	}

	// Convert a relative URL (/wp-content/uploads/2016/12/temple-in-nara-300x200.jpg)
	// To a WP URL (2016/12/temple-in-nara-300x200.jpg)
	function from_relative_to_wp_url( $relative_path ) {
		$upload_dir = wp_upload_dir();
		$upload_url = parse_url( $upload_dir['baseurl'] );
		$upload_url = trim( $upload_url['path'], '/' );
		$relative_path = trim( $relative_path, '/' );
		$wp_relative_path = trim( str_replace( $upload_url, '', $relative_path ), '/' );
		return $wp_relative_path;
	}

	function find_attachment_id_by( $relative_path ) {
		$wpurl = $this->from_relative_to_wp_url( $relative_path );

		global $wpdb;
		$postmeta_table_name = $wpdb->prefix . 'postmeta';

		// Look for attached file
		$sql = $wpdb->prepare( "SELECT post_id FROM {$postmeta_table_name} WHERE meta_key = '_wp_attached_file' AND meta_value = %s" , $wpurl );
		$ret = $wpdb->get_var( $sql );

		return empty( $ret ) ? null : $ret;
	}

	function get_upload_root_url() {
		$uploads = wp_upload_dir();
		return trailingslashit( $uploads['baseurl'] );
	}

	function get_upload_root() {
		$uploads = wp_upload_dir();
		return trailingslashit( $uploads['basedir'] );
	}

	function get_pathinfo_from_image_src( $image_src ) {
		$uploads_url = Meow_MediaUsage_Core::get_upload_root_url();
		if ( strpos( $image_src, $uploads_url ) === 0 )
			return ltrim( substr( $image_src, strlen( $uploads_url ) ), '/');
		else if ( strpos( $image_src, wp_make_link_relative( $uploads_url ) ) === 0 )
			return ltrim( substr( $image_src, strlen( wp_make_link_relative( $uploads_url ) ) ), '/');
		$img_info = parse_url( $image_src );
		return ltrim( $img_info['path'], '/' );
	}

}

?>
