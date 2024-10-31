<?php
/*
Plugin Name: Permalink Validator
Plugin URI: http://wordpress.org/extend/plugins/permalink-validator/
Description: Validates the URL used and if not matching the official permalink then it issues a HTTP 301 or HTTP 404 message.
Author: Rolf Kristensen
Version: 0.7
Author URI: http://smallvoid.com/
*/

function permalink_validator_activate()
{
	if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == 'POST')
		return false;
	if (defined('BBPATH'))
		return false;
	if ( is_404() )
		return false;
	if ( is_preview() )
		return false;
	if ( is_search() )
		return false;	// use noindex in header
	if ( is_date() )
		return false;	// use noindex in header
	if ( function_exists( 'is_tag' ) && is_tag() )
		return false;	// use noindex in header
	if ( is_trackback() )
		return false;
	if ( is_admin() )
		return false;
	if ( is_feed() )
		return false;	// block feeds from being index'ed in robots.txt
	if ( is_comments_popup() )
		return false;
	if ( function_exists( 'is_keyword' ) && is_keyword() )
		return false;	// Jerome�s Keywords (Use noindex in header)

	// Hack to make it easy to exclude one or more special page urls
	//   $excludes = array("/index.php/forum/");
	//   $excludes = array("/forum/", "/photos/");
	$excludes = array();
	foreach($excludes as $exclude) {
		if (strpos($_SERVER['REQUEST_URI'],$exclude)==0) {
			return false;
		}
	}

	return true;
}

// Is frontpage page of posts
function permalink_page_of_posts($page)
{
	if (get_option('show_on_front') && get_option('page_for_posts') && strpos($page,get_page_link(get_option('page_for_posts')))!==FALSE)
		return true;
	else
		return false;
}

