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
        $source_type = '';
        if (isset($_GET['source_type']) && $_GET['source_type'] == 'landing') {
            $source_type = $_GET['source_type'];
        }
        $apply_recapt = get_option('ech_lfg_apply_recapt');
        $recapt_site_key = get_option('ech_lfg_recapt_site_key');
        $recapt_score = get_option('ech_lfg_recapt_score');

        $paraArr = shortcode_atts([
            'tel_prefix_display' => '1',			// tel_prefix_display. 0 = false, 1 = true
            'shop' => null,											// shop
            'shop_code' => null,								// shop MSP token
            'shop_label' => $this->form_echolang(['*Select area','*請選擇地區','*请选择地区']),		// shop label
            'submit_label' => $this->form_echolang(['Submit','提交','提交']), 										//submit button label
            'wati_msg' => null,
            'msg_header' => null,        				// parameters need to pass to omnichat, sleekflow, kommo api
            'msg_body' => null,									// parameters need to pass to omnichat, sleekflow, kommo api
            'msg_button' => null,								// parameters need to pass to omnichat, sleekflow, kommo api
        ], $atts);


        if ($paraArr['shop'] == null) {
            return '<div class="code_error">shortcode error - shop not specified</div>';
        }
        if ($paraArr['shop_code'] == null) {
            return '<div class="code_error">shortcode error - shop_code not specified</div>';
        }


        $paraArr['shop'] = array_map('trim', str_getcsv($paraArr['shop'], ','));
        $paraArr['shop_code'] = array_map('trim', str_getcsv($paraArr['shop_code'], ','));

        if (count($paraArr['shop']) != count($paraArr['shop_code'])) {
            return '<div class="code_error">shortcode error - shop and shop_code must be corresponding to each other</div>';
        }
        $tel_prefix_display = htmlspecialchars(str_replace(' ', '', $paraArr['tel_prefix_display']));
        if ($tel_prefix_display == "1") {
            $is_tel_prefix_display = true;
        } else {
            $is_tel_prefix_display = false;
        }
        $shop_label = htmlspecialchars(str_replace(' ', '', $paraArr['shop_label']));
        $submit_label = htmlspecialchars(str_replace(' ', '', $paraArr['submit_label']));

        // Wati
        $wati_msg = htmlspecialchars(str_replace(' ', '', $paraArr['wati_msg'] ?? ''));
        $msg_header = htmlspecialchars(str_replace(' ', '', $paraArr['msg_header'] ?? ''));
        $msg_body = htmlspecialchars(str_replace(' ', '', $paraArr['msg_body'] ?? ''));
        $msg_button = htmlspecialchars(str_replace(' ', '', $paraArr['msg_button'] ?? ''));
        $msg_send_api = get_option('ech_lfg_msg_api');
        if (empty($msg_send_api)) {
            return '<div class="code_error">Sending Message Api error - Sending Message Api Should be choose. Please setup in dashboard. </div>';
        }

        if ($wati_msg == null && $msg_send_api != 'kommo') {
            error_log($msg_send_api);
            return '<div class="code_error">wati_send error - wati_send enabled, wati_msg cannot be empty</div>';
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
                $wati_msg_ary = array_filter(array_map('trim', array_map('strtolower', str_getcsv($wati_msg, '|'))));
                if (count($wati_msg_ary) != 2) {
                    return '<div class="code_error">wati_msg error - Sleekflow objectKey or Wati API are empty.</div>';
                }
                break;

            case 'kommo':
                $get_kommo_token = get_option('ech_lfg_kommo_token');
                $get_kommo_pipeline_id = get_option('ech_lfg_kommo_pipeline_id');
                $get_kommo_status_id = get_option('ech_lfg_kommo_status_id');
                if (empty($get_kommo_token) || empty($get_kommo_pipeline_id) || empty($get_kommo_status_id)) {
                    return '<div class="code_error">Kommo error - Kommo Token or Kommo Pipeline ID or Status ID are empty. Please setup in dashboard. </div>';
                }
                break;
        }

        $ip = $_SERVER['REMOTE_ADDR'];

        $shop_count = count($paraArr['shop']);

        $output = '';

        // *********** Custom styling ***************/
        if (!empty(get_option('echc_submitBtn_color')) || !empty(get_option('echc_submitBtn_hoverColor') || !empty(get_option('echc_submitBtn_text_color')) || !empty(get_option('echc_submitBtn_text_hoverColor')))) {
            $output .= '<style>';

            $output .= '.echc_form #submitBtn { ';
            (!empty(get_option('echc_submitBtn_color'))) ? $output .= 'background:' . get_option('echc_submitBtn_color') . ';' : '';
            (!empty(get_option('echc_submitBtn_text_color'))) ? $output .= 'color:' . get_option('echc_submitBtn_text_color') . ';' : '';
            $output .= '}';

            $output .= '.echc_form #submitBtn:hover { ';
            (!empty(get_option('echc_submitBtn_hoverColor'))) ? $output .= 'background:' . get_option('echc_submitBtn_hoverColor') . ';' : '';
            (!empty(get_option('echc_submitBtn_text_hoverColor'))) ? $output .= 'color:' . get_option('echc_submitBtn_text_hoverColor') . ';' : '';
            $output .= '}';


            $output .= '</style>';
        }
        // *********** (END) Custom styling ****************/

        // *********** Check if apply reCAPTCHA v3 ***************/
        if ($apply_recapt == "1") {
            $output .= '<script src="https://www.google.com/recaptcha/api.js?render=' . $recapt_site_key . '"></script>';
        }
        // *********** (END) Check if apply reCAPTCHA v3 ***************/

        $output .= '
		<form class="echc_form" id="echc_form" action="" method="post" data-shop-label="' . $shop_label . '" data-shop-count="' . $shop_count . '" data-ajaxurl="' . get_admin_url(null, 'admin-ajax.php') . '" data-ip="' . $ip . '" data-url="https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '" data-apply-recapt="' . $apply_recapt . '" data-recapt-site-key="' . $recapt_site_key . '" data-recapt-score="' . $recapt_score . '" data-msg-send-api="' . $msg_send_api . '" data-wati-msg="' . $wati_msg . '" data-msg-header="' . $msg_header . '" data-msg-body="' . $msg_body . '" data-msg-button="' . $msg_button . '">
			<div class="form_row echc_formMsg"></div>
			';

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
        if ($source_type) {

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


        //**** Location Options
        $output .= '
			<div class="form_row" data-ech-field="shop">';
        if ($shop_count <= 3) {
            // radio
            if ($shop_count == 1) {
                $output .= '<label class="radio_label" style="display: none;"><input type="radio" value="' . $paraArr['shop_code'][0] . '" data-shop-text-value="' . $paraArr['shop'][0] . '" name="shop" checked onclick="return false;">' . $paraArr['shop'][0] . '</label>';
            } else {
                $output .= '<div>' . $shop_label . '</div>';
                for ($i = 0; $i < $shop_count; $i++) {
                    $output .= '<label class="radio_label"><input type="radio" value="' . $paraArr['shop_code'][$i] . '" name="shop" data-shop-text-value="' . $paraArr['shop'][$i] . '" required>' . $paraArr['shop'][$i] . '</label>';
                }
            }
        } else {
            // select
            $output .= '
					<select class="form-control" name="shop" id="shop" required >
						<option disabled="" selected="" value="">' . $shop_label . '</option>';
            for ($i = 0; $i < $shop_count; $i++) {
                $output .= '<option value="' . $paraArr['shop_code'][$i] . '">' . $paraArr['shop'][$i] . '</option>';
            }
            $output .= '
					</select>';
        }
        $output .= '
			</div>';

        //**** (END) Location Options

        //**** Consultant Options
        $output .= '
			<div class="form_row" data-ech-field="consultant">
				<select class="form-control" name="consultant" id="consultant" required>
						<option disabled="" selected="" value="">'. $this->form_echolang(['*Please Select Consultant','*請選擇顧問','*请选择顾问']) .'</option>
						<option disabled="" value="">'. $this->form_echolang(['Please Select Area First','請先選擇地區','请先选择地区']) .'</option>
				</select>
			</div>';
        //**** (END) Consultant Options

        //**** Submit
        $output .= '
			<div class="form_row" data-ech-btn="submit">
					<button type="submit" id= "submitBtn" >' . $submit_label . '</button>
			</div>';
        //**** (END) Submit

        $output .= '
		</form>';
        $output .= '<div class="consultant-container"></div>';
        return $output;
    } // function display_ech_consultant_form()
		public function get_ec_consultants() {
			$shop = strtolower($_POST['shop_area_code']);
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
			];
	
			$query = new WP_Query($args);
			$output = '<option disabled="" selected="" value="">*請選擇顧問</option>';
	
			if ($query->have_posts()) {
					foreach ($query->posts as $post) {
							$output .= '<option value="consultant-' . $post->ID . '">' . esc_html($post->post_title) . '</option>';
					}
					wp_reset_postdata();
			} else {
					$output = '<option value="" disabled>'.$this->form_echolang(['*Please reselect Area','*請重新選擇地區','*请重新选择地区']).'</option>';
			}
	
			echo $output;
			wp_die();
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
