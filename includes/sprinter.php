<?php

if(!defined('STDIN'))  define('STDIN',  fopen('php://stdin',  'rb'));
if(!defined('STDOUT')) define('STDOUT', fopen('php://stdout', 'wb'));
if(!defined('STDERR')) define('STDERR', fopen('php://stderr', 'wb'));


register_activation_hook( __FILE__, 'sprinter_create_folder' );
function sprinter_create_folder(){
    
    $upload_dir = wp_upload_dir();

    if (!file_exists( $upload_dir['basedir'].'/sprinter' )) {
        mkdir( $upload_dir['basedir'].'/sprinter', 0755, true );
    }
}


function sprinter_get_url() {
    $upload_dir = wp_upload_dir();
    return $upload_dir['baseurl'] . '/sprinter/';
}


function sprinter_get_folder() {
    $upload_dir = wp_upload_dir();
    $upload_dir = $upload_dir['basedir']  . '/sprinter/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    return $upload_dir;
}

function sprinter_get_settings_url() {
    return get_admin_url(null, 'admin.php?page=wc-settings&tab=sprinter');
}

function sprinter_is_valid_json($json) {
    $json=trim($json);
    $s = substr($json,0,1);
    if ($s != '{' && $s != '[') return false;
    @json_decode($json);
    return (json_last_error() === JSON_ERROR_NONE);
}

function sprinter_url_cache($url, $hours, $purpose, $mimetype) {
    $filename=sprinter_get_folder() . $purpose . '.cache';
    $filecontents = '';
    // if (file_exists($filename) && abs(round((strtotime(time()) - strtotime(filemtime($filename)))/3600, 1)) <= abs($hours)) {
    //     $filecontents = file_get_contents($filename);
    // }
    // else {
    //     $filecontents = sprinter_get_url_contents( $url, false );
    //     if (!sprinter_isnullorempty($filecontents)) {
    //         file_put_contents($filename,$filecontents);
    //     }
    // }

    $filecontents = sprinter_get_url_contents( $url, false );
    if (!sprinter_isnullorempty($filecontents)) {
        file_put_contents($filename,$filecontents);
    }

    return sprinter_remove_utf8_bom($filecontents);
}

function sprinter_get_url_contents($url) {
    $response = wp_remote_get( $url );
    if (is_array($response) && !is_wp_error($response)) {
        $contents = wp_remote_retrieve_body( $response );
        if (!sprinter_isnullorempty($contents)) {
            return $contents;
        }
    }
    return null;
}

function sprinter_remove_utf8_bom($text)
{
    $bom = pack('H*','EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return $text;
}


add_filter('manage_edit-shop_order_columns' , 'sprinter_order_column_title', 10);
function sprinter_order_column_title( $columns ) {

    if(  get_option( 'sprinter_allapot' ) == 'yes' ){
        $columns['sprinter'] = '<span><img src="'.SP_PPP_PLUGIN_DIR_URL.'/images/sprinter_ikon.png"></span>';  
    }
    
    return $columns;
}


add_action('manage_shop_order_posts_custom_column' , 'sprinter_order_column', 10, 2 );
function sprinter_order_column( $column_name, $order_id ) {
    if(  get_option( 'sprinter_allapot' ) == 'yes' ){
        switch ( $column_name ) {
            case 'sprinter' :
                sprinter_gombok( $order_id );
                break;
        }
    }
}


function sprinter_gombok( $order_id, $string = false, $finalize = false ){
    $kimenet = '';
    $sprinter_azonosito = get_post_meta( $order_id, '_sprinter_azonosito', true );
    $sprinter_fuvarlevelszam = get_post_meta( $order_id, '_sprinter_fuvarlevelszam', true );
    $sprinter_fuvarlevelszam_cserecsomagra = get_post_meta( $order_id, '_sprinter_fuvarlevelszam_cserecsomag', true );

    $ortderType = get_post_meta( $order_id, 'parcel_type', true );
    $sprinter_kivalasztott_pickpackpont = get_post_meta( $order_id, '_sprinter_kivalasztott_pickpackpont',true );
    
    $kimenet .= '<div class="sprinter_megrendeles_gomb_box_'.$order_id.'">';
    
    if( empty( $sprinter_fuvarlevelszam ) ){
        $kimenet .= '<a class="button sprinter_button_megrendeles" order_id="'.$order_id.'" >'.__( 'Megrendelés', 'sprinter' ).'</a>';
    }
    else if( !empty( $sprinter_fuvarlevelszam ) ){
        $kimenet .= '<a class="button sprinter_button_ujrarendeles" order_id="'.$order_id.'" >'.__( 'Újrarendelés', 'sprinter' ).'</a>';
        $kimenet .= '<p>';
        $kimenet .= '<span><strong>'.__( 'Fuvarlevélszám:', 'sprinter' ).'</strong> '.$sprinter_fuvarlevelszam.'</span>';
        $kimenet .= '<br/>';

        if(!sprinter_isnullorempty($sprinter_fuvarlevelszam)) {
            $kimenet .= '<a target="_blank" data-fuvarlevel="1" class="button button-secondary sprinter_button sprinter_button_fuvarlevel" order_id="' . $order_id . '" title="'.__( 'Fuvarlevél', 'sprinter' ).'"></a>';
            $nyomkovetes_url = sprinter_nyomkovetes_url( $sprinter_fuvarlevelszam );
            $kimenet .= '<a target="_blank" class="button button-secondary sprinter_button sprinter_button_nyomkovetes" title="'.__( 'Nyomkövetés', 'sprinter' ).'" href="'.$nyomkovetes_url.'"></a>';
        }
        
        $kimenet .= '</p>';
    }
    if( !empty($ortderType) && empty($sprinter_kivalasztott_pickpackpont) ){
        if($ortderType == 'SPRINTER-SWAP' && !sprinter_isnullorempty($sprinter_fuvarlevelszam_cserecsomagra)){
            $kimenet .= 
            ($ortderType == 'SPRINTER-SWAP' && !sprinter_isnullorempty($sprinter_fuvarlevelszam_cserecsomagra) ? '<div><button type="button" class="cserecsomag_link"><a href="">Cserecsomag megnyitása</a></button></div>': "").'';    
        }
        elseif($ortderType == 'SPRINTER-SWAP' && sprinter_isnullorempty($sprinter_fuvarlevelszam_cserecsomagra)){
            $kimenet .= '<br>Cserecsomagos';
        }
       
        $kimenet .= '<div class="cserecsomag_box"'. (!sprinter_isnullorempty($sprinter_fuvarlevelszam_cserecsomagra) ? ' style="display:none;">' : '>');
        if( !empty( $sprinter_fuvarlevelszam_cserecsomagra ) ){
            $kimenet .= '<p>';
            $kimenet .= '<span><strong>'.__( 'Fuvarlevélszám:', 'sprinter' ).'</strong> '.$sprinter_fuvarlevelszam_cserecsomagra.'</span>';
            $kimenet .= '<br/>';
    
            if(!sprinter_isnullorempty($sprinter_fuvarlevelszam_cserecsomagra)) {
                $kimenet .= '<a target="_blank" data-fuvarlevel="2" class="button button-secondary sprinter_button sprinter_button_fuvarlevel" order_id="' . $order_id . '" title="'.__( 'Fuvarlevél', 'sprinter' ).'"></a>';
                $nyomkovetes_url = sprinter_nyomkovetes_url( $sprinter_fuvarlevelszam_cserecsomagra );
                $kimenet .= '<a target="_blank" class="button button-secondary sprinter_button sprinter_button_nyomkovetes" title="'.__( 'Nyomkövetés', 'sprinter' ).'" href="'.$nyomkovetes_url.'"></a>';
            }
            
            $kimenet .= '</p>';
        }
        $kimenet .= '</div>';
    }
    $kimenet .= '</div>';
   
    if( $string == true ){
        return $kimenet;
    }
    else{
        echo $kimenet;
    }
}


function sprinter_nyomkovetes_url( $sprinter_fuvarlevelszam ){
    return str_replace('{barcode}',$sprinter_fuvarlevelszam,'https://www.sprinter.hu/csomagkereso/?bc={barcode}');
}


add_action("add_meta_boxes", "sprinter_order_meta_box");
function sprinter_order_meta_box(){
    if(  get_option( 'sprinter_allapot' ) == 'yes' ){
        add_meta_box("sprinter_meta_box", '<div class="sprinter_order_meta_box_fejlec"><img src="'.SP_PPP_PLUGIN_DIR_URL.'/images/sprinter_ikon.png" /><span></span></div>', 'sprinter_order_meta_box_content', 'shop_order', 'side', 'high', null);
    }
}


function sprinter_order_meta_box_content( $order ){
    if(  get_option( 'sprinter_allapot' ) == 'yes' ){
        if( !empty( $order->ID ) ){
            sprinter_gombok( $order->ID );
        }
    }
}


add_filter( 'woocommerce_settings_tabs_array', 'sprinter_add_settings_tab', 50 );
function sprinter_add_settings_tab( $settings_tabs ) {
    $settings_tabs['sprinter'] = 'Sprinter / Pick Pack Pont';
    return $settings_tabs;
}


add_action( 'woocommerce_settings_tabs_sprinter', 'sprinter_settings_tab' );
function sprinter_settings_tab() {
    echo '<table class="form-table">';
    woocommerce_admin_fields( sprinter_get_settings() );
    echo '</table>';
}


add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'sprinter_action_links' );
function sprinter_action_links( $links ) {
   $links[] = '<a href="'. sprinter_get_settings_url() .'">'.__( 'Beállítások', 'sprinter' ).'</a>';
   return $links;
}


