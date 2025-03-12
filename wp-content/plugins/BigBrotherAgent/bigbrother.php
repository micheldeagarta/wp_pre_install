<?php
/*
Plugin Name: BigBrother Agent
Plugin URI: https://agarta.fr
Description: Monitoring & Surveillance de la galaxie de sites gérés par Agarta
Author: Agarta Creative Solutions
Version: 1.73
*/

if (!function_exists('write_log')) {
    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}

/* Mise à jour du plugin */
require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://bigbrother.agartaworld.com/update_plugin/bigbrother_agent_update.json',
	__FILE__, //Full path to the main plugin file or functions.php.
	'unique-plugin-or-theme-slug'
);

function __construct(){	
}  

// tache cron pour le calcul de u stockage utilisé */
add_filter( 'cron_schedules', 'add_cron_interval' );
function add_cron_interval( $schedules ) { 
    $schedules['one_minute'] = array(
        'interval' => 1 * 60 * 60,             // Tache cron toute les heures
        'display'  => esc_html__( 'Every Two Hours' ), );
    return $schedules;
}
add_action( 'bl_cron_hook', 'bl_cron_exec' );
function bl_cron_exec() {
	write_log('Cron script size');
	$size=0;
	if (!is_multisite()){ // On calcule la taille que si c'est pas un multisites.
		$size = sizeFilter (folderSize (ABSPATH) );
	}
    update_option( 'size', $size );
}
if ( ! wp_next_scheduled( 'bl_cron_hook' ) ) {
    wp_schedule_event( time(), 'one_minute', 'bl_cron_hook' );
}
register_deactivation_hook( __FILE__, 'bl_deactivate' ); 
 
function bl_deactivate() {
    $timestamp = wp_next_scheduled( 'bl_cron_hook' );
    wp_unschedule_event( $timestamp, 'bl_cron_hook' );
}




// Création du menu
function david_admin_menu() {
    add_menu_page('BigBrother','BigBrother','manage_options','admin-menu-page', 'david_page_admin','dashicons-visibility');
}
    add_action('admin_menu','david_admin_menu');
    
function david_page_admin() {
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    require_once('admin/admin-menu-page.php'); 
}

function folderSize ($dir)
{
    $bytestotal = 0;
    $path = realpath($dir);
    if($path!==false && $path!='' && file_exists($path)){
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object){
            $bytestotal += $object->getSize();
        }
    }
    return $bytestotal;
}
function sizeFilter($bytes)
    {
        return round($bytes / 1024 / 1024 / 1024,4);	

    }

add_action( 'init', 'function_requete_david' );


function send_mail_for_mplugin($texte){

	$site_title = get_bloginfo( 'name' );

	$headers = array('From: INFORMATION BIGBROTHER <david@agarta-agency.fr>','Content-Type: text/html; charset=UTF-8');
	$to = 'fred@agarta.fr,michel@agarta.fr';
	$subject = 'Le virus '. $texte .' a été détecté !';
	$message = 'Le virus '.$texte.' a été détecté dans le site "'.$site_title.'". La destruction est engagées.' ;
	wp_mail($to, $subject, $message, $headers); // envoi du mail de rappel pour la facturation
}

// Fonction qui réceptionne la requête du module principal et envoie un accusé de réception pour signaler que la requête a bien été reçue

function function_requete_david() {

	if (isset($_GET['requete_david']) && $_GET['requete_david']!='') {
        if ($_GET['requete_david']==1) {
            write_log(get_option('module', 0));
            //if (get_option('module', 0)==1) {
            write_log('requete detectee');
				
				
			/************** delete asset virus si il existe  ********/
				write_log('delete asset virus');
				$base_url_site = get_site_url();
				$path_to_asset_file = ABSPATH . '/asset/images/accesson.php';
				write_log('Test du virus Asset : ' . $path_to_asset_file);
				if(file_exists($path_to_asset_file))
				{
					write_log('asset virus detecté');
					$headers = array('From: INFORMATION SITE DAVID <david@agarta-agency.fr>','Content-Type: text/html; charset=UTF-8');
					$to = 'djessym@agarta.fr,michel@agarta.fr';
					$subject = 'Le virus asset a été détecté !';
					$message = 'Le virus asset a été détecté dans ce répertoire : ' . $path_to_asset_file ;
                	wp_mail($to, $subject, $message, $headers); // envoi du mail de rappel pour la facturation

					if (unlink($path_to_asset_file)){
						write_log('The file ' . $path_to_asset_file . ' was deleted successfully!');
						$subject = 'Le virus asset a été effacé !';
						$message = 'Le virus asset a été effacé dans ce répertoire : ' . $path_to_asset_file ;
                		wp_mail($to, $subject, $message, $headers); // envoi du mail de rappel pour la facturation
					} else {
						write_log('There was a error deleting the file ' . $path_to_asset_file);
					}
				}
			/************** delete mplugin si il existe ********/
				write_log('delete mplugin');
                
				$filename = '../mplugin.php';
				if(file_exists(plugin_dir_path(__FILE__) . $filename))
				{
					send_mail_for_mplugin('mplugin');
					if (unlink(plugin_dir_path(__FILE__) . $filename)){
						write_log('The file ' . $filename . ' was deleted successfully!');
					} else {
						write_log('There was a error deleting the file ' . $filename);
					}
				}
				
				$filename = '../admin_ips.txt';
				if(file_exists(plugin_dir_path(__FILE__) . $filename))
				{
					send_mail_for_mplugin('admin_ips');
					if (unlink(plugin_dir_path(__FILE__) . $filename)){
						write_log('The file ' . $filename . ' was deleted successfully!');
					} else {
						write_log('There was a error deleting the file ' . $filename);
					}
				}
				
				$serialised = get_option( 'active_plugins' );
				$data = maybe_unserialize( $serialised );
				if (($key = array_search('mplugin.php', $data)) !== false) {
					unset($data[$key]);
				}
				//write_log('active_plugins : ');
				//write_log($data);
				update_option( 'active_plugins', $data );
				
				$serialised = get_option( '_site_transient_et_update_all_plugins' );
				$data = maybe_unserialize( $serialised );
				/*if (($key = array_search('mplugin.php', $data)) !== false) {
					unset($data[$key]);
				}*/
				//write_log('_site_transient_et_update_all_plugins : ');
				//write_log($data);
				//update_option( '_site_transient_et_update_all_plugins', $data );
				
				$serialised = get_option( '_site_transient_update_plugins' );
				$data = maybe_unserialize( $serialised );
				/*if (($key = array_search('mplugin.php', $data)) !== false) {
					unset($data[$key]);
				}*/
				//write_log('_site_transient_update_plugins : ');
				//write_log($data);
				//update_option( '_site_transient_update_plugins', $data );
				/*******************************************************/	
			
				/* Size directory */
				//echo ABSPATH;
				
				
				$size = get_option('size', 0);
				
				if ($size == 0){
					echo 'Ok Mauryl';
				}else{
					echo 'Ok Mauryl Size:'.$size.'Go';
				}
            	
				
            die();
            //}
        }
    }
}
