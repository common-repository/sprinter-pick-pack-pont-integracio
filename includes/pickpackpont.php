<?php

/*
https://www.pickpackpont.hu/pick-pack-pont-kereso/
function receiveMessage(event){  data = event.data; }; window.addEventListener(“message”, receiveMessage, false);
*/

function sprinter_ppp_azonosito ($id) {
    return str_pad($id, 10, "0", STR_PAD_LEFT);
}

add_action( 'woocommerce_shipping_init', 'sprinter_pickpackpont_class', 0 );

function sprinter_pickpackpont_class() {

    class Sprinter_PickPackPont extends WC_Shipping_Method {

    	public function __construct( $instance_id = 0 ) {
    	      
    		$this->id = 'sprinter_pickpackpont';
            $this->instance_id = absint( $instance_id );
    		$this->method_title = __( 'Pick Pack Pont', 'sprinter' );
    		$this->method_description = __( 'Szállítás Pick Pack Pontra', 'sprinter' );

            $this->enabled = "yes";
            
    
            $this->supports = array(
    			'shipping-zones',
    			'instance-settings',
    			'instance-settings-modal',
    		);
    
    		$this->init();
    
    	}
        
    	function init() {
    
    		$this->init_form_fields();
    		$this->init_settings();

            $this->title = $this->get_option( 'title' );
            $this->cost = $this->get_option( 'cost' );

    		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    	}
        
        public function init_form_fields() {
            
    		$this->instance_form_fields = array(
    			'title' => array(
    				'title'       => __( 'Elnevezés', 'sprinter' ),
    				'type'        => 'text',
    				'description' => __( 'A vásárlók által a pénztárban látható elnevezés.', 'sprinter' ),
    				'default'     => __( 'Pick Pack Pont', 'sprinter' ),
    				'desc_tip'    => true,
    			),

                'cost' => array(
                    'title'       => __( 'Ár', 'sprinter' ),
                    'type'        => 'text',
                    'description' => __( 'Szállítás költsége', 'sprinter' ),
                    'desc_tip'    => true,
                ),
    		);
            
    	}
        
       	public function calculate_shipping( $package = array() ) {
    		$this->add_rate( array(
    			'label' 	 => $this->title,
    			'package'    => $package,
    			'cost'       => $this->cost,
    		) );
    	}
        
        public function admin_options() {
            
            if ( ! $this->instance_id ) {
                echo '<h3>';
                    echo $this->get_method_title();
                echo '</h3>';
            }
            
            echo'<p>';
                echo $this->get_method_description();
            echo'</p>';
    
            echo $this->get_admin_options_html();
        }
    
    }
       
}


add_filter( 'woocommerce_shipping_methods', 'sprinter_pickpackpont_szallitasi_mod' );

function sprinter_pickpackpont_szallitasi_mod( $methods ) {
    $methods['sprinter_pickpackpont'] = 'Sprinter_PickPackPont';
    return $methods;
}


function sprinter_pickpackpont_adatok( $filter = '' ) {
    $sprinter_pickpackpontok = array();
    $url = 'https://partner.pickpackpont.hu/stores/ShopList.json';
   
    $sprinter_pickpackpontok_json = sprinter_url_cache($url,1.0,'boltlista','application/json');
    $sprinter_pickpackpontok_tomb = json_decode( $sprinter_pickpackpontok_json, true);  

    foreach ( $sprinter_pickpackpontok_tomb as $sprinter_pickpackpont ){
        
        if( !empty( $filter ) ){
            if( $filter == $sprinter_pickpackpont['zipCode'] ){
                $sprinter_pickpackpontok[] = $sprinter_pickpackpont;
            }
        }
        else{
            $sprinter_pickpackpontok[] = $sprinter_pickpackpont;
        }
    }
    
    return json_encode( $sprinter_pickpackpontok );
}


add_action( 'wp_ajax_sprinter_ajax_szallitasi_mod_ellenorzese', 'sprinter_ajax_szallitasi_mod_ellenorzese' );
add_action( 'wp_ajax_nopriv_sprinter_ajax_szallitasi_mod_ellenorzese', 'sprinter_ajax_szallitasi_mod_ellenorzese' );
function sprinter_ajax_szallitasi_mod_ellenorzese() {
    if ( isset($_REQUEST) ) {
         $sprinter_kivalasztott_szallitasi_mod = sprinter_kivalasztott_szallitasi_mod( 'array' );
         echo json_encode( 
            array(
               'nev' => $sprinter_kivalasztott_szallitasi_mod['name'],
               'tipus' => get_option( 'sprinter_pickpackpont_tipus' )
             ) 
         );
    }
    die();
}