add_action( 'woocommerce_update_options_sprinter', 'sprinter_update_settings' );
function sprinter_update_settings() {
    woocommerce_update_options( sprinter_get_settings() );
}


function sprinter_get_settings() {

    $settings = array(
        'sprinter_section_1' => array(
            'name'     => __( 'Alapbeállítások', 'sprinter' ),
            'type'     => 'title',
        ),
            'sprinter_allapot' => array(
                'title'   => __( 'Bekapcsolás / Kikapcsolás', 'sprinter' ),
                'type'    => 'checkbox',
                'default' => 'no',
                'desc' => __( 'Sprinter / Pick Pack Pont engedélyezése', 'sprinter' ),
                'desc_tip'    => false,
                'id'      => 'sprinter_allapot'
            ),
            'sprinter_cimke_meret' => array(
                'title'   => __( 'Címke méret', 'sprinter' ),
                'type'    => 'radio',
                'default' => 'a4',
                'options'     => array( "a4" => "A4", "a6" => "A6"),
                'desc' => __( 'Címke méret kiválasztása: A4 / A6-os', 'sprinter' ),
                'desc_tip'    => false,
                'id'      => 'sprinter_cimke_meret'
            ),
            'sprinter_kornyezet_url' => array(
                'title'       => __( 'Környezet', 'sprinter' ),
                'type'        => 'radio',
                'default'     => 'teszt',
                'options'     => array( "teszt" => "teszt", "eles" => "éles"),
                'desc' => __( 'Teszt vagy éles Sprinter / Pick Pack Pont környezet kiválsztása. Ha a teszt az aktív, partnerkód és token megadása nem szükséges.', 'sprinter' ),
                'desc_tip'    => false,
                'id'      => 'sprinter_kornyezet'
            ),
            'sprinter_partnerkod' => array(
                'title'       => __( 'Partnerkód', 'sprinter' ),
                'type'        => 'text',
                'default'     => '',
                'desc' => __( 'Sprinter / Pick Pack Pont partnerkód', 'sprinter' ),
                'desc_tip'    => false,
                'placeholder' => 'P000000001',
                'id'      => 'sprinter_partnerkod'
            ),
            'sprinter_token' => array(
                'title'       => __( 'Token', 'sprinter' ),
                'type'        => 'text',
                'default'     => '',
                'desc' => __( 'Sprinter / Pick Pack Pont API token', 'sprinter' ),
                'placeholder' => 'a0bcd1ef23',
                'desc_tip'    => false,
                'id'      => 'sprinter_token'
            ),
            'sprinter_prefix' => array(
                'title'       => __( 'Prefix', 'sprinter' ),
                'type'        => 'text',
                'default'     => '',
                'desc' => __( 'Sprinter / Pick Pack Pont vonalkódok prefixe', 'sprinter' ),
                'placeholder' => 'XYZ',
                'desc_tip'    => false,
                'id'      => 'sprinter_prefix'
            ),
            'sprinter_debug' => array(
                'title'       => __( 'Debug mód', 'sprinter' ),
                'type'        => 'checkbox',
                'default'     => 'no',
                'desc' => __( 'Az API kérések és válaszok megjelenítése', 'sprinter' ),
                'desc_tip'    => false,
                'id'      => 'sprinter_debug'
            ),
            'sprinter_fizetesi_modok' => array(
                'title'       => __( 'További utánvétes fizetési módok', 'sprinter' ),
                'type'        => 'multiselect',
                'options'     => sprinter_engedelyezett_fizetesi_modok(),
                'desc'        => __( 'A bővítmény automatikusan kiválasztja az alapértelmezett utánvétes fizetési módot:', 'sprinter' ).' <strong>'.sprinter_engedelyezett_fizetesi_modok( 'cod' ).'</strong>.'.
                                '<br/>'.__( 'Amennyiben webáruházában további fizetési módok is utánvétesnek számítanak, úgy itt állíthatja be azokat.', 'sprinter' ),
                'id'      => 'sprinter_fizetesi_modok',
            ),
        'sprinter_section_1_end' => array(
             'type' => 'sectionend'
        ),
        
        
        'sprinter_section_2' => array(
            'name'     => __( 'Nyilatkozatok', 'sprinter' ),
            'type'     => 'title',
            'desc'     => '',
        ),
            'sprinter_szallitasbol_kizart' => array(
                'title'   => __('Szállításból kizárt termékek'),
                'type'    => 'checkbox',
                'default' => 'no',
                'desc' => __( 'Nyilatkozom, hogy nem küldök szállításból kizárt terméket', 'sprinter' ).'<br>'.
                    __( 'Szállításból kizárt termékek:', 'sprinter' ).' '.
                    '<a target="_blank" href="http://www.sprinter.hu/segedanyagok/dijakaszf/ ">http://www.sprinter.hu/segedanyagok/dijakaszf/</a><br>'.
                    __('Logisztikai útmutató csomagoláshoz:', 'sprinter').' '.
                    '<a target="_blank" href="http://www.sprinter.hu/segedanyagok/logisztikai-utmutato/">http://www.sprinter.hu/segedanyagok/logisztikai-utmutato/</a>',
                'desc_tip'    => true,
                'id'      => 'sprinter_szallitasbol_kizart'
            ),
            'sprinter_aszf' => array(
                'title'   => __('ÁSZF'),
                'type'    => 'checkbox',
                'default' => 'no',
                'desc' => __( 'Nyilatkozom, hogy ismerem és elfogadom a Sprinter Futárszolgálat Kft. Általános Szerződési Feltételeit', 'sprinter' ).'<br>'.
                    '<a target="_blank" href="https://sprinter.hu/aszf">https://sprinter.hu/aszf</a>',
                'desc_tip'    => true,
                'id'      => 'sprinter_aszf'
            ),
        'sprinter_section_2_end' => array(
             'type' => 'sectionend'
        ),

        'sprinter_section_3' => array(
            'name'     => __( 'Pick Pack Pont beállítások', 'sprinter' ),
            'type'     => 'title',
        ),
            'sprinter_pickpackpont_meret' => array(
                'title'   => __( 'Csomagméret', 'sprinter' ),
                'type'    => 'radio',
                'default' => 'L',
                'options' => array( 'S' => __( 'S', 'sprinter' ), 'M' => __( 'M', 'sprinter' ), 'L' => __( 'L', 'sprinter' ), 'XL' => __( 'XL', 'sprinter' ) ),
                'desc' => __('A Pick Pack Pontra feladott csomagok mérete'),
                'desc_tip'    => false,
                'id'      => 'sprinter_pickpackpont_meret'
            ),
            'sprinter_pickpackpont_tipus' => array(
                'title'   => __( 'Kereső típusa', 'sprinter' ),
                'type'    => 'radio',
                'default' => 'lista',
                'options' => array( 'lista' => __( 'Lista', 'sprinter' ), 'terkep' => __( 'Térkép', 'sprinter' ) ),
                'desc' => __('Megadhatja, hogy a vásárlók listából vagy térképesen választhassák ki az átvételi Pick Pack Pontot'),
                'desc_tip'    => false,
                'id'      => 'sprinter_pickpackpont_tipus'
            ),
            'sprinter_pickpackpont_gmapikey' => array(
                'title'   => __( 'Google Maps API kulcs térképes keresőhöz', 'sprinter' ),
                'type'    => 'text',
                'default' => '',
                'desc' => __('Adja meg a Google Maps API kulcsát a térképes Pick Pack Pont kereső megfelelő működéséhez','sprinter'),
                'desc_tip'    => false,
                'id'      => 'sprinter_pickpackpont_gmapikey'
            ),
            'sprinter_pickpackpont_pos2pos' => array(
                'title'       => __( 'Csomagpont azonosító bolti csomagfeladáshoz', 'sprinter' ),
                'type'        => 'text',
                'default'     => '',
                'desc' => __( 'Amennyiben nem raktárban, hanem Pick Pack Ponton adja fel csomagjait, itt adja meg a kiinduló Pick Pack Pont kódját.','sprinter').'<br/>'. 
                            '<strong>' . __('NORMÁL RAKTÁRI FELADÁS ESETÉN EZT A MEZŐT HAGYJA ÜRESEN!', 'sprinter' ) . '</strong>',
                'placeholder' => '123456',
                'desc_tip'    => false,
                'id'      => 'sprinter_pickpackpont_pos2pos'
            ),
        'sprinter_section_3_end' => array(
             'type' => 'sectionend'
        ),

        'sprinter_section_4' => array(
            'name'     => __( 'Sprinter házhozszállítási beállítások', 'sprinter' ),
            'type'     => 'title',
        ),
            'sprinter_hosszusag' => array(
                'title'       => __( 'Csomag hosszúság', 'sprinter' ),
                'type'        => 'text',
                'default'     => '20',
                'desc' => __( 'Sprinter csomag alapértelmezett hosszúsága', 'sprinter' ),
                'placeholder' => '20',
                'desc_tip'    => false,
                'id'      => 'sprinter_hosszusag'
            ),
            'sprinter_szelesseg' => array(
                'title'       => __( 'Csomag szélesség', 'sprinter' ),
                'type'        => 'text',
                'default'     => '20',
                'desc' => __( 'Sprinter csomag alapértelmezett xzélessége', 'sprinter' ),
                'placeholder' => '20',
                'desc_tip'    => false,
                'id'      => 'sprinter_szelesseg'
            ),
            'sprinter_magassag' => array(
                'title'       => __( 'Csomag magasság', 'sprinter' ),
                'type'        => 'text',
                'default'     => '20',
                'desc' => __( 'Sprinter csomag alapértelmezett magassága', 'sprinter' ),
                'placeholder' => '20',
                'desc_tip'    => false,
                'id'      => 'sprinter_magassag'
            ),
            'sprinter_suly' => array(
                'title'       => __( 'Csomag súlya', 'sprinter' ),
                'type'        => 'text',
                'default'     => '2',
                'desc' => __( 'Sprinter csomag alapértelmezett súlya', 'sprinter' ),
                'placeholder' => '2',
                'desc_tip'    => false,
                'id'      => 'sprinter_suly'
            ),
            'sprinter_csv_feladas_helye' => array(
                'title'   => __( 'Feladás helye', 'sprinter' ),
                'type'    => 'radio',
                'default' => 'lista',
                'options' => array( 'telephely' => __( 'Telephely', 'sprinter' ), 'depo' => __( 'Depó', 'sprinter' ) ),
                'desc' => __('Ez a beállítás csak a tömeges CSV fájl előállításához szükséges.') . '<br/>' . __('Megadhatja, hogy a csomagokat a Sprinter futár a telephelyéről veszi fel, vagy Ön szállítja be depóba.'),
                'desc_tip'    => false,
                'id'      => 'sprinter_csv_feladas_helye'
            ),
        'sprinter_section_4_end' => array(
             'type' => 'sectionend'
        ),
       
        'sprinter_section_5' => array(
            'name'     => __( 'PDF/CSV dokumentumok', 'sprinter' ),
            'type'     => 'title',
            'desc'     => __( 'Amennyiben törölni szeretné az eddig készült PDF és CSV dokumentumokat a tárhelyéről, kattintson a Törlés gombra.', 'sprinter' ).'<br/><p><a class="button sprinter_fuvarlevelek_torles_gomb">'.__( 'Törlés', 'sprinter' ).'</a></p>',
        ),
        'sprinter_section_5_end' => array(
             'type' => 'sectionend'
        ),

        'sprinter_section_6' => array(
            'name'     => __( 'Hasznos információk', 'sprinter' ),
            'type'     => 'title',
            'desc'     => __( 'További hasznos információkért látogassa meg az alábbi oldalt:', 'sprinter' ).'<br><a target="_blank" href="https://www.sprinter.hu/woocommerce-plugin/">https://www.sprinter.hu/woocommerce-plugin</a>',
        ),
        'sprinter_section_6_end' => array(
             'type' => 'sectionend'
        ),
                
    );

    return apply_filters( 'sprinter_settings', $settings );
}    
    
