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
	}


    public function echc_SleekflowSendMsg() {
        $phone = preg_replace('/\D/', '', $_POST['phone']);
        $customer_id = $this->get_sleekflow_contact_id($phone);
        $object_key = explode('|',$_POST['wati_msg'])[0];
        $wati_msg = explode('|',$_POST['wati_msg'])[1];
        if(!$customer_id){

            $customer_data = [
                'first_name' => $_POST['first_name'],    
                'last_name' => $_POST['last_name'],                      
                'email' => $_POST['email'],
                'phone' => $phone
            ];

            $customer_id = $this->create_sleekflow_contact($customer_data);

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
                'brand' => get_option( 'ech_consultant_brand_name' ),
                'client_name' => $_POST['name'],
                'booking_location' => $_POST['booking_location'],
                'booking_item' => $_POST['booking_item']
            ],
            'referencedUserProfileId' => $customer_id
        ];
        $create_custom_objects = $this->consultant_sleekflow_customObjects_curl($object_key, $custom_object);

        $data = array();
        $data['channel'] = "whatsappcloudapi";
        $data['from'] = get_option( 'ech_lfg_brand_whatsapp' );
        $data['to'] = $phone;
        $data['messageType'] = "template";
        $components = [];

        $msg_header = '';
        $media_type=['image','video','document'];
        $headerComponent = [];

        if(isset($_POST['msg_header']) && !empty($_POST['msg_header'])){
            $msg_header = $_POST['msg_header'];
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

        $msg_body = '';
        $bodyComponent= [
            'type' => 'body',
            'parameters' => []
        ];

        if(isset($_POST['msg_body']) && !empty($_POST['msg_body'])){
            $msg_body = $_POST['msg_body'];
            foreach (explode(',',$msg_body) as $value) {
                $temp = ['type' => 'text', 'text' => $_POST[$value]];
                array_push($bodyComponent['parameters'],$temp);
            }
        }else{
            if(strpos($wati_msg,"epay") !== false ){
                $bodyComponent = [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $_POST['booking_date']],
                        ['type' => 'text', 'text' => $_POST['booking_time']],
                        ['type' => 'text', 'text' => $_POST['booking_location']],
                    ]
                ];
            }else{
                $bodyComponent = [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $_POST['name']],
                        ['type' => 'text', 'text' => $_POST['booking_location']],
                        ['type' => 'text', 'text' => $_POST['booking_item']],
                    ]
                ];
            }
        }
        array_push($components,$bodyComponent);

        $msg_button = '';
        $epayParam = '';
        if(isset($_POST['msg_button']) && !empty($_POST['msg_button'])){
            $msg_button = $_POST['msg_button'];
            if(strpos($wati_msg,"epay") !== false ){

                $epayData = array(
                    "username" => $_POST['name'], 
                    "phone" => preg_replace('/\D/', '', $_POST['phone']), 
                    "email" => $_POST['email'], 
                    "booking_date" => $_POST['booking_date'],
                    "booking_time" => $_POST['booking_time'],
                    "booking_item" => $_POST['booking_item'],
                    "booking_location"=>$_POST['booking_location'],    
                    "website_url" => $_POST['website_url'],
                    "epay_refcode" => $_POST['epayRefCode']
                );
                $epayData = $this->encrypted_epay($epayData);
                $epayParam = $epayData;
                $buttonComponent = [
                    'type' => 'button',
                    'sub_type' => 'URL',
                    'index' => '0',
                    'parameters' => [
                        ['type' => 'text','text' => "?epay=".$epayData]
                    ]
                ];
                array_push($components,$buttonComponent);
            }else{
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
        }

        $data['extendedMessage'] = [
            'WhatsappCloudApiTemplateMessageObject' => [
                'templateName' => $wati_msg,
                'language'=> 'zh_HK',
                'components' => $components
            ]
        ];

        $result	= $this->consultant_sleekflow_curl("https://api.sleekflow.io/api/message/send/json", $data);
        // echo $result;
        echo json_encode(['result' => $result,'epayParam' => $epayParam, 'createCustomObjects' => $create_custom_objects]);
        wp_die();
    }
    private function encrypted_epay($epayData){
        $secretKey = get_option( 'ech_consultant_epay_secret_key' );

        $jsonString = json_encode($epayData);
        $compressedData = gzcompress($jsonString);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encryptedData = openssl_encrypt($compressedData, 'aes-256-cbc', $secretKey, 0, $iv);
        $encryptedPayload = base64_encode($encryptedData . "::" . base64_encode($iv));

        return $encryptedPayload;
    }
    private function consultant_sleekflow_curl($api_link, $dataArr = null) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $api_link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        $headers = array();
        $headers[] = 'Accept: application/json';
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'X-Sleekflow-Api-Key: '. get_option( 'ech_lfg_sleekflow_token' );        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataArr) );

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        return $result;
    }
    private function consultant_sleekflow_customObjects_curl($objectKey, $dataArr = null) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.sleekflow.io/api/customObjects/'.$objectKey.'/records');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        $headers = array();
        $headers[] = 'Accept: application/json';
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'X-Sleekflow-Api-Key: '. get_option( 'ech_consultant_sleekflow_token' );        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataArr) );

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        return $result;
	}

    private function get_sleekflow_contact_id($phone) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.sleekflow.io/api/contact?limit=1&offset=0&phoneNumber='.$phone);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST , 'GET');

        $headers = array();
        $headers[] = 'Accept: application/json';
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'X-Sleekflow-Api-Key: '. get_option( 'ech_consultant_sleekflow_token' );        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);

        if (!empty($data) && isset($data[0]['id'])) {
            return $data[0]['id'];
        }

        return null;
    }

    private function create_sleekflow_contact($customer_data) {
        $ch = curl_init();
        $data = [
            [
                "firstName" => $customer_data['first_name'],
                "lastName" => $customer_data['last_name'],
                "email" => $customer_data['email'], 
                "phoneNumber" => $customer_data['phone'],
            ]
        ];
        
        curl_setopt($ch, CURLOPT_URL, 'https://api.sleekflow.io/api/contact/addOrUpdate');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        $headers = array();
        $headers[] = 'Accept: application/json';
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'X-Sleekflow-Api-Key: '. get_option( 'ech_consultant_sleekflow_token' );        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        if (!empty($result) && isset($result[0]['id'])) {
            return $result[0]['id'];
        } else {
            return ['error' => 'Failed to create or update contact', 'response' => $response,'data' => $data];
        }

    }
    
}