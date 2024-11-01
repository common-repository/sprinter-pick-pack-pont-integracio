<?php
/**
  Plugin Name: Sprinter - Pick Pack Pont integráció
  Plugin URI: https://www.sprinter.hu/woocommerce-plugin/
  Description: Sprinter és Pick Pack Pont csomagküldés integrációja WooCommerce-hez
  Version: 1.3.0
  Author: Sprinter.hu
  Author URI:  https://sprinter.hu
  Text Domain: sprinter
  Domain Path: /lang
  WC requires at least: 4.0
  WC tested up to: 7.9.0
*/

define( 'SP_PPP_PLUGIN_VER', '1.3.0' );
define( 'SP_PPP_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'SP_PPP_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );

require_once('includes/logger.php');
require_once('includes/isnullorempty.php');
require_once('includes/sprinter.php');
require_once('includes/pickpackpont.php');

// Sprinter cserecsomag rendelés előtt a meta_adatokba(SPRINTER-SWAP) beírja a csomag típusát
// add_action('woocommerce_process_shop_order_meta', 'before_checkout_create_order', 20, 2);
add_action('woocommerce_before_order_object_save', 'before_checkout_create_order', 20, 2);

function before_checkout_create_order($order) {
    //Csak akkor van cserecsomag ha nincs kiválasztva a pickpackpont
    // Cserecsomag manuálisan pipálva
    // ha be van pipálva a cserecsomag
    if (isset($_POST['cserecsomag']) && !empty($_POST['cserecsomag']) ) {
        $order_id = $order->get_id();
        // frissítik a rendelés meta adatát
        $order->update_meta_data('parcel_type', 'SPRINTER-SWAP');
        $meta_key = 'parcel_type';
        $meta_value = 'SPRINTER-SWAP';
        add_post_meta($order_id, $meta_key, $meta_value);
        //ha van previous_order_id post és nem üres akkor ezt is beírja a add_post_meta-ba - mert akkor kapcsolható egyz előző rendeléshez
        if(isset($_POST['previous_order_id']) && !empty($_POST['previous_order_id'])){
            $previous_order_id = $_POST['previous_order_id'];
            $meta_key = 'previous_order_id';
            $meta_value = $previous_order_id;
            add_post_meta($order_id, $meta_key, $meta_value);
        }
    }
    else{
        // Automatikus cserecsomag felismerés KATEGÓRIA ALAPJÁN
        // Ha a termékkategória flakoncsere, akkor a rendelés meta adatába(SPRINTER-SWAP) beírja a csomag típusát
        $items = $order->get_items();
        foreach ($items as $item) { 
            
            $product_id = $item->get_product_id();
            if (has_term('flakoncsere', 'product_cat', $product_id)){ 
                $order->update_meta_data('parcel_type', 'SPRINTER-SWAP');
                $order_id = $order->get_id();
                $meta_key = 'parcel_type';
                $meta_value = 'SPRINTER-SWAP';
                add_post_meta($order_id, $meta_key, $meta_value);
                break; 
            }
            //ha van SPRINTER-SWAP post meta data de nincs benne a kategóriába és a $_POST-ba akkor edit-ben kivették belőle a pipát vagy nem is volt pipa
            else{
            
                $order_id = $order->get_id();
                $meta_key = 'parcel_type';
                $meta_value = 'SPRINTER-SWAP';
                delete_post_meta($order_id, $meta_key, $meta_value);
            }
        } 
    }
}


add_action( 'plugins_loaded', 'sprinter_forditas', 0 );

function sprinter_forditas() {

    $locale = apply_filters( 'plugin_locale', get_locale(), 'sprinter' );

	load_textdomain( 'sprinter', WP_LANG_DIR . '/plugins/sprinter-' . $locale . '.mo' );
	load_plugin_textdomain( 'sprinter', false, plugin_basename( dirname( __FILE__ ) ) . '/lang' );
    
}

add_action( 'admin_enqueue_scripts', 'sprinter_admin_css_js' );

function sprinter_admin_css_js() {
    
    wp_enqueue_script( 'sprinter_admin_js', SP_PPP_PLUGIN_DIR_URL.'js/admin.js', array('jquery'), SP_PPP_PLUGIN_VER, false );
    
    $sprinter_admin_adatok = array( 
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'plugin_dir_url' => SP_PPP_PLUGIN_DIR_URL,
        'nincs_kivalasztott_pickpackpont' => __( 'még nincs kiválasztva', 'sprinter' ),
    );
    
    wp_localize_script( 'sprinter_admin_js', 'sprinter_admin_adatok', $sprinter_admin_adatok );
    
    wp_enqueue_style( 'sprinter_admin_css', SP_PPP_PLUGIN_DIR_URL.'css/admin.css', '', SP_PPP_PLUGIN_VER, 'all' );
    
}

add_action( 'wp_enqueue_scripts', 'sprinter_frontend_css_js' );

function sprinter_frontend_css_js() {
    
    wp_enqueue_script( 'sprinter_frontend_js', SP_PPP_PLUGIN_DIR_URL.'js/frontend.js', array('jquery'), SP_PPP_PLUGIN_VER, false );
    
    $sprinter_frontend_adatok = array( 
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'plugin_dir_url' => SP_PPP_PLUGIN_DIR_URL,
        'pickpackpont_cim' => __( 'Pick Pack Pont választó', 'sprinter' ),
        'kivalasztott_pickpackpont' => __( 'Választott Pick Pack Pont:', 'sprinter' ),
        'pickpackpont_kereses' => __( 'Keresés irányítószám alapján', 'sprinter' ),
        'nincs_kivalasztott_pickpackpont' => __( 'még nincs kiválasztva', 'sprinter' ),
        'kosar_pickpackpont_valasztas' => __( 'A Pick Pack Pont kiválasztása a következő lépésben történik.', 'sprinter' ),
    );
    
    wp_localize_script( 'sprinter_frontend_js', 'sprinter_frontend_adatok', $sprinter_frontend_adatok );
    
    wp_enqueue_script( 'sprinter_gmap', 'https://maps.googleapis.com/maps/api/js?key=' . get_option('sprinter_pickpackpont_gmapikey') );

    wp_enqueue_script( 'sprinter_gmap_markerclusterer', 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js' );

    wp_enqueue_style( 'sprinter_frontend_css', SP_PPP_PLUGIN_DIR_URL.'css/frontend.css', '', SP_PPP_PLUGIN_VER, 'all' );

	if( is_checkout() ) {
		wp_enqueue_script( 'postcode', SP_PPP_PLUGIN_DIR_URL.'js/postcode.js', array( 'jquery' ), SP_PPP_PLUGIN_VER, false );
		wp_enqueue_script( 'postcodecity', SP_PPP_PLUGIN_DIR_URL.'js/postcodecity.js', array( 'postcode' ), SP_PPP_PLUGIN_VER, false );
	}

}

add_filter( 'plugin_row_meta', 'sprinter_sitengo_plugin_row_meta', 10, 2 );
 
function sprinter_sitengo_plugin_row_meta( $links, $file ) {    
    if ( plugin_basename( __FILE__ ) == $file ) {
        $row_meta = array(
          'docs'    => __('Fejlesztő partner','sprinter') . ': ' . '<a href="' . esc_url( 'https://sitengo.hu/wordpress-plugin-fejlesztes/' ) . '" target="_blank" >' . '[site&amp;go]' . '</a>'
        );
 
        return array_merge( $links, $row_meta );
    }
    return (array) $links;
}




?>