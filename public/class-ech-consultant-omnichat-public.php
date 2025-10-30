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

		$this->omnichat_channel = get_option('ech_lfg_brand_whatsapp');
        $this->omnichat_token = get_option('ech_lfg_omnichat_token');
	}


    public function echc_OmnichatSendMsg() {
        $domain = get_site_url();
        $channel_id = $this->omnichat_channel;
        $source_type = isset($_POST['source_type']) && $_POST['source_type'] != '' ? $_POST['source_type'] : '';
        $name = isset($_POST['name']) && $_POST['name'] != '' ? $_POST['name'] : '';
        $phone = preg_replace('/\D/', '', $_POST['phone']);
        $booking_date = isset($_POST['booking_date']) && $_POST['booking_date'] != '' ? $_POST['booking_date'] : '';
        $booking_time = isset($_POST['booking_time']) && $_POST['booking_time'] != '' ? $_POST['booking_time'] : '';
        $booking_location = isset($_POST['booking_location']) && $_POST['booking_location'] != '' ? $_POST['booking_location'] : '';
        $consultant = isset($_POST['consultant']) && $_POST['consultant'] != '' ? $_POST['consultant'] : '';
        $msg_template = isset($_POST['msg_template']) ? $_POST['msg_template'] : '';
        if($source_type){
            $msg_template.= '_'.$source_type;
        }
        $msg_header = isset($_POST['msg_header']) && $_POST['msg_header'] != '' ? $_POST['msg_header'] : '';
        $msg_body = isset($_POST['msg_body']) && $_POST['msg_body'] != '' ? $_POST['msg_body'] : '';
        $msg_button = isset($_POST['msg_button']) && $_POST['msg_button'] != '' ? $_POST['msg_button'] : '';

        $data = array();

        $data['trackId'] = $msg_template;
        $data['platform'] = "whatsapp";
        $data['channelId'] = $channel_id;
        $data['to'] = $_POST['phone'];
        $data['tags'] = [$msg_template];

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
                'name' => $msg_template,
                'components' => []
            ]
            
        ];

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
                        ['type' => 'text', 'text' => $name],
                        ['type' => 'text', 'text' => $booking_location],
                        ['type' => 'text', 'text' => $consultant],
                    ]
                ];

            }else{
                $bodyComponent = [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $name],
                        ['type' => 'text', 'text' => $booking_date],
                        ['type' => 'text', 'text' => $booking_time],
                        ['type' => 'text', 'text' => $booking_location],
                        ['type' => 'text', 'text' => $consultant],
                    ]
                ];
            }
        }
        array_push($messages['whatsappTemplate']['components'],$bodyComponent);

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
                array_push($messages['whatsappTemplate']['components'],$temp);
            }
        }
        
        $data['messages'] = [$messages];

        $result	= $this->consultant_omnichat_curl("https://open-api.omnichat.ai/v1/direct-messages", $data);

        wp_send_json_success([
            'result' => $result,
            'send_data' => $data
        ]);
    }

    private function consultant_omnichat_curl($api_link, $dataArr = null) {
        $token = $this->omnichat_token;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        $headers = array();
        $headers[] = 'Accept: */*';
        $headers[] = 'Authorization: '. $token;        
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataArr) );

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
    
        if ($error) {
            return ['error' => $error];
        }
    
        return json_decode($response, true); 
	}



}