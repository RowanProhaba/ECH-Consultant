<?php 

class Ech_consultant_Sleekflow_Public
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

		$this->brand_name = get_option('ech_lfg_brand_name');
		$this->brand_whatsapp = get_option('ech_lfg_brand_whatsapp');
        $this->sleekflow_token = get_option('ech_lfg_sleekflow_token');
	}


    public function echc_SleekflowSendMsg() {
        $source_type = isset($_POST['source_type']) && $_POST['source_type'] != '' ? $_POST['source_type'] : '';

        
        $object_key = '';
        $msg_template = '';
        if(isset($_POST['msg_template']) && $_POST['msg_template'] !=''){
            $object_key = explode('|',$_POST['msg_template'])[0];
            $msg_template = explode('|',$_POST['msg_template'])[1];
            if($source_type){
                $msg_template.= $msg_template.'_'.$source_type;
            }
        }

        $booking_date = isset($_POST['booking_date']) && $_POST['booking_date'] != '' ? $_POST['booking_date'] : '';
        $booking_time = isset($_POST['booking_time']) && $_POST['booking_time'] != '' ? $_POST['booking_time'] : '';
        $booking_location = isset($_POST['booking_location']) && $_POST['booking_location'] != '' ? $_POST['booking_location'] : '';
        $consultant = isset($_POST['consultant']) && $_POST['consultant'] != '' ? $_POST['consultant'] : '';
        $msg_header = isset($_POST['msg_header']) && $_POST['msg_header'] != '' ? $_POST['msg_header'] : '';
        $msg_body = isset($_POST['msg_body']) && $_POST['msg_body'] != '' ? $_POST['msg_body'] : '';
        $msg_button = isset($_POST['msg_button']) && $_POST['msg_button'] != '' ? $_POST['msg_button'] : '';
        $phone = preg_replace('/\D/', '', $_POST['phone']);
        $check_customer = $this->consultant_sleekflow_curl(
            "https://api.sleekflow.io/api/contact?limit=1&offset=0&phoneNumber={$phone}",
            'GET'
        );
        $customer_id = $check_customer[0]['id'] ?? null;
        if(!$customer_id){

            $customer_data = [
                'phone' => $phone
            ];

            $customer_id = $this->consultant_sleekflow_curl(
                "https://api.sleekflow.io/api/contact/addOrUpdate",
                'POST',
                $customer_data
            );

            if (is_array($customer_id) && isset($customer_id['error'])) {
                echo json_encode([
                    'success' => false,
                    'message' => '無法建立 SleekFlow 聯絡人',
                    'error' => $customer_id['error'],
                    'api_response' => $customer_id['response'] ?? null,
                    'data' => $customer_id['data']
                ]);
                wp_die();
            }
        }
        $custom_object = [
            // 'primaryPropertyValue' => null,
            'propertyValues' => [
                'brand' => $this->brand_name,
                'phone' => $phone,
                'booking_date_time' => $booking_date.' '.$booking_time,
                'booking_location' => $booking_location,
                'consultant_name' => $consultant,
            ],
            'referencedUserProfileId' => $customer_id
        ];
        $create_custom_objects = $this->consultant_sleekflow_curl(
            "https://api.sleekflow.io/api/customObjects/{$object_key}/records",
            'POST',
            $custom_object
        );
        $create_custom_objects = [];

        $data = array();
        $data['channel'] = "whatsappcloudapi";
        $data['from'] = $this->brand_whatsapp;
        $data['to'] = $phone;
        $data['messageType'] = "template";
        $components = [];

        $media_type=['image','video','document'];
        $headerComponent = [];

        if($msg_header){
            $type = explode('|',$msg_header)[0];
            $content = explode('|',$msg_header)[1];
            if(in_array($type,$media_type)){
                $headerComponent = [
                    'type' => 'header',
                    'parameters' => [
                        [
                            'type' => $type,
                            $type => ['link' => $content],
                            'filename' => 'filename'
                        ]
                    ]
                ];
            }else{
                $headerComponent = [
                    'type' => 'header',
                    'parameters' => [
                        ['type' => $type,'text' => $content]
                    ]
                ];
            }
            array_push($components,$headerComponent);
        }

        $bodyComponent= [
            'type' => 'body',
            'parameters' => []
        ];

        if($msg_body){
            foreach (explode(',',$msg_body) as $value) {
                $temp = ['type' => 'text', 'text' => $_POST[$value]];
                array_push($bodyComponent['parameters'],$temp);
            }
        }else{
            if($source_type === 'landing'){
                $bodyComponent = [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $booking_location],
                        ['type' => 'text', 'text' => $consultant],
                    ]
                ];
            }else{
                $bodyComponent = [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $booking_date],
                        ['type' => 'text', 'text' => $booking_time],
                        ['type' => 'text', 'text' => $booking_location],
                        ['type' => 'text', 'text' => $consultant],
                    ]
                ];
            }
        }
        array_push($components,$bodyComponent);

        if($msg_button){
            foreach (explode(',',$msg_button) as $key => $value) {
                $temp = [
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => $key,
                    'parameters' => [
                        ['type' => 'text','text' => $value]
                    ]
                ];
                array_push($components,$temp);
            }
        }

        $data['extendedMessage'] = [
            'WhatsappCloudApiTemplateMessageObject' => [
                'templateName' => $msg_template,
                'language'=> 'zh_HK',
                'components' => $components
            ]
        ];

        $result = $this->consultant_sleekflow_curl(
            "https://api.sleekflow.io/api/message/send/json",
            'POST',
            $data
        );
        
        wp_send_json_success([
            'result' => $result,
            'send_data' => $data
        ]);
    }

    private function consultant_sleekflow_curl($api_link, $method = 'POST', $dataArr = null) {
        $token = $this->sleekflow_token;
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Sleekflow-Api-Key: ' . $token
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        } elseif ($method === 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
    
        if (!empty($dataArr)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataArr));
        }
    
        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);
    
        if ($error) {
            return ['error' => $error];
        }
    
        return json_decode($response, true);
    }
    
    
}