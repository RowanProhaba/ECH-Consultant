(function( $ ) {
	'use strict';
	$(function(){


		/************* GENERAL FORM **************/
		$('#echc_gen_settings_form').on('submit', function(e){
			e.preventDefault();
			$('.statusMsg').removeClass('error');
			$('.statusMsg').removeClass('updated');
			let statusMsg = '';
			let validStatus = false;
			const msgTemplate = $('#echc_msg_template').val();

			// form validation
			if( msgTemplate == '') {
				validStatus = false;
				statusMsg += 'Message Template is missing <br>';
			} else {
				validStatus = true;
			}

			// set error status msg
			if ( !validStatus ) {
				$('.statusMsg').html(statusMsg);
				$('.statusMsg').addClass('error');
				return;
			} else {
				$('#echc_gen_settings_form').attr('action', 'options.php');
				$('#echc_gen_settings_form')[0].submit();
				// output success msg
				statusMsg += 'Settings updated <br>';
				$('.statusMsg').html(statusMsg);
				$('.statusMsg').addClass('updated');
			}
		});
		/************* (END) GENERAL FORM **************/

		/************* COPY SAMPLE SHORTCODE **************/
		$('#copyShortcode').click(function(){
			const shortcode = $('#sample_shortcode').text();
			navigator.clipboard.writeText(shortcode).then(
				function(){
					$('#copyMsg').html('');
					$('#copyShortcode').html('Copied !'); 
					setTimeout(function(){
						$('#copyShortcode').html('Copy Shortcode'); 
					}, 3000);
				},
				function() {
					$('#copyMsg').html('Unable to copy, try again ...');
				}
			);
		});
		/************* (END)COPY SAMPLE SHORTCODE **************/



	}); // doc ready

})( jQuery );