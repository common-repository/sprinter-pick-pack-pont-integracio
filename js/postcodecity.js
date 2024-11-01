jQuery(document).ready(function($){

	if(typeof postcodes != 'undefined'){

		if(jQuery('.woocommerce-checkout #billing_postcode').length){
			var $postcodeFieldBilling = jQuery('.woocommerce-checkout #billing_postcode');
			var $cityFieldBilling = jQuery('.woocommerce-checkout #billing_city');
			var cityFieldBillingTouched = false;

			$cityFieldBilling.keyup(function() {
				cityFieldBillingTouched = true;
			});

			$postcodeFieldBilling.on('blur input change focusout keyup', function(){
				var postcodeBilling = parseInt($postcodeFieldBilling.val());
				var cityIndexBilling = postcodes.indexOf(postcodeBilling);
				var cityBilling = cities[cityIndexBilling];
				if($postcodeFieldBilling.val().length == 4 && cityIndexBilling > -1 && ($cityFieldBilling.val() == '' || !cityFieldBillingTouched) && postcodes[cityIndexBilling+1] != postcodeBilling){
					$cityFieldBilling.val( cityBilling );
					if($cityFieldBilling.val() != '' && jQuery("#billing_city_field").hasClass("woocommerce-invalid woocommerce-invalid-required-field")){
						jQuery("#billing_city_field").removeClass("woocommerce-invalid woocommerce-invalid-required-field");
					}
					if($cityFieldBilling.val() != ''){
						jQuery("#billing_city_field").addClass("woocommerce-validated");
					}
					jQuery('body').trigger('update_checkout');
				}
			});
		}

		if(jQuery('.woocommerce-checkout #shipping_postcode').length){
			var $postcodeFieldShipping = jQuery('.woocommerce-checkout #shipping_postcode');
			var $cityFieldShipping = jQuery('.woocommerce-checkout #shipping_city');
			var cityFieldShippingTouched = false;

			$cityFieldShipping.keyup(function() {
				cityFieldShippingTouched = true;
			});

			$postcodeFieldShipping.on('blur input change focusout keyup', function(){
				var postcodeShipping = parseInt($postcodeFieldShipping.val());
				var cityIndexShipping = postcodes.indexOf(postcodeShipping);
				var cityShipping = cities[cityIndexShipping];
				if($postcodeFieldShipping.val().length == 4 && cityIndexShipping > -1 && ($cityFieldShipping.val() == '' || !cityFieldShippingTouched) && postcodes[cityIndexShipping+1] != postcodeShipping){
					$cityFieldShipping.val( cityShipping );
					jQuery('body').trigger('update_checkout');
				}
			});
		}

	}

});