function sprinter_all($array, $predicate) {
    return array_filter($array, $predicate) === $array;
}

function sprinter_any($array, $predicate) {
    return array_filter($array, $predicate) !== array();
}
    
function sprinter_ajax_megrendeles() {
    if ( isset($_REQUEST) ) {
        $order_id = wc_sanitize_order_id($_REQUEST['order_id']);
        if (is_numeric($order_id) || sprinter_all($order_id,'is_numeric')) {
            $context = sanitize_text_field($_REQUEST['context']);
            $custom_parcel_count = sanitize_text_field($_REQUEST['custom_parcel_count']);
            $bulk_wsh = sanitize_text_field($_REQUEST['bulk_wsh']);
            $fuvarlevel_index = sanitize_text_field($_REQUEST['fuvarlevel_index']);
            if (is_string($context) && 
                    (   $context === 'veglegesites' ||
                        $context === 'dokumentumok' ||
                        $context === 'vonalkod' ||
                        $context === 'megrendeles' ||
                        $context === 'ujrarendeles' ) ||
                        $context === '') {
                $sprinter_megrendeles_return = sprinter_megrendeles( $order_id, $context, $custom_parcel_count, $bulk_wsh,$fuvarlevel_index );
                echo $sprinter_megrendeles_return;
            }
        }
    }
    die();
}


