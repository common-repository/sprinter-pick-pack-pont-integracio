jQuery(document).ready(function(){
    jQuery( 'body' ).bind( 'updated_checkout', function() {
        var shipping_fields = jQuery('.woocommerce-shipping-fields');
        var eltero_szallitasi_cim = jQuery('#ship-to-different-address-checkbox');
        jQuery.ajax({
            url: sprinter_frontend_adatok.ajax_url,
            data: {
                'action':'sprinter_ajax_szallitasi_mod_ellenorzese'
            },
            success:function( sprinter_ajax_szallitasi_mod_ellenorzese_return ) {
                szallitasi_mod = jQuery.parseJSON( sprinter_ajax_szallitasi_mod_ellenorzese_return );
                if( szallitasi_mod['nev']  == 'sprinter_pickpackpont' ){
                    eltero_szallitasi_cim.prop('checked',false);
                    shipping_fields.hide();
                    if( szallitasi_mod['tipus'] == 'terkep' ){
                        if( jQuery( '#sprinter_pickpackpont_kontener' ).length == 0 ) {
                            sprinter_pickpackpont_kontener = '<div id="sprinter_pickpackpont_kontener">';
                                sprinter_pickpackpont_kontener += '<h3>'+sprinter_frontend_adatok.pickpackpont_cim+'</h3>';
                                sprinter_pickpackpont_kontener += '<div class="sprinter_pickpackpont_terkep_kontener">';
                                    sprinter_pickpackpont_kontener += '<div id="sprinter_pickpackpont_terkep" style="height:500px;">';
                                    sprinter_pickpackpont_kontener += '</div>';
                                sprinter_pickpackpont_kontener += '</div>';  
                                sprinter_pickpackpont_kontener += '<div class="sprinter_pickpackpont_kivalasztott_kontener">';
                                    sprinter_pickpackpont_kontener += '<div class="sprinter_pickpackpont_kivalasztott_kontener_cim"><strong>'+sprinter_frontend_adatok.kivalasztott_pickpackpont+'</strong></div>';
                                    sprinter_pickpackpont_kontener += '<input type="text" id="sprinter_kivalasztott_pickpackpont" name="sprinter_kivalasztott_pickpackpont" value="">';
                                    sprinter_pickpackpont_kontener += '<div class="sprinter_pickpackpont_kivalasztott_kontener_adatok sprinter_pickpackpont_kivalasztott_kontener_adatok_ures">'+sprinter_frontend_adatok.nincs_kivalasztott_pickpackpont+'</div>';
                                sprinter_pickpackpont_kontener += '</div>';
                            sprinter_pickpackpont_kontener += '</div>';
                            jQuery( sprinter_pickpackpont_kontener ).insertAfter( jQuery(".shop_table") );
                            sprinter_pickpackpont_terkep_generalas();
                        }
                    }
                    else if( szallitasi_mod['tipus'] == 'lista' ){
                        if( jQuery( '#sprinter_pickpackpont_kontener' ).length == 0 ) {
                            sprinter_pickpackpont_kontener = '<div id="sprinter_pickpackpont_kontener">';
                                sprinter_pickpackpont_kontener += '<h3>'+sprinter_frontend_adatok.pickpackpont_cim+'</h3>';
                                sprinter_pickpackpont_kontener += '<div class="sprinter_pickpackpont_filter_kontener">';
                                    sprinter_pickpackpont_kontener += '<input type="text" id="sprinter_pickpackpont_filter" name="sprinter_pickpackpont_filter" value="" placeholder="'+sprinter_frontend_adatok.pickpackpont_kereses+'">';
                                sprinter_pickpackpont_kontener += '</div>';
                                sprinter_pickpackpont_kontener += '<div class="sprinter_pickpackpont_lista_kontener">';
                                    sprinter_pickpackpont_kontener += '<div class="sprinter_pickpackpont_lista_elemek">';
                                    sprinter_pickpackpont_kontener += '</div>';  
                                sprinter_pickpackpont_kontener += '</div>';  
                                sprinter_pickpackpont_kontener += '<div class="sprinter_pickpackpont_kivalasztott_kontener">';
                                    sprinter_pickpackpont_kontener += '<div class="sprinter_pickpackpont_kivalasztott_kontener_cim"><strong>'+sprinter_frontend_adatok.kivalasztott_pickpackpont+'</strong></div>';
                                    sprinter_pickpackpont_kontener += '<input type="text" id="sprinter_kivalasztott_pickpackpont" name="sprinter_kivalasztott_pickpackpont" value="">';
                                    sprinter_pickpackpont_kontener += '<div class="sprinter_pickpackpont_kivalasztott_kontener_adatok sprinter_pickpackpont_kivalasztott_kontener_adatok_ures">'+sprinter_frontend_adatok.nincs_kivalasztott_pickpackpont+'</div>';
                                sprinter_pickpackpont_kontener += '</div>';
                            sprinter_pickpackpont_kontener += '</div>';
                            jQuery( sprinter_pickpackpont_kontener ).insertAfter( jQuery(".shop_table") );
                            sprinter_pickpackpont_legordulo_generalas();
                        }
                    }
                }
                else{
                    if( jQuery( '#sprinter_pickpackpont_kontener' ).length > 0 ) {
                        jQuery("#sprinter_pickpackpont_kontener").html('').remove();
                    }
                    shipping_fields.show();

                }
            },
            error: function (err) {
                console.log('AJAX error: '+err);
            }
        });
	});
    
    function sprinter_pickpackpont_legordulo_generalas( filter = '' ){
            console.log(sprinter_frontend_adatok.ajax_url+' --- '+filter);
        jQuery.ajax({
            url: sprinter_frontend_adatok.ajax_url,
            data: {
                'action':'sprinter_ajax_pickpackpontok',
                'filter': filter
            },
            success:function( sprinter_ajax_pickpackpontok_return ) {
                var sprinter_pickpackpontok = jQuery.parseJSON( sprinter_ajax_pickpackpontok_return );
                jQuery('.sprinter_pickpackpont_lista_elemek').find('.sprinter_pickpackpont_lista_elem').remove();
                jQuery.each( sprinter_pickpackpontok, function(key, sprinter_pickpackpont) {
                    sprinter_pickpackpont_adatok = JSON.stringify(sprinter_pickpackpont);
                    sprinter_pickpackpont_adatok = sprinter_pickpackpont_adatok.replace( /"/g, "'" );
                    lista_class = '';
                    if( jQuery('#sprinter_kivalasztott_pickpackpont').val() == sprinter_pickpackpont_adatok ){
                        lista_class = 'sprinter_pickpackpont_kivalasztott_lista_elem';
                    }

                    sprinter_pickpackpont_cim = sprinter_pickpackpont.zipCode+' '+sprinter_pickpackpont.city+' '+sprinter_pickpackpont.address;
                    
                    pickpackpont = '<div class="sprinter_pickpackpont_lista_elem '+lista_class+'">';
                        pickpackpont += sprinter_pickpackpont.shopName;
                        pickpackpont += '<div>';
                            pickpackpont += sprinter_pickpackpont.shopType;
                        pickpackpont += '</div>';
                        pickpackpont += '<div>';
                            pickpackpont += sprinter_pickpackpont_cim;
                        pickpackpont += '</div>';
                    pickpackpont += '</div>';
                    
                    jQuery('.sprinter_pickpackpont_lista_elemek').append( jQuery(pickpackpont).
                        attr("sprinter_pickpackpont_nev",sprinter_pickpackpont.shopName).
                        attr("sprinter_pickpackpont_cim",sprinter_pickpackpont_cim).
                        attr("sprinter_pickpackpont_adatok",sprinter_pickpackpont_adatok) 
                    ); 
                });
            }
        });
    }
    
    jQuery( 'body' ).on("click",'.sprinter_pickpackpont_lista_elem', function() { 
        jQuery('#sprinter_kivalasztott_pickpackpont').val( jQuery( this ).attr( 'sprinter_pickpackpont_adatok' ) );
        jQuery('.sprinter_pickpackpont_kivalasztott_kontener_adatok').remove();
        jQuery( '<div class="sprinter_pickpackpont_kivalasztott_kontener_adatok">'+jQuery( this ).attr( 'sprinter_pickpackpont_nev' )+'<br/>'+jQuery( this ).attr( 'sprinter_pickpackpont_cim' )+'</div>' ).insertAfter( '#sprinter_kivalasztott_pickpackpont' );
        jQuery( '.sprinter_pickpackpont_lista_elem' ).removeClass( 'sprinter_pickpackpont_kivalasztott_lista_elem' );
        jQuery( this ).addClass( 'sprinter_pickpackpont_kivalasztott_lista_elem' );
    });
    
    jQuery( 'body' ).on("keyup","#sprinter_pickpackpont_filter", function() { 
        filter = jQuery(this).val();
        if( filter.length == 4 || filter.length == 0 ){
            sprinter_pickpackpont_legordulo_generalas( filter );
        }
    });
    
    function sprinter_pickpackpont_terkep_generalas(){
        var currentInfoWindow = null;
        var map;
        var bounds = new google.maps.LatLngBounds();
        var markers = [];
        console.log(sprinter_frontend_adatok.ajax_url+' --- n/a');

        map = new google.maps.Map(document.getElementById('sprinter_pickpackpont_terkep'), {
          center: {lat: 47.180086, lng: 19.503736},
          zoom: 8,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        });

        var sprinter_pickpackpont_kep = {
          url: sprinter_frontend_adatok.plugin_dir_url+'/images/pickpackpont.png',
          size: new google.maps.Size(50, 35),
          origin: new google.maps.Point(0, 0),
          anchor: new google.maps.Point(25, 0)
        };
                
        var markerClusterOptions = {
            imagePath:sprinter_frontend_adatok.plugin_dir_url+'/images/jelolo',
            imageExtension:'png',
        };

        jQuery.ajax({
            url: sprinter_frontend_adatok.ajax_url,
            data: {
                'action':'sprinter_ajax_pickpackpontok',
                'filter': '',
            },
            success:function( sprinter_ajax_pickpackpontok_return ) {
                var sprinter_pickpackpontok = jQuery.parseJSON( sprinter_ajax_pickpackpontok_return );
                console.log(sprinter_pickpackpontok);
                jQuery.each( sprinter_pickpackpontok, function(key, sprinter_pickpackpont) {
                                        
                    sprinter_pickpackpont.lat = sprinter_pickpackpont.lat.replace(",", ".");
                    sprinter_pickpackpont.lng = sprinter_pickpackpont.lng.replace(",", ".");
            
                    var position = new google.maps.LatLng( sprinter_pickpackpont.lat, sprinter_pickpackpont.lng );
                    
                    bounds.extend( position );
                    
                    var marker = new google.maps.Marker( { 
                        position: position, 
                        map: map,
                        icon: sprinter_pickpackpont_kep,
                        title: sprinter_pickpackpont.shopName,
                    });
                    
                    sprinter_pickpackpont.marker = sprinter_pickpackpont_kep['url'];
        
                    markers.push(marker);
                    sprinter_pickpackpont_adatok = JSON.stringify( sprinter_pickpackpont );
                    sprinter_pickpackpont_adatok =  sprinter_pickpackpont_adatok.replace( /"/g, "'" );
                    sprinter_pickpackpont_cim = sprinter_pickpackpont.zipCode+' '+sprinter_pickpackpont.city+' '+sprinter_pickpackpont.address;

                    var tartalom =
                        '<div class="sprinter_pickpackpont_infowindow">'+
                            '<div class="sprinter_pickpackpont_infowindow_nev">'+sprinter_pickpackpont.shopName+'</div>'+
                            '<div class="sprinter_pickpackpont_infowindow_nev">'+sprinter_pickpackpont.shopType+'</div>'+
                            '<div class="sprinter_pickpackpont_infowindow_cim" >'+
                                sprinter_pickpackpont_cim +
                            '</div>'+
                            '<div class="sprinter_pickpackpont_infowindow_gomb" >'+
                                '<div class="sprinter_pickpackpont_kivalasztas" sprinter_pickpackpont_nev="'+sprinter_pickpackpont.shopName+'" sprinter_pickpackpont_cim="'+sprinter_pickpackpont_cim+'" sprinter_pickpackpont_adatok="'+sprinter_pickpackpont_adatok+'"  >Kiválasztom</div>' +
                            '</div>'+
                        '</div>';
                    
                    var infowindow = new google.maps.InfoWindow({
                      content: tartalom
                    });
                    
                    marker.addListener('click', function() {
 
                        if (currentInfoWindow != null) {
                            currentInfoWindow.close();
                        } 
        
                      infowindow.open(map, marker);
                  
                      currentInfoWindow = infowindow; 
                    });

                });

                var markerCluster = new MarkerClusterer( map, markers, markerClusterOptions );

                if(!bounds.isEmpty()) { 
                    map.fitBounds(bounds);
                }
            }
        });
    }
     
    jQuery( 'body' ).on("click",'.sprinter_pickpackpont_kivalasztas', function() { 
        jQuery('#sprinter_kivalasztott_pickpackpont').val( jQuery( this ).attr( 'sprinter_pickpackpont_adatok' ) );
        jQuery('.sprinter_pickpackpont_kivalasztott_kontener_adatok').remove();
        jQuery( '<div class="sprinter_pickpackpont_kivalasztott_kontener_adatok">'+jQuery( this ).attr( 'sprinter_pickpackpont_nev' )+'<br/>'+jQuery( this ).attr( 'sprinter_pickpackpont_cim' )+'</div>' ).insertAfter( '#sprinter_kivalasztott_pickpackpont' );
    });
    
    if ( jQuery( 'body' ).hasClass( "woocommerce-cart" ) ) {
        sprinter_pickpackpont_szallitas_kalkulator_eltuntetese();
    }
    
    jQuery( 'body' ).bind( 'updated_cart_totals', function() {
        sprinter_pickpackpont_szallitas_kalkulator_eltuntetese();
    });
    
    function sprinter_pickpackpont_szallitas_kalkulator_eltuntetese(){
        jQuery.ajax({
            url: sprinter_frontend_adatok.ajax_url,
            data: {
                'action':'sprinter_ajax_szallitasi_mod_ellenorzese'
            },
            success:function( sprinter_ajax_szallitasi_mod_ellenorzese_return ) {
                szallitasi_mod = jQuery.parseJSON( sprinter_ajax_szallitasi_mod_ellenorzese_return );
                if( szallitasi_mod['nev']  == 'sprinter_pickpackpont' ){
                    if ( !jQuery( '.woocommerce-shipping-calculator' ).hasClass( "sprinter_pickpackpont_szallitas_kalkulator_eltuntetese" ) ) {
                        jQuery( '.woocommerce-shipping-calculator' ).addClass( 'sprinter_pickpackpont_szallitas_kalkulator_eltuntetese' );
						jQuery( '.woocommerce-shipping-destination' ).addClass( 'sprinter_pickpackpont_szallitas_kalkulator_eltuntetese' );
                        jQuery( '<span="sprinter_pickpackpont_szallitas_kalkulator_uzenet">'+sprinter_frontend_adatok.kosar_pickpackpont_valasztas+'</span>' ).insertAfter( '.woocommerce-shipping-calculator' );
                    }
                }
                else{
                    if ( jQuery( '.woocommerce-shipping-calculator' ).hasClass( "sprinter_pickpackpont_szallitas_kalkulator_eltuntetese" ) ) {
                        jQuery( '.woocommerce-shipping-calculator' ).removeClass( 'sprinter_pickpackpont_szallitas_kalkulator_eltuntetese' );
                        jQuery( '.woocommerce-shipping-destination' ).removeClass( 'sprinter_pickpackpont_szallitas_kalkulator_eltuntetese' );
                        jQuery( '.sprinter_pickpackpont_szallitas_kalkulator_uzenet' ).remove();
                    }
                }
            }
        });
    }
});
