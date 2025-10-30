<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://127.0.0.1
 * @since      1.0.0
 *
 * @package    Ech_Consultant
 * @subpackage Ech_Consultant/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Ech_Consultant
 * @subpackage Ech_Consultant/admin
 * @author     Rowan Chang <rowanchang@prohaba.com>
 */
class Ech_Consultant_Admin
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
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Ech_Consultant_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Ech_Consultant_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        if ((isset($_GET['page']) && $_GET['page'] == 'ech_consultant_settings')) {
            wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/ech-consultant-admin.css', [], $this->version, 'all');
        }

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Ech_Consultant_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Ech_Consultant_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/ech-consultant-admin.js', [ 'jquery' ], $this->version, false);

    }

    /**
   * ^^^ Add ECH Consultant Admin menu
   *
   * @since    1.0.0
   */
    public function echc_admin_menu()
    {
        // Main Menu
        add_menu_page(
            'ECH Consultant',
            'ECH Consultant',
            'manage_options',
            'ech_consultant_settings',
            [ $this, 'ech_consultant_admin_page' ],
            'dashicons-businessperson',
            110,
        );
    }

    // return view
    public function ech_consultant_admin_page()
    {
        require_once('partials/ech-consultant-admin-display.php');
    }

    public function reg_ech_consultant_settings()
    {
        register_setting( 'echc_gen_settings', 'echc_primary_color');
        register_setting( 'echc_gen_settings', 'echc_disclaimer');
        register_setting( 'echc_gen_settings', 'echc_msg_template');
        register_setting( 'echc_gen_settings', 'echc_kommo_status_name');
        register_setting( 'echc_gen_settings', 'echc_kommo_status_id');
    }

    public function init_kommo_status_id() {
        $msg_api     = get_option('ech_lfg_msg_api');
        if ($msg_api !== 'kommo') {
            return;
        }
    
        $pipeline_id = get_option('ech_lfg_kommo_pipeline_id');
        $status_name = get_option('echc_kommo_status_name');
        $status_id   = get_option('echc_kommo_status_id');

        if (!$pipeline_id || !$status_name) {
            return;
        }

        if ($status_id) {
            return;
        }

        $public = new Ech_consultant_Kommo_Public($this->plugin_name, $this->version);
        $status_id = $public->get_kommo_status_id_by_pipeline($pipeline_id, $status_name);
        if ($status_id) {
            update_option('echc_kommo_status_id', $status_id);
        }
    }
    




}