add_action( 'wp_ajax_sprinter_megrendeles', 'sprinter_ajax_megrendeles' );

function sprinter_megrendeles_torlese( $order_id ) {
                    
    $sprinter_azonosito = get_post_meta( $order_id, '_sprinter_azonosito', true );
    $sprinter_fuvarlevelszam = get_post_meta( $order_id, '_sprinter_fuvarlevelszam', true );  
    
    if( $sprinter_azonosito ){
        update_post_meta( $order_id, '_sprinter_azonosito', '' );
    }
    
    if( $sprinter_fuvarlevelszam ){
        update_post_meta( $order_id, '_sprinter_fuvarlevelszam', '' );

        $fajlnev_dir = sprinter_get_folder().$sprinter_fuvarlevelszam.'.pdf';
    
        if( file_exists( $fajlnev_dir ) ){
            unlink( $fajlnev_dir );
        }
    }
}

function sprinter_fuvarlevel_link($flv) {
    if (sprinter_isnullorempty($flv)) return '';
    if (file_exists(sprinter_get_folder() . $flv . '.pdf')) {
        $linkHtml='<a target="_blank" href="' . sprinter_get_url() . $flv . '.pdf">' . $flv . '</a>';
    }
    else {
        $linkHtml=$flv;
    }
    return $linkHtml;
}

