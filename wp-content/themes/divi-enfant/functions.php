<?php

// ACTIVTATION DU THEME ENFANT

function theme_enqueue_styles() {
 wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );


// SUPPRESSION DU NUMERO DE VERSION DE WORDPRESS

function dc_delete_version() {
  return '';
}
add_filter('the_generator', 'dc_delete_version');


// AJOUT DES ICONES FONT AWESOME

function dc_load_fontawesome() {
  wp_enqueue_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css', null, '4.7.0' );
}
add_action('wp_enqueue_scripts', 'dc_load_fontawesome');


// MASQUER LES ERREURS DE CONNEXION A L'ADMINISTRATION

function wpm_hide_errors() {
	return "L'identifiant ou le mot de passe est incorrect";
}
add_filter('login_errors', 'wpm_hide_errors');

/* Afficher "À partir de" pour les produits variables */
add_filter( 'woocommerce_variable_sale_price_html', 'wpm_variation_price_format', 10, 2 );
add_filter( 'woocommerce_variable_price_html', 'wpm_variation_price_format', 10, 2 );

function wpm_variation_price_format( $price, $product ) {
	//On récupère le prix min et max du produit variable
	$min_price = $product->get_variation_price( 'min', true );
	$max_price = $product->get_variation_price( 'max', true );

	// Si les prix sont différents on affiche "À partir de ..."
	if ($min_price != $max_price){
		$price = sprintf( __( 'À partir de %1$s', 'woocommerce' ), wc_price( $min_price ) );
		return $price;
	// Sinon on affiche juste le prix
	} else {
		$price = sprintf( __( '%1$s', 'woocommerce' ), wc_price( $min_price ) );
		return $price;
	}
}

/* Enregistrement des taxonomies custom Metiers / CPT UI */
function cptui_register_my_taxes() {

	/**
	 * Taxonomy: Métiers.
	 */

	$labels = [
		"name" => esc_html__( "Métiers", "custom-post-type-ui" ),
		"singular_name" => esc_html__( "Métier", "custom-post-type-ui" ),
	];

	
	$args = [
		"label" => esc_html__( "Métiers", "custom-post-type-ui" ),
		"labels" => $labels,
		"public" => true,
		"publicly_queryable" => true,
		"hierarchical" => false,
		"show_ui" => true,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"query_var" => true,
		"rewrite" => [ 'slug' => 'metier', 'with_front' => true, ],
		"show_admin_column" => true,
		"show_in_rest" => true,
		"show_tagcloud" => false,
		"rest_base" => "metier",
		"rest_controller_class" => "WP_REST_Terms_Controller",
		"rest_namespace" => "wp/v2",
		"show_in_quick_edit" => false,
		"sort" => false,
		"show_in_graphql" => false,
	];
	register_taxonomy( "metier", [ "product" ], $args );
}
add_action( 'init', 'cptui_register_my_taxes' );


/* Trad bouton checkout */
function quadlayers_woocommerce_button_proceed_to_checkout() { ?>
<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="checkout-button button alt wc-forward">
<?php esc_html_e( 'Finaliser ma commande', 'woocommerce' ); ?>
</a>
<?php
}


// Traduction de chaines de caracteres

function wpm_traduction($texte) { 
	$texte = str_ireplace('Proceed to checkout', 'Valider la commande', $texte); 
	return $texte; 
} 

add_filter('gettext', 'wpm_traduction'); 
add_filter('ngettext', 'wpm_traduction');