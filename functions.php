<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function divi_child_theme_enqueue_assets() {
    // 1. Enqueue Parent Theme Style
    wp_enqueue_style( 'divi-parent-style', get_template_directory_uri() . '/style.css' );

    // 2. Enqueue Custom CSS (from assets folder)
    wp_enqueue_style( 'divi-child-custom-style', get_stylesheet_directory_uri() . '/assets/css/custom-styles.css', array('divi-parent-style'), '1.0.0' );

    // 3. Enqueue Custom JavaScript (from assets folder)
    wp_enqueue_script( 'divi-child-custom-js', get_stylesheet_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'divi_child_theme_enqueue_assets' );

// Add any custom WooCommerce logic below this line