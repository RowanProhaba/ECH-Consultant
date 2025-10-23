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
    <form method="post" id="echc_gen_settings_form">
        <?php
          settings_fields('echc_gen_settings');
          do_settings_sections('echc_gen_settings');
          ?>
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
                <input type="text" value="<?= htmlspecialchars(get_option('ech_lfg_omnichat_token'))?> " readonly/>
            </div>
          <?php break;

          case 'sleekflow': ?>
            <h2>SleekFlow Settings</h2>
            <div class="form_row">
                <label>SleekFlow Token: </label>
                <input type="text" value="<?= htmlspecialchars(get_option('ech_lfg_sleekflow_token'))?> " readonly/>
            </div>
          <?php break;

          case 'kommo': 
            $pipeline_id = get_option('ech_lfg_kommo_pipeline_id');
            $status_name = get_option('echc_kommo_status_name');
            $status_id = get_option('echc_kommo_status_id');
          ?>
          
            <h2>Kommo Settings</h2>
            <div class="form_row">
                <label>Kommo Token: </label>
                <input type="text" value="<?= htmlspecialchars(get_option('ech_lfg_kommo_token'))?> " readonly/>
            </div>
            <div class="form_row">
                <label>Kommo Pipeline ID: </label>
                <input type="number" value="<?= $pipeline_id?>" readonly/>
            </div>
            <div class="form_row">
                <label>Kommo Consultant Status Name: </label>
                <input type="text" name="echc_kommo_status_name" value="<?= $status_name;?>" <?= ($status_name)? 'readonly' : ''?>/>
            </div>
            <div class="form_row">
                <label>Kommo Consultant Status ID: </label>
                <input type="number" value="<?= $status_id;?>" readonly/>
            </div>
            
            <?php break;

        endswitch;?>
      
        <div class="form_row">
            <label>Message Template: </label>
            <input type="text" name="echc_msg_template" value="<?= htmlspecialchars(get_option('echc_msg_template'))?>"/>
        </div>

        <div class="form_row">
            <button type="submit"> Save </button>
        </div>
      </form>
      <div class="statusMsg"></div>
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