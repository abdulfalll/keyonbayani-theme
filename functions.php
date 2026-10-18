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

// Custom WooCommerce Logic Below

// 1. Change single product & archive button text to "Buy Now"
add_filter( 'woocommerce_product_single_add_to_cart_text', 'custom_digital_order_button' );
add_filter( 'woocommerce_product_add_to_cart_text', 'custom_digital_order_button' );

function custom_digital_order_button() {
    return __( 'Buy Now', 'woocommerce' );
}

// 2. Display Checkout Checkbox with updated Tooltip text
add_action( 'woocommerce_review_order_before_submit', 'add_custom_checkout_checkbox' );
function add_custom_checkout_checkbox() {
    
    // Updated label and bilingual agreement terms for standard digital fulfillment
    $checkbox_label = 'I acknowledge the order terms. <span class="preorder-tooltip">ⓘ<span class="preorder-tooltiptext"><strong>Order Agreement:</strong><br>I understand that I am placing an order for a digital product. I agree to be charged now and acknowledge that once the download link has been provided, I will lose my right of withdrawal and will not be eligible for a refund.<br><br><strong>Accord de Commande:</strong><br>Je comprends que je passe une commande pour un produit numérique. J\'accepte d\'être débité maintenant et je reconnais qu\'une fois le lien de téléchargement fourni, je perdrai mon droit de rétractation et ne serai plus éligible à un remboursement.</span></span>';

    woocommerce_form_field( 'order_agreement_checkbox', array(
        'type'          => 'checkbox',
        'class'         => array('form-row custom-checkbox'),
        'label_class'   => array('woocommerce-form__label woocommerce-form__label-for-checkbox checkbox'),
        'input_class'   => array('woocommerce-form__input woocommerce-form__input-checkbox input-checkbox'),
        'required'      => true,
        'label'         => $checkbox_label,
    ), WC()->checkout->get_value( 'order_agreement_checkbox' ) );
}

// 3. Validate the checkbox at checkout
add_action( 'woocommerce_checkout_process', 'validate_custom_checkout_checkbox' );
function validate_custom_checkout_checkbox() {
    if ( ! (int) isset( $_POST['order_agreement_checkbox'] ) ) {
        wc_add_notice( __( 'Please acknowledge the order terms to proceed with your purchase.', 'woocommerce' ), 'error' );
    }
}

// 4. Save agreement to order metadata
add_action( 'woocommerce_checkout_update_order_meta', 'save_custom_checkout_checkbox' );
function save_custom_checkout_checkbox( $order_id ) {
    if ( ! empty( $_POST['order_agreement_checkbox'] ) ) {
        update_post_meta( $order_id, '_order_agreement_checkbox', sanitize_text_field( $_POST['order_agreement_checkbox'] ) );
    }
}

// 5. Display agreement status in Admin WooCommerce Order screen
add_action( 'woocommerce_admin_order_data_after_billing_address', 'display_custom_checkbox_in_admin', 10, 1 );
function display_custom_checkbox_in_admin( $order ) {
    $acknowledged = get_post_meta( $order->get_id(), '_order_agreement_checkbox', true );
    if ( $acknowledged ) {
        echo '<p><strong>Order Terms:</strong> <span style="color: green; font-weight: bold;">Acknowledged</span></p>';
    } else {
        echo '<p><strong>Order Terms:</strong> <span style="color: red;">Not Acknowledged</span></p>';
    }
}

// 6. Display payment method on PDF invoices
add_action( 'wpo_wcpdf_after_billing_address', 'wpo_wcpdf_show_payment_method', 10, 2 );
function wpo_wcpdf_show_payment_method( $document_type, $order ) {
    if ( $document_type == 'invoice' ) {
        ?>
        <div class="payment-method">
            <strong>Payment Method:</strong> <?php echo wp_kses_post( $order->get_payment_method_title() ); ?>
        </div>
        <?php
    }
}