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
		add_action( 'init', array( $this, 'redirect_memory' ) );
		add_action( 'init', array( $this, 'memory_save' ) );
		add_action( 'admin_menu', array( $this , 'my_plugin_menu' ) );
		add_action( 'init', array($this, 'unlock_code'));
		add_action( 'init',array($this, 'my_plugin_options'));
	//	add_action( 'init', array( $this, 'memory_adduser' ) );
	}
	
	public function my_plugin_menu() {
	add_options_page( 'Memory Test', 'Memory_test', 'manage_options', 'my-unique-identifier', 'my_plugin_options' );
}

public function my_plugin_options() {

		}
		
// redirect to memory for enter by url
	
	public function redirect_memory() {
		$page_viewed = basename($_SERVER['REQUEST_URI']);
		$login_page  = 'http://memory.tsuru.qdqmedia.com//login/v1/wordpress/?referer=' . site_url();
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
			if( strpos($page_viewed, '?memory-uuid') == false && $_SERVER['REQUEST_METHOD'] == 'GET') {
				$token = preg_replace("/.memory-uuid=(.*)/", "$1", $page_viewed);
				add_option( 'memory_session', $token , '', 'yes' );
			}
	}

function unlock_code() {
	if($token == $login_page) {

} }


/*
 *-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDXvdeYE6iBVpPFkcb+faSGrHXy
t7HXU4A34HB159qY7N8lgmYpd7jG+k9WW603LOXkEeuRj8OAs6Vos75YsSL37PbJ
EeG/OGd/KwyVnBLqhm3Qix0sgFdPtIpVZZ/ftnybifHLGsSh/KJJb2CeUw8WerwK
wSFYmrV2A+wyydYeMQIDAQAB
-----END PUBLIC KEY-----
*/

// create and login users :)

/*
 *
	function memory_adduser() {
		$username = $_POST['username'];
			if ( username_exists( $username ) {
				ob_start();
					if ( !is_user_logged_in() ) {
					$user = get_userdatabylogin( $username );
					$user_id = $user->ID;
					wp_set_current_user( $user_id, $user_login );
					wp_set_auth_cookie( $user_id );
					do_action( 'wp_login', $user_login );
					} 
				ob_end_clean();
			}
			else {
				wp_create_user( $username, $password, $email ));
					$username = new WP_User( $user_id );
					 if ( username_exists( $username ) {
				ob_start();
					if ( !is_user_logged_in() ) {
					$user = get_userdatabylogin( $username );
					$user_id = $user->ID;
					wp_set_current_user( $user_id, $user_login );
					wp_set_auth_cookie( $user_id );
					do_action( 'wp_login', $user_login );
					} 
				ob_end_clean();
			}
				}
	}


*/





}

$GLOBALS['memory_login'] = new WP_Memory_Login();
} 