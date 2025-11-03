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
			const $form = $(this),
						ajaxurl = $form.data('ajaxurl'),
						consultantTitle = $form.data('consultant-title'),
						$container = $form.find('div[data-ech-field="consultant"]');

			$form.find('input[name="shop"]').on('change', async function () {
				$form.find('.location-item').removeClass('active');
				$(this).parent().addClass('active');
				const shopCode = $(this).val();
				$container.html('<div class="loading-spinner"></div>');

				try {
					const res = await fetch(ajaxurl, {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: new URLSearchParams({
							action: 'get_consultant_list',
							shop_area_code: shopCode,
							consultant_title: consultantTitle
						})
					});

					const json = await res.json();
					$container.empty();
					if (json.success && json.data.consultant_list.length) {
						$container.append(json.data.consultant_list);
						
					} else {
						$container.append(`<div>${json.data?.message || '沒有符合的顧問'}</div>`);
					}
				} catch (error) {
					console.error(error);
					$container.html('<div class="consultant-list-title">載入失敗，請稍後再試。</div>');
				}
			});

			$form.on('change', 'input[name="consultant"]', function () {
				const $input = $(this);
				$form.find('.consultant-item label').text('選擇此顧問');
				$form.find('.consultant-item').removeClass('active');
			
				$input.siblings('label').text('已選取');
				$input.closest('.consultant-item').addClass('active');
			});

		});

		/*********** .echc_form submit ***********/
		$('.echc_form').on("submit", async function (e) {
			e.preventDefault();
			const $form = $(this);
			const ajaxurl = $form.data('ajaxurl'),
				_source_type = $form.data('source-type'),
				_first_name = $form.find("input[name='first_name']").val(),
				_last_name = $form.find("input[name='last_name']").val(),
				_name = _last_name + _first_name,
				_tel_prefix = $form.find("select[name='telPrefix']").val(),
				_tel = $form.find("input[name='tel']").val(),
				_phone = _tel_prefix + _tel,
				_booking_date = $form.find("input[name='booking_date']").val(),
				_booking_time = $form.find("input[name='booking_time']").val(),
				_booking_location = $form.find("input[name='shop']:checked").data('shop-text'),
				_consultant = $form.find("input[name='consultant']:checked").data('consultant-text'),
				_msg_api = $form.data("msg-send-api"),
				_msg_template = $form.data("msg-template"),
				_msg_header = $form.data("msg-header"),
				_msg_body = $form.data("msg-body"),
				_msg_button = $form.data("msg-button"),
				_fbcapi_send = $form.data("fbcapi-send"),
				_acceptPll = $form.data("accept-pll"),
				_user_ip = $form.data("ip");

			if ((_tel_prefix === "+852" && _tel.length !== 8) || (_tel_prefix === "+853" && _tel.length !== 8)) {
				$form.find(".echc_formMsg").html("+852, +853電話必需8位數字(沒有空格)");
				return false;
			}

			if ((_tel_prefix === "+86" && _tel.length !== 11)) {
				$form.find(".echc_formMsg").html("+86電話必需11位數字(沒有空格)");
				return false;
			}

			if (!_consultant) {
				$form.find(".echc_formMsg").html("必須選擇顧問");
				return false;
			}

			$form.find(".echc_formMsg").html("提交中...");
			$form.find('button[type="submit"]').prop('disabled', true).html("提交中...");

			const msgData = {
				ajaxurl: ajaxurl,
				source_type: _source_type,
				first_name: _first_name,
				last_name: _last_name,
				name: _name,
				phone: _phone,
				booking_date: _booking_date,
				booking_time: _booking_time,
				booking_location: _booking_location,
				consultant: _consultant,
				msg_api: _msg_api,
				msg_template: _msg_template,
				msg_header: _msg_header,
				msg_body: _msg_body,
				msg_button: _msg_button,
			};
			// console.log(msgData);

			// reCaptcha
			const applyRecapt = $form.data("apply-recapt");
			if (applyRecapt == "1") {
				const recaptSiteKey = $form.data("recapt-site-key");
				const recaptScore = $form.data("recapt-score");

				grecaptcha.ready(function () {
					grecaptcha.execute(recaptSiteKey, { action: 'submit' }).then(async function (recapt_token) {
						const recaptRes = await fetch(ajaxurl, {
							method: 'POST',
							headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
							body: new URLSearchParams({ action: 'echc_recaptVerify', recapt_token })
						});

						const recaptObj = await recaptRes.json();
						if (recaptObj.success && recaptObj.score >= recaptScore) {
							if(_fbcapi_send == "1"){
								echcFBCapiSend(ajaxurl, _acceptPll, _phone, _first_name, _last_name, _user_ip);
							}
							wtsSendMsg(msgData);
						}
					});
				});
			} else {
				if(_fbcapi_send == "1"){
					echcFBCapiSend(ajaxurl, _acceptPll, _phone, _first_name, _last_name, _user_ip);
				}
				wtsSendMsg(msgData);
			}
		});

	}); // end $(function)
	async function wtsSendMsg(data) {
		const ajaxurl = data.ajaxurl;
		let _action = {
			'omnichat': 'echc_OmnichatSendMsg',
			'sleekflow': 'echc_SleekflowSendMsg',
			'kommo': 'echc_KommoSendMsg'
		};

		const msgData = {
			action: _action[data.msg_api],
			source_type: data.source_type,
			name: data.name,
			first_name: data.first_name,
			last_name: data.last_name,
			phone: data.phone,
			booking_date: data.booking_date,
			booking_time: data.booking_time,
			booking_location: data.booking_location,
			consultant: data.consultant,
			msg_template: data.msg_template,
			msg_header: data.msg_header,
			msg_body: data.msg_body,
			msg_button: data.msg_button,
		};
		const handlers = {
			omnichat: result => result.content?.messageId,
			sleekflow: result => result.status === "Sending",
			kommo: result => !!result
		};

		try {
			const res = await fetch(ajaxurl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams(msgData)
			});

			const json = await res.json();
			// console.log("response: ", json);
			if (!json.success) {
				throw new Error(json.data.message);
			}
			// const result = JSON.parse(json.data.result);
			const result = json.data.result;
			// console.log(result);
			const isSuccess = handlers[data.msg_api]?.(result);
			if (!isSuccess) {
				throw new Error(`${data.msg_api} 發送失敗`);
			}
			console.log(`${data.msg_api} 發送成功`);
	
			window.location.replace(origin + '/thanks');
	
		} catch (error) {
			console.error(error);
			alert("無法提交閣下資料，請重試");
			location.reload(true);
		}
	} // wtsSendMsg

	async function echcFBCapiSend(_ajaxurl, _acceptPll, _phone, _fn, _ln, _user_ip) {
		const _website_url_no_para = location.origin + location.pathname;
		const _event_id = Date.now(); // unique event ID
		const _currnetUrl = window.location.href;
		let _fbp = getCookieValue('_fbp'),
				_external_id = getCookieValue('_fbuuid'),
				_fbc = getCookieValue('_fbc');
	
		if (_fbc == null) {
			const urlParams = new URLSearchParams(_currnetUrl);
			const fbclid = urlParams.get('fbclid');
			if (fbclid) {
				_fbc = 'fb.1.' + pageEnterTime + '.' + _fbc;
			}
		}

		const fb_data = {
			action: 'echc_FBCapi',
			website_url: _website_url_no_para,
			user_agent: navigator.userAgent,
			user_phone: _phone,
			user_fn: _fn,
			user_ln: _ln,
			event_id: _event_id,
			fbp: _fbp,
			fbc: _fbc,
			accept_pll: _acceptPll,
			external_id: _external_id,
			user_ip: _user_ip
		};
		console.log(fb_data);
		// ---- send to Meta Pixel front-end ----
		if (_acceptPll) {
			fbq('trackCustom', 'Consultant', { event_source_url: _website_url_no_para }, { eventID: 'Consultant' + _event_id, external_id: _external_id });
			fbq('track', 'Purchase', { value: 0.00, currency: 'HKD' }, { eventID: 'Purchase' + _event_id });
			fbq('track', 'CompleteRegistration', {}, { eventID: 'CompleteRegistration' + _event_id });
		} else {
			fbq('trackCustom', 'ConsultantWithoutPII', { event_source_url: _website_url_no_para }, { eventID: 'Consultant' + _event_id, external_id: _external_id });
			fbq('trackCustom', 'PurchaseWithoutPII', { value: 0.00, currency: 'HKD', event_source_url: _website_url_no_para }, { eventID: 'Purchase' + _event_id, external_id: _external_id });
			fbq('trackCustom', 'CompleteRegistrationWithoutPII', { event_source_url: _website_url_no_para }, { eventID: 'CompleteRegistration' + _event_id, external_id: _external_id });
		}
	
		try {
			const res = await fetch(_ajaxurl, {
				method: "POST",
				headers: { "Content-Type": "application/x-www-form-urlencoded" },
				body: new URLSearchParams(fb_data)
			});
			const json = await res.json();
			if (!json.success) {
				throw new Error(json.data.message);
			}
			// const result = JSON.parse(json.data.result);
			const result = json.data.result;
	
			Object.keys(result).forEach(eventName => {
				const event = result[eventName];
				if (event.hasOwnProperty("events_received")) {
					console.log(`${eventName}: ${event.events_received}`);
				} else {
					console.log(eventName, event);
				}
			});
		} catch (error) {
			console.error("FBCapi request failed:", error);
		}
	}
	




})(jQuery);
