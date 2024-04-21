jQuery(document).ready(function() {

	// #1 package block
    jQuery('.js-submit .elementor-button-text').click(function() {

		var post_id = 0;
		var selected_card = jQuery('.e-loop-item .selected-card');

		if( selected_card.length > 0 ) { 

			var classList = jQuery(selected_card).parent().attr('class').split(/\s+/);
			jQuery.each(classList, function(index, item) {

				if (item.indexOf("post-") >= 0) {
					post_id = item.split('-')[1];
				}
			});

			if( post_id ) {
				AjaxRedirectSB( post_id );
			}
			
		}

        return false;

    });

	// #2 package block
    jQuery('.package .elementor-button-wrapper').click(function() {

		var classList = jQuery(this).parent().parent().parent().parent().parent().attr('class').split(/\s+/);

		jQuery.each(classList, function(index, item) {

			if (item.indexOf("post-") >= 0) {
				post_id = item.split('-')[1];
			}
		});

		if( post_id ) {
			AjaxRedirectSB( post_id );
		}

        return false;

    });

});


function AjaxRedirectSB( post_id=false ) {

	var data = {
		action: 'sb_checkout_redirect',
		post_id: post_id
	};

	jQuery.ajax({
		type: 'POST',
		url: '/wp-admin/admin-ajax.php',
		data: data,
		dataType: 'json',
		success: function(response) { 

			if( response.redirect_url ) {
				window.location.href = response.redirect_url;
			}
			
		},
		
	});

}