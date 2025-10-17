<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://127.0.0.1
 * @since      1.0.0
 *
 * @package    Ech_Consultant
 * @subpackage Ech_Consultant/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="echPlg_wrap">
  <h1>Ech Consultant Settings</h1>
  <div class="plg_intro">
    <div class="shtcode_container">
      <pre id="sample_shortcode">[ech_consultant]</pre>
      <div id="copyMsg"></div>
      <button id="copyShortcode">Copy Shortcode</button>
    </div>
  </div>
  <div class="form_container">
    <div class="form_row">
      <h2>If you want to change the default settings, please visit <a href="/wp-admin/admin.php?page=reg_ech_blog_settings" target="_blank">ECH Form Settings</a></h2>
      <?php $getMsgApi = get_option('ech_lfg_msg_api'); ?>
      <label>Sending Message Api : </label>
      <select name="ech_lfg_msg_api" disabled>
          <option value="<?php echo $getMsgApi; ?>" selected><?php echo $getMsgApi; ?></option>
      </select>
    </div>
    <div class="form_row">
        <label>Brand Whatsapp Number: </label>
        <input type="text" value="<?= htmlspecialchars(get_option('ech_lfg_brand_whatsapp'))?>" readonly />
    </div>
    <?php
      switch ($getMsgApi) :
        case 'omnichat': ?>
          <h2>Omnichat Settings</h2>
          <div class="form_row">
              <label>Omnichat Token: </label>
              <input type="text" name="ech_lfg_omnichat_token" value="<?= htmlspecialchars(get_option('ech_lfg_omnichat_token'))?> " readonly/>
          </div>
        <?php break;

        case 'sleekflow': ?>
          <h2>SleekFlow Settings</h2>
          <div class="form_row">
              <label>SleekFlow Token: </label>
              <input type="text" name="ech_lfg_sleekflow_token" value="<?= htmlspecialchars(get_option('ech_lfg_sleekflow_token'))?> " readonly/>
          </div>
        <?php break;

        case 'kommo': ?>
          <h2>Kommo Settings</h2>
          <div class="form_row">
              <label>Kommo Token: </label>
              <input type="text" name="ech_lfg_kommo_token" value="<?= htmlspecialchars(get_option('ech_lfg_kommo_token'))?> " readonly/>
          </div>
          <div class="form_row">
              <label>Kommo Pipeline ID: </label>
              <input type="number" name="ech_lfg_kommo_pipeline_id" value="<?= htmlspecialchars(get_option('ech_lfg_kommo_pipeline_id'))?>"/>
          </div>
          <div class="form_row">
              <label>Kommo Status ID: </label>
              <input type="number" name="ech_lfg_kommo_status_id" value="<?= htmlspecialchars(get_option('ech_lfg_kommo_status_id'))?>"/>
          </div>
          <?php break;

endswitch;?>
      <form method="post" id="lfg_gen_settings_form">
        <?php
          settings_fields('echc_gen_settings');
          do_settings_sections('echc_gen_settings');
          
          ?>
        <div class="form_row">
            <label>Message Template: </label>
            <input type="text" name="echc_msg_template" value="<?= htmlspecialchars(get_option('echc_msg_template'))?>"/>
        </div>

        <!-- <h2>General</h2>
        <div class="form_row">
            <label>地區 : </label>
            <input type="checkbox" name="echc_shop_area[]" value="<?= get_option('echc_shop_area')?>" id="echc_shop_area">
        </div> -->
      </form>
      <?php
      $ech_lfg_apply_recapt = get_option('ech_lfg_apply_recapt');
      if($ech_lfg_apply_recapt): ?>
        <h2>LFG reCAPTCHA v3</h2>
        <div class="form_row">
            <label>Recaptcha Site Key: </label>
            <input type="text" name="ech_lfg_recaptcha_site_key" value="<?= htmlspecialchars(get_option('ech_lfg_recaptcha_site_key'))?>"/>
        </div>
        <div class="form_row">
            <label>Recaptcha Secret Key: </label>
            <input type="text" name="ech_lfg_recaptcha_secret_key" value="<?= htmlspecialchars(get_option('ech_lfg_recaptcha_secret_key'))?>"/>
        </div>

      <?php 
        endif;
      ?>
      
  </div>
</div>