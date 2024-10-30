<?php

class Meow_MediaUsage_Library {

	public function __construct() {
		add_filter( 'manage_media_columns', array( $this, 'manage_media_columns' ) );
		add_action( 'manage_media_custom_column', array( $this, 'manage_media_custom_column' ), 10, 2 );
	}

	function manage_media_columns( $cols ) {
		$cols["MediaUsage"] = "Usage";
		return $cols;
	}

	function manage_media_custom_column( $column_name, $id ) {
		if ( $column_name != 'MediaUsage' )
			return;
		global $Meow_MediaUsage_Core;
		$stats = $Meow_MediaUsage_Core->get_usage_stats_for_media( $id );
		$verylast = null;
		$now = current_time( 'timestamp' );
		echo '<ul class="meow-sized-images">';
		foreach ( $stats as $path => $stat ) {
			$path = basename( $path );
			if ( empty( $verylast ) || $verylast < $stat[ 'last' ]  ) {
				$verylast = $stat['last'];
			}
			$first = $stat[ 'first' ] ? new DateTime( $stat[ 'first' ] ) : null;
			$last = $stat[ 'last' ] ? new DateTime( $stat[ 'last' ] ) : null;
			$text = "Size: {$stat['size']}\nFile: {$path}\nVisits: {$stat['visits']}\nViews: {$stat['views']}\nFirst: " .
				( is_null( $first ) ? 'Never' : human_time_diff( $first->getTimestamp(), $now ) ) . "\nLast: " .
				( is_null( $last ) ? 'Never' : human_time_diff( $last->getTimestamp(), $now ) ) . "";
			echo '<a href="admin.php?page=mmu_settings-menu&action=analyze_media&id=' . $id . '">';
			echo '<li class="' .
				( ( $stat['visits'] >= 2 ) ? 'meow-bk-blue' : ( ( $stat['visits'] > 0 ) ? 'meow-bk-gray' : '' ) ) .
				'" title="' . $text . '">' .
				Meow_Admin::size_shortname( $stat['size'] ) .
				'</li></a>';
		}
		echo "</ul>";
		$ago = new DateTime( $verylast );
		if ( is_null( $verylast ) )
			echo "<span style='font-size: 12px;'>Never accessed.</span>";
		else
			echo "<span style='font-size: 12px;'>" . human_time_diff( $ago->getTimestamp(), $now ) . ".</span>";

	}

}

?>