add_action( 'wp_ajax_sprinter_ajax_pickpackpontok', 'sprinter_ajax_pickpackpontok' );
add_action( 'wp_ajax_nopriv_sprinter_ajax_pickpackpontok', 'sprinter_ajax_pickpackpontok' );
function sprinter_ajax_pickpackpontok() {
    
    if ( isset($_REQUEST) ) {
        $sprinter_pickpackpont_adatok_return = sprinter_pickpackpont_adatok( sanitize_text_field($_REQUEST['filter']) );
        echo $sprinter_pickpackpont_adatok_return;
    }
    
    die();
}


function sprinter_kivalasztott_szallitasi_mod( $type = 'array' ) {
        
     $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
     list( $name, $id ) = explode( ':', $chosen_methods[0] );
     
     if( $type == 'array' ){
        return array(
           'name' => $name,
           'id' => $id
         );
     }
     else if( $type == 'name' ){
        return $name;
     }
     else if( $type == 'id' ){
        return $id;
     }  
}


add_action( 'woocommerce_checkout_update_order_meta', 'sprinter_kivalasztott_pickpackpont_mentese' );
function sprinter_kivalasztott_pickpackpont_mentese( $order_id ) {
	if ( ! empty( $_POST['sprinter_kivalasztott_pickpackpont'] ) ) {
        $kivalasztottPPP = sanitize_text_field($_POST['sprinter_kivalasztott_pickpackpont']);
        $kivalasztottPPP = str_replace( "'", '"', $kivalasztottPPP );
        if (sprinter_is_valid_json($kivalasztottPPP)) {
            update_post_meta( $order_id, '_sprinter_kivalasztott_pickpackpont',  $kivalasztottPPP);
        }
    }
}


add_action( 'woocommerce_checkout_process', 'sprinter_kivalasztott_pickpackpont_ellenorzese' );
function sprinter_kivalasztott_pickpackpont_ellenorzese() {
    if( sprinter_kivalasztott_szallitasi_mod('name') == 'sprinter_pickpackpont' ){
        if ( ! $_POST['sprinter_kivalasztott_pickpackpont'] ){
	        wc_add_notice( __( 'Kérjük, válassz Pick Pack Pontot!', 'sprinter' ), 'error' );
	    }
    }
}


add_action( 'save_post', 'sprinter_pickpackpont_modositas', 10, 3 );
function sprinter_pickpackpont_modositas( $post_id, $post, $update ) {
    
    $order = wc_get_order( $post_id );
    $post_type = get_post_type( $post_id );
    if ( $post_type != 'shop_order' ){ 
        return;
    }

    if ( isset( $_POST['sprinter_kivalasztott_pickpackpont'] ) ) {
        $sprinter_kivalasztott_pickpackpont = get_post_meta( $post_id, '_sprinter_kivalasztott_pickpackpont',true );
        if( empty( $sprinter_kivalasztott_pickpackpont ) ) {
            if( !empty( $_POST['sprinter_kivalasztott_pickpackpont'] ) ){
                $order->add_order_note( __( 'Pick Pack Pont hozzáadva.', 'sprinter' ) );
            }
        }
        else{
            if( !empty( $_POST['sprinter_kivalasztott_pickpackpont'] ) ){
                $order->add_order_note( __( 'Pick Pack Pont módosítva.', 'sprinter' ) );
            }
            else{
                $order->add_order_note( __( 'Pick Pack Pont törölve.', 'sprinter' ) );
            }
        }
        update_post_meta( $post_id, '_sprinter_kivalasztott_pickpackpont', sanitize_text_field( $_POST['sprinter_kivalasztott_pickpackpont'] ) );
    }
}


