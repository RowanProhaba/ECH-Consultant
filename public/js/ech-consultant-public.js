(function ($) {
	'use strict';

	$(function () {

		/*********** 初始化：Timepicker & Datepicker ***********/
		$('.echc_timepicker').timepicker({ minTime: '11:00am', maxTime: '7:30pm', step: 15 });
		$('.echc_datepicker').datepicker({
			beforeShowDay: nosunday,
			dateFormat: 'yy-mm-dd',
			minDate: 1 // T+1
		});
		$('#ui-datepicker-div').addClass('skiptranslate notranslate');


		$('.echc_form').each(function () {
			const $form = $(this);
			const ajaxurl = $form.data('ajaxurl');
			const $consultantSelect = $form.find('select[name="consultant"]');
			const $container = $('.consultant-container');

			$form.find('select[name="shop"], input[name="shop"]').on('change', function () {
				const shopCode = $(this).val();
				$consultantSelect.html('<option disabled selected>載入中...</option>');
				$container.html('');

				const data = {
					action: 'get_ec_consultants',
					shop_area_code: shopCode
				};

				$.post(ajaxurl, data, function (res) {
					$consultantSelect.empty();
					if (res.success && res.data.consultants.length > 0) {
						$consultantSelect.append('<option disabled selected>*請選擇顧問</option>');
						res.data.consultants.forEach(item => {
							$consultantSelect.append(`<option value="${item.id}">${item.name}</option>`);
						});
					} else {
						const msg = res.data?.message || '沒有符合的顧問';
						$consultantSelect.append(`<option disabled selected>${msg}</option>`);
					}
				}).fail(function () {
					$consultantSelect.html('<option disabled selected>載入失敗，請稍後再試。</option>');
				});
			});

			$form.find('select[name="consultant"]').on('change', function () {
				const consultantId = $(this).val();
				// console.log(consultantId);
				$container.html(`<div class="loading-spinner"></div>`);

				const data = {
					action: 'get_consultant_info',
					consultant_id: consultantId
				};

				$.post(ajaxurl, data, function (res) {
					// console.log(res);
					$container.html(res);
				}).fail(function () {
					$container.html('<p class="error">載入失敗，請稍後再試。</p>');
				});
			});

		});

		/*********** .echc_form submit ***********/
		$('.echc_form').on("submit", function (e) {
			e.preventDefault();
			const $form = $(this);
			const ajaxurl = $form.data('ajaxurl');
			const _tel_prefix = $form.find("select[name='telPrefix']").val();
			const _tel = $form.find("input[name='tel']").val();

			if ((_tel_prefix === "+852" && _tel.length !== 8) || (_tel_prefix === "+853" && _tel.length !== 8)) {
				$form.find(".echc_formMsg").html("+852, +853電話必需8位數字(沒有空格)");
				return false;
			} else if ((_tel_prefix === "+86" && _tel.length !== 11)) {
				$form.find(".echc_formMsg").html("+86電話必需11位數字(沒有空格)");
				return false;
			} else {
				$(".ech_echc_form button[type=submit]").prop('disabled', true);
				$form.find(".echc_formMsg").html("提交中...");
				$(".ech_echc_form button[type=submit]").html("提交中...");

				const applyRecapt = $(this).data("apply-recapt");
				if (applyRecapt == "1") {
					const recaptSiteKey = $(this).data("recapt-site-key");
					const recaptScore = $(this).data("recapt-score");
					grecaptcha.ready(function () {
						grecaptcha.execute(recaptSiteKey, { action: 'submit' }).then(function (recapt_token) {
							const recaptData = {
								'action': 'echc_recaptVerify',
								'recapt_token': recapt_token
							};
							$.post(ajaxurl, recaptData, function (recapt_msg) {
								const recaptObj = JSON.parse(recapt_msg);
								if (recaptObj.success && recaptObj.score >= recaptScore) {
									// success, send to MSP
								}
							});
						});
					});
				} else {
					// recaptcha disabled
				}
			}
		});

		function initConsultantList($form, shopCode) {
			const ajaxurl = $form.data('ajaxurl');
			const $consultantSelect = $form.find('select[name="consultant"]');
			const $container = $('.consultant-container');

			$consultantSelect.html('<option disabled selected>載入中...</option>');
			$container.html('');

			const data = {
				action: 'get_ec_consultants',
				shop_area_code: shopCode
			};

			$.post(ajaxurl, data, function (res) {
				$consultantSelect.empty();
				if (res.success && res.data.consultants.length > 0) {
					$consultantSelect.append('<option disabled selected>*請選擇顧問</option>');
					res.data.consultants.forEach(item => {
						$consultantSelect.append(`<option value="${item.id}">${item.name}</option>`);
					});
				} else {
					const msg = res.data?.message || '沒有符合的顧問';
					$consultantSelect.append(`<option disabled selected>${msg}</option>`);
				}
			}).fail(function () {
				$consultantSelect.html('<option disabled selected>載入失敗，請稍後再試。</option>');
			});
		}

		function initConsultantInfo($form, consultantId) {
			const ajaxurl = $form.data('ajaxurl');
			const $container = $('.consultant-container');
			$container.html(`<div class="loading-spinner"></div>`);

			const data = {
				action: 'get_consultant_info',
				consultant_id: consultantId
			};

			$.post(ajaxurl, data, function (res) {
				// console.log(res);
				$container.html(res);
				console.log($container.html(res));
			}).fail(function () {
				$container.html('<p class="error">載入失敗，請稍後再試。</p>');
			});
		}

	}); // end $(function)

})(jQuery);
