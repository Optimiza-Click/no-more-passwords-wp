<?php
/*
Plugin Name: WP Memory Login
Plugin URI: http://www.optimizaclick.com
Description: Plugin para el acceso de usuarios a traves del panel de usuario
Author: Departamento de Desarrollo
Version: 0.4
*/

require_once( dirname(__FILE__) . '/update.php' );

if ( ! class_exists( 'WP_Memory_Login' ) ) {
	class WP_Memory_Login {
		
	private $group_users = array("optimizaclick.manager","optimizaclick.user");	
		
	function __construct() {
		add_action( 'plugins_loaded', array( $this, 'includes' ));
		add_action( 'init', array( $this, 'redirect_memory' ) );
		add_action( 'init', array( $this, 'memory_save' ) );
		add_action( 'init', array( $this, 'memory_login' ) );
		add_action( 'init', array( $this, 'memory_options' ) );
		add_action( 'init', array( $this, 'memory_register_options' ) );
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
		
		if(get_option("memory_login_group_users") == "")
			add_option("memory_login_group_users", $this->group_users);
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
			
			if(in_array($decoded->username, explode(";", get_option("memory_login_users"))) ||
			count(array_intersect(get_option("memory_login_group_users"), $decoded->project_permissions)) > 0)
			{		
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
			else
			{
				echo "<script>alert('El usuario introducido no tiene permisos para acceder.')</script>";
				
				print_r($decoded->project_permissions);
			}
		}
	}
	
	// add menu option
	
	public function memory_options() {
		
		if ( ! function_exists( 'add_management_page' ) ) 
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		
		$current_user = wp_get_current_user();
			
		// memory options only display for qdqmedia.com users	
		if( strpos ($current_user->user_email, "@qdqmedia.com") > 0)	
			$menu = add_management_page( 'Memory Login', 'Memory Login', 'read',  'memory-login', array( $this, 'memory_options_form' ) );
	}
	
	// memory options form 
	
	public function memory_options_form() {
		
		?>
		
		<h1>Opciones</h1>
		
		<form method="post" action="options.php" >
		
		<?php
			settings_fields( 'users_group_memory_options' ); 
			do_settings_sections( 'users_group_memory_options' ); 
		?>
		
		<p><label for="memory_login_users">Name users: (username1;username2;...)</label></p><p><textarea id="memory_login_users" name="memory_login_users" placeholder="User Names" ><?php echo get_option("memory_login_users"); ?></textarea></p>
		
		<p><label for="memory_login_group_users">Group users:</label></p><p><select multiple id="memory_login_group_users" name="memory_login_group_users[]" style="width: 200px;" >
		
		<?php	
		
		foreach( $this->group_users as $group)
		{
			echo "<option ";
			
			if(in_array($group,  get_option("memory_login_group_users")))
				echo " selected='selected' ";
			
			echo " value='".$group."' >".$group."</option>";
		} ?>
					
		</select></p>
				
		<?php submit_button();  ?>
		
		</form>	

		<?php 

	}	
	
	public function memory_register_options() 
	{
		register_setting( 'users_group_memory_options', 'memory_login_users' );
		register_setting( 'users_group_memory_options', 'memory_login_group_users' );
	}
	
}
$GLOBALS['memory_login'] = new WP_Memory_Login();
new WP_Memory_Login_Auto_Update();
} 