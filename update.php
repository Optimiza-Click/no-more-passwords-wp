<?php

if (!class_exists('WP_Memory_Login_Auto_Update')) {

	class WP_Memory_Login_Auto_Update {
		
		public $respository_url = "https://githubversions.optimizaclick.com/repositories/view/66937235";
		
		public $temp_name = "temp_wp_memory_limit.zip";
		
		public $main_file = "memory-login.php";
		
		function __construct() {
			
			if ( ! function_exists( 'register_activation_hook' ) ) 
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			
			add_action( 'wp_login', array( $this, 'auto_update_plugin' ));

			//SE ACTIVAN LAS ACTIVIDADES CRON DEL PLUGIN AL SER ACTIVADO
			register_activation_hook(__DIR__ ."/".$this->main_file , array( $this,'activate_cron_accions_wp_memory_login'));
				
			//SE ASOCIA UNA FUNCION AL DESACTIVAR EL PLUGIN
			register_deactivation_hook(__DIR__ ."/".$this->main_file   , array( $this,'desactivate_cron_accions_wp_memory_login'));
		}


		//SE ASOCIA UNA FUNCION AL ACTIVARSE EL PLUGIN
		public function activate_cron_accions_wp_memory_login() 
		{
			//SE REGISTRA UNA ACCION PARA QUE SE EJECUTE DIARIAMENTE
			if (! wp_next_scheduled ( 'auto_update_wp_memory_login' )) 
				wp_schedule_event(time(), 'daily', 'auto_update_wp_memory_login');	
			
			//SE ASOCIAN LAS FUNCIONES QUE REALIZARAN LAS ACCIONES DE LAS ACTIVIDADES DEL CRON
			add_action('auto_update_wp_memory_login', array( $this,'update_wp_memory_login'));
		}

		//SE CANCELAN LAS ACTIVIDADES CRON DEL PLUGIN AL SER DESACTIVADO
		public function desactivate_cron_accions_wp_memory_login() 
		{
			wp_clear_scheduled_hook('auto_update_wp_memory_login');
		}
				
		//FUNCION QUE COMPRUEBA SI HAY UNA VERSION NUEVA DEL PLUGIN PARA ACTUALIZARLO
		public function update_wp_memory_login()
		{
			//SE COMPRUEBA SI HAY UNA VERSION MAS ACTUAL DEL PLUGIN EN EL RESPOSITORIO PARA ACTUALIZARSE
			if(get_version_plugin() < get_repository_values("version"))
				auto_update_plugin();	
		}

		//FUNCION PARA ACTUALIZAR EL PLUGIN
		public function auto_update_plugin()
		{
			$link = get_repository_values("url");
			
			//SE COMPRUEBA EL DIRECTORIO ACTUAL PARA PODER GUARDAR EL .ZIP CON LA ACTUALIZACION
			if(strpos($_SERVER['REQUEST_URI'], "/wp-admin/") === false)
			{
				$file = "./wp-content/plugins/".$this->temp_name;	
				$dir = "./wp-content/plugins/";
			}
			else
			{		
				$file = "../wp-content/plugins/".$this->temp_name;
				$dir = "../wp-content/plugins/";
			}
			
			//SE DESCARGA EL .ZIP CON LA ULTIMA VERSION DEL PLUGIN
			file_put_contents($file, fopen($link, 'r'));
			
			$zip = new ZipArchive;
			
			//SE DESCOMPRIME Y REEMPLAZAN LOS FICHEROS DEL PLUGIN PARA DEJARLO ACTUALIZADO
			if ($zip->open($file) === TRUE) 
			{
				$zip->extractTo($dir);
				$zip->close();
			} 
			
			//SE ELIMINA EL .ZIP
			unlink($file);
		}

		//FUNCION QUE DEVUELVE LA VERSION ACTUAL DEL PLUGIN INSTALADO
		public function get_version_plugin()
		{
			if ( ! function_exists( 'get_plugins' ) ) 
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			
			$plugins = get_plugins(); 
			
			return $plugins['no-more-passwords-wp-master/memory-login.php']["Version"];
		}	

		//FUNCION QUE DEVUELVE LA VERSION ACTUAL DEL PLUGIN EN EL RESPOSITORIO DE GITHUB O LA URL DE DESCARGA
		public function get_repository_values($data)
		{	
			$content = file_get_contents($this->respository_url);
			
			$values = explode("|", $content);
			
			if($data == "version")
				return $values[0];
			else
				return $values[1]; 
		}
	}
}

?>