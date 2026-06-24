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
        return __( 'Pre-Order Now', 'woocommerce' );
    }
    
    return $text;
}

// 1. Display the checkbox with a Tooltip
add_action( 'woocommerce_review_order_before_submit', 'add_custom_checkout_checkbox' );
function add_custom_checkout_checkbox() {
    
    // The short label + the tooltip HTML containing your bilingual text
    $checkbox_label = 'I acknowledge the pre-order delivery terms. <span class="preorder-tooltip">ⓘ<span class="preorder-tooltiptext"><strong>Pre-Order Agreement:</strong><br>I understand that I am placing a pre-order for a product that will be delivered on or around October 12, 2026. I agree to be charged now and acknowledge that I may cancel my order and receive a full refund up until the moment the digital product is delivered to me. Once the download link has been provided, I will lose my right of withdrawal and will not be eligible for a refund.<br><br><strong>Accord de Précommande:</strong><br>Je comprends que je passe une précommande pour un produit qui sera livré le 12 octobre 2026 ou aux alentours de cette date. J\'accepte d\'être débité maintenant et je reconnais que je peux annuler ma commande et recevoir un remboursement intégral jusqu\'au moment où le produit numérique me sera livré. Une fois le lien de téléchargement fourni, je perdrai mon droit de rétractation et ne serai plus éligible à un remboursement.</span></span>';

    woocommerce_form_field( 'preorder_agreement_checkbox', array(
        'type'          => 'checkbox',
        'class'         => array('form-row custom-checkbox'),
        'label_class'   => array('woocommerce-form__label woocommerce-form__label-for-checkbox checkbox'),
        'input_class'   => array('woocommerce-form__input woocommerce-form__input-checkbox input-checkbox'),
        'required'      => true,
        'label'         => $checkbox_label,
    ), WC()->checkout->get_value( 'preorder_agreement_checkbox' ) );
}

// 2. Validate the checkbox (Stop the transaction if left unchecked)
add_action( 'woocommerce_checkout_process', 'validate_custom_checkout_checkbox' );
function validate_custom_checkout_checkbox() {
    if ( ! (int) isset( $_POST['preorder_agreement_checkbox'] ) ) {
        // This is the error message the customer sees if they forget to check the box
        wc_add_notice( __( 'Please acknowledge the pre-order terms to proceed with your purchase.', 'woocommerce' ), 'error' );
    }
}

// 3. Save the agreement to the backend order details (For your records)
add_action( 'woocommerce_checkout_update_order_meta', 'save_custom_checkout_checkbox' );
function save_custom_checkout_checkbox( $order_id ) {
    if ( ! empty( $_POST['preorder_agreement_checkbox'] ) ) {
        update_post_meta( $order_id, '_preorder_agreement_checkbox', sanitize_text_field( $_POST['preorder_agreement_checkbox'] ) );
    }
}

// 4. Display the customer's agreement on your admin order screen
add_action( 'woocommerce_admin_order_data_after_billing_address', 'display_custom_checkbox_in_admin', 10, 1 );
function display_custom_checkbox_in_admin( $order ) {
    $acknowledged = get_post_meta( $order->get_id(), '_preorder_agreement_checkbox', true );
    if ( $acknowledged ) {
        echo '<p><strong>Pre-Order Terms:</strong> <span style="color: green; font-weight: bold;">Acknowledged</span></p>';
    } else {
        echo '<p><strong>Pre-Order Terms:</strong> <span style="color: red;">Not Acknowledged</span></p>';
    }
}
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