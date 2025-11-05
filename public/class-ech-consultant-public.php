<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://127.0.0.1
 * @since      1.0.0
 *
 * @package    Ech_Consultant
 * @subpackage Ech_Consultant/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Ech_Consultant
 * @subpackage Ech_Consultant/public
 * @author     Rowan Chang <rowanchang@prohaba.com>
 */
class Ech_Consultant_Public
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

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/ech-consultant-public.css', [], $this->version, 'all');
        wp_enqueue_style($this->plugin_name . '_jqueryUI', plugin_dir_url(__FILE__) . 'lib/jquery-ui-1.12.1/jquery-ui.min.css', [], $this->version, 'all');
        wp_enqueue_style($this->plugin_name . '_timepicker', plugin_dir_url(__FILE__) . 'lib/jquery-timepicker/jquery.timepicker.css', [], $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        wp_enqueue_script($this->plugin_name . '_jqueryUI', plugin_dir_url(__FILE__) . 'lib/jquery-ui-1.12.1/jquery-ui.min.js', ['jquery'], $this->version, false);
        wp_enqueue_script($this->plugin_name . '_timepicker', plugin_dir_url(__FILE__) . 'lib/jquery-timepicker/jquery.timepicker.min.js', ['jquery'], $this->version, false);
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/ech-consultant-public.js', [ 'jquery' ], $this->version, false);

    }

    // ^^^ ECH Consultant shortcode
    public function display_ech_consultant_form($atts)
    {

        $source_type = 'wts';
        if (isset($_GET['source_type']) && $_GET['source_type'] == 'landing') {
            $source_type = $_GET['source_type'];
        }
        $apply_recapt = get_option('ech_lfg_apply_recapt');
        $recapt_site_key = get_option('ech_lfg_recapt_site_key');
        $recapt_score = get_option('ech_lfg_recapt_score');

        $paraArr = shortcode_atts([
            'tel_prefix_display' => '0',			// tel_prefix_display. 0 = false, 1 = true
            'name_required' => '0',					// name_required. 0 = false, 1 = true
            'last_name_required' => '1',			// last_name_required. 0 = false, 1 = true
            'submit_label' => $this->form_echolang(['Submit','提交','提交']), 										//submit button label
            'consultant_title' => $this->form_echolang(['Please Select Consultant','請選擇您專屬的顧問','请选择您专属的顾问']),
            'msg_template' => get_option('echc_msg_template'),
            'msg_header' => null,        			// parameters need to pass to omnichat, sleekflow, kommo api
            'msg_body' => null,						// parameters need to pass to omnichat, sleekflow, kommo api
            'msg_button' => null,					// parameters need to pass to omnichat, sleekflow, kommo api
            'fbcapi_send' => '0',					// enable or disable the fbcapi send. 0 = false, 1 = true
        ], $atts);


        $tel_prefix_display = htmlspecialchars(str_replace(' ', '', $paraArr['tel_prefix_display']));
        if ($tel_prefix_display == "1") {
            $is_tel_prefix_display = true;
        } else {
            $is_tel_prefix_display = false;
        }

        $name_required = htmlspecialchars(str_replace(' ', '', $paraArr['name_required']));
		if ($name_required == "1") {
			$name_required_bool = true;
		} else {
			$name_required_bool = false;
		}
        $last_name_required = htmlspecialchars(str_replace(' ', '', $paraArr['last_name_required']));
		if ($last_name_required == "1") {
			$last_name_required_bool = true;
		} else {
			$last_name_required_bool = false;
		}
        $note_phone = get_option( 'ech_lfg_note_phone' );
		$note_whatapps_link = get_option( 'ech_lfg_note_whatapps_link' );
		if ( empty($note_phone) || empty($note_whatapps_link) ) {
			return '<div class="code_error">Note error - Note Phone or Whatsapp Link are empty. Please setup in dashboard. </div>';
		}
        $submit_label = htmlspecialchars(str_replace(' ', '', $paraArr['submit_label']));
        $consultant_title = htmlspecialchars(str_replace(' ', '', $paraArr['consultant_title']));

        $disclaimer = get_option('echc_disclaimer');

        // Whatsapp send
        $msg_template = htmlspecialchars(str_replace(' ', '', $paraArr['msg_template'] ?? ''));
        $msg_header = htmlspecialchars(str_replace(' ', '', $paraArr['msg_header'] ?? ''));
        $msg_body = htmlspecialchars(str_replace(' ', '', $paraArr['msg_body'] ?? ''));
        $msg_button = htmlspecialchars(str_replace(' ', '', $paraArr['msg_button'] ?? ''));
        $msg_send_api = get_option('ech_lfg_msg_api');
        if (empty($msg_send_api)) {
            return '<div class="code_error">Sending Message Api error - Sending Message Api Should be choose. Please setup in dashboard. </div>';
        }
        if (empty($msg_template) || $msg_template == null) {
            return '<div class="code_error">Whatsapp send error - Whatsapp send enabled, Message Template cannot be empty</div>';
        }
        $get_brandWtsNo = get_option('ech_lfg_brand_whatsapp');
        if (empty($get_brandWtsNo)) {
            return '<div class="code_error">Brand Whatsapp Number is empty. Please setup in dashboard. </div>';
        }
        switch ($msg_send_api) {
            case 'omnichat':
                $get_omnichat_token = get_option('ech_lfg_omnichat_token');
                if (empty($get_omnichat_token)) {
                    return '<div class="code_error">Omnichat error - Omnichat Token are empty. Please setup in dashboard. </div>';
                }
                break;

            case 'sleekflow':
                $get_sleekflow_token = get_option('ech_lfg_sleekflow_token');
                if (empty($get_sleekflow_token)) {
                    return '<div class="code_error">Sleekflow error - Sleekflow Token are empty. Please setup in dashboard. </div>';
                }
                $wati_msg_ary = array_filter(array_map('trim', array_map('strtolower', str_getcsv($msg_template, '|'))));
                if (count($wati_msg_ary) != 2) {
                    return '<div class="code_error">wati_msg error - Sleekflow objectKey or Wati API are empty.</div>';
                }
                break;

            case 'kommo':
                $get_kommo_token = get_option('ech_lfg_kommo_token');
                $get_kommo_pipeline_id = get_option('ech_lfg_kommo_pipeline_id');
                $get_kommo_status_id = get_option('echc_kommo_status_id');
                if (empty($get_kommo_token) || empty($get_kommo_pipeline_id) || empty($get_kommo_status_id)) {
                    return '<div class="code_error">Kommo error - Kommo Token or Kommo Pipeline ID or Status ID are empty. Please setup in dashboard. </div>';
                }
                break;
        }
        // FB Capi 
        $fbcapi_send = htmlspecialchars(str_replace(' ', '', $paraArr['fbcapi_send']));
        $accept_pll = get_option( 'ech_lfg_accept_pll' );
        if($fbcapi_send){
            $get_pixelId = get_option( 'ech_lfg_pixel_id' );
            $get_fbAccessToken = get_option( 'ech_lfg_fb_access_token' );

            if ( empty($get_pixelId) || empty($get_fbAccessToken) ) {
                return '<div class="code_error">FB Capi error - Pixel id or FB Access Token are empty. Please setup in dashboard. </div>';
            }
        }
        $ip = $_SERVER['REMOTE_ADDR'];

        $output = '';

        // *********** Custom styling ***************/
        $form_primary_color = get_option('echc_primary_color');
        if (!empty($form_primary_color)) {
            $output .= '
            <style>
                .echc_form #submitBtn { background:#fff;color:' . $form_primary_color . ';border-color:' . $form_primary_color . ';}
                .echc_form #submitBtn:not([disabled]):hover { background:' . $form_primary_color . ';color:#fff;border-color:#fff;}
                .customer-info-contanier{background:' . $form_primary_color . ';} 
                .location-list-title,.consultant-list-title{color:' . $form_primary_color . ';}
                .location-item.active{background:' . $form_primary_color . ';border-color:' . $form_primary_color . ';}
                .consultant-item input[name="consultant"]:checked ~ label{background:' . $form_primary_color . ';border-color:' . $form_primary_color . ';}
                .consultant-item.active::before{border-top-color:' . $form_primary_color . ';} 
            </style>';
        }
        // *********** (END) Custom styling ****************/

        // *********** Check if apply reCAPTCHA v3 ***************/
        if ($apply_recapt == "1") {
            $output .= '<script src="https://www.google.com/recaptcha/api.js?render=' . $recapt_site_key . '"></script>';
        }
        // *********** (END) Check if apply reCAPTCHA v3 ***************/

        
        $output .= '
		<form class="echc_form" id="echc_form" action="" method="post" data-source-type="' . $source_type . '" data-consultant-title="'.$consultant_title.'" data-ajaxurl="' . get_admin_url(null, 'admin-ajax.php') . '" data-ip="' . $ip . '" data-url="https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '" data-apply-recapt="' . $apply_recapt . '" data-recapt-site-key="' . $recapt_site_key . '" data-recapt-score="' . $recapt_score . '" data-msg-send-api="' . $msg_send_api . '" data-msg-template="' . $msg_template . '" data-msg-header="' . $msg_header . '" data-msg-body="' . $msg_body . '" data-msg-button="' . $msg_button . '" data-fbcapi-send="'. $fbcapi_send .'" data-accept-pll="'. $accept_pll .'">';
        
        // *********** Location list ***************/
        $locations = $this->get_location();
        $init_location = '';
        if(!empty($locations)) {
            $output .= '<div class="form_row" data-ech-field="shop">';
            $output .= '<div class="location-list-title">'.$this->form_echolang(['Select Location','選擇地區','选择地区']).'</div>';
            $output .= '<div class="consultant-location-list">';
            $init_location = array_key_first($locations);
            foreach ($locations as $key => $location) {
                $output .= '<label class="location-item '.($key == $init_location ? 'active' : '').'">'.$location;
                $output .= '<input type="radio" name="shop" value="' . $key . '" data-shop-text="' . $location . '" '.($key == $init_location ? 'checked' : '').'>';
                $output .= '</label>';
            }
            $output .= '</div>';
            $output .= '</div>';
        }
        // *********** (END) Location list ***************/
        
        //**** Consultant list
        $consultants = [];
        $consultant_list = '';
        if($init_location != '') {
            $consultants = $this->get_consultants($init_location);
            $consultant_list = $this->render_consultant_list($consultants, $consultant_title);
        }
        $output .= '<div class="form_row" data-ech-field="consultant">';
        $output .= $consultant_list;
        $output .= '</div>';

        //**** (END) Consultant list
        $output .= '<div class="customer-info-contanier form_row">';
        $output .= ' <div class="form_row echc_formMsg"></div>';
        if($name_required_bool) {
            if ($last_name_required_bool) {
                $output .='
                <div class="form_row" data-ech-field="last_name">
                    <input type="text" name="last_name" id="last_name"  class="form-control"  placeholder="'.$this->form_echolang(['*Last Name','*姓氏','*姓氏']).'" pattern="[ A-Za-z\u3000\u3400-\u4DBF\u4E00-\u9FFF]{1,}"  size="40" required >
                </div>
                ';
            } else {
                $output .='
                <div class="form_row"  data-ech-field="last_name" style="display:none;">
                    <input type="text" name="last_name" id="last_name"  class="form-control"  placeholder="*姓氏" pattern="[ A-Za-z\u3000\u3400-\u4DBF\u4E00-\u9FFF]{1,}"  size="40">
                </div>
                ';
            }
            $output .= '
            <div class="form_row" data-ech-field="first_name">
                <input type="text" name="first_name" id="first_name" class="form-control" placeholder="'.$this->form_echolang(['*First Name','*名字','*名字']).'" pattern="[ A-Za-z\u3000\u3400-\u4DBF\u4E00-\u9FFF]{1,}" size="40" required >
            </div>
            ';
        }else{
            $output .='
                <div class="form_row"  data-ech-field="last_name" style="display:none;">
                    <input type="text" name="last_name" id="last_name"  class="form-control"  placeholder="*姓氏" pattern="[ A-Za-z\u3000\u3400-\u4DBF\u4E00-\u9FFF]{1,}"  size="40">
                </div>
                <div class="form_row" data-ech-field="first_name" style="display:none;">
                    <input type="text" name="first_name" id="first_name" class="form-control" placeholder="'.$this->form_echolang(['*First Name','*名字','*名字']).'" pattern="[ A-Za-z\u3000\u3400-\u4DBF\u4E00-\u9FFF]{1,}" size="40">
                </div>
                ';

        }

        //**** Tel Prefix
        if ($is_tel_prefix_display) {
            $output .= '
				<div class="form_row" data-ech-field="telPrefix">
					<select  class="form-control" name="telPrefix" id="tel_prefix" style="width: 100%;" required >
						<option value="+852" selected>+852</option>
						<option value="+853">+853</option>
						<option value="+86">+86</option> 
					</select>
				</div>';
        } else {
            $output .= '
				<div class="form_row" data-ech-field="telPrefix" style="display:none;">
					<select  class="form-control" name="telPrefix" id="tel_prefix" style="width: 100%;" required >
						<option value="+852" selected>+852</option>
						<option value="+853">+853</option>
						<option value="+86">+86</option> 
					</select>
				</div>';
        }
        //**** (END) Tel Prefix

        //**** Tel
        $output .= '
			<div class="form_row" data-ech-field="tel">
				<input type="text" name="tel" placeholder="' . $this->form_echolang(['*Phone','*電話','*电话']) . '"  class="form-control" size="30" id="tel" pattern="[0-9]{8,11}" required >
			</div>';
        //**** (END) Tel

        // Booking Date and Time
        if ($source_type === 'landing') {

            $output .= '
				<div class="form_row" data-ech-field="booking_date" style="display:none">
					<input type="text" placeholder="' . $this->form_echolang(['*Booking Date','*預約日期','*预约日期']) . '" class="form-control echc_datepicker" name="booking_date" value="">
				</div>

				<div class="form_row" data-ech-field="booking_time" style="display:none">
					<input type="text" placeholder="' . $this->form_echolang(['*Booking Time','*預約時間','*预约时间']) . '" id="booking_time" class="form-control echc_timepicker ui-timepicker-input" name="booking_time">
				</div>';

        } else {
            $output .= '
				<div class="form_row" data-ech-field="booking_date">
					<input type="text" placeholder="' . $this->form_echolang(['*Booking Date','*預約日期','*预约日期']) . '" class="form-control echc_datepicker" name="booking_date" autocomplete="off" value="" size="40" required>
				</div>
				<div class="form_row" data-ech-field="booking_time">
						<input type="text" placeholder="' . $this->form_echolang(['*Booking Time','*預約時間','*预约时间']) . '" id="booking_time" class="form-control echc_timepicker ui-timepicker-input" name="booking_time" autocomplete="off" value="" size="40" required="">
				</div>';
        }

        $privacyPolicyUrl = get_option( 'ech_lfg_privacy_policy' );
        $output .= ' 
        <div class="form_row" data-ech-field="info_remark">
            <label for="agree"><input id="agree" type="checkbox" class="agree"  value="agreed_policy" name="info_remark[]" checked required > '.$this->form_echolang(['* I have read and agreed with the terms and conditions of <a class="ech-pp-url" href="'.$privacyPolicyUrl.'" target="_blank">Privacy Policy.</a>','*本人已閱讀並同意有關<a class="ech-pp-url" href="'.$privacyPolicyUrl.'" target="_blank">私隱政策聲明</a>','*本人已阅读并同意有关<a class="ech-pp-url" href="'.$privacyPolicyUrl.'" target="_blank">私隐政策声明</a>']).'。</label>
            <small>'.$this->form_echolang(['*Required','*必需填寫','*必需填写']).'<br>'.$this->form_echolang(['For same day reservation, please <a href="tel:tel:'.$note_phone.'">call</a> or message us on <a class="wtsL" href="'.$note_whatapps_link.'" target="_blank">WhatsApp</a>.','當天預約請<a href="tel:'.$note_phone.'">致電</a>或透過<a class="wtsL" href="'.$note_whatapps_link.'" target="_blank">WhatsApp</a>聯繫我們。','当天预约请<a href="tel:'.$note_phone.'">致电</a>或透过<a class="wtsL" href="'.$note_whatapps_link.'" target="_blank">WhatsApp</a>联系我们。']).'</small>
        </div>';

        //**** Submit
        $output .= '
			<div class="form_row" data-ech-btn="submit">
					<button type="submit" id= "submitBtn" >' . $submit_label . '</button>
			</div>';
        //**** (END) Submit
        $output .= '</div>'; //customer info container
        $output .= '
		</form>';
        if($disclaimer){
            $output .= '<div class="disclaimer-container"> ' . $disclaimer . '</div>';
        }
        return $output;
    } // function display_ech_consultant_form()

    public function get_location(){
        $taxonomy = 'consultant-category';
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
        ]);
        $locations = [];
        if (!empty($terms) && !is_wp_error($terms)) {
            
            foreach ($terms as $key => $term) {
                $name_en = get_field('name_en', $taxonomy.'_'.$term->term_id);
                $name_zh = get_field('name_zh', $taxonomy.'_'.$term->term_id);
                $name_cn = get_field('name_cn', $taxonomy.'_'.$term->term_id);
                $name = $this->form_echolang([$name_en, $name_zh, $name_cn]);
                $locations[$term->slug]=$name;
            }
        }
        return $locations;
    }

    public function get_consultants($shop)
    {
        $args = [
            'post_type' => 'ec-consultant',
            'tax_query' => [
                [
                    'taxonomy' => 'consultant-category',
                    'field'    => 'slug',
                    'terms'    => $shop,
                ],
            ],
            'posts_per_page' => -1,
            'no_found_rows' => true,
            'fields' => 'ids',
        ];

        $ids = get_posts($args);
        $consultants = [];
        if (!empty($ids)) {
            foreach ($ids as $consultant_id) {
                $name_en = get_post_meta($consultant_id, 'name_en', true);
                $name_zh = get_post_meta($consultant_id, 'name_zh', true);
                $name_cn = get_post_meta($consultant_id, 'name_cn', true);
                $name = $this->form_echolang([$name_en, $name_zh, $name_cn]);

                $description_en = get_post_meta($consultant_id, 'description_en', true);
                $description_zh = get_post_meta($consultant_id, 'description_zh', true);
                $description_cn = get_post_meta($consultant_id, 'description_cn', true);
                $description = $this->form_echolang([$description_en, $description_zh, $description_cn]);
                $profile_picture = get_field('profile_picture', $consultant_id);
                if (!empty($profile_picture)) {
                    $profile_picture = $profile_picture['sizes']['medium_large'] ?? '';
                }else{
                    $profile_picture = plugin_dir_url(dirname(__FILE__)) . 'public/img/circle-user-solid-full.svg';
                }
                $consultants[] = [
                    'id'   => $consultant_id,
                    'name' => $name,
                    'description' => $description,
                    'img' => $profile_picture,
                ];
            }
        }

        return $consultants;
    }

    public function render_consultant_list($consultants, $consultant_title = null){
        $output = '';
        $title = $consultant_title ?: $this->form_echolang(['Please Select Consultant','請選擇顧問','请选择顾问']);
        if (!empty($consultants)) {
            $output .= '<div class="consultant-list-container">';
            if(count($consultants) < 4){
                $output .= '<style>.consultant-list-container{justify-content: center;}</style>';
            }
            $output .= '<div class="consultant-list-title">'.$title.'</div>';

            foreach ($consultants as $consultant) {
                $output .= '<div class="consultant-item">';
                $output .= '<div class="consultant-info">';
                $output .= '<div class="consultant-img"><img src="'.$consultant['img'].'" alt="'.$consultant['name'].'"></div>';
                $output .= '<h4 class="consultant-name">'.$consultant['name'].'</h4>';
                $output .= '<div class="consultant-description">'.$consultant['description'].'</div>';
                $output .= '</div>';
                $output .= '<input id="consultant-'.$consultant['id'].'" type="radio" name="consultant" value="'.$consultant['id'].'" data-consultant-text="'.$consultant['name'].'">';
                $output .= '<label for="consultant-'.$consultant['id'].'">'.$this->form_echolang(['Select','選擇此顧問','选择此顾问']);
                $output .= '</label>';
                $output .= '</div>';
            }
            $output .= '</div>';
        }else{
            $output .= '<div class="consultant-list-title">'.$this->form_echolang(['Please reselect area','請重新選擇地區','请重新选择地区']).'</div>';
            $output .= '<div class="consultant-list-container">';
            $output .= '<h6>'.$this->form_echolang(['There are no consultants in this area.','此地區沒有顧問','此地区没有顾问']).'</h6>';
            $output .= '</div>';
        }

        return $output;
    }
    public function get_consultant_list()
    {
        $shop = isset($_POST['shop_area_code']) ? sanitize_text_field(strtolower($_POST['shop_area_code'])) : '';
        if (empty($shop)) {
            wp_send_json_error(['message' => '缺少地區代碼']);
        }
        $consultant_title = isset($_POST['consultant_title']) ? sanitize_text_field($_POST['consultant_title']) : null;
        $consultants = $this->get_consultants($shop);
        $consultant_list = $this->render_consultant_list($consultants, $consultant_title);
        
        wp_send_json_success([
            'consultant_list' => $consultant_list,
        ]);
    }


    public function echc_recaptVerify()
    {
        $crData = [];
        $crData['response'] = $_POST['recapt_token'];
        $crData['secret'] = get_option('ech_lfg_recapt_secret_key');

        $result	= $this->echc_curl('https://www.google.com/recaptcha/api/siteverify', $crData, true);
        echo $result;
        wp_die();
    }

    private function echc_curl($i_url, $i_fields = null, $i_isPOST = 0)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $i_url);
        curl_setopt($ch, CURLOPT_POST, $i_isPOST);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($i_fields != null && is_array($i_fields)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($i_fields));
        }
        $rs = curl_exec($ch);
        curl_close($ch);

        return $rs;
    }
    public function form_echolang($stringArr)
    {
        global $TRP_LANGUAGE;

        switch ($TRP_LANGUAGE) {
            case 'zh_HK':
                $langString = $stringArr[1];
                break;
            case 'zh_CN':
                $langString = $stringArr[2];
                break;
            default:
                $langString = $stringArr[0];
        }

        if (empty($langString) || $langString == '' || $langString == null) {
            $langString = $stringArr[1]; //zh_HK
        }

        return $langString;

    }

}