function permalink_validator() {
	global $wp_query;
	global $wp_rewrite;
	global $posts;
	
	if (!permalink_validator_activate())
		return;
	
	$page  = ( isset($_SERVER['HTTPS'] ) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://';
	$page .= $_SERVER['HTTP_HOST'];
	$page .= $_SERVER['REQUEST_URI'];

	if ( is_home() )
	{
		$link = get_settings('home') . '/';
		$page_of_posts = permalink_page_of_posts($page);
		if ($page_of_posts===true)
			$link = get_page_link(get_option('page_for_posts'));

		if (is_paged())
		{
			if (count($posts) < 1 || $posts[0]->ID=='')
			{
				$wp_query->set_404();
				return;
			}

			$paged = abs($wp_query->get('paged'));
			if (!$wp_rewrite->using_permalinks())
			{
				if ($paged > 1)
				{
					if ($page_of_posts===true)
						$link .= '&paged=' . $paged;
					else
						$link .= 'index.php?paged=' . $paged;
				}
			}
			else
			{
				if ($paged > 1)
				{
					if ($wp_rewrite->using_index_permalinks() && $page_of_posts===false)
						$link .= 'index.php/';
					$link .= 'page/' . $paged . '/';
				}
			}
		}
			
		if ($link != $page)
		{
			header('HTTP/1.1 301 Moved Permanently');
			header('Status: 301 Moved Permanently');
			header("Location: $link");
			exit(0);
		}
	}
	else if ( is_category() )
	{
		$cat_obj = $wp_query->get_queried_object();
		if (!isset($cat_obj))
		{
			$wp_query->set_404();
			return;
		}

		$link = get_category_link($cat_obj->cat_ID);
		if (is_paged())
		{
			if (count($posts) < 1 || $posts[0]->ID=='')
			{
				$wp_query->set_404();
				return;
			}

			$paged = abs($wp_query->get('paged'));
			if (!$wp_rewrite->using_permalinks())
			{
				if ($paged > 1)
					$link .= '&paged=' . $paged;
			}
			else
			{
				if ($paged > 1)
					$link .= 'page/' . $paged . '/';
			}
		}

		if ($link!=$page)
		{
			header('HTTP/1.1 301 Moved Permanently');
			header('Status: 301 Moved Permanently');
			header("Location: $link");
			exit(0);
		}
	}
	else if ( is_single() )
	{
		if (count($posts) < 1 || $posts[0]->ID=='')
		{
			$wp_query->set_404();
			return;
		}


		if (get_permalink($posts[0]->ID)!=$page)
		{
			$link = get_permalink($posts[0]->ID);
			header('HTTP/1.1 301 Moved Permanently');
			header('Status: 301 Moved Permanently');
			header("Location: $link");
			exit(0);
		}
	}
	else if ( is_page() )
	{
		if (count($posts) < 1 || $posts[0]->ID=='')
		{
			$wp_query->set_404();
			return;
		}

		if (get_option('show_on_front')=='page' && get_option('page_on_front')==$posts[0]->ID)
		{
			// Static frontpage (WP 2.1+)
			if ($page!=(get_settings('home') . '/'))
			{
				$link = get_settings('home');
				header('HTTP/1.1 301 Moved Permanently');
				header('Status: 301 Moved Permanently');
				header("Location: $link");
				exit(0);
			}
			return;
		}

		if (get_page_link($posts[0]->ID)!=$page)
		{
			$link = get_page_link($posts[0]->ID);
			header('HTTP/1.1 301 Moved Permanently');
			header('Status: 301 Moved Permanently');
			header("Location: $link");
			exit(0);
		}
	}
	else
	{
		return;	// Unknown page-type (just pass it through)

		echo $_SERVER['REQUEST_URI'];
		exit(0);

		$wp_query->set_404();
		return;
	}
	return;
}

function fix_request_uri_iis()
{
	global $is_IIS;
	global $wp_rewrite;

	// Only IIS is having problem with REQUEST_URI
	if (!$is_IIS)
		return;

	// Should only fix REQUEST_URI for pages where the validator is used
	if (!permalink_validator_activate())
		return;

	// IIS Mod-Rewrite
	if (isset($_SERVER['HTTP_X_ORIGINAL_URL']))
	{
		$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
		return;
	}
	
	// IIS Isapi_Rewrite
	if (isset($_SERVER['HTTP_X_REWRITE_URL']))
	{
		$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
		return;
	}
	
 	// Use ORIG_PATH_INFO if there is no PATH_INFO 
 	if ( !isset($_SERVER['PATH_INFO']) && isset($_SERVER['ORIG_PATH_INFO']) ) 
		$_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO']; 

	// Simulate REQUEST_URI on IIS	
	if (!empty($_SERVER['PATH_INFO']) && ($_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME']))
	{
		// Some IIS and PHP combinations puts the same value in PATH_INFO and SCRIPT_NAME
		if ($wp_rewrite->using_permalinks())
			$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
		else
			$_SERVER['REQUEST_URI'] = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/')) . '/';
	}
	else
	{
		//  SCRIPT_NAME includes script-path + script-filename
		if ($wp_rewrite->using_index_permalinks())
		{
			// If root then simulate that no script-name was specified
			if (empty($_SERVER['PATH_INFO']))
				$_SERVER['REQUEST_URI'] = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/')) . '/';
			else
				$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
		}
		else
		{
			// If root then simulate that no script-name was specified
			if (empty($_SERVER['PATH_INFO']))
				$_SERVER['REQUEST_URI'] = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/')) . '/';
			else
				$_SERVER['REQUEST_URI'] = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/')) . $_SERVER['PATH_INFO'];
		}
	}
	
	// Append the query string if it exists and isn't null
	if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']))
	{
		$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
	}
}

// Fix for missing slash in category- and page-links when not having trailing slash in post permalink structure (WP 2.2+)
function fix_user_trailingslashit($link, $type) {
	global $wp_rewrite;
	if ($wp_rewrite->using_permalinks() && $wp_rewrite->use_trailing_slashes==false && $type != 'single')
		return trailingslashit($link);
	return $link;
}

if ( function_exists( 'add_filter' ) && function_exists('user_trailingslashit'))
{
	add_filter('user_trailingslashit', 'fix_user_trailingslashit', 66, 2 );
}

if ( function_exists( 'add_action' ) )
{
	add_action('template_redirect', 'permalink_validator');
	add_action('init', 'fix_request_uri_iis');
}
?>
