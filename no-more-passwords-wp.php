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


?>