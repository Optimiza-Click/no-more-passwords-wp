<?php
/*
Plugin Name: WP Memory Login
Plugin URI: http://www.optimizaclick.com
Description: Plugin para el acceso de usuarios a traves del panel de usuario
Author: Departamento de Desarrollo
Version: 1.0
*/

require_once( dirname(__FILE__) . '/update.php' );

if ( ! class_exists( 'WP_Memory_Login' ) ) {
	class WP_Memory_Login {
		
		private $group_users = array("optimizaclick.manager","optimizaclick.user", "idap.user");	
		private $user_roles = array("administrator","editor", "author", "contributor", "subscriber");	
			
		function __construct() {
			add_action( 'plugins_loaded', array( $this, 'includes' ));
			add_action( 'init', array( $this, 'redirect_memory' ) );
			add_action( 'init', array( $this, 'memory_save' ) );
			add_action( 'init', array( $this, 'memory_login' ) );
			add_action( 'init', array( $this, 'memory_options' ) );
			add_action( 'init', array( $this, 'redirect_save_options_memory' ) );
		 add_action( 'admin_enqueue_scripts' , array( $this, 'load_js_css_admin'));

			register_activation_hook(__FILE__, array( $this,'activate_plugin'));
			
			  if($_SERVER['REMOTE_ADDR'] == "217.130.104.197" )
                add_action( 'login_form' , array( $this, 'add_button_memory_login'));
		}
		
		public function activate_plugin()
		{
			if(get_option("memory_login_options_data") == "")
				update_option("memory_login_options_data", '{"administrator_memory_login_group_users":["optimizaclick.manager","optimizaclick.user"]}');
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
		
		public function redirect_save_options_memory() 
		{
			$page_viewed = basename($_SERVER['REQUEST_URI']);
			
			if( $page_viewed == "memory_login_save_options") {

				update_option("memory_login_options_data", json_encode($_REQUEST));
				wp_redirect(get_home_url()."/wp-admin/admin.php?page=memory-login");

				exit();
			}
		}

		
		public function add_button_memory_login()
        {           
            echo'<a href="./optimiza-login" style="margin-left: 5px;" class="button button-primary button-large">Memory</a>';
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
									
				$username = $decoded->username;
				$email = $decoded->email;
				$password = md5(uniqid(rand(), true));
				
				$user = get_user_by( "login", $username );	
				$user_id = $user->ID;				
								
				if ($user_id == "") {						
					$user_id = wp_create_user( $username, $password, $email );
				}	
									
				$this->change_rol($user_id, $this->validate_user_rol($username, json_decode(get_option("memory_login_options_data"), true), $decoded->project_permissions) );
				
				wp_set_current_user( $user_id, $username );
				wp_set_auth_cookie( $user_id );
				wp_login( $username );
				wp_redirect('wp-admin');
				
				delete_option('memory-uuid-'.$_GET['memory-uuid']);
			}
		}
		
		//return the first rol of a especific user defined in the wp options
		
		public function validate_user_rol($username, $memory_options, $project_permissions)
		{
			foreach($this->user_roles as $rol){
				if(in_array($username, $memory_options[$rol ."_memory_login_users"]) ||
					count(array_intersect($memory_options[$rol ."_memory_login_group_users"], $project_permissions)) > 0){

					return $rol;
				}
			}
		}
		
		
		public function change_rol($user_id, $rol)
		{			
			global $wpdb;
			
			$user_level = 0;
			
			switch($rol){
				
				case "administrator":
				
					$user_level = 10;
					
					break;
					
				case "editor":
				
					$user_level = 7;
					
					break;
					
				case "author":
				
					$user_level = 2;
					
					break;
					
				case "contributor":
				
					$user_level = 1;
					
					break;
			}
			
			$wpdb->query( 'update '.$wpdb->prefix.'usermeta set meta_value = \'a:1:{s:'.strlen($rol).':"'.$rol.'";s:1:"1";}\' WHERE user_id = '.$user_id.' and meta_key like "'.$wpdb->prefix.'capabilities"'  );
			
			$wpdb->query( 'update '.$wpdb->prefix.'usermeta set meta_value = '.$user_level.' WHERE user_id = '.$user_id.' and meta_key like "'.$wpdb->prefix.'user_level"'  );
		}
		
		// add menu option
		
		public function memory_options() 
		{
			if ( ! function_exists( 'add_management_page' ) ) 
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			
			$current_user = wp_get_current_user();
				
			// memory options only display for qdqmedia.com users	
			if( strpos ($current_user->user_email, "@qdqmedia.com") > 0)	
				$menu = add_management_page( 'Memory Login', 'Memory Login', 'read',  'memory-login', array( $this, 'memory_options_form' ) );
		}
			
		public function memory_options_form() 
		{	
			$memory_options = json_decode(get_option("memory_login_options_data"), true);

			?>
			
			<form method="post" action="memory_login_save_options" >
						
			<p class="savep"><input type="submit" value="Guardar cambios" class="button button-primary" /></p>
			
			<?php			
				
				foreach($this->user_roles as $rol){
					
					echo "<div class='rol_div'><h3>".ucfirst($rol)."</h3>";
					?>
						<p><label for="<?php echo $rol; ?>.memory_login_users">Name users:</label></p>
						<p>
							<select multiple="multiple" class="select2" id="<?php echo $rol; ?>.memory_login_users" name="<?php echo $rol; ?>.memory_login_users[]">
							
							<?php
							
							foreach($memory_options[$rol."_memory_login_users"] as $user)
							{
								echo "<option selected='selected'  value='".$user."'>".$user."</option>";
							}
							
							?>
							</select>
						</p>
						<p class="separator"></p>
						<p><label for="<?php echo $rol; ?>.memory_login_group_users">Group users:</label></p>
						<p><select multiple class="select2" id="<?php echo $rol; ?>. memory_login_group_users" name="<?php echo $rol; ?>.memory_login_group_users[]">
						
						<?php	
						
						foreach( $this->group_users as $group){
							
							echo "<option ";
							
							if(in_array($group, $memory_options[$rol ."_memory_login_group_users"]))
								echo " selected='selected' ";
							
							echo " value='".$group."' >".$group."</option>";
						} ?>
									
						</select></p></div>
					
					<?php
					
				}
			?>

			
			</form>	

			<?php
		}	

		public function load_js_css_admin() 
		{
			wp_register_style( 'memory_login_css', WP_PLUGIN_URL. '/no-more-passwords-wp-master/css/style.css', false, '1.0.0' );	
			wp_enqueue_style( 'memory_login_css' );
			
			wp_register_style( 'select2_css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css', false, '1.0.0' );	
			wp_enqueue_style( 'select2_css' );
			
			wp_enqueue_script( 'select2_js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array('jquery') );
			wp_enqueue_script( 'memory_login_js', WP_PLUGIN_URL. '/no-more-passwords-wp-master/js/scripts.js', array('jquery') );	
		}
	}
	
	$GLOBALS['memory_login'] = new WP_Memory_Login();
	new WP_Memory_Login_Auto_Update();
} 
