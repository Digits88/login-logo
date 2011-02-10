<?php
/*
Plugin Name: Login Logo
Description: Drop a PNG file named <code>login-logo.png</code> into your <code>wp-content</code> directory. This simple plugin takes care of the rest, with zero configuration. Transparent backgrounds work best. Keep the width below 326 pixels.
Version: 0.3-beta
License: GPL
Author: Mark Jaquith
Author URI: http://coveredwebservices.com/
*/

class CWS_Login_Logo_Plugin {
	static $instance;
	const cutoff = 326;
	var $logo_locations;
	var $logo_location;
	var $width = 0;
	var $height = 0;
	var $original_height;
	var $logo_size;
	var $logo_file_exists;

	public function __construct() {
		self::$instance = $this;
		$this->add_hooks();
	}

	private function add_hooks() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'login_head', array( $this, 'login_head' ) );
		add_filter( 'login_headerurl', array( $this, 'login_headerurl' ) );
	}

	public function init() {
		$this->logo_locations = array();
		if ( is_multisite() && function_exists( 'get_current_site' ) ) {
			$site = get_current_site();
			if ( $site && isset( $site->site_id ) ) {
				$this->logo_locations['network'] = array(
					'path' => WP_CONTENT_DIR . '/login-logo-network-' . $site->site_id . '.png',
					'url' => WP_CONTENT_URL . '/login-logo-network-' . $site->site_id . '.png'
					);
			}
		}
		$this->logo_locations['global'] =  array(
			'path' => WP_CONTENT_DIR . '/login-logo.png',
			'url' => WP_CONTENT_URL . '/login-logo.png'
			);

	}

	private function logo_file_exists() {
		if ( ! isset( $this->logo_file_exists ) ) {
			foreach ( $this->logo_locations as $location ) {
				if ( $this->logo_file_exists = !! file_exists( $location['path'] ) ) {
					$this->logo_location = $location;
				}
			}
		}
		return !! $this->logo_file_exists;
	}

	private function get_location( $what = '' ) {
		if ( $this->logo_file_exists() ) {
			if ( 'path' == $what || 'url' == $what )
				return $this->logo_location[$what];
			else
				return $this->logo_location;
		}
		return false;
	}

	private function get_width() {
		$this->get_logo_size();
		return absint( $this->width );
	}

	private function get_height() {
		$this->get_logo_size();
		return absint( $this->height );
	}

	private function get_original_height() {
		$this->get_logo_size();
		return absint( $this->original_height );
	}

	private function get_logo_size() {
		if ( !$this->logo_file_exists() )
			return false;
		if ( !$this->logo_size ) {
			if ( $sizes = getimagesize( $this->get_location( 'path' ) ) ) {
				$this->logo_size = $sizes;
				$this->width  = $sizes[0];
				$this->height = $sizes[1];
				$this->original_height = $this->height;
				if ( $this->width > self::cutoff ) {
					// Use CSS 3 scaling
					$ratio = $this->height / $this->width;
					$this->height = ceil( $ratio * self::cutoff );
					$this->width = self::cutoff;
				}
			} else {
				$this->logo_file_exists = false;
			}
		}
		return array( $this->width, $this->height );
	}

	private function css3( $rule, $value ) {
		foreach ( array( '', '-o-', '-webkit-', '-khtml-', '-moz-', '-ms-' ) as $prefix ) {
			echo $prefix . $rule . ': ' . $value . '; ';
		}
	}

	public function login_headerurl() {
		return trailingslashit( get_bloginfo( 'url' ) );
	}

	public function login_head() {
	?>
	<!-- Login Logo plugin for WordPress: http://txfx.net/wordpress-plugins/login-logo/ -->
	<style type="text/css">
		h1 a {
			background: url(<?php echo esc_url_raw( $this->get_location( 'url' ) ); ?>) no-repeat top center;
			width: <?php echo self::cutoff; ?>px;
			height: <?php echo $this->get_height() + 3; ?>px;
			<?php if ( self::cutoff < $this->get_width() ) $this->css3( 'background-size', 'contain' ); ?>
		}
	</style>
<?php if ( self::cutoff < $this->get_width() ) { ?>
<!--[if lt IE 9]>
	<style type="text/css">
		height: <?php echo $this->get_original_height() + 3; ?>px;
	</style>
<![endif]-->
<?php
		}
	}

}

// Bootstrap
new CWS_Login_Logo_Plugin;
