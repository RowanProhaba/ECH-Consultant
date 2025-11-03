<?php 

class Ech_consultant_Fb_Capi_Public
{
    /**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->pixel_id = get_option( 'ech_lfg_pixel_id' );
		$this->fb_access_token= get_option( 'ech_lfg_fb_access_token' );
	}


  public function echc_FBCapi() {

		$event_id = $_POST['event_id'];
		$current_page = $_POST['website_url'];
		$user_agent = $_POST['user_agent'];
		$user_phone = $_POST['user_phone'];
		$user_fn = $_POST['user_fn'];
		$user_ln = $_POST['user_ln'];
		$fbp = $_POST['fbp'];
		$fbc = $_POST['fbc'];
		$accept_pll = $_POST['accept_pll'];
		$external_id = $_POST['external_id'];
		
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$user_ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$user_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$user_ip = $_SERVER['REMOTE_ADDR'];
		}

		$user_data = [
			// "em" => hash('sha256', $user_email),
			"client_ip_address" => $user_ip,
			"client_user_agent" => $user_agent,
			"fbp" => $fbp,
			"fbc" => $fbc,
			"external_id" => $external_id,
		];
		$withoutPII_str='WithoutPII';
		if (intval($accept_pll)) {
			$withoutPII_str = '';
			$user_data['ph'] = hash('sha256', $user_phone);
			if(!empty($user_fn)){
				$user_data['fn'] = hash('sha256', $user_fn);
			}
			if(!empty($user_ln)){
				$user_data['ln'] = hash('sha256', $user_ln);
			}
		}

		$param_datas = [
			'Consultant'.$withoutPII_str => [
				"event_id" => "Consultant".$event_id,
				"event_name" => "Consultant".$withoutPII_str,
				"event_time" => time(),
				"action_source" => "website",
				"event_source_url" => $current_page,
				"user_data" => $user_data
			],
			'Purchase'.$withoutPII_str => [
				"event_id" => "Purchase".$event_id,
				"event_name" => "Purchase".$withoutPII_str,
				"event_time" => time(),
				"action_source" => "website",
				"event_source_url" => $current_page,
				"custom_data" => [
					"content_name" => "Consultant",
					"currency" => "HKD",
					"value" => 0.00
				],
				"user_data" => $user_data
			],
			'CompleteRegistration'.$withoutPII_str => [
				"event_id" => "CompleteRegistration".$event_id,
				"event_name" => "CompleteRegistration".$withoutPII_str,
				"event_time" => time(),
				"action_source" => "website",
				"event_source_url" => $current_page,
				"user_data" => $user_data
			]
		];

		if (empty($user_phone)) {
			wp_send_json_error(['message' => 'Missing phone number.']);
		}

		$results = [];
		foreach ($param_datas as $key => $data) {
				$results[$key] = $this->fb_curl(['data' => [$data]]);
		}

		wp_send_json_success([
			'result' => $results,
			'send_data' => $param_datas
		]);
	}

	private function fb_curl($param_data) {
    $ch = curl_init();

		$fbAPI_version = "v11.0";
		$pixel_id = $this->pixel_id;
		$fb_access_token= $this->fb_access_token;
		$fb_graph_link = "https://graph.facebook.com/".$fbAPI_version."/".$pixel_id."/events?access_token=".$fb_access_token;

    $headers = array(
        "content-type: application/json",
    );

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_URL, $fb_graph_link);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($param_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error];
    }
    
    return json_decode($response, true);
	}



}