function sprinter_megrendeles( $order_id, $context, $custom_parcel_count = null, $bulk_wsh = false, $fuvarlevel_index = 0) {
    //fuvarlevél index az a fuvarlevél megnyomás után tárolja el hogy melyik fuvarlevelet akarjuk
    //1 az a sima, 2 a cserecsomagos, 0 meg a sima generálás
    require_once('pudo/PudoServices.php');
    
    $orders = array();
    $pudoResult = array();

    $csomagreg=false;
    $dokulek=false;
    $flvment=false;
    $atveteliment=false;    

    switch ($context)
    {
        case 'veglegesites':
            $csomagreg=true;        $dokulek=false;     $flvment=false;     $atveteliment=false;    
            break;
        case 'dokumentumok':
            $csomagreg=false;       $dokulek=true;      $flvment=true;      $atveteliment=true;    
            break;
        case 'vonalkod':
            $csomagreg=false;       $dokulek=true;      $flvment=true;      $atveteliment=false;    
            break;
        case 'megrendeles':
        case 'ujrarendeles':
        default:
            $csomagreg=true;        $dokulek=false;     $flvment=false;     $atveteliment=false;    
            break;
    }

    $params=array();
    $params['ctx']=$context;
    $params['csom']=$csomagreg;
    $params['dok']=$dokulek;
    $params['flv']=$flvment;
    $params['atv']=$atveteliment;
    $params['custom_parcel_count']=$custom_parcel_count;

    if (is_array($order_id)) {
        $bulk=true;
        foreach ($order_id as $oid) {
            $order = wc_get_order( $oid );
            $orders[] = $order;          
        }
    }
    else {
        $bulk=false;
        $order = wc_get_order( $order_id );
        $orders[] = $order;
    }

    $sprinter_megrendeles_return = array();

    $test = ''; 

    if( get_option( 'sprinter_kornyezet' ) == 'teszt' ){
        $test = __('(TESZT) ');   
    }

    if (get_option('sprinter_szallitasbol_kizart')!='yes' || get_option('sprinter_aszf')!='yes') {
        $pudoResultItem['status'] = 'NOK';
        $pudoResultItem['message'] = __('A bővítmény beállításainál nyilatkoznia kell, hogy nem ad fel szállításból kizárt terméket, valamint elfogadja az ÁSZF-et.','sprinter') . '<br/>' 
                            . '<a href="' . sprinter_get_settings_url() . '">' .  __('Beállítások','sprinter') . '</a>';
        $pudoResult[]=$pudoResultItem;
    }
    else {

        // order-type meghatározása
        $orderType = get_post_meta( $order_id, 'parcel_type', true );

        // ha sprinter-swap akkor két fuvarlevél kell (cserecsomag), és nem vonalkódos megrendelés azaz fuvarlevel_index nem 1 vagy 2 értékű 
        if($fuvarlevel_index == 1 || $fuvarlevel_index == 2){
            $pudoResult[] = PudoRequest($orders, $params, $fuvarlevel_index, $deliverytype='');
        }
        else{
            if($orderType == 'SPRINTER-SWAP'){ 
                $pudoResult[] = PudoRequest($orders, $params, $fuvarlevelindex = 0, $deliverytype='sima');
                $pudoResult[] = PudoRequest($orders, $params, $fuvarlevelindex = 0, $deliverytype= 'visszaru');
            }
            else{
                $pudoResult[] = PudoRequest($orders, $params, $fuvarlevelindex = 0, $deliverytype='');
            }
        }
           
          
    }

    foreach($pudoResult as $index => $pudoResultInner){
        for ($i=0; $i<count($pudoResultInner); $i++) {
            $resultArray[$index][]=$pudoResultInner[$i];
        }
    }
    

    $retArray = array();
    $realOrderId = 0;

    $generated_gomb_view = false;

    foreach($pudoResult as $index => $pudoResultInner){
        for ($key = 0; $key<count($resultArray[$index]); $key++) {
            $result = $resultArray[$index][$key];
            $realOrderId = $result["order_id"];

            $sprinter_megrendeles_return = array();

            if( get_option( 'sprinter_debug' ) == 'yes' ){
                $sprinter_megrendeles_return['debug'] = '<strong>'.$test.__( 'Sprinter API kérés:', 'sprinter' ).'</strong><pre>'.$result["apirequest"].'</pre><strong>'.$test.__( 'Sprinter API válasz:', 'sprinter' ).'</strong><pre>'.$result["apiresponse"].'</pre>';
            }

            if ( $result["status"] == "NOK" ) {
                $sprinter_megrendeles_return['sikertelen'] = '<strong>'.$test.__( 'Sikertelen Sprinter megrendelés.', 'sprinter' ).'</strong> '.__( 'Webshop rendelési azonosító:', 'sprinter' ).' '.$realOrderId;
                $sprinter_megrendeles_return['hiba'] = '<strong>'.$test. __( 'Webshop rendelési azonosító:', 'sprinter' ).' '.$realOrderId . ' ' . __( 'Hibaüzenet:', 'sprinter' ).'</strong> '.$result["message"];
                $order->add_order_note( '<strong>'.$test.__( 'Sprinter hibaüzenet:', 'sprinter' ).'</strong> '.$result["message"] );   
            }
            else if( $result["status"] == "OK" ){
                $url = sprinter_get_url();

                if( $result['message']!='' ){
                        $sprinter_megrendeles_return['hiba'] = '<strong>'.$test .__( 'Webshop rendelési azonosító:', 'sprinter' ).' '.$realOrderId . ' ' . __( 'Sprinter hibaüzenet:', 'sprinter' ).'</strong> '.$result['message'];
                }
                    
                update_post_meta( $realOrderId, '_sprinter_azonosito', $result['shipment'] );

                //Ha van cserecsomag akkor a második fuvarlevél számát is elmentjük ami a cserecsomagé lesz
                if($fuvarlevel_index == 1 || $fuvarlevel_index == 2)
                {
                    if($fuvarlevel_index == 1 ){
                        update_post_meta( $realOrderId, '_sprinter_fuvarlevelszam', $result['barcode'] );
                    }
                    else{
                        update_post_meta( $realOrderId, '_sprinter_fuvarlevelszam_cserecsomag', $result['barcode'] );
                    }
                }
                else{
                    if ($index == 0){
                        update_post_meta( $realOrderId, '_sprinter_fuvarlevelszam', $result['barcode'] );
                    }
                    else{
                        update_post_meta( $realOrderId, '_sprinter_fuvarlevelszam_cserecsomag', $result['barcode'] );
                    }
                }     
                $sprinter_megrendeles_return['sikeres'] = '<strong>'.$test.__( 'Sikeres Sprinter megrendelés.', 'sprinter' ).'</strong>  '.__( 'Webshop rendelési azonosító:', 'sprinter' ).' '.$realOrderId.'. '.
                __( 'Fuvarlevélszám:', 'sprinter' ).' ' . $result['barcode'].'.';
                $sprinter_megrendeles_return['azonosito']=$result['shipment'];
                $sprinter_megrendeles_return['fuvarlevelszam']=$result['barcode'];
                $sprinter_megrendeles_return['nyomkovetes_url']=sprinter_nyomkovetes_url($result['barcode']);
                $sprinter_megrendeles_return['fajlnev_url'] = $url . $result['barcode_file'] . '.pdf';
                $sprinter_megrendeles_return['fajlnev']=$result['barcode_file'];
                if (!sprinter_isnullorempty($result['shipment'])) {
                    $sprinter_megrendeles_return['shipment_url']= $url . $result['shipment'] . '.pdf';
                }

                $order->add_order_note( 
                    sprintf( 
                            $test.__( 'Sikeres Sprinter megrendelés. Fuvarlevélszám: %s.', 'sprinter' ),
                            $result['barcode'])
                );
            }
            else {
                $sprinter_megrendeles_return['sikertelen'] = '<strong>'.$test.__( 'Hiba: üres visszatérési státusz.', 'sprinter' ).'</strong> '.__( 'Webshop rendelési azonosító:', 'sprinter' ).' '.$realOrderId;
                $sprinter_megrendeles_return['hiba'] = '<strong>'.$test. __( 'Webshop rendelési azonosító:', 'sprinter' ).' '.$realOrderId . ' ' . __( 'Hibaüzenet:', 'sprinter' ).'</strong> '.$result["message"];
            }

            // if($orderType == 'SPRINTER-SWAP' && $index == 1) {
            //     $sprinter_megrendeles_return['sprinter_gombok'] = sprinter_gombok( $realOrderId, true, $params );
            //     $retArray[]=$sprinter_megrendeles_return;
            // }
            // elseif($orderType != 'SPRINTER-SWAP' && $index == 0) {
            //     $sprinter_megrendeles_return['sprinter_gombok'] = sprinter_gombok( $realOrderId, true, $params );
            //     $retArray[]=$sprinter_megrendeles_return;
            // }
            // elseif($fuvarlevel_index == 1 || $fuvarlevel_index == 2)
            // {
            //     $sprinter_megrendeles_return['sprinter_gombok'] = sprinter_gombok( $realOrderId, true, $params );
            //     $retArray[]=$sprinter_megrendeles_return;
            // }

            // megrendelés esetén mindig az első generált html kell
            // újrarendelés esetén mindig a második generált html kell
            
            
            if($context != 'megrendeles' && $context != 'ujrarendeles'){
                $sprinter_megrendeles_return['sprinter_gombok'] = sprinter_gombok( $realOrderId, true, $params );
                $retArray[]=$sprinter_megrendeles_return;
            }
            else{
                if($generated_gomb_view == false && $index == 0 && $orderType != 'SPRINTER-SWAP'){
                    $sprinter_megrendeles_return['sprinter_gombok'] = sprinter_gombok( $realOrderId, true, $params );
                    $retArray[]=$sprinter_megrendeles_return;
                    $generated_gomb_view = true;
                }
                elseif($generated_gomb_view == false && $index == 1  && $orderType == 'SPRINTER-SWAP'){
                    $sprinter_megrendeles_return['sprinter_gombok'] = sprinter_gombok( $realOrderId, true, $params );
                    $retArray[]=$sprinter_megrendeles_return;
                    $generated_gomb_view = true;
                }
            }

            

        }
        
    }

    // if($context != 'dokumentumok' && !$bulk){
    //     $retArray=$retArray[0];
    // }


    return json_encode( $retArray );
    // return $retArray;
} 


add_filter( 'bulk_actions-edit-shop_order', 'sprinter_bulk_actions' );
function sprinter_bulk_actions( $bulk_array ) {
    
	$bulk_array['sprinter_megrendeles'] = __( 'Sprinter megrendelések véglegesítése', 'sprinter' );
    $bulk_array['sprinter_dokumentumok'] = __( 'Sprinter dokumentumok lekérése', 'sprinter' );
	$bulk_array['sprinter_tomeges_csv'] = __( 'Sprinter tömeges betöltő CSV', 'sprinter' );
    
	return $bulk_array;
}


