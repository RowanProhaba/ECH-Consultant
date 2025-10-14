(function( $ ) {
	'use strict';

	$(function () {

		/*********** Datepicker & Timepicker ***********/
		jQuery('.echc_timepicker').timepicker({ minTime: '11:00am', maxTime: '7:30pm',step: 15 });
		jQuery(".echc_datepicker").datepicker({
			beforeShowDay: nosunday,
			dateFormat: 'yy-mm-dd',
			minDate: 1 // T+1
		});
		jQuery('#ui-datepicker-div').addClass('skiptranslate notranslate');
		/*********** (END) Datepicker & Timepicker ***********/


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
			} else if (seminar == 1 && jQuery(this).find("select[name='select_seminar'] option:selected").attr("data-shop") === undefined) {
				jQuery(this).find(".echc_formMsg").html("請選擇講座場次");
				return false;
			} else {
				var checked_item_count = jQuery(this).find("input[name='item']:checked").length;
				if (checked_item_count == 0 && item_required == 1) {
					jQuery(this).find(".echc_formMsg").html("請選擇咨詢項目");
					return false;
				} else {
					jQuery(".ech_echc_form button[type=submit]").prop('disabled', true);
					jQuery(this).find(".echc_formMsg").html("提交中...");
					jQuery(".ech_echc_form button[type=submit]").html("提交中...");

					// if apply reCAPTCHA
					var applyRecapt = jQuery(this).data("apply-recapt");
					var thisForm = jQuery(this);
					if (applyRecapt == "1") {
						var recaptSiteKey = jQuery(this).data("recapt-site-key");
						var recaptScore = jQuery(this).data("recapt-score");
						grecaptcha.ready(function () {
							grecaptcha.execute(recaptSiteKey, { action: 'submit' }).then(function (recapt_token) {
								var recaptData = {
									'action': 'echc_recaptVerify',
									'recapt_token': recapt_token
								};
								$.post(ajaxurl, recaptData, function (recapt_msg) {
									var recaptObj = JSON.parse(recapt_msg);
									if (recaptObj.success && recaptObj.score >= recaptScore) {
										// if recapt success then send to MSP
										echc_dataSendToMSP(thisForm, _token, _source, _name, _user_ip, _website_name, _website_url, items, _tel_prefix, _tel, _email, _age_group, _shop_area_code, _booking_date, _booking_time, _remarks, ajaxurl, tks_para);
									}
								});
							}); // grecaptcha.execute.then
						}); //grecaptcha.ready

					} else {
						// if recapt is disabled, send to msp
						echc_dataSendToMSP(thisForm, _token, _source, _name, _user_ip, _website_name, _website_url, items, _tel_prefix, _tel, _email, _age_group, _shop_area_code, _booking_date, _booking_time, _remarks, ajaxurl, tks_para);
					}

				} // checked_item_count
			}//_tel_prefix
		}); // onclick
		/*********** (END) Form Submit ***********/
	});

})( jQuery );
