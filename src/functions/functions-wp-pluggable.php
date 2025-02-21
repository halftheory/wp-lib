<?php
if ( ! function_exists('ht_is_user_logged_in') ) {
	function ht_is_user_logged_in() {
		if ( function_exists('is_user_logged_in') ) {
			return is_user_logged_in();
		}
		if ( isset($_COOKIE) && ! empty($_COOKIE) ) {
			foreach ( $_COOKIE as $key => $value ) {
				if ( str_contains($key, 'wordpress_logged_in_') ) {
					return true;
				}
			}
		}
		return false;
	}
}

if ( ! function_exists('ht_wp_redirect') ) {
	function ht_wp_redirect( $location, $status = 302, $x_redirect_by = false ) {
		if ( headers_sent() ) {
			?>
<script type="text/javascript"><!--
setTimeout("window.location.href = '<?php echo esc_url($location); ?>'",0);
//--></script>
			<?php
			return true;
		} elseif ( function_exists('wp_redirect') ) {
			return wp_redirect($location, $status, $x_redirect_by);
		} else {
			header('Location: ' . $location, true, $status);
			return true;
		}
	}
}
