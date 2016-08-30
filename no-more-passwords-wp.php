<?php
/*
Plugin Name: Inicio de Sesión Optimizaclick
Description: Plugin para iniciar sesión en Wordpress sin necesidad de contraseñas.
Author: Departamento de Desarrollo - Optimizaclick
Author URI: http://www.optimizaclick.com/
Text Domain: Inicio de Sesión Optimizaclick
Version: 0.1
Plugin URI: http://www.optimizaclick.com/
*/


//FUNCION INICIAL 
function nmpw_admin_menu() 
{	

	
}

//ACCION INICIAL 
add_action( 'admin_init', 'nmpw_admin_menu' );

//SE REGISTRAN LAS ACTIVIDADES CRON DEL PLUGIN AL SER ACTIVADO
register_activation_hook(__FILE__, 'activate_nmpw_cron');

//SE ASOCIA UNA FUNCION AL ACTIVARSE EL PLUGIN
function activate_nmpw_cron() 
{
	//SE REGISTRA UNA ACCION PARA QUE SE EJECUTE DIARIAMENTE
    if (! wp_next_scheduled ( 'nmpw_auto_update' )) 
		wp_schedule_event(time(), 'daily', 'nmpw_auto_update');
}

//SE ASOCIA LAS FUNCION QUE REALIZARA LA ACCION DE LA ACTIVIDAD DEL CRON
add_action('nmpw_auto_update', 'nmpw_update');


//SE ASOCIA UNA FUNCION AL DESACTIVAR EL PLUGIN
register_deactivation_hook(__FILE__, 'desactivate_nmpw_cron');

//SE CANCELA LA ACTIVIDAD CRON DEL PLUGIN AL SER DESACTIVADO
function desactivate_nmpw_cron() 
{
	wp_clear_scheduled_hook('nmpw_auto_update');
}


?>