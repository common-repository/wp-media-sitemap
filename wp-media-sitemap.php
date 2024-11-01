<?php
/*
Plugin Name: WP-Media-SiteMap
Plugin URI: http://thomas-genin.com/projects/wordpress-media-sitemap
Description: WP-Media-SiteMap is a sitemap generator for images, video, and flash animation
Author: Thomas Genin
Author URI: http://thomas.genin.com/
Version: 2.1
*/
/** Copyright 2010  Thomas GENIN  (email : xt6.thomas.genin{[@}\gmail.com)
 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once 'sitemap.class.php';

//---------------------------------------------------------------------------
register_activation_hook(__FILE__, 'wp_media_sitemap_install');
register_deactivation_hook(__FILE__, 'wp_media_sitemap_uninstall');
//---------------------------------------------------------------------------
//add_action('admin_init', 'wp_media_sitemap_generate');
//add_action('save_post ', 'wp_media_sitemap_generate');


//Existing posts was deleted
add_action('delete_post', 'wp_media_sitemap_generate',9999,1);
			
//Existing post was published
add_action('publish_post', 'wp_media_sitemap_generate',9999,1);
			
//Existing page was published
add_action('publish_page', 'wp_media_sitemap_generate',9999,1);
			

//add_action('publish_page ', 'wp_media_sitemap_generate');
add_action('admin_menu', 'wp_media_sitemap_create_menu');
//---------------------------------------------------------------------------	

define('OPTN_IMAGE','wp_media_sitemap_image');
define('OPTN_VIDEO','wp_media_sitemap_video');
define('OPTN_FLASH','wp_media_sitemap_flash');
define('OPTN_SYSTM','wp_media_sitemap_systm');

define('WPMS_DIR','media-sitemap');

define('INDEX_SITEMAP','media_sitemap.xml');
define('IMG_SITEMAP','img_sitemap');
define('VID_SITEMAP','vid_sitemap');

	/**
	 * 
	 * @return 
	 */
if(!function_exists("wp_media_sitemap_install")){
	function wp_media_sitemap_install(){
//		$path = wp_media_sitemap_get_root_path( true );
		
		$errorM = '';
		$isError = false;
/*		
		if( ! mkdir( $path . WPMS_DIR , 0755) ){
			$isError = true;
			$errorM .= $path . ' '. __('the directory must be writable at least for the installation of the plugin.','wp-media-sitemap') . '<br/><br/>'; 
		}
/**/		
		if(! function_exists('file_put_contents') ){
			$isError = true;
			$errorM .= __('Please update to PHP5','wp-media-sitemap') . '<br/><br/>';
		}
		if(version_compare($wp_version,"2.9","<")){
			$isError = true;
			$errorM .=  __('Task Manager requires WordPress 2.9 or newer.<a href="http://codex.wordpress.org/Upgrading_WordPress">Please update!</a><br/>');
		}
		
		if($isError){
			//die( $errorM );
		}
		
		add_option( OPTN_IMAGE, '', '', 'no');
		add_option( OPTN_VIDEO, '', '', 'no');
		add_option( OPTN_FLASH, '', '', 'no');
		add_option( OPTN_SYSTM, serialize(array('time' => 0, 'success' => 3 )), '', 'no');
	}
}

	/**
	 * Destroy everything create by the plugin 
	 * @return 
	 */
if(!function_exists("wp_media_siteap_uninstall")){
	function wp_media_sitemap_uninstall(){    
		delete_option( OPTN_IMAGE, serialize( array() ), '', 'no');
		delete_option( OPTN_VIDEO, serialize( array() ), '', 'no');
		delete_option( OPTN_FLASH, serialize( array() ), '', 'no');
		delete_option( OPTN_SYSTM, serialize( array() ), '', 'no');
	}	
}	
	


if(!function_exists("wp_media_sitemap_loadOption")){	
	function wp_media_sitemap_loadOption( $name){
		return get_option( $name) ;
	}
}


if(!function_exists("wp_media_sitemap_saveOption")){	
	function wp_media_sitemap_saveOption($name, $array){
		update_option( $name, serialize($array) );
	}
}



	/**
	 * Return the absolute path of the root directory of the website
	 * @return string
	 */
