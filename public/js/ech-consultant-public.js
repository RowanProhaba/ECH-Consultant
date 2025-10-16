(function ($) {
	'use strict';

	$(function () {

		/*********** Datepicker & Timepicker ***********/
		jQuery('.echc_timepicker').timepicker({ minTime: '11:00am', maxTime: '7:30pm', step: 15 });
		jQuery(".echc_datepicker").datepicker({
			beforeShowDay: nosunday,
			dateFormat: 'yy-mm-dd',
			minDate: 1 // T+1
		});
		jQuery('#ui-datepicker-div').addClass('skiptranslate notranslate');
		/*********** (END) Datepicker & Timepicker ***********/

		jQuery('.echc_form div[data-ech-field="shop"] select[name="shop"], .echc_form div[data-ech-field="shop"] input[name="shop"]').on('change', function () {
			const thisForm = jQuery(this).parents('.echc_form'),
				ajaxurl = jQuery(this).parents('.echc_form').data("ajaxurl"),
				shop_area_code = jQuery(this).val();
			jQuery(thisForm).find('select[name="consultant"]').html('<option disabled="" selected="" value="">載入中...</option>');

			const data = {
				'action': 'get_ec_consultants',
				'shop_area_code': shop_area_code
			}
			$.post(ajaxurl, data, function (res) {
				jQuery('.consultant-container').empty();
				const consultantSelect = jQuery('.echc_form').find('select[name="consultant"]');
				jQuery(consultantSelect).empty();

				if (res.success) {
					jQuery(consultantSelect).append('<option disabled selected>*請選擇顧問</option>');
					res.data.consultants.forEach(item => {
						jQuery(consultantSelect).append(`<option value="${item.id}">${item.name}</option>`);
					});
				} else {
					jQuery(consultantSelect).append(`<option disabled selected>${res.data?.message || '沒有符合的顧問'}</option>`);
				}
			});
		});

		jQuery('.echc_form div[data-ech-field="consultant"] select[name="consultant"]').on('change', function () {
			const thisForm = jQuery(this).parents('.echc_form'),
				ajaxurl = jQuery(this).parents('.echc_form').data("ajaxurl"),
				consultantId = jQuery(this).val();
			jQuery('.consultant-container').html('載入中...');

			const data = {
				'action': 'get_consultant_info',
				'consultant_id': consultantId
			}
			$.post(ajaxurl, data, function (res) {
				jQuery('.consultant-container').html(res);
			});
		});

		/*********** Form Submit ***********/
		jQuery('.echc_form').on("submit", function (e) {
			e.preventDefault();
			const ip = jQuery(this).data("ip"),
				ajaxurl = jQuery(this).data("ajaxurl"),
				shop_count = jQuery(this).data("shop-count"),
				_website_url = jQuery(this).data("url"),
				_tel_prefix = jQuery(this).find("select[name='telPrefix']").val(),
				_tel = jQuery(this).find("input[name='tel']").val(),
				_booking_date = jQuery(this).find(".echc_datepicker").val(),
				_booking_time = jQuery(this).find(".echc_timepicker").val();

			if (shop_count <= 3) {
				const _shop_area_code = jQuery(this).find('input[name=shop]:checked').val();
			} else {
				const _shop_area_code = jQuery(this).find('select[name=shop]').val();
			}

			if ((_tel_prefix == "+852" && _tel.length != 8) || (_tel_prefix == "+853" && _tel.length != 8)) {
				jQuery(this).find(".echc_formMsg").html("+852, +853電話必需8位數字(沒有空格)");
				return false;
			} else if ((_tel_prefix == "+86" && _tel.length != 11)) {
				jQuery(this).find(".echc_formMsg").html("+86電話必需11位數字(沒有空格)");
				return false;

			} else {
				jQuery(".ech_echc_form button[type=submit]").prop('disabled', true);
				jQuery(this).find(".echc_formMsg").html("提交中...");
				jQuery(".ech_echc_form button[type=submit]").html("提交中...");

				// if apply reCAPTCHA
				const applyRecapt = jQuery(this).data("apply-recapt");
				const thisForm = jQuery(this);
				if (applyRecapt == "1") {
					const recaptSiteKey = jQuery(this).data("recapt-site-key");
					const recaptScore = jQuery(this).data("recapt-score");
					grecaptcha.ready(function () {
						grecaptcha.execute(recaptSiteKey, { action: 'submit' }).then(function (recapt_token) {
							const recaptData = {
								'action': 'echc_recaptVerify',
								'recapt_token': recapt_token
							};
							$.post(ajaxurl, recaptData, function (recapt_msg) {
								const recaptObj = JSON.parse(recapt_msg);
								if (recaptObj.success && recaptObj.score >= recaptScore) {
									// if recapt success then send to MSP

								}
							});
						}); // grecaptcha.execute.then
					}); //grecaptcha.ready

				} else {
					// if recapt is disabled, send to msp

				}
			}//_tel_prefix
		}); // onclick
		/*********** (END) Form Submit ***********/
	});

})(jQuery);
