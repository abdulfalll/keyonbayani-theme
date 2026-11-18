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

// 1. Set button text for product 135 and use "Order Now" for other products
add_filter( 'woocommerce_product_single_add_to_cart_text', 'custom_digital_order_button', 10, 2 );
add_filter( 'woocommerce_product_add_to_cart_text', 'custom_digital_order_button', 10, 2 );

function custom_digital_order_button( $text, $product ) {
    if ( $product instanceof WC_Product && 135 === $product->get_id() ) {
        return __( 'Buy Now', 'woocommerce' );
    }

    return __( 'Order Now', 'woocommerce' );
}

// 2. Display Checkout Checkbox with updated Tooltip text
add_action( 'woocommerce_review_order_before_submit', 'add_custom_checkout_checkbox' );
function add_custom_checkout_checkbox() {
    
    // Updated label with 14-day right of withdrawal waiver agreement text
    $checkbox_label = 'I acknowledge the order terms. <span class="preorder-tooltip">ⓘ<span class="preorder-tooltiptext"><strong>Digital Product Purchase Agreement:</strong><br>I understand that I am purchasing a digital product that will be delivered immediately. I acknowledge that I have a 14-day right of withdrawal (cooling-off period) under applicable consumer protection law, and I expressly agree to waive that right and request that the digital product be delivered to me before the 14-day period expires. I understand that once the download link has been provided and I have begun downloading the product, I will lose my right of withdrawal and will not be eligible for a refund.<br><br><strong>Accord d\'Achat de Produit Numérique :</strong><br>Je comprends que j\'achète un produit numérique qui sera livré immédiatement. Je reconnais que je dispose d\'un droit de rétractation de 14 jours conformément à la législation applicable en matière de protection des consommateurs, et j\'accepte expressément de renoncer à ce droit et demande que le produit numérique me soit livré avant l\'expiration de ce délai de 14 jours. Je comprends qu\'une fois le lien de téléchargement fourni et que j\'ai commencé à télécharger le produit, je perdrai mon droit de rétractation et ne serai plus éligible à un remboursement.</span></span>';

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

/**
 * 1. Control which pages display the Bottom Scrapbook Dock.
 *    Excludes /sybg/ so the book page keeps its original top navigation header.
 */
function kb_should_show_bottom_dock() {
	$allowed_pages = array(
		'about-me',
		'signs-from-the-other-side',
		'contact',
		'links',
	);

	return is_front_page()
		|| is_page( $allowed_pages )
		|| ( function_exists( 'is_shop' ) && is_shop() );
}

/**
 * 2. Enqueue the Bottom Dock CSS and JS only on allowed pages.
 */
function kb_enqueue_bottom_dock_assets() {
	if ( ! kb_should_show_bottom_dock() ) {
		return;
	}

	$css_rel_path = '/assets/css/bottom-dock.css';
	$js_rel_path  = '/assets/js/bottom-dock.js';

	$css_full_path = get_stylesheet_directory() . $css_rel_path;
	$js_full_path  = get_stylesheet_directory() . $js_rel_path;

	wp_enqueue_style(
		'kb-bottom-dock-style',
		get_stylesheet_directory_uri() . $css_rel_path,
		array(),
		file_exists( $css_full_path ) ? filemtime( $css_full_path ) : '1.0.0'
	);

	wp_enqueue_script(
		'kb-bottom-dock-script',
		get_stylesheet_directory_uri() . $js_rel_path,
		array(),
		file_exists( $js_full_path ) ? filemtime( $js_full_path ) : '1.0.0',
		true
	);
}
add_action( 'wp_enqueue_scripts', 'kb_enqueue_bottom_dock_assets' );

/**
 * 3. Render the Bottom Dock HTML in wp_footer on allowed pages.
 */
function kb_render_bottom_dock() {
	if ( ! kb_should_show_bottom_dock() ) {
		return;
	}

	$shop_url = function_exists( 'wc_get_page_permalink' )
		? wc_get_page_permalink( 'shop' )
		: home_url( '/shop/' );
	?>
	<div id="kb-bottom-dock" class="kb-dock-wrapper">
		<nav id="kb-dock-panel" class="kb-dock-panel" aria-hidden="true">
			<span class="kb-tape kb-tape--left" aria-hidden="true"></span>
			<span class="kb-tape kb-tape--right" aria-hidden="true"></span>

			<ul class="kb-dock-links">
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>" data-accent="yellow">Home</a></li>
				<li><a href="<?php echo esc_url( home_url( '/sybg/' ) ); ?>" data-accent="pink">My Book</a></li>
				<li><a href="<?php echo esc_url( $shop_url ); ?>" data-accent="mint">Shop</a></li>
				<li><a href="<?php echo esc_url( home_url( '/about-me/' ) ); ?>" data-accent="lavender">About</a></li>
				<li><a href="<?php echo esc_url( home_url( '/signs-from-the-other-side/' ) ); ?>" data-accent="yellow">Signs</a></li>
				<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" data-accent="pink">Contact</a></li>
			</ul>
		</nav>

		<div class="kb-dock-bar">
			<!-- Left Marigold Home Stamp -->
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="kb-dock-stamp" aria-label="Home">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1E1B18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M12 2L14.2 9.2L21.5 11.5L14.2 13.8L12 21L9.8 13.8L2.5 11.5L9.8 9.2L12 2Z" fill="#FFFDF8"/>
				</svg>
			</a>

			<!-- Center Menu Toggle Button -->
			<button
				id="kb-dock-toggle"
				class="kb-dock-toggle"
				type="button"
				aria-expanded="false"
				aria-controls="kb-dock-panel"
			>
				<span class="kb-toggle-label">MENU</span>
				<span class="kb-toggle-icon" aria-hidden="true">✦</span>
			</button>

			<!-- Right Mint Shopping Bag Stamp (WooCommerce) -->
			<?php if ( class_exists( 'WooCommerce' ) ) :
				$cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
			?>
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="kb-dock-cart" aria-label="View Shopping Cart">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1E1B18" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
						<path d="M6 7h12l1 13H5L6 7z" fill="#FFFDF8"/>
						<path d="M9 7V5a3 3 0 0 1 6 0v2"/>
					</svg>
					<span class="kb-cart-count<?php echo ( 0 === $cart_count ) ? ' is-empty' : ''; ?>">
						<?php echo esc_html( $cart_count ); ?>
					</span>
				</a>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'kb_render_bottom_dock' );

/**
 * 4. Keep the Bottom Dock cart badge synced via WooCommerce AJAX fragments.
 */
function kb_dock_cart_count_fragment( $fragments ) {
	if ( ! class_exists( 'WooCommerce' ) || ! WC()->cart ) {
		return $fragments;
	}

	$cart_count  = WC()->cart->get_cart_contents_count();
	$empty_class = ( 0 === $cart_count ) ? ' is-empty' : '';

	ob_start();
	?>
	<span class="kb-cart-count<?php echo esc_attr( $empty_class ); ?>">
		<?php echo esc_html( $cart_count ); ?>
	</span>
	<?php
	$fragments['span.kb-cart-count'] = ob_get_clean();

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'kb_dock_cart_count_fragment' );