if(!function_exists("wp_media_sitemap_get_root_path")){
	function wp_media_sitemap_get_root_path( $isRoot = false ){
		$home = get_option( 'home' );
		$siteurl = get_option( 'siteurl' );
		if ( $home != '' && $home != $siteurl ) {
		        $wp_path_rel_to_home = str_replace($home, '', $siteurl); /* $siteurl - $home */
		        $pos = strpos($_SERVER["SCRIPT_FILENAME"], $wp_path_rel_to_home);
		        $home_path = substr($_SERVER["SCRIPT_FILENAME"], 0, $pos);
		} else {
			$home_path = ABSPATH;
		}
		if( $isRoot )
			return trailingslashit($home_path);
		else
			return trailingslashit($home_path . WPMS_DIR);
	}
}	
	
	/**
	 * Generate Sitemap
	 * @return unknown_type
	 */
if(!function_exists("wp_media_sitemap_generate")){
	function wp_media_sitemap_generate(){
		$startDate = wp_media_sitemap_timer();
		$sitemap = new Sitemap( wp_media_sitemap_get_root_path() );
		$res = $sitemap->generate();
		if( $res === false  ){
			$endDate = wp_media_sitemap_timer();
			$executionTime = number_format($endDate - $startDate,7);
			$options = array('time' => $executionTime, 'success' => 0 );
			wp_media_sitemap_saveOption(OPTN_SYSTM, $options);
			return false;
		}
		$endDate = wp_media_sitemap_timer();
		$executionTime = number_format($endDate - $startDate,7);
		$options = array('time' => $executionTime, 'success' => 1 );
		wp_media_sitemap_saveOption(OPTN_SYSTM, $options);
		/**/
	}
}
	
if(!function_exists("wp_media_sitemap_error")){	
	function wp_media_sitemap_error(){
		echo '<script type="text/javascript">alert("WP-Media-SiteMap encounter an error and wasn\'t able to generate the sitemap file.\n\nPlease look at the option page of the plugin for more information.")</script>';
	}
}
	
if(!function_exists("wp_media_sitemap_create_menu")){
	function wp_media_sitemap_create_menu(){
		add_options_page('WP-Media-SiteMap','WP-Media-SiteMap',1, __FILE__,'wp_media_sitemap_option_page','wp_media_sitemap_option_page');
	}
}
	