add_filter( 'handle_bulk_actions-edit-shop_order', 'sprinter_bulk_action_handler', 10, 3 );
function sprinter_bulk_action_handler( $redirect, $doaction, $order_ids ) {
 
	$redirect = remove_query_arg( array( 'sprinter_megrendeles_kesz', 'sprinter_dokumentumok_kesz', 'sprinter_tomeges_csv_kesz' ), $redirect );

	if ( $doaction == 'sprinter_megrendeles' ) {
        foreach ( $order_ids as $order_id ) {
            $sprinter_tomeges_megrendeles_return[] = sprinter_megrendeles( $order_id, 'veglegesites' );
        }
        // $sprinter_tomeges_megrendeles_return = sprinter_tomeges_megrendeles( $order_ids, 'veglegesites' );

		if( !session_id() ){
            session_start();
        }
        
        $_SESSION['sprinter_tomeges_megrendeles_return'] = $sprinter_tomeges_megrendeles_return[0];
        $redirect = add_query_arg('sprinter_megrendeles_kesz', 1, $redirect );
    }
    
    if ( $doaction == 'sprinter_dokumentumok' ) {
        foreach ( $order_ids as $order_id ) {
            $sprinter_fuvarlevel_letoltes_return[] = sprinter_megrendeles( $order_id, 'dokumentumok' );
        }
        // $sprinter_fuvarlevel_letoltes_return = sprinter_tomeges_megrendeles( $order_ids, 'dokumentumok' );

		if( !session_id() ){
            session_start();
        }
        
        // $_SESSION['sprinter_dokumentumok_return'] = $sprinter_fuvarlevel_letoltes_return[0];
        $_SESSION['sprinter_dokumentumok_return'] = $sprinter_fuvarlevel_letoltes_return;
        $redirect = add_query_arg('sprinter_dokumentumok_kesz', 1, $redirect );
    }
    
    if ( $doaction == 'sprinter_tomeges_csv' ) {
        $sprinter_tomeges_csv_return[] = sprinter_tomeges_csv( $order_ids, 'megrendeles' );
        
        if( !session_id() ){
            session_start();
        }

        $_SESSION['sprinter_tomeges_csv_return'] = $sprinter_tomeges_csv_return;
        $redirect = add_query_arg('sprinter_tomeges_csv_kesz', 1, $redirect );
	}
    return $redirect;
}

add_action( 'admin_notices', 'sprinter_bulk_action_notices' );
function sprinter_bulk_action_notices() {

    if(!empty($_REQUEST['sprinter_megrendeles_kesz'])) {

        if(!session_id()) {
            session_start();
        }

        if(isset($_SESSION['sprinter_tomeges_megrendeles_return']) && sprinter_is_valid_json($_SESSION['sprinter_tomeges_megrendeles_return'])) {

            $jsonString = sanitize_text_field($_SESSION['sprinter_tomeges_megrendeles_return']);
            $megrendeles_adatok_array = json_decode($jsonString);

            $uzenetSikeres = '';
            $uzenetSikertelen = '';

            foreach($megrendeles_adatok_array as $adat) {
                $megrendeles_adatok = (array)$adat;

                if(isset($megrendeles_adatok['sikeres'])) {
                    $uzenetSikeres .= '<br/><br/>';
                    $uzenetSikeres .= $megrendeles_adatok['sikeres'];
                    if(isset($megrendeles_adatok['hiba'])) {
                        $uzenetSikeres .= '<br/>';
                        $uzenetSikeres .= $megrendeles_adatok['hiba'];
                    }
                    if(isset($megrendeles_adatok['debug'])) {
                        $uzenetSikeres .= '<br/><br/>';
                        $uzenetSikeres .= $megrendeles_adatok['debug'];
                    }
                } elseif(isset($megrendeles_adatok['sikertelen'])) {
                    $uzenetSikertelen .= '<br/><br/>';
                    $uzenetSikertelen .= $megrendeles_adatok['sikertelen'];
                    if(isset($megrendeles_adatok['hiba'])) {
                        $uzenetSikertelen .= '<br/>';
                        $uzenetSikertelen .= $megrendeles_adatok['hiba'];
                    }
                    if(isset($megrendeles_adatok['debug'])) {
                        $uzenetSikertelen .= '<br/><br/>';
                        $uzenetSikertelen .= $megrendeles_adatok['debug'];
                    }
                } else {
                    if(isset($megrendeles_adatok['hiba'])) {
                        $uzenetSikertelen .= '<br/><br/>';
                        $uzenetSikertelen .= $megrendeles_adatok['hiba'];
                    }
                    if(isset($megrendeles_adatok['debug'])) {
                        $uzenetSikertelen .= '<br/><br/>';
                        $uzenetSikertelen .= $megrendeles_adatok['debug'];
                    }
                }
            }

            if (!sprinter_isnullorempty($uzenetSikeres)) {
                echo '<div class="updated notice is-dismissible sprinter_notice"><div><img src="' . SP_PPP_PLUGIN_DIR_URL . '/images/sprinter_ikon.png" />' . $uzenetSikeres . '</div></div>';
            }
            if (!sprinter_isnullorempty($uzenetSikertelen)) {
                echo '<div class="error notice is-dismissible sprinter_notice"><div><img src="' . SP_PPP_PLUGIN_DIR_URL . '/images/sprinter_ikon.png" />' . $uzenetSikertelen . '</div></div>';
            }

            unset($_SESSION['sprinter_tomeges_megrendeles_return']);
        }
    }

    if(!empty($_REQUEST['sprinter_dokumentumok_kesz'])) {

        if(!session_id()) {
            session_start();
        }

        $listaShp = array();
        $listaFlv = array();
        $linksShp = '';
        $linksFlv = '';

        $uzenetSikeres = '';
        $uzenetSikertelen = '';

        foreach($_SESSION['sprinter_dokumentumok_return'] as $sprinter_dokumentumok_return) {

            if(isset($sprinter_dokumentumok_return) && sprinter_is_valid_json($sprinter_dokumentumok_return)) {

                $jsonString = sanitize_text_field($sprinter_dokumentumok_return);
                $atveteli_adatok_array = json_decode($jsonString);
                $atveteli_adatok_array = (array)$atveteli_adatok_array;

                foreach ($atveteli_adatok_array as $atveteli_adatok) {
                    $atveteli_adatok = (array)$atveteli_adatok;


                    if (!sprinter_isnullorempty($atveteli_adatok['sikeres'])) {
                        $uzenetSikeres .= ('<br/><br/>' . $atveteli_adatok['sikeres']);
                        if(!in_array($atveteli_adatok['shipment_url'], $listaShp)) {
                            $listaShp[] = $atveteli_adatok['shipment_url'];
                            $linksShp .= ('<li><a href="' . $atveteli_adatok['shipment_url'] . '" target="_blank">' . $atveteli_adatok['azonosito'] . '</a>');
                        }
                        if(!in_array($atveteli_adatok['fajlnev_url'], $listaFlv)) {
                            $listaFlv[] = $atveteli_adatok['fajlnev_url'];
                            $linksFlv .= ('<li><a href="' . $atveteli_adatok['fajlnev_url'] . '" target="_blank">' . $atveteli_adatok['fajlnev'] . '</a>');
                        }
                    } else {
                        $uzenetSikertelen .= ('<br/>' . $atveteli_adatok['hiba']);
                    }

                }
            }
        }


        if ($linksFlv != '' || $linksShp != '') {
            $linksFlv = '<br/><ul">' . $linksFlv . '</ul>';
            $linksShp = '<br/><ul">' . $linksShp . '</ul>';
            echo '<div class="updated notice is-dismissible sprinter_notice"><div><img src="' . SP_PPP_PLUGIN_DIR_URL . '/images/sprinter_ikon.png" />'
                . $uzenetSikeres . '<br/><br/>'
                . __('Fuvarlevél(ek):') . $linksFlv . '<br/><br/>'
                . __('Átvételi elismervény(ek):') . $linksShp
                . '</div></div>';
        }
        if ($uzenetSikertelen != '') {
            echo '<div class="error notice is-dismissible sprinter_notice"><div><img src="' . SP_PPP_PLUGIN_DIR_URL . '/images/sprinter_ikon.png" />' . $uzenetSikertelen . '</div></div>';
        }

        unset($_SESSION['sprinter_dokumentumok_return']);
    }


    if(!empty($_REQUEST['sprinter_tomeges_csv_kesz'])) {

        if(!session_id()) {
            session_start();
        }

        if(isset($_SESSION['sprinter_tomeges_csv_return']) && is_array($_SESSION['sprinter_tomeges_csv_return'])) {

            $uzenetSikeres = '';
            $uzenetSikertelen = '';

            // $csv_adatok = array_map('wp_kses_post', $_SESSION['sprinter_tomeges_csv_return']);
            $csv_adatok = $_SESSION['sprinter_tomeges_csv_return'];
            $csv_adatok = $csv_adatok[0];

            if (!sprinter_isnullorempty($csv_adatok['sikeres'])) {
                $uzenetSikeres .= ('<br/>' . $csv_adatok['sikeres']);
            }
            if (isset($csv_adatok['sikertelen']) && !sprinter_isnullorempty($csv_adatok['sikertelen'])) {
                $uzenetSikertelen .= ('<br/>' . $csv_adatok['sikertelen']);
            }

            if ($uzenetSikeres != '') {
                echo '<div class="updated notice is-dismissible sprinter_notice"><div><img src="' . SP_PPP_PLUGIN_DIR_URL . '/images/sprinter_ikon.png" />' . $uzenetSikeres . '</div></div>';
            }
            if ($uzenetSikertelen != '') {
                echo '<div class="error notice is-dismissible sprinter_notice"><div><img src="' . SP_PPP_PLUGIN_DIR_URL . '/images/sprinter_ikon.png" />' . $uzenetSikertelen . '</div></div>';
            }
        }
        unset($_SESSION['sprinter_tomeges_csv_return']);
    }
}