add_action( 'woocommerce_admin_order_data_after_shipping_address', 'sprinter_pickpackpont_admin', 10, 1 );
function sprinter_pickpackpont_admin( $order ){
    
    if( is_object( $order ) ) {
        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
    }
    else{
        $order_id = $order;
    }

	$sprinter_kivalasztott_pickpackpont_json = get_post_meta( $order_id, '_sprinter_kivalasztott_pickpackpont', true );
    echo '<div class="clear"></div>';
    echo '<br/>';
    echo '<div>';
        echo '<h3>';
            echo __( 'Pick Pack Pont', 'sprinter' );
            echo '<span class="sprinter_kivalasztott_pickpackpont_szerkesztes">';
                echo __( 'Szerkesztés', 'sprinter' );
            echo '</span>';
        echo '</h3>';
        echo '<div id="sprinter_pickpackpont_kontener">';
            echo '<div class="sprinter_kivalasztott_pickpackpont_szerkesztes">';

                if( $sprinter_kivalasztott_pickpackpont_json != '' ) {
                    echo '<span class="sprinter_kivalasztott_pickpackpont_torles">';
                        echo __( 'Kiválasztott Pick Pack Pont törlése', 'sprinter' );
                    echo '</span>';
                }
                
                echo '<p>';
                    echo '<div class="sprinter_pickpackpont_filter_kontener">';
                        echo '<input type="text" id="sprinter_pickpackpont_filter" name="sprinter_pickpackpont_filter" value="" placeholder="'.__( 'Keresés irányítószám alapján', 'sprinter' ).'">';
                    echo '</div>';
                    echo '<div class="sprinter_pickpackpont_lista_kontener">';
                        echo '<div class="sprinter_pickpackpont_lista_elemek">';
                        echo '</div>';  
                    echo '</div>';  
                echo '</p>';
            echo '</div>';  
            echo '<p>';
                echo '<div class="sprinter_pickpackpont_kivalasztott_kontener">';
                    echo '<div class="sprinter_pickpackpont_kivalasztott_kontener_cim">';
                        echo '<strong>';
                            echo __( 'Választott Pick Pack Pont:', 'sprinter' );
                        echo '</strong>';
                    echo '</div>';
                
                    if( $sprinter_kivalasztott_pickpackpont_json != '' /*&& sprinter_is_valid_json($sprinter_kivalasztott_pickpackpont_json)*/) {
                        $sprinter_kivalasztott_pickpackpont = json_decode( str_replace( "'", '"', $sprinter_kivalasztott_pickpackpont_json ), true  );
                        echo '<input type="text" id="sprinter_kivalasztott_pickpackpont" name="sprinter_kivalasztott_pickpackpont" sprinter_pickpackpont_kod="'.$sprinter_kivalasztott_pickpackpont['shopCode'].'" value="'.str_replace( '"', "'", json_encode( $sprinter_kivalasztott_pickpackpont ) ).'">';
                
                        echo '<div class="sprinter_pickpackpont_kivalasztott_kontener_adatok ">';
                        echo __('Boltazonostó:') . ' ' . $sprinter_kivalasztott_pickpackpont['shopCode'];
                        echo '<br/>';
                        echo __('Név:') . ' ' . $sprinter_kivalasztott_pickpackpont['shopName'];
                        echo '<br/>';
                        echo __('Típus:') . ' ' . $sprinter_kivalasztott_pickpackpont['shopType'];
                        echo '<br/>';
                        echo __('Irányítószám:') . ' ' . $sprinter_kivalasztott_pickpackpont['zipCode'];
                        echo '<br/>';
                        echo __('Város:') . ' ' . $sprinter_kivalasztott_pickpackpont['city'];
                        echo '<br/>';
                        echo __('Cím:') . ' ' . $sprinter_kivalasztott_pickpackpont['address'];
                        echo '</div>';
                    }
                    else{
                        echo '<input type="text" id="sprinter_kivalasztott_pickpackpont" name="sprinter_kivalasztott_pickpackpont" value="">';
                        echo '<div class="sprinter_pickpackpont_kivalasztott_kontener_adatok sprinter_pickpackpont_kivalasztott_kontener_adatok_ures">';
                            echo __( 'még nincs kiválasztva', 'sprinter' );
                        echo '</div>';
                    }
                echo '</div>';
            $cserecsomag = get_post_meta( $order_id, 'parcel_type', true );
            $elozo_rendeles = get_post_meta( $order_id, 'previous_order_id', true );
            $sprinter_kivalasztott_pickpackpont = get_post_meta( $order_id, '_sprinter_kivalasztott_pickpackpont',true );
            $items = $order->get_items();
            $flakoncategory = false;
            foreach ($items as $item) { 
                
                $product_id = $item->get_product_id();
                if (has_term('flakoncsere', 'product_cat', $product_id)){ 
                    $order->update_meta_data('parcel_type', 'SPRINTER-SWAP');
                    $order_id = $order->get_id();
                    $flakoncategory = true;
                    break; 
                }
            }
            echo '</p>';
            echo '<div class="swapdisplay"'.(!empty($sprinter_kivalasztott_pickpackpont) ? " style='display:none'" : "" ).'>';
            echo '<div>
            <input type="checkbox" id="cserecsomag"'.($flakoncategory ? ' disabled data-sprintercscs="dfh"': '').' name="cserecsomag"'.(isset($cserecsomag) && !empty($cserecsomag) && $cserecsomag == 'SPRINTER-SWAP' ? ' checked' : '').'/>
            <label for="cserecsomag">Cserecsomag</label>
            </div>';
            if(!$flakoncategory){
                echo '<div class="previous_order_id_show" style="display:'.(isset($elozo_rendeles) && !empty($elozo_rendeles) && $cserecsomag == 'SPRINTER-SWAP' ? 'block;' : 'none;').'"><label for="previous_order_id">Az előző rendelés azonosítója</label><br/>';
                echo '<input type="text" id="previous_order_id" name="previous_order_id"'.(isset($elozo_rendeles) && !empty($elozo_rendeles) && $cserecsomag == 'SPRINTER-SWAP' ? ' value='.$elozo_rendeles.'' : '').'> </div>';
            }
        echo '</div>';
        echo '</div>';



    echo '</div>';
}


