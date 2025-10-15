<?php

/**
 * Fired during plugin activation
 *
 * @link       https://127.0.0.1
 * @since      1.0.0
 *
 * @package    Ech_Consultant
 * @subpackage Ech_Consultant/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Ech_Consultant
 * @subpackage Ech_Consultant/includes
 * @author     Rowan Chang <rowanchang@prohaba.com>
 */
class Ech_Consultant_Activator
{
    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate()
    {

        if (! function_exists('is_plugin_active')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $has_ech_lfg = class_exists('Ech_Lfg');
        $has_acf = class_exists('ACF');
        if (! $has_ech_lfg || ! $has_acf) {
            deactivate_plugins('ech-consultant/ech-consultant.php');

            $missing = [];
            if (! $has_acf) {
                $missing[] = '<strong>Advanced Custom Fields Pro</strong>';
            }
            if (! $has_ech_lfg) {
                $missing[] = '<strong>ECH Landing Form Generator</strong>';
            }

            wp_die(
                sprintf(
                    '<p><strong>啟用失敗：</strong> 此外掛需要先啟用以下外掛：</p><ul><li>%s</li></ul><p><a href="%s">&laquo; 返回外掛頁面</a></p>',
                    implode('</li><li>', $missing),
                    esc_url(admin_url('plugins.php')),
                ),
            );
        }

        if (post_type_exists('ec-consultant')) {
            error_log('ec-consultant already exists, skip import.');
            return;
        }

        // 匯入 JSON
        self::import_acf_json();

        // 重新刷新 permalink
        flush_rewrite_rules();
    }

    private static function import_acf_json()
    {

        $json_file = plugin_dir_path(dirname(__FILE__)) . 'admin/acf-json/ec-consultant-acf-export.json';

        if (! file_exists($json_file)) {
            error_log('ECH Consultant: JSON file not found at ' . $json_file);
            return;
        }

        $json = json_decode(file_get_contents($json_file), true);

        if (empty($json)) {
            error_log('ECH Consultant: JSON empty or invalid.');
            return;
        }

        foreach ($json as $item) {

            // 匯入 CPT
            if (isset($item['key']) && str_starts_with($item['key'], 'post_type_')) {
                if (function_exists('acf_import_post_type')) {
                    acf_import_post_type($item);
                }
            }

            // 匯入 Field Group
            if (isset($item['key']) && str_starts_with($item['key'], 'group_')) {
                if (function_exists('acf_import_field_group')) {
                    acf_import_field_group($item);
                }
            }

						// 匯入 Taxonomy
            if (isset($item['key']) && str_starts_with($item['key'], 'taxonomy_')) {
                if (function_exists('acf_import_taxonomy')) {
                    acf_import_taxonomy($item);
                }
            }
        }
    }
}
