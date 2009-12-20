<?php
/*
Plugin Name: Target Page Navigation
Plugin URI: http://www.siterighter.com
Description: This plugin adds a new optoin (only available on the Add/Edit page sections) that enables the author to assign the page to one of 4 navigation types(Super, Head, Side, Page, Foot) to be used in a new function (wp_list_navtype_pages), that will replace the wp_list_pages() function.
Author: siteRighter
Version: 0.2
Author URI: http://www.siteRighter.com
*/

/*

Copyright 2007 Mike Olaski (mjo@emjayoh.com)

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
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/



function filter_get_pages($pages){
global $TPN,$wpdb;

	if(!$TPN)
		return $pages;

	$query = "SELECT post_id FROM navigation_types WHERE navigation_type = '$TPN'" ;

	$nav_pages = $wpdb->get_col($query,0, ARRAY_A);

	foreach($pages as $page){
		if(in_array($page->ID,$nav_pages))
			$new_pages[] = $page;
	}
	return $new_pages;
}

function wp_list_navtype_pages($args = '') {
global $TPN;
	$TPN = strtolower($args["navigation_type"]);
	add_filter('get_pages', 'filter_get_pages');

	// Query pages.
	$pages = wp_list_pages($args);

	remove_filter('get_pages', 'filter_get_pages');
	unset($TPN);
	
}


function addTable(){
	
	global $wpdb;
	$query = "CREATE TABLE IF NOT EXISTS `navigation_types` (
  `id` mediumint(9) NOT NULL auto_increment,
  `post_id` mediumint(9) default NULL,
  `navigation_type` enum('super','head','side','page','foot') default 'page',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=latin1";
	
	$wpdb->query($query);
	
}

function hookSideBox(){
	 echo getNavDiv();
}

function saveNavType($post_id){
	global $wpdb;
	addTable();
	if(empty($post_id))
		$post_id = (int) $_POST["post_ID"];
	
	if(!empty($post_id)){		
	//Drop All:::::::::
		$wpdb->query( "DELETE FROM navigation_types WHERE post_id = $post_id" );
	
	//	Update NavTypes::::::::::	
		if($_POST['nav_super'] == 'on')
			$wpdb->query("INSERT INTO navigation_types(post_id,navigation_type) VALUES('$post_id','super')");			
		if($_POST['nav_head'] == 'on')
			$wpdb->query("INSERT INTO navigation_types(post_id,navigation_type) VALUES('$post_id','head')");
		if($_POST['nav_side'] == 'on')
			$wpdb->query("INSERT INTO navigation_types(post_id,navigation_type) VALUES('$post_id','side')");
		if($_POST['nav_page'] == 'on')
			$wpdb->query("INSERT INTO navigation_types(post_id,navigation_type) VALUES('$post_id','page')");
		if($_POST['nav_foot'] == 'on')
			$wpdb->query("INSERT INTO navigation_types(post_id,navigation_type) VALUES('$post_id','foot')");
	
	}else{		
		$post_id = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_title='".$_POST['post_title']."' AND post_content='".$_POST['post_content']."' ORDER BY ID DESC" );
		saveNavType($post_id);		
	}
	
}

function getNavType($navType, $postID){
	
	global $wpdb;
	addTable();
	if($postID)
		$res = $wpdb->get_var( "SELECT post_id FROM navigation_types WHERE navigation_type='$navType' AND post_id=$postID" );
	
	if($res)
		return 'checked="checked"';
	else
		return '';
}

function getNavDiv(){ 
	$navigation = 
		
		  	'<p>'.
			'<strong>Choose what navigation object do you want this page to be displayed in:</strong><br/><br/>'. 
			'<input type="checkbox" id="nav_super" name="nav_super" '.getNavType("super", $_GET["post"]).' /> Super Nav<br>'.
			'<input type="checkbox" id="nav_head" name="nav_head" '.getNavType("head", $_GET["post"]).' /> Head Nav<br>'.
			'<input type="checkbox" id="nav_side" name="nav_side" '.getNavType("side", $_GET["post"]).' /> Side Nav<br>'.
			//'<input type="checkbox" id="nav_page" name="nav_page" '.getNavType("page", $_GET["post"]).' /> Page Nav<br>'.
			'<input type="checkbox" id="nav_foot" name="nav_foot" '.getNavType("foot", $_GET["post"]).' /> Foot Nav<br>'.
			'</p>';
	
	
	return $navigation;	
}

function TPN_customUI_box(){
	add_meta_box( 'TPN','Target Page Navigation', 'hookSideBox', 'page','normal','core');
}

//add_action('edit_page_form', '');
add_action('save_post', 'saveNavType'); 

add_action('submitpage_box',  'TPN_customUI_box');
?>