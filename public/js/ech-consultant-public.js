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
			const $container = $form.next('.consultant-container');

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
						$consultantSelect.append('<option disabled="" selected="" value="">*請選擇顧問</option>');
						res.data.consultants.forEach(item => {
							$consultantSelect.append(`<option value="${item.id}">${item.name}</option>`);
						});
					} else {
						const msg = res.data?.message || '沒有符合的顧問';
						$consultantSelect.append(`<option disabled="" selected="" value="">${msg}</option>`);
					}
				}).fail(function () {
					$consultantSelect.html('<option disabled="" selected="" value="">載入失敗，請稍後再試。</option>');
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
			const $form = $(this), 
						ajaxurl = $form.data('ajaxurl'), 
						_tel_prefix = $form.find("select[name='telPrefix']").val(), 
						_tel = $form.find("input[name='tel']").val(),
						_booking_date = $form.find("input[name='booking_date']").val(),
						_booking_time = $form.find("input[name='booking_time']").val(),
						_booking_location = $form.find("select[name='shop'] option:selected, input[name='shop']:checked").data("shop-text-value"),
						_consultant = $form.find("select[name='consultant'] option:selected").text(),
						_msg_api = $form.data("msg-send-api"),
						_msg_template = $form.data("msg-template"),
						_msg_header = $form.data("msg-header"),
						_msg_body = $form.data("msg-body"),
						_msg_button = $form.data("msg-button");
			if ((_tel_prefix === "+852" && _tel.length !== 8) || (_tel_prefix === "+853" && _tel.length !== 8)) {
				$form.find(".echc_formMsg").html("+852, +853電話必需8位數字(沒有空格)");
				return false;
			} else if ((_tel_prefix === "+86" && _tel.length !== 11)) {
				$form.find(".echc_formMsg").html("+86電話必需11位數字(沒有空格)");
				return false;
			} else if (_consultant == null || _consultant == undefined) {
				$form.find(".echc_formMsg").html("必須選擇顧問");
				return false;
			} else {
				$(".ech_echc_form button[type=submit]").prop('disabled', true);
				$form.find(".echc_formMsg").html("提交中...");
				$(".ech_echc_form button[type=submit]").html("提交中...");

				const msgData = {
					ajaxurl : ajaxurl,
					phone : _tel_prefix + _tel,
					booking_date : _booking_date,
					booking_time : _booking_time,
					booking_location : _booking_location,
					consultant : _consultant,
					msg_api : _msg_api,
					msg_template : _msg_template,
					msg_header : _msg_header,
					msg_body : _msg_body,
					msg_button : _msg_button,
				}
				console.log(msgData);
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
									// success, send to msg api
									wtsSendMsg(msgData);
								}
							});
						});
					});
				} else {
					// recaptcha disabled
					wtsSendMsg(msgData);
				}
			}
		});

	}); // end $(function)
	function wtsSendMsg(data) {
		const ajaxurl = data.ajaxurl;
		const msg_api = data.msg_api;
		let _action = '';
		switch (msg_api) {
			case 'omnichat':
				_action = 'echc_OmnichatSendMsg';
				break;
			case 'sleekflow':
				_action = 'echc_SleekflowSendMsg';
				break;
			case 'kommo':
				_action = 'echc_KommoSendMsg';
				break;
		}
		const msgData = {
			'action': _action,
			'msg_template': data.msg_template,
			'phone': data.phone,
			'booking_date': data.booking_date,
			'booking_time': data.booking_time,
			'booking_location': data.booking_location,
			'consultant': data.consultant,
			'msg_header': data.msg_header,
			'msg_body': data.msg_body,
			'msg_button': data.msg_button,
		};
		console.log(msgData);
		$.post(ajaxurl, msgData, function (res) {
			console.log(res);
			if (res.success) {
				const result = JSON.parse(res.data.result);
				console.log(result);
				switch (msg_api) {
					case 'omnichat':						
						if (result.content.messageId) {
							console.log('wtsapp msg sent');
						} else {
							console.log('wati send error');
						}
						break;
					case 'sleekflow':
						const createCustomObjects = JSON.parse(result.createCustomObjects);
						if (result.status === "Sending") {
							console.log('wtsapp msg sent');
						} else {
							console.error("SleekFlow 訊息發送失敗:", sendMsg);
						}
						if (createCustomObjects.primaryPropertyValue) {
							console.log('Created Custom Objects');
						} else {
							console.error("SleekFlow Create Custom Objects 失敗:", createCustomObjects);
						}
						break;
					case 'kommo':
						if (result) {
							console.log('wtsapp msg sent');
						} else {
							console.log(resObj.message);
						}
						break;
				}

				window.location.replace(origin + '/thanks');

			} else {
					console.error(res.data.message);
					alert("無法提交閣下資料, 請重試");
					location.reload(true);
			}
		});
	} // wtsSendMsg
})(jQuery);
