<?php

// launching operation cleanup
add_action( 'init', 'threeam_head_cleanup' );
// A better title
add_filter( 'wp_title', 'rw_title', 10, 3 );
// remove WP version from RSS
add_filter( 'the_generator', 'threeam_rss_version' );
// remove pesky injected css for recent comments widget
add_filter( 'wp_head', 'threeam_remove_wp_widget_recent_comments_style', 1 );
// clean up comment styles in the head
add_action( 'wp_head', 'threeam_remove_recent_comments_style', 1 );
// clean up gallery output in wp
add_filter( 'gallery_style', 'threeam_gallery_style' );

// enqueue base scripts and styles
// add_action( 'wp_enqueue_scripts', 'threeam_scripts_and_styles', 999 );
// ie conditional wrapper

// adding sidebars to Wordpress (these are created in functions.php)
// add_action( 'widgets_init', 'threeam_register_sidebars' );

// cleaning up random code around images
add_filter( 'the_content', 'threeam_filter_ptags_on_images' );
// cleaning up excerpt
add_filter( 'excerpt_more', 'threeam_excerpt_more' );
//custom excerpt length
add_filter('excerpt_length', 'threeam_excerpt_length');

function threeam_head_cleanup() {
	// category feeds
	remove_action( 'wp_head', 'feed_links_extra', 3 );
	// post and comment feeds
	remove_action( 'wp_head', 'feed_links', 2 );
	// EditURI link
	remove_action( 'wp_head', 'rsd_link' );
	// windows live writer
	remove_action( 'wp_head', 'wlwmanifest_link' );
	// previous link
	remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 );
	// start link
	remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );
	// links for adjacent posts
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
	// WP version
	remove_action( 'wp_head', 'wp_generator' );
	// remove WP version from css
	add_filter( 'style_loader_src', 'threeam_remove_wp_ver_css_js', 9999 );
	// remove Wp version from scripts
	add_filter( 'script_loader_src', 'threeam_remove_wp_ver_css_js', 9999 );
}

// A better title
// http://www.deluxeblogtips.com/2012/03/better-title-meta-tag.html
function rw_title( $title, $sep, $seplocation ) {
  global $page, $paged;

  // Don't affect in feeds.
  if ( is_feed() ) return $title;

  // Add the blog's name
  if ( 'right' == $seplocation ) {
    $title .= get_bloginfo( 'name' );
  } else {
    $title = get_bloginfo( 'name' ) . $title;
  }

  // Add the blog description for the home/front page.
  $site_description = get_bloginfo( 'description', 'display' );

  if ( $site_description && ( is_home() || is_front_page() ) ) {
    $title .= " {$sep} {$site_description}";
  }

  // Add a page number if necessary:
  if ( $paged >= 2 || $page >= 2 ) {
    $title .= " {$sep} " . sprintf( __( 'Page %s', 'dbt' ), max( $paged, $page ) );
  }

  return $title;
}

// remove WP version from RSS
function threeam_rss_version() { return ''; }

// remove WP version from scripts
function threeam_remove_wp_ver_css_js( $src ) {
	global $wp_version;
	if ( strpos( $src, 'ver=' . $wp_version ) )
		$src = remove_query_arg( 'ver', $src );
	return $src;
}

// remove injected CSS for recent comments widget
function threeam_remove_wp_widget_recent_comments_style() {
	if ( has_filter( 'wp_head', 'wp_widget_recent_comments_style' ) ) {
		remove_filter( 'wp_head', 'wp_widget_recent_comments_style' );
	}
}

// remove injected CSS from recent comments widget
function threeam_remove_recent_comments_style() {
	global $wp_widget_factory;
	if ( isset( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'] ) ) {
		remove_action( 'wp_head', array( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style' ) );
	}
}

// remove injected CSS from gallery
function threeam_gallery_style( $css ) {
	return preg_replace( "!<style type='text/css'>(.*?)</style>!s", '', $css );
}

// remove the p from around imgs (http://css-tricks.com/snippets/wordpress/remove-paragraph-tags-from-around-images/)
function threeam_filter_ptags_on_images( $content ) {
	return preg_replace( '/<p>\s*(<a .*>)?\s*(<img .* \/>)\s*(<\/a>)?\s*<\/p>/iU', '\1\2\3', $content );
}

// This removes the annoying […] to a Read More link
function threeam_excerpt_more( $more ) {
	global $post;
	// edit here if you like
	return '...  <a class="excerpt-read-more" href="'. get_permalink( $post->ID ) . '" title="'. __( 'Read ', 'threeamtheme' ) . esc_attr( get_the_title( $post->ID ) ).'">'. __( 'Read more &raquo;', 'threeamtheme' ) .'</a>';
}

// Customise the excerpt length
function threeam_excerpt_length( $length ) {
	return 20;
}


