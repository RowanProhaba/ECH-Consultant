<?php 

class Ech_consultant_Omnichat_Public
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


    public function consultant_OmnichatSendMsg() {
        $domain = get_site_url();
        $data = array();

        $data['trackId'] = $_POST['wati_msg'];
        $data['platform'] = "whatsapp";
        $data['channelId'] = get_option( 'ech_lfg_brand_whatsapp' );
        $data['to'] = $_POST['phone'];
        $data['tags'] = [$_POST['wati_msg']];

        // data sample
        // {
        //     "trackId": "cny2023",
        //     "platform": "whatsapp",
        //     "channelId": "85290000001",
        //     "to": "85260000001",
        //     "tags": [
        //         "remarking-001"
        //     ],
        //     "messages": [
        //         {
        //             "type": "whatsappTemplate",
        //             "whatsappTemplate": {
        //                 "name": "cny_campaign_2023",
        //                 "components": [
        //                     {
        //                         "type": "header",
        //                         "parameters": [
        //                             {
        //                                 "type": "image",
        //                                 "image": {
        //                                     "link": "https://example.com/red-pocket.png"
        //                                 }
        //                             }
        //                         ]
        //                     },
        //                     {
        //                         "type": "body",
        //                         "parameters": [
        //                             {
        //                                 "type": "text",
        //                                 "text": "Mr. Chan"
        //                             },
        //                             {
        //                                 "type": "text",
        //                                 "text": "$100 Red pocket"
        //                             }
        //                         ]
        //                     },
        //                     {
        //                         "type": "button",
        //                         "sub_type": "url",
        //                         "index": "0",
        //                         "parameters": [
        //                             {
        //                                 "type": "text",
        //                                 "text": "https://example.com/landing.html"
        //                             }
        //                         ]
        //                     }
        //                 ]
        //             }
        //         }
        //     ]
        // }

        $messages = [
            'type' => 'whatsappTemplate',
            'whatsappTemplate' => [
                'name' => $_POST['wati_msg'],
                'components' => []
            ]
            
        ];

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
                                            $type => ['link' => $content]
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
            array_push($messages['whatsappTemplate']['components'],$headerComponent);
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
            if(strpos($_POST['wati_msg'],"epay") !== false ){
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
        array_push($messages['whatsappTemplate']['components'],$bodyComponent);

        $msg_button = '';
        $epayParam = '';
        if(isset($_POST['msg_button']) && !empty($_POST['msg_button'])){
            $msg_button = $_POST['msg_button'];
            if(strpos($_POST['wati_msg'],"epay") !== false ){
                $epayParam = $epayData;
                $buttonComponent = [
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => '0',
                    'parameters' => [
                        ['type' => 'text','text' => $msg_button."?epay=".$epayData]
                    ]
                ];
                array_push($messages['whatsappTemplate']['components'],$buttonComponent);
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
                    array_push($messages['whatsappTemplate']['components'],$temp);
                }
            }
        }
        
        $data['messages'] = [$messages];

        $result	= $this->consultant_omnichat_curl("https://open-api.omnichat.ai/v1/direct-messages", $data);
        echo json_encode(['result' => $result,'epayParam' => $epayParam]);
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
    private function consultant_omnichat_curl($api_link, $dataArr = null) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $api_link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        $headers = array();
        $headers[] = 'Accept: */*';
        $headers[] = 'Authorization: '. get_option( 'ech_consultant_omnichat_token' );        
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataArr) );

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        return $result;
	}



}