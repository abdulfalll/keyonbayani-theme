<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function divi_child_theme_enqueue_assets() {
    // 1. Enqueue Parent Theme Style
    wp_enqueue_style( 'divi-parent-style', get_template_directory_uri() . '/style.css' );

// 2. Enqueue Custom CSS (from the root style.css)
    wp_enqueue_style( 'divi-child-custom-style', get_stylesheet_directory_uri() . '/style.css', array('divi-parent-style'), '1.0.1' );

    // 3. Enqueue Custom JavaScript (from assets folder)
    wp_enqueue_script( 'divi-child-custom-js', get_stylesheet_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'divi_child_theme_enqueue_assets' );

// Access to allow SVG upload
function divi_child_allow_svg_upload( $mimes ) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
} 
add_filter( 'upload_mimes', 'divi_child_allow_svg_upload' );
// Add any custom WooCommerce logic below this line

add_filter( 'woocommerce_product_single_add_to_cart_text', 'custom_digital_preorder_button', 10, 2 );
add_filter( 'woocommerce_product_add_to_cart_text', 'custom_digital_preorder_button', 10, 2 );

function custom_digital_preorder_button( $text, $product ) {
    // Check if the product has the preorder tag
    if ( has_term( 'preorder', 'product_tag', $product->get_id() ) ) {
        return __( 'Pre Order Now', 'woocommerce' );
    }
    
    return $text;
}