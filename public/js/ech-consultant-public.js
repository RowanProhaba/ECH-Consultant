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

			$form.find('select[name="shop"], input[name="shop"]').on('change', async function () {
				const shopCode = $(this).val();
				$consultantSelect.html('<option disabled="" selected="" value="">載入中...</option>');
				$container.empty();

				try {
					const res = await fetch(ajaxurl, {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: new URLSearchParams({
							action: 'get_ec_consultants',
							shop_area_code: shopCode
						})
					});

					const json = await res.json();
					$consultantSelect.empty();

					if (json.success && json.data.consultants.length) {
						$consultantSelect.append('<option disabled="" selected="" value="">*請選擇顧問</option>');
						json.data.consultants.forEach(item => {
							$consultantSelect.append(`<option value="${item.id}">${item.name}</option>`);
						});
					} else {
						$consultantSelect.append(`<option disabled="" selected="" value="">${json.data?.message || '沒有符合的顧問'}</option>`);
					}
				} catch (error) {
					console.error(error);
					$consultantSelect.html('<option disabled="" selected="" value="">載入失敗，請稍後再試。</option>');
				}
			});

			$form.find('select[name="consultant"]').on('change', async function () {
				const consultantId = $(this).val();
				$container.html('<div class="loading-spinner"></div>');

				try {
					const res = await fetch(ajaxurl, {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: new URLSearchParams({
							action: 'get_consultant_info',
							consultant_id: consultantId
						})
					});

					const html = await res.text();
					$container.html(html);

				} catch (error) {
					console.error(error);
					$container.html('<p class="error">載入失敗，請稍後再試。</p>');
				}
			});

		});

		/*********** .echc_form submit ***********/
		$('.echc_form').on("submit", async function (e) {
			e.preventDefault();
			const $form = $(this);

			const ajaxurl = $form.data('ajaxurl'),
				_source_type = $form.data('source-type');
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
			$(".ech_echc_form button[type=submit]").prop('disabled', true).html("提交中...");

			const msgData = {
				ajaxurl: ajaxurl,
				source_type: _source_type,
				phone: _tel_prefix + _tel,
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
							wtsSendMsg(msgData);
						}
					});
				});
			} else {
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
			console.log("response: ", json);
			if (!json.success) {
				throw new Error(json.data.message);
			}
			// const result = JSON.parse(json.data.result);
			const result = json.data.result;
			console.log(result);
			const isSuccess = handlers[data.msg_api]?.(result);
			if (!isSuccess) {
				throw new Error(`${data.msg_api} 發送失敗`);
			}
			console.log(`${data.msg_api} 發送成功`);

			// window.location.replace(origin + '/thanks');
		} catch (error) {
			console.error(error);
			alert("無法提交閣下資料，請重試");
			// location.reload(true);
		}
	} // wtsSendMsg
})(jQuery);