function sprinter_tomeges_megrendeles( $order_ids, $context ) {
    $sprinter_megrendeles_return = sprinter_megrendeles($order_ids,$context);
    return $sprinter_megrendeles_return;
}

function sprinter_tomeges_csv( $order_ids ) {
    require_once('csvexport.php');

    $sprinter_tomeges_csv_return = array();

    if( !empty( $order_ids ) ){
        $pppRows=array();
        $hdRows=array();
        
        foreach ($order_ids as $oid) {
            
            $row=array();
            $order = wc_get_order($oid);
            $PPP = get_post_meta( $order->get_id(), '_sprinter_kivalasztott_pickpackpont', true );

            $auth_code = get_post_meta($oid,'_auth_code',true);
            if (!$auth_code) $auth_code=strval($oid);
            $inv_number = get_post_meta($oid,'_invoice_number',true);
            if (!$inv_number) $inv_number='';
            
            if (!sprinter_isnullorempty($PPP)) {
                $row['Feladási boltazonosító']=get_option('sprinter_pickpackpont_pos2pos');
                $PPP_data = json_decode( str_replace( "'", '"', $PPP ), true  );
                $row['Kézbesítési boltazonosító']=sprinter_ppp_azonosito($PPP_data['shopCode']);
                $row['Címzett neve']=$order->get_shipping_last_name() . ' ' . $order->get_shipping_first_name();
                $row['Címzett telefonszáma (pl. +36301234567)']=$order->get_billing_phone();;
                $row['Címzett email címe']=$order->get_billing_email();;
                $row['Ügyfélkód']=''; // ???
                $row['Címzett cím Ir.száma']=$order->get_shipping_postcode();
                $row['Címzett cím Helység név']=$order->get_shipping_city();
                $row['Címzett cím utca']=$order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2();
                $row['Címzett cím hsz., em., ajtó']='';
                $row['Méretbesorolás']=get_option('sprinter_pickpackpont_meret');
                $row['Megrendelés kódja']=$auth_code; //??
                $row['Termék számlájának azonosítója']=$inv_number; //??
                $row['Utánvét értéke']=round($order->get_total());
                $row['Utánvét devizaneme']=$order->get_currency();
                $row['Csomag értéke']=round($order->get_total());
                $row['Csomag értéke devizaneme']=$order->get_currency();
                $pppRows[]=$row;
            }
            else {
                $feladas=get_option('sprinter_csv_feladas_helye');
                if ($feladas=='telephely') {
                    $feladas='Telephely';
                }
                else {
                    $feladas='Depó';
                }
                $row['Feladás helye']=$feladas; //'Telephely'; //Depó ; Egyedi cím (Lehívás) ; Telephely
                $row['Kézbesítés helye']='Egyedi cím'; //Depó ; Egyedi cím ; Telephely
                $row['Feladási / Kézbesítési cím Ir.száma']=$order->get_shipping_postcode();
                $row['Feladási / Kézbesítési cím Helység név']=$order->get_shipping_city();
                $row['Feladási / Kézbesítési cím utca']=$order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2();
                $row['Feladási / Kézbesítési cím hsz., em., ajtó']='.';
                $row['Címzett neve']=$order->get_shipping_last_name() . ' ' . $order->get_shipping_first_name();
                $row['Címzett telefonszáma (pl. +36301234567)']=$order->get_billing_phone();;
                $row['Címzett email címe']=$order->get_billing_email();;
                $row['Ügyfélkód']=''; // ???
                $row['Szállítási idő']='2 munkanapos';
                $row['Csomagok darabszáma']='1';
                $row['Szélesség (cm)']=get_option('sprinter_szelesseg');
                $row['Magasság (cm)']=get_option('sprinter_magassag');
                $row['Hosszúság (cm)']=get_option('sprinter_hosszusag');
                $row['Tömeg (kg)']=get_option('sprinter_suly');
                $row['Megjegyzés a futárnak']=$order->get_customer_note();
                $row['Megrendelés kódja']=$auth_code; // ???
                $row['Termék számlájának azonosítója']=$inv_number; // ???
                $orderContent='';
                foreach ($order->get_items() as $item) {
                    $orderContent .= ($item['name'].';');
                }
                $row['Csomag tartalma']=$orderContent;
                $row['Utánvét értéke']=round($order->get_total());
                $row['Utánvét devizaneme (pl. HUF)']=$order->get_currency();
                $row['Csomag értéke']=round($order->get_total());
                $row['Csomag értéke devizaneme (pl. HUF)']=$order->get_currency();
                $row['Okmányvisszaforgatást kérek']='';
                $row['Csomagcserét kérek']='';
                $row['Csere csomagok darabszáma']='';
                $row['Szélesség (cm)##']='';
                $row['Magasság (cm)##']='';
                $row['Hosszúság (cm)##']='';
                $row['Tömeg (kg)##']='';
                $row['Megjegyzés a futárnak##']='';
                $row['Megrendelés kódja##']='';
                $row['Termék számlájának azonosítója##']='';
                $row['Csomag tartalma##']='';
                $row['Utánvét értéke##']='';
                $row['Utánvét devizaneme (pl. HUF)##']='';
                $row['Csomag értéke##']='';
                $row['Csomag értéke devizaneme (pl. HUF)##']='';
                $row['Feladási / Kézbesítési cím Országkód']='';
                $hdRows[]=$row;
            }
        }
        

        $sikeresUzenet='';
        $sikertelenUzenet='';

        $postfix=strtotime( date( 'Y-m-d H:i:s' ));

        if (count($pppRows)>0) {
            $pppFilename='tomeges_PPP_csv_'.$postfix.'.csv';
            if (sprinter_export2csv($pppRows,sprinter_get_folder() . $pppFilename, true)) {
                $sikeresUzenet .= '<li>'.__('Pick Pack Pont:') . '&nbsp;' . '<a href="' . sprinter_get_url() . $pppFilename .  '">' . $pppFilename . '</a><br/>';
            }
            else {
                $sikertelenUzenet .= '<li>'.__('Pick Pack Pont') . '<br/>';
            }
        }
        if (count($hdRows)>0) {
            $hdFilename='tomeges_hazhozszallitas_csv_'.$postfix.'.csv';
            if (sprinter_export2csv($hdRows,sprinter_get_folder() . $hdFilename, true, '##')) {
                $sikeresUzenet .= '<li>'.__('Házhoz szállítás:') . '&nbsp;' . '<a href="' . sprinter_get_url() . $hdFilename .  '">' . $hdFilename . '</a><br/>';
            }
            else {
                $sikertelenUzenet .= '<li>'.__('Házhoz szállítás') . '<br/>';
            }
        }
    }



    if (!sprinter_isnullorempty($sikeresUzenet)) {
    $sprinter_tomeges_csv_return['sikeres'] = 
    '<strong>'.__( 'Sikeres Sprinter tömeges betöltő CSV generálás.', 'sprinter' ).'</strong><br/><br/><ul>' . $sikeresUzenet .'</ul>';
    }

    if (!sprinter_isnullorempty($sikertelenUzenet)) {
        $sprinter_tomeges_csv_return['sikertelen'] = 
        '<strong>'.__( 'Sikertelen Sprinter tömeges betöltő CSV generálás.', 'sprinter' ).'</strong><br/><br/><ul>' . $sikertelenUzenet .'</ul>';
    }

    return $sprinter_tomeges_csv_return;
}