if(!function_exists("wp_media_sitemap_option_page")){
	function wp_media_sitemap_option_page(){
		if( isset($_GET['action']) && $_GET['action'] === 'generate'){
			wp_media_sitemap_generate();
		}
?>
		<h1>WP-Media-SiteMap</h1>
		<br/>
		
		<a href="<?= $_SERVER['PHP_SELF'] ?>?page=wp-media-sitemap/wp-media-sitemap.php&action=generate">Click here to manually generate the sitemap !</a>
		<div>
<?php
		$optionsSys = wp_media_sitemap_loadOption( OPTN_SYSTM );
		$optionsImg = wp_media_sitemap_loadOption( OPTN_IMAGE );
		$optionsVid = wp_media_sitemap_loadOption( OPTN_VIDEO );
		$path = wp_media_sitemap_get_root_path( );
?>
		<span style="color:red;font-weight: bolder; font-size: 14px;">
<?php
		if( ! file_exists( $path ) ){
			echo $path . __('is missing ! Please create it and put permission to 766','wp-media-sitemap');
		}else if( ! wpms_IsFileWritable( $path ) ){
			echo $path . __('need to be writable','wp-media-sitemap');
		}
		if( isset($optionsSys['success'] ) && $optionsSys['success'] === 0){
?>
			<p>
				<?php _e('An attemp to create a site media sitemap have been detected.','wp-media-sitemap');?><br/>
				<?php _e('But some errors occurs.','wp-media-sitemap'); ?><br/>
				<?php _e('If no indications are display, please visit ','wp-media-sitemap');?>
				<a href="http://thomas-genin.com/projects/wordpress-media-sitemap"><?php _e('the plugin website','wp-media-sitemap')?></a>
				<?php _e('for more informations or to ask for help !','wp-media-sitemap')?>
			</p>
<?php
		}else if( isset($optionsSys['success'] ) && $optionsSys['success'] === 1 ){
			$siteURL = wpms_get_site_url();
			
?>
		<br/>
		</span>
		<br/>
<?php
				if( file_exists($path . INDEX_SITEMAP ) ){
?>
				<span style="color:red; font-weight: bolder"><?php _e('File to give to the Google Webmaster Tools','wp-media-sitemap');?>:&nbsp;</span>
				<a href="<?= $siteURL.INDEX_SITEMAP ?>"><?= $siteURL.INDEX_SITEMAP ?></a>
				<br/>
<?php 
				}
?>
				<h3><?php _e('Details About SiteMap files','wp-media-sitemap')?></h3>
				<?= __('Generation Time','wp-media-sitemap').'&nbsp;:&nbsp;' . number_format( $optionsSys['time'], 7,'.',''); ?> sec<br/><br/>
				<?= __('Number of Images','wp-media-sitemap').'&nbsp;:&nbsp;' . $optionsImg['nbImg']; ?>
				<br/><br/>
				<table style="border: 1px solid">
					<tr>
						<th style="width:70px;">Type</th>
						<th style="width:200px;">Link</th>
						<th>Size (bytes)</th>
					</tr>
					<tr>
						<td><?php _e('Index','wp-media-sitemap')?></td>
<?php
				if( file_exists($path.INDEX_SITEMAP) ){
?>
						<td><a href="<?= $siteURL . INDEX_SITEMAP?>"><?= INDEX_SITEMAP?></a></td>
						<td style="text-align: right;"><?= number_format( filesize($path.INDEX_SITEMAP) ) ?></td>
<?php
				}else{
?>
						<td><?= __('ERROR Index File','wp-media-sitemap'); ?></td>
						<td></td>
<?php
				}
?>
					</tr>
<?php
				$count = $optionsImg['nbImgFile'] + 1;
				for($i=0; $i<$count; $i++){
?>
					<tr>
						<td><?= _e('Image','wp-media-sitemap');?></td>
<?php 
					if( file_exists( $path.IMG_SITEMAP.$i.'.xml') ){
?>
						<td><a href="<?= $siteURL . IMG_SITEMAP . $i?>.xml"><?= IMG_SITEMAP.$i?>.xml</a></td>
						<td style="text-align: right;"><?= number_format( filesize($path.IMG_SITEMAP.$i.'.xml') )?></td>
<?php
					}else{
?>
						<td><?= __('ERROR No Image SiteMap Found','wp-media-sitemap')?></td>
						<td></td>
<?php	
					}
?>
					</tr>			
<?php
				}
				
				$count = $optionsVid['nbVideoFile'] + 1;
				for($i=0; $i<$count; $i++){
?>
					<tr>
						<td><?= _e('Video','wp-media-sitemap');?></td>
<?php 
					if( file_exists( $path.VID_SITEMAP.$i.'.xml') ){
?>
						<td><a href="<?= $siteURL .VID_SITEMAP . $i?>.xml"><?= VID_SITEMAP.$i?>.xml</a></td>
						<td style="text-align: right;"><?= number_format( filesize($path.VID_SITEMAP.$i.'.xml') )?></td>
<?php
					}else{
?>
						<td><?= __('ERROR No Image SiteMap Found','wp-media-sitemap')?></td>
						<td></td>
<?php	
					}
?>
					</tr>			
<?php
				}				
?>				
				</table>
				<br/>
<?php
		}else{
?>
			<p><?php _e('No media sitemap found. The files are generated when the content of your blog change.','wp-media-sitemap');?></p>
<?php			
		}
?>
	</div>
<?php
	}//end function
}
	
	/**
	 * Function necessary to get time execution of the script
	 * @return unknown_type
	 */
if(!function_exists("wp_media_sitemap_timer")){
	function wp_media_sitemap_timer()
	{
		$time=explode(' ',microtime() );
		return $time[0] + $time[1];
	} 
}
	
	/**
	 * Checks if a file is writable and tries to make it if not.
	 *
	 * @since 3.05b
	 * @access private
	 * @author  VJTD3 <http://www.VJTD3.com>
	 * @return bool true if writable
	 */
if(!function_exists("wpms_IsFileWritable")){
	function wpms_IsFileWritable($filename) {
		//can we write?
		if(!is_writable($filename)) {
			//no we can't.
			if(!@chmod($filename, 0666)) {
				$pathtofilename = dirname($filename);
				//Lets check if parent directory is writable.
				if(!is_writable($pathtofilename)) {
					//it's not writeable too.
					if(!@chmod($pathtoffilename, 0666)) {
						//darn couldn't fix up parrent directory this hosting is foobar.
						//Lets error because of the permissions problems.
						return false;
					}
				}
			}
		}
		//we can write, return 1/true/happy dance.
		return true;
	}
}

if(!function_exists("wpms_get_site_url")){
	function wpms_get_site_url(){
		return trailingslashit(get_option('siteurl') ) . WPMS_DIR . '/' ;
	}
}