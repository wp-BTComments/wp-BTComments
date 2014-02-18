<?php

require_once dirname( __FILE__ ) . '/wordpress-settings-api-class/class.settings-api.php';
require_once dirname( __FILE__ ) . '/class.BitcoinAddressValidation.php';

if ( !class_exists('BitComments_Settings' ) ):
class BitComments_Settings {

    private $settings_api;

    function __construct() {
        $this->settings_api = new WeDevs_Settings_API;

        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_menu() {
        add_options_page( 'BitComments Settings', 'BitComments Settings', 'delete_posts', 'bitcomments-settings', array($this, 'plugin_page') );
    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id' => 'bitcomments_basics',
                'title' => __( 'Basic Settings', 'bitcomments' )
            )
        );
        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        $settings_fields = array(
            'bitcomments_basics' => array(
                array(
                    'name' => 'blockchain_identifier',
                    'label' => __( 'Blockchain.info Identifier', 'bitcomments' ),
                    'desc' => __( 'Blockchain.info Identifier (sometimes called a GUID)', 'bitcomments' ),
                    'type' => 'text',
                    'default' => ''
                    /* 'sanitize_callback' => 'intval' */
                    // TODO: use BitcoinAddressValidation routines to sanitize this value
                ),
                array(
                    'name' => 'blockchain_password',
                    'label' => __( 'Blockchain Password', 'bitcomments' ),
                    'desc' => __( 'Blockchain Password', 'bitcomments' ),
                    'type' => 'password',
                    'default' => ''
                )
                /* array( */
                /*     'name' => 'address_label', */
                /*     'label' => __( 'New Receive Address Label (defaults to "blog")', 'bitcomments' ), */
                /*     'desc' => __( 'This is the label that will be applied to each new receive address in your wallet.', 'bitcomments' ), */
                /*     'type' => 'text', */
                /*     'default' => '' */
                /* ), */
            )
        );

        return $settings_fields;
    }

    function plugin_page() {
        echo '<div class="wrap">';

        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';
    }

    /**
     * Get all the pages
     *
     * @return array page names with key value pairs
     */
    function get_pages() {
        $pages = get_pages();
        $pages_options = array();
        if ( $pages ) {
            foreach ($pages as $page) {
                $pages_options[$page->ID] = $page->post_title;
            }
        }

        return $pages_options;
    }

}
endif;

$settings = new BitComments_Settings();
