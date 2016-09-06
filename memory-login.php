<?php
/*
Plugin Name: WP Memory Login
Plugin URI: http://www.optimizaclick.com
Description: Plugin para el acceso de usuarios a traves del panel de usuario
Author: Departamento de Desarrollo
Version: 0.1 
*/


if ( ! class_exists( 'WP_Memory_Login' ) ) {
	class WP_Memory_Login {

	function __construct() {
		add_action( 'plugins_loaded', array( $this, 'includes' ));
		add_action( 'init', array( $this, 'redirect_memory' ) );
		add_action( 'init', array( $this, 'memory_save' ) );
		add_action( 'init', array( $this, 'memory_login' ) );
	}


// add library jwt to decode

public function includes() {
		require_once( dirname(__FILE__) . '/lib/jwt.php' );
		require_once( dirname(__FILE__) . '/lib/BeforeValidException.php' );
		require_once( dirname(__FILE__) . '/lib/ExpiredException.php' );
		require_once( dirname(__FILE__) . '/lib/SignatureInvalidException.php' );
}

// redirect to memory for enter by url
	
public function redirect_memory() {
		$page_viewed = basename($_SERVER['REQUEST_URI']);
		$login_page  = 'http://memory.tsuru.qdqmedia.com//login/v1/wordpress/?referer=' . site_url().'/';
			if( $page_viewed == "optimiza-login" && $_SERVER['REQUEST_METHOD'] == 'GET') {
				wp_redirect($login_page);
			exit();
		}
	}


// save json token in db


public function memory_save() {
		global $wpdb, $post;
		$page_viewed = basename($_SERVER['REQUEST_URI']);
		$url =  "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
		if( isset($_GET['memory-uuid']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
			add_option('memory-uuid-'.$_GET['memory-uuid'] , $_POST['user']);
		}
	}

	
// get the uuid from wp-options

public function memory_login() {
		global $wpdb, $post;
		$page_viewed = basename($_SERVER['REQUEST_URI']);
		$url =  "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
		if( isset($_GET['memory-uuid']) && $_SERVER['REQUEST_METHOD'] == 'GET') {		
			$key = file_get_contents('key.txt', FILE_USE_INCLUDE_PATH);
			$token = get_option('memory-uuid-'.$_GET['memory-uuid'] );
			$decoded = JWT::decode($token, $key , ['RS256']);
			$username = $decoded->username;
			$email = $decoded->email;
			$password = md5(uniqid(rand(), true));
			if (username_exists($username)) {
				$user = get_userdatabylogin( $username );
				$user_id = $user->ID;
				wp_set_current_user( $user_id, $user_login );
				wp_set_auth_cookie( $user_id );
				do_action( 'wp_login', $user_login );
				wp_redirect('wp-admin');
			}
			elseif(!username_exists($username)) {
				$user_id = wp_create_user( $username, $password, $email );
				$username = new WP_User( $user_id );
				
				$jquery = $wpdb->query( 'update '.$wpdb->prefix.'usermeta set meta_value = \'a:1:{s:13:"administrator";s:1:"1";}\' WHERE user_id = '.$user_id.' and meta_key like "'.$wpdb->prefix.'capabilities"'  );
		
				$jquery = $wpdb->query( 'update '.$wpdb->prefix.'usermeta set meta_value = 10 WHERE user_id = '.$user_id.' and meta_key like "'.$wpdb->prefix.'user_level"'  );
					
				$user = get_userdatabylogin( $username );
				wp_set_current_user( $user_id, $user_login );
				wp_set_auth_cookie( $user_id );
				do_action( 'wp_login', $user_login );
				wp_redirect('wp-admin');
			}
			delete_option('memory-uuid-'.$_GET['memory-uuid']);
			
		}
		
	}
}
$GLOBALS['memory_login'] = new WP_Memory_Login();
} 