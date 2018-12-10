<?php

add_action( 'wp_enqueue_scripts', 'threeam_enqueue_assets' );
function threeam_enqueue_assets() {
    wp_enqueue_style( 'style', get_stylesheet_uri() );
    wp_enqueue_style( 'aleo', '//brick.freetls.fastly.net/Aleo:300,400,700' );
    wp_enqueue_style( 'font-awesome', 'https://use.fontawesome.com/releases/v5.2.0/css/all.css' );

    // wp_enqueue_script( 'main', get_stylesheet_directory_uri() . '/assets/js/main.js', array( 'jquery' ), '1.0.0', true );
    // wp_enqueue_script( 'pjax', get_stylesheet_directory_uri() . '/assets/js/pjax.min.js', array(), '1.0.0', false );

    // wp_enqueue_script( 'grid-polyfill', 'https://rawgit.com/svenvandescheur/css-grid-polyfill/master/src/index.js', array( ), '0.1.0', true );
}