function sprinter_engedelyezett_fizetesi_modok( $filter = '' ){
    $fizetesi_modok = WC()->payment_gateways->payment_gateways();
    $sprinter_engedelyezett_fizetesi_modok = array();
    foreach ( $fizetesi_modok as $fizetesi_mod ) {
        if( $filter != '' && $fizetesi_mod->id == $filter ){
            return $fizetesi_mod->title;
        }
        
        if( $fizetesi_mod->id != 'cod' ){
            if( $fizetesi_mod->enabled == 'yes' ){
                $sprinter_engedelyezett_fizetesi_modok[ $fizetesi_mod->id ] = $fizetesi_mod->title.' ('.__( 'Engedélyezett', 'sprinter' ).')';
            }
            else{
                $sprinter_engedelyezett_fizetesi_modok[ $fizetesi_mod->id ] = $fizetesi_mod->title.' ('.__( 'Nem engedélyezett', 'sprinter' ).')';
            }
        }
    }
	return $sprinter_engedelyezett_fizetesi_modok;
}


add_action( 'wp_ajax_sprinter_fuvarlevelek_ajax_torles', 'sprinter_fuvarlevelek_ajax_torles' );
function sprinter_fuvarlevelek_ajax_torles() {
    $counter = 0;
    if ( isset($_REQUEST) ) {
        foreach ( glob( sprinter_get_folder().'*.pdf' ) as $filename ) {
            unlink($filename);
            $counter++;
        }
        foreach ( glob( sprinter_get_folder().'*.csv' ) as $filename ) {
            unlink($filename);
            $counter++;
        }
    }
    echo $counter.' '.__( 'fájl törölve.', 'sprinter' );
    die();
}


add_action( 'admin_notices', 'sprinter_kornyezet_ellenorzes' );
function sprinter_kornyezet_ellenorzes() {
    if( !extension_loaded( 'curl' ) && !ini_get( 'allow_url_fopen' ) ){
        if(  get_option( 'sprinter_allapot' ) == 'yes' ){
            update_option( 'sprinter_allapot', 'no' );
        }
        
        echo '<div class="error notice is-dismissible sprinter_notice">';
            echo '<div>';
                echo '<img src="'.SP_PPP_PLUGIN_DIR_URL.'/images/sprinter_ikon.png" />';
                echo '<strong>';
                    echo __( 'Sprinter hibaüzenet', 'sprinter' );
                    echo '<br/><br/>';
                    echo __( 'A bővítmény használatához szükség van a CURL telepítésére vagy az allow_url_fopen engedélyezésére. Kérem, jelezze ezt a weboldal vagy a tárhely üzemeltetőjének.', 'sprinter' );
                echo '</strong>';
            echo '</div>';
            echo '<button type="button" class="notice-dismiss sprinter_notice_dismiss"></button>';
        echo '</div>';
    }
}


add_action( 'admin_notices', 'sprinter_utanvet_beallitas_ellenorzes' );
function sprinter_utanvet_beallitas_ellenorzes() {

    if( get_option( 'sprinter_allapot' ) == 'yes' && get_option( 'sprinter_cod_notice_dismiss' ) != 'yes'  ){
        echo '<div class="sprinter_warning notice notice-warning sprinter_notice">';
            echo '<div>';
                echo '<img src="'.SP_PPP_PLUGIN_DIR_URL.'/images/sprinter_ikon.png" />';
                echo '<strong>';
                    echo __( 'Sprinter figyelmeztetés', 'sprinter' );
                echo '</strong>';
                echo '<br/>';
                echo '<p>';
                    echo __( 'A bővítmény automatikusan kiválasztja az alapértelmezett utánvétes fizetési módot:', 'sprinter' ).' <strong>'.sprinter_engedelyezett_fizetesi_modok( 'cod' ).'</strong>.';
                echo '</p>';
                echo '<p>';
                    echo __( 'Amennyiben webáruházában további fizetési módok is utánvétesnek számítanak, ', 'sprinter' );
                    echo ' <a href="'. sprinter_get_settings_url() .'#sprinter_fizetesi_modok">'.__( 'úgy itt állíthatja be azokat.', 'sprinter' ).'</a>';
                echo '</p>'; 
                echo '<p>';  
                    echo '<button type="button" class="button button-secondary sprinter_cod_notice_dismiss">'.__( 'Megértettem', 'sprinter' ).'</button>';
                echo '</p>';  
            echo '</div>';
        echo '</div>';
    }
}


add_action( 'wp_ajax_sprinter_cod_notice_dismiss', 'sprinter_cod_notice_dismiss' );
function sprinter_cod_notice_dismiss() {
    if ( isset($_REQUEST) ) {
         update_option( 'sprinter_cod_notice_dismiss', 'yes' );
    }
    die();
}
   
?>