add_action( 'woocommerce_email_after_order_table', 'sprinter_kivalasztott_pickpackpont_emailben', 10, 1 );
function sprinter_kivalasztott_pickpackpont_emailben( $order ){
    
    if( is_object( $order ) ) {
        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
    }
    else{
        $order_id = $order;
    }

	$sprinter_kivalasztott_pickpackpont = get_post_meta( $order_id, '_sprinter_kivalasztott_pickpackpont',true );
    
	if( !empty( $sprinter_kivalasztott_pickpackpont && sprinter_is_valid_json($sprinter_kivalasztott_pickpackpont)) ) {
        $sprinter_kivalasztott_pickpackpont = json_decode( str_replace( "'", '"', $sprinter_kivalasztott_pickpackpont ), true );
        echo '<div>';
            echo '<h2>';
                echo __( 'Választott Pick Pack Pont', 'sprinter' );
            echo '</h2>';
            echo '<div>';
                    echo '<p>';
                    echo __('Boltazonostó:') . ' ' . $sprinter_kivalasztott_pickpackpont['shopCode'];
                    echo '<br/>';
                    echo __('Név:') . ' ' . $sprinter_kivalasztott_pickpackpont['shopName'];
                    echo '<br/>';
                    echo __('Típus:') . ' ' . $sprinter_kivalasztott_pickpackpont['shopType'];
                    echo '<br/>';
                    echo __('Irányítószám:') . ' ' . $sprinter_kivalasztott_pickpackpont['zipCode'];
                    echo '<br/>';
                    echo __('Város:') . ' ' . $sprinter_kivalasztott_pickpackpont['city'];
                    echo '<br/>';
                    echo __('Cím:') . ' ' . $sprinter_kivalasztott_pickpackpont['qddress'];
                echo '</p>';
            echo '</div>';
            echo '<br/>';
        echo '</div>';
	}
}


add_action( 'woocommerce_thankyou', 'sprinter_kivalasztott_pickpackpont_rendelesnel' );
function sprinter_kivalasztott_pickpackpont_rendelesnel( $order ){
    
    if( is_object( $order ) ) {
        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
    }
    else{
        $order_id = $order;
    }

	$sprinter_kivalasztott_pickpackpont = get_post_meta( $order_id, '_sprinter_kivalasztott_pickpackpont',true );
    
	if( !empty( $sprinter_kivalasztott_pickpackpont ) && sprinter_is_valid_json($sprinter_kivalasztott_pickpackpont) ) {
        $sprinter_kivalasztott_pickpackpont = json_decode( str_replace( "'", '"', $sprinter_kivalasztott_pickpackpont ), true );
        echo '<section class="woocommerce-columns">';
            echo '<h3 class="woocommerce-column__title">';
                echo __( 'Választott Pick Pack Pont', 'sprinter' );
            echo '</h3>';
            echo '<address>';
                echo __('Boltazonostó:') . ' ' . $sprinter_kivalasztott_pickpackpont['shopCode'];
                echo '<br/>';
                echo __('Név:') . ' ' . $sprinter_kivalasztott_pickpackpont['shopName'];
                echo '<br/>';
                echo __('Típu:') . ' ' . $sprinter_kivalasztott_pickpackpont['shopType'];
                echo '<br/>';
                echo __('Irányítószám:') . ' ' . $sprinter_kivalasztott_pickpackpont['zipCode'];
                echo '<br/>';
                echo __('Város:') . ' ' . $sprinter_kivalasztott_pickpackpont['city'];
                echo '<br/>';
                echo __('Cím:') . ' ' . $sprinter_kivalasztott_pickpackpont['address'];
            echo '</address>';
        echo '</section>';
	}
}
    
?>