<?php

function is_threeam_employee() {
  if ( $_SERVER['REMOTE_ADDR'] === '150.101.23.241' || substr( $_SERVER['REMOTE_ADDR'], 0, 8 ) === '192.168.' ) {
    return true;
  }

  return false;
}

function three_get_template_part( $slug, $name = null, array $variables = array() ) {
	/**
	 * Fires before the specified template part file is loaded.
	 *
	 * The dynamic portion of the hook name, `$slug`, refers to the slug name
	 * for the generic template part.
	 *
	 * @since 3.0.0
	 *
	 * @param string      $slug The slug name for the generic template.
	 * @param string|null $name The name of the specialized template.
	 * @param array       $variables The variables that you'd like to pass to the template.
	 */
	do_action( "get_template_part_{$slug}", $slug, $name );

	$templates = array();
	$name = (string) $name;
	if ( '' !== $name )
	    $templates[] = "{$slug}-{$name}.php";

	$templates[] = "{$slug}.php";

	three_locate_template( $templates, true, false, $variables );
}


function three_locate_template( $template_names, $load = false, $require_once = true, $variables = array() ) {	
  $located = '';
  foreach ( (array) $template_names as $template_name ) {
    if ( !$template_name )
      continue;
    if ( file_exists( STYLESHEETPATH . '/' . $template_name ) ) {
      $located = STYLESHEETPATH . '/' . $template_name;
      break;
    } elseif ( file_exists( TEMPLATEPATH . '/' . $template_name ) ) {
      $located = TEMPLATEPATH . '/' . $template_name;
      break;
    } elseif ( file_exists( ABSPATH . WPINC . '/theme-compat/' . $template_name ) ) {
      $located = ABSPATH . WPINC . '/theme-compat/' . $template_name;
      break;
    }
  }

  if ( $load && '' != $located )
    three_load_template( $located, $require_once, $variables );

  return $located;
}

function three_load_template( $_template_file, $require_once = true, $variables = array() ) {
  global $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;

  if ( is_array( $wp_query->query_vars ) ) {
    extract( $wp_query->query_vars, EXTR_SKIP );
  }

  if ( is_array( $variables ) ) {
  	extract( $variables, EXTR_SKIP );
  }

  if ( isset( $s ) ) {
    $s = esc_attr( $s );
  }

  if ( $require_once ) {
    require_once( $_template_file );
  } else {
    require( $_template_file );
  }
}

function return_first_set_value() {
  foreach ( func_get_args() as $value ) {
    if ( !empty( $value ) )
      return $value;
  }

  return null;
}

function return_if_true( $expression, $return_value ) {
  if ( $expression )
    return $return_value;

  return null;
}

function current_page_url() {
  return ( isset( $_SERVER['HTTPS'] ) ? "https" : "http" ) . "://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
}

function none_of() {
  foreach ( func_get_args() as $value ) {
    if ( $value )
      return false;
  }

  return true;
}

function threeam_year() {
	return date( 'Y' );
}

function the() {
  return wf()->the;
}
