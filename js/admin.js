jQuery(document).ready(function(){
    
    jQuery(document).on('click', '.cserecsomag_link', function(e) {
        e.preventDefault();
        var sprinter = jQuery(this).closest('.column-sprinter');
        if(jQuery(sprinter).find('.cserecsomag_box').css('display') == 'none') {
            jQuery(sprinter).find('.cserecsomag_box').css('display','block');
        } else {
            jQuery(sprinter).find('.cserecsomag_box').css('display','none');
        }
    });

    // cserecsoamg checkbox pipa esetén a previous_order_id mező kötelezővé tétele
    jQuery(document).on('change', '#cserecsomag', function() {
        if(jQuery(this).is(":checked") && jQuery('#cserecsomag').attr('data-sprintercscs') !== 'undefined') {
            jQuery('.previous_order_id_show').css('display','block');
            jQuery('#previous_order_id').prop('required', true);

        } else {
            jQuery('.previous_order_id_show').css('display','none');
            jQuery('#previous_order_id').prop('required', false);
        }
    });

    // ha a previous_order_id mezőben van érték, akkor kötelezővé teszi a cserecsomag checkboxot
    jQuery(document).on('keyup', '#previous_order_id', function() {
        jQuery('#previous_order_id').prop('required', true);
    });

    jQuery('.sprinter_button_megrendeles, .sprinter_button_ujrarendeles').click( function() {

        // csomagszám felülbírálása
        let custom_parcel_count = prompt("Hány csomagban szeretné feladni a megrendelést?", "1");
        if (custom_parcel_count != null) {
            // console.log('Kívánt csomagok száma: ' + custom_parcel_count);
        }
        else{
            return false;
        }

        gomb = jQuery(this);
        gomb_parent = gomb.parent().parent();
        gomb.unbind("click");
        order_id = gomb.attr("order_id");
        context = 'megrendeles';
        if (gomb.hasClass('sprinter_button_ujrarendeles')) {
            context = 'ujrarendeles';
        }
        gomb.after( '<div class="spinner spinner_'+order_id+'" style="visibility:visible; float:left;"></div>' );
        jQuery.ajax({
            url: sprinter_admin_adatok.ajax_url,
            data: {
                'action':'sprinter_megrendeles',
                'order_id' : order_id,
                'custom_parcel_count' : custom_parcel_count,
                'context': context,
                'bulk_wsh' : true
            },
            error: function (err) {
                console.log("AJAX error: " + JSON.stringify(err, null, 2));
                console.log(err.status);
                console.log(err.statusText);
                jQuery( ".wrap .sprinter_notice" ).remove( );
                jQuery( ".wrap" ).prepend( '<div class="sprinter_notice"></div>' );
                jQuery( ".wrap .sprinter_notice" ).prepend( 
                    '<div  class="error notice"><p><img src="'+sprinter_admin_adatok.plugin_dir_url+'/images/sprinter_ikon.png" />'
                    +
                    err.status + ' ' + err.statusText
                    +
                    '</p></div>' 
                );
                jQuery('.spinner_'+order_id+'').remove();
                gomb.bind("click");
            
            },
            success:function(data) {
                data=data.replace(/[\u200B-\u200D\uFEFF]/g, '');
                data = jQuery.parseJSON(data);
                jQuery( ".wrap .sprinter_notice" ).remove( );
                jQuery( ".wrap" ).prepend( '<div class="sprinter_notice"></div>' );

                // data each function
                var data_arr = data;
                // TODO: átmeneti empty
                gomb_parent.empty();

                // console.log('adat:');
                // console.log(data_arr);

                jQuery.each(data_arr, function(key, data) {
                //     console.log('data');
                //     console.log(data);
                    if( data['debug'] ){
                        jQuery( ".wrap .sprinter_notice" ). prepend( 
                        '<div  class="updated notice"><p><img src="'+sprinter_admin_adatok.plugin_dir_url+'/images/sprinter_ikon.png" />'
                        +
                            data['debug']
                            +
                            '</p></div>' 
                        );
                    }
                    if( data['sikeres'] ){
                            jQuery( ".wrap .sprinter_notice" ). prepend( 
                            '<div class="updated notice"><p><img src="'+sprinter_admin_adatok.plugin_dir_url+'/images/sprinter_ikon.png" />'
                            +
                                data['sikeres']
                                +
                                '</p></div>' 
                            );
                            gomb_after = data['sprinter_gombok'];
                            // gomb_parent.empty();
                            gomb_parent.append(gomb_after);
                            // gomb=jQuery(this);
                            // gomb.bind('click');
                    }
                    else 
                    if( data['hiba'] ) {
                        jQuery( ".wrap .sprinter_notice" ).prepend( 
                            '<div  class="error notice"><p><img src="'+sprinter_admin_adatok.plugin_dir_url+'/images/sprinter_ikon.png" />'
                            +
                            data['hiba']
                            +
                            '</p></div>' 
                        );
                        jQuery('.spinner_'+order_id+'').remove();
                        gomb=jQuery(this);
                        gomb.bind('click');
                    }
                });       
            }
        });
    });

    jQuery('.column-sprinter').on('click','.sprinter_button_fuvarlevel', function() {
        gomb = jQuery(this);
        //gomb has data-fuvarlevel
        fuvarlevel_kattintas_index = gomb.attr("data-fuvarlevel");
        
        gomb.unbind("click");
        order_id = gomb.attr("order_id");
        gomb_parent = jQuery(this).closest('.sprinter_megrendeles_gomb_box_'+order_id);
        context = 'vonalkod';
        gomb.after( '<div class="spinner spinner_'+order_id+'" style="visibility:visible; float:left;"></div>' );
        jQuery.ajax({
            url: sprinter_admin_adatok.ajax_url,
            data: {
                'action':'sprinter_megrendeles',
                'order_id' : order_id,
                'context': context,
                'bulk_wsh' : true,
                'fuvarlevel_index' : fuvarlevel_kattintas_index,
            },
            error: function (err) {
                console.log("AJAX error: " + JSON.stringify(err, null, 2));
                console.log(err.status);
                console.log(err.statusText);
                jQuery( ".wrap .sprinter_notice" ).remove( );
                jQuery( ".wrap" ).prepend( '<div class="sprinter_notice"></div>' );
                jQuery( ".wrap .sprinter_notice" ).prepend( 
                    '<div  class="error notice"><p><img src="'+sprinter_admin_adatok.plugin_dir_url+'/images/sprinter_ikon.png" />'
                    +
                    err.status + ' ' + err.statusText
                    +
                    '</p></div>' 
                );
                jQuery('.spinner_'+order_id+'').remove();
                gomb.bind("click");
                console.log(order_id);
            
            },
            success:function(data) {
                // console.log(data);
                data=data.replace(/[\u200B-\u200D\uFEFF]/g, '');
                data = jQuery.parseJSON(data);
                // data[0] = data;
                jQuery( ".wrap .sprinter_notice" ).remove( );
                jQuery( ".wrap" ).prepend( '<div class="sprinter_notice"></div>' );
                if( data[0]['debug'] ){
                    jQuery( ".wrap .sprinter_notice" ). prepend( 
                    '<div  class="updated notice"><p><img src="'+sprinter_admin_adatok.plugin_dir_url+'/images/sprinter_ikon.png" />'
                    +
                        data[0]['debug']
                        +
                        '</p></div>' 
                    );
                }
                if(data[0]["sikeres"] ){
                    // gomb_after = data[0]['sprinter_gombok'];
                    gomb_after = ""
                    gomb_after += '<a style="background: #fffcbb !important;" target="_blank" href="'+data[0]['fajlnev_url']+'">[&nbsp;'+data[0]['fajlnev']+'&nbsp;]</a>';
                    // gomb_parent.empty(); // tod: teszt átmeneti
                    gomb_parent.append(gomb_after);
                    jQuery('.spinner_'+order_id+'').remove();
                }
                else 
                if( data[0]['hiba'] ) {
                    jQuery( ".wrap .sprinter_notice" ).prepend( 
                        '<div  class="error notice"><p><img src="'+sprinter_admin_adatok.plugin_dir_url+'/images/sprinter_ikon.png" />'
                        +
                        data[0]['hiba']
                        +
                        '</p></div>' 
                    );
                    jQuery('.spinner_'+order_id+'').remove();
                    gomb.bind('click');
                }             
            }
        });
    });


    function sprinter_notice( tipus, tartalom ){
        jQuery( ".wp-header-end" ).after( 
        '<div  class="'+tipus+' notice is-dismissible sprinter_notice"><div><img src="'+sprinter_admin_adatok.plugin_dir_url+'/images/sprinter_ikon.png" />'+tartalom+'</div><button type="button" class="notice-dismiss sprinter_notice_dismiss"></button></div>' 
        );
    }

    jQuery('body').on('click', '.sprinter_notice_dismiss', function( event ){
        var gomb = jQuery( event.target );
        gomb.parent( '.notice' ).fadeTo(100, 0, function() {
            gomb.parent( '.notice' ).slideUp(100, function() {
                gomb.parent( '.notice' ).remove();
            });
        });
    });

    jQuery('body').on('click', 'span.sprinter_kivalasztott_pickpackpont_szerkesztes', function( ){
        jQuery( 'div.sprinter_kivalasztott_pickpackpont_szerkesztes' ).toggle();
        jQuery( '.sprinter_pickpackpont_kivalasztott_kontener_cim' ).toggle();
    });

    jQuery('body').on('click','span.sprinter_kivalasztott_pickpackpont_torles', function( ){
        jQuery('#sprinter_kivalasztott_pickpackpont').val( '' );
        jQuery('.sprinter_pickpackpont_kivalasztott_kontener_adatok').remove();
        jQuery( '<div class="sprinter_pickpackpont_kivalasztott_kontener_adatok sprinter_pickpackpont_kivalasztott_kontener_adatok_ures">'+sprinter_admin_adatok.nincs_kivalasztott_pickpackpont+'</div>' ).insertAfter( '#sprinter_kivalasztott_pickpackpont' );
        jQuery( '.sprinter_pickpackpont_lista_elem' ).removeClass( 'sprinter_pickpackpont_kivalasztott_lista_elem' );

        jQuery('.swapdisplay').css('display','block');
        jQuery('#cserecsomag').prop('disabled', false);
        
    });

    if( jQuery( '.sprinter_pickpackpont_lista_elemek' ).length > 0 ) {
        sprinter_pickpackpont_legordulo_generalas();
    }
                    
    function sprinter_pickpackpont_legordulo_generalas( filter = '' ){
        
        jQuery.ajax({
            url: sprinter_admin_adatok.ajax_url,
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

                    /**/
                    lista_class = '';

                    if( jQuery('#sprinter_kivalasztott_pickpackpont').val() ){
                        if( jQuery('#sprinter_kivalasztott_pickpackpont').attr('sprinter_pickpackpont_kod') == sprinter_pickpackpont.shopCode ){
                            lista_class = 'sprinter_pickpackpont_kivalasztott_lista_elem';
                        }
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
                        attr("sprinter_pickpackpont_kod",sprinter_pickpackpont.shopCode).
                        attr("sprinter_pickpackpont_nev",sprinter_pickpackpont.shopName).
                        attr("sprinter_pickpackpont_tipus",sprinter_pickpackpont.shopType).
                        attr("sprinter_pickpackpont_iranyitoszam",sprinter_pickpackpont.zipCode).
                        attr("sprinter_pickpackpont_varos",sprinter_pickpackpont.city).
                        attr("sprinter_pickpackpont_utca",sprinter_pickpackpont.address).
                        attr("sprinter_pickpackpont_adatok",sprinter_pickpackpont_adatok) 
                    ); 
                });
            }
        });
    }

    jQuery( 'body' ).on("click",'.sprinter_pickpackpont_lista_elem', function() { 

        jQuery('#sprinter_kivalasztott_pickpackpont').val( jQuery( this ).attr( 'sprinter_pickpackpont_adatok' ) );
        jQuery('.sprinter_pickpackpont_kivalasztott_kontener_adatok').remove();
        jQuery( 
            '<div class="sprinter_pickpackpont_kivalasztott_kontener_adatok">'+
                jQuery( this ).attr( 'sprinter_pickpackpont_nev' )+'<br/>'+
                jQuery( this ).attr( 'sprinter_pickpackpont_tipus' )+'<br/>'+
                jQuery( this ).attr( 'sprinter_pickpackpont_iranyitoszam' )+'<br/>'+
                jQuery( this ).attr( 'sprinter_pickpackpont_varos' )+'<br/>'+
                jQuery( this ).attr( 'sprinter_pickpackpont_utca' )+
            '</div>' 
        ).insertAfter( '#sprinter_kivalasztott_pickpackpont' );

        jQuery( '.sprinter_pickpackpont_lista_elem' ).removeClass( 'sprinter_pickpackpont_kivalasztott_lista_elem' );
        jQuery( this ).addClass( 'sprinter_pickpackpont_kivalasztott_lista_elem' );

        jQuery('.swapdisplay').css('display','none');
        jQuery('#cserecsomag').prop('disabled', true);
    }); 

    jQuery('body' ).on("keyup", "#sprinter_pickpackpont_filter", function() { 
        
        filter = jQuery(this).val();
        
        if( filter.length == 4 || filter.length == 0 ){
            sprinter_pickpackpont_legordulo_generalas( filter );
        }
    });

    jQuery('body').on('click','.sprinter_fuvarlevelek_torles_gomb', function(){

        jQuery.ajax({
            url: sprinter_admin_adatok.ajax_url,
            data: {
                'action':'sprinter_fuvarlevelek_ajax_torles'
            },
            success:function(sprinter_fuvarlevelek_ajax_torles_return) {
                
                jQuery( ".sprinter_notice" ).remove();

                jQuery( '.sprinter_fuvarlevelek_torles_gomb' ).after( 
                '<div class="updated notice is-dismissible sprinter_notice"><div><img src="'+sprinter_admin_adatok.plugin_dir_url+'/images/sprinter_ikon.png" />'+sprinter_fuvarlevelek_ajax_torles_return+'</div><button type="button" class="notice-dismiss sprinter_notice_dismiss"></button></div>' 
                );

            }
        });

    });

    if( jQuery( "select#sprinter_fizetesi_modok" ).length > 0 ) {
        jQuery("select#sprinter_fizetesi_modok").select2();
    }

    jQuery( 'body' ).on('click','.sprinter_cod_notice_dismiss', function( event ){
        var gomb = jQuery( event.target );
        jQuery.ajax({
            url: sprinter_admin_adatok.ajax_url,
            data: {
                'action':'sprinter_cod_notice_dismiss'
            },
            success:function() {
                gomb.parent( 'p' ).parent( 'div' ).parent( '.notice' ).fadeTo(100, 0, function() {
                gomb.parent( 'p' ).parent( 'div' ).parent( '.notice' ).slideUp(100, function() {
                gomb.parent( 'p' ).parent( 'div' ).parent( '.notice' ).remove();
                });
            });
            }
        });
    });

});
