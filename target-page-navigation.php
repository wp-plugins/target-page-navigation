<?php
/*
Plugin Name: Target Page Navigation
Plugin URI: http://www.siterighter.com
Description: This plugin adds a new sidebox (only available on the Add/Edit page sections) from which the author can configure the scope of the wp_list_navtype_pages() function and the edited/created page, wp_list_navtype_pages function supports the same parameters wp_list_pages function does, but it also adds support for the new "navigation_type" parameter. Possible values are: wp_list_pages(navigation_type = 'Super' || 'Head' || 'Side' || 'Page' || 'Foot').Updated to use with WordPress 2.5.1 
Author: siteRighter
Version: 0.1.1
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

function &get_navtype_pages($args = '') {
	global $wpdb;
	$navigation_type = strtolower($args["navigation_type"]);
	//var_dump($navigation_type);
	if ( is_array($args) )
		$r = &$args;
	else
		parse_str($args, $r);

	$defaults = array('child_of' => 0, 'sort_order' => 'ASC', 'sort_column' => 'post_title',
				'hierarchical' => 1, 'exclude' => '', 'include' => '', 'meta_key' => '', 'meta_value' => '', 'authors' => '');
	$r = array_merge($defaults, $r);
	extract($r);

	$key = md5( serialize( $r ) );
	if ( $cache = wp_cache_get( 'get_pages', 'page' ) )
		if ( isset( $cache[ $key ] ) )
			return apply_filters('get_pages', $cache[ $key ], $r );

	$inclusions = '';
	if ( !empty($include) ) {
		$child_of = 0; //ignore child_of, exclude, meta_key, and meta_value params if using include 
		$exclude = '';
		$meta_key = '';
		$meta_value = '';
		$incpages = preg_split('/[\s,]+/',$include);
		if ( count($incpages) ) {
			foreach ( $incpages as $incpage ) {
				if (empty($inclusions))
					$inclusions = ' AND ( ID = ' . intval($incpage) . ' ';
				else
					$inclusions .= ' OR ID = ' . intval($incpage) . ' ';
			}
		}
	}
	if (!empty($inclusions))
		$inclusions .= ')';

	$exclusions = '';
	if ( !empty($exclude) ) {
		$expages = preg_split('/[\s,]+/',$exclude);
		if ( count($expages) ) {
			foreach ( $expages as $expage ) {
				if (empty($exclusions))
					$exclusions = ' AND ( ID <> ' . intval($expage) . ' ';
				else
					$exclusions .= ' AND ID <> ' . intval($expage) . ' ';
			}
		}
	}
	if (!empty($exclusions)) 
		$exclusions .= ')';

	$author_query = '';
	if (!empty($authors)) {
		$post_authors = preg_split('/[\s,]+/',$authors);
		
		if ( count($post_authors) ) {
			foreach ( $post_authors as $post_author ) {
				//Do we have an author id or an author login?
				if ( 0 == intval($post_author) ) {
					$post_author = get_userdatabylogin($post_author);
					if ( empty($post_author) )
						continue;
					if ( empty($post_author->ID) )
						continue;
					$post_author = $post_author->ID;
				}

				if ( '' == $author_query )
					$author_query = ' post_author = ' . intval($post_author) . ' ';
				else
					$author_query .= ' OR post_author = ' . intval($post_author) . ' ';
			}
			if ( '' != $author_query )
				$author_query = " AND ($author_query)";
		}
	}

	$query = "SELECT * FROM $wpdb->posts " ;
if($navigation_type != "")
	$query .= " RIGHT JOIN  navigation_types nt ON ".$wpdb->posts.".ID = post_id" ;
	$query .= ( empty( $meta_key ) ? "" : ", $wpdb->postmeta " ) ; 
	$query .= " WHERE (post_type = 'page' AND post_status = 'publish') $exclusions $inclusions " ;
	$query .= ( empty( $meta_key ) | empty($meta_value)  ? "" : " AND ($wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '$meta_key' AND $wpdb->postmeta.meta_value = '$meta_value' )" ) ;
if($navigation_type != "")	
	$query .= " AND nt.navigation_type = '$navigation_type'" ;
	$query .= $author_query;
	$query .= " ORDER BY " . $sort_column . " " . $sort_order ;

	$pages = $wpdb->get_results($query);
	$pages = apply_filters('get_pages', $pages, $r);

	if ( empty($pages) )
		return array();

	// Update cache.
	update_page_cache($pages);

	if ( $child_of || $hierarchical )
		$pages = & get_page_children($child_of, $pages);

	$cache[ $key ] = $pages;
	wp_cache_set( 'get_pages', $cache, 'page' );

	return $pages;
}

function wp_list_navtype_pages($args = '') {
	if ( is_array($args) )
		$r = &$args;
	else
		parse_str($args, $r);

	$defaults = array('depth' => 0, 'show_date' => '', 'date_format' => get_option('date_format'),
		'child_of' => 0, 'exclude' => '', 'title_li' => __('Pages'), 'echo' => 1, 'authors' => '');
	$r = array_merge($defaults, $r);

	$output = '';

	// sanitize, mostly to keep spaces out
	$r['exclude'] = preg_replace('[^0-9,]', '', $r['exclude']);

	// Allow plugins to filter an array of excluded pages
	$r['exclude'] = implode(',', apply_filters('wp_list_pages_excludes', explode(',', $r['exclude'])));

	// Query pages.
	$pages = get_navtype_pages($r);

	if ( !empty($pages) ) {
		if ( $r['title_li'] )
			$output .= '<li class="pagenav">' . $r['title_li'] . '<ul>';

		global $wp_query;
		$current_page = $wp_query->get_queried_object_id();
		$output .= walk_page_tree($pages, $r['depth'], $current_page, $r);

		if ( $r['title_li'] )
			$output .= '</ul></li>';
	}

	$output = apply_filters('wp_list_pages', $output);

	if ( $r['echo'] )
		echo $output;
	else
		return $output;
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
	echo "<script>document.getElementById('post-body').innerHTML = document.getElementById('post-body').innerHTML + '".getNavDiv()."';</script>";
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
	
	'<div class="wrap">' .
	'<div id="navigation" class="postbox">'.
		'<h3>Target Navigation</h3>'.
		'<div class="inside">'.
		  	'<p>'.
			'<strong>Choose what navigation object you want this page to be displayed in:</strong><br/><br/>'. 
			'<input type="checkbox" id="nav_super" name="nav_super" '.getNavType("super", $_GET["post"]).' /> Super Nav<br>'.
			'<input type="checkbox" id="nav_head" name="nav_head" '.getNavType("head", $_GET["post"]).' /> Head Nav<br>'.
			'<input type="checkbox" id="nav_side" name="nav_side" '.getNavType("side", $_GET["post"]).' /> Side Nav<br>'.
			//'<input type="checkbox" id="nav_page" name="nav_page" '.getNavType("page", $_GET["post"]).' /> Page Nav<br>'.
			'<input type="checkbox" id="nav_foot" name="nav_foot" '.getNavType("foot", $_GET["post"]).' /> Foot Nav<br>'.
			'</p>'.
		'</div>'.
	'</div>'.
	'</div>';
	
	return $navigation;	
}

add_action('edit_page_form', 'hookSideBox');
add_action('save_post', 'saveNavType'); 
?>