<?php

/**
 * Class Give_Paynimo_Gateway_Settings
 *
 * @since 1.0
 */
class Give_Paynimo_Gateway_Settings {
    /**
     * @since  1.0
     * @access static
     * @var Give_Paynimo_Gateway_Settings $instance
     */
    static private $instance;

    /**
     * @since  1.0
     * @access private
     * @var string $section_id
     */
    private $section_id;

    /**
     * @since  1.0
     * @access private
     * @var string $section_label
     */
    private $section_label;

    /**
     * Give_Paynimo_Gateway_Settings constructor.
     */
    private function __construct() {
    }

    /**
     * get class object.
     *
     * @since 1.0
     * @return Give_Paynimo_Gateway_Settings
     */
    static function get_instance() {
        if ( null === static::$instance ) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Setup hooks.
     *
     * @since 1.0
     */
    public function setup_hooks() {
        $this->section_id    = 'paynimo';
        $this->section_label = __( 'Paynimo', 'give-paynimo' );

        // Add payment gateway to payment gateways list.
        add_filter( 'give_payment_gateways', array( $this, 'add_gateways' ) );

        if ( is_admin() ) {

            // Add section to payment gateways tab.
            add_filter( 'give_get_sections_gateways', array( $this, 'add_section' ) );

            // Add section settings.
            add_filter( 'give_get_settings_gateways', array( $this, 'add_settings' ) );
        }
    }

    /**
     * Add payment gateways to gateways list.
     *
     * @since 1.0
     *
     * @param array $gateways array of payment gateways.
     *
     * @return array
     */
    public function add_gateways( $gateways ) {
        $gateways[ $this->section_id ] = array(
            'admin_label'  => $this->section_label,
            'checkout_label' => give_paynimo_get_payment_method_label(),
        );

        return $gateways;
    }

    /**
     * Add setting section.
     *
     * @since 1.0
     *
     * @param array $sections Array of section.
     *
     * @return array
     */
    public function add_section( $sections ) {
        $sections[ $this->section_id ] = $this->section_label;

        return $sections;
    }

    /**
     * Add plugin settings.
     *
     * @since 1.0
     *
     * @param array $settings Array of setting fields.
     *
     * @return array
     */
    public function add_settings( $settings ) {
        $current_section = give_get_current_setting_section();

        if ( $this->section_id === $current_section ) {
            $settings = array(
                array(
                    'id' => 'give_paynimo_payments_setting',
                    'type' => 'title',
                ),
                array(
                    'title'    => __( 'Title', 'give-paynimo' ),
                    'type'=> 'text',
                    'id'=>'paynimo_title',
                    'desc_tip'    => true,
                    'placeholder' => __( 'Paynimo', 'give-paynimo' ),
                    'description' => __('Your desire title name .it will show during checkout proccess.', 'paynimo'),
                    'default' => __('Paynimo', 'paynimo'),
                ),
                array(
                    'title' => __( 'Collect Billing Details', 'give-paynimo' ),
                    'id' => 'paynimo_billing_details',
                    'type' => 'radio_inline',
                    'options' => array(
                        'enabled' => esc_html__( 'Enabled', 'give-paynimo' ),
                        'disabled' => esc_html__( 'Disabled', 'give-paynimo' ),
                    ),
                    'default' => 'disabled',
                    'description' => __( 'This option will enable the billing details section for Paynimo which requires the donor\'s address to complete the donation. These fields are not required by Paynimo to process the transaction, but you may have the need to collect the data.', 'give-paynimo' ),
                ),
                array(
                    'title' => __( 'Description', 'give-paynimo' ),
                    'type' => 'textarea',
                    'id'=> 'paynimo_textarea',
                    'desc_tip' => true,
                    'placeholder' => __( 'Description', 'give-paynimo' ),
                    'description' => __('Pay securely through Paynimo.', 'paynimo'),
                    'default' => __('Pay securely through Paynimo.', 'paynimo'),
                    'options' => array(
                        'textarea_rows' => 6
                    ),
                ),
                array(
                    'title' => __( 'Paynimo Merchant Code', 'give-paynimo' ),
                    'type' => 'text',
                    'id'=> 'paynimo_merchant_code',
                    'desc_tip' => true,
                    'placeholder' => __( 'Merchant Code', 'give-paynimo' ),
                    'description' => __('Merchant Code'),
                ),
                array(
                    'title' => __( 'Paynimo Request Type' , 'give-paynimo'),
                    'id' => 'paynimo_request_type',
                    'type' => 'select',
                    'class' => 'chosen_select',
                    'css' => 'min-width:350px;',
                    'description' => __( 'Choose request type.', 'give-paynimo' ),
                    'default' => 'T',
                    'desc_tip' => true,
                    'options' => array(
                    'T' => __( 'T', 'give-paynimo' ),
                    ),
                ),
                array(
                    'title' => __( 'Hashing Algorithm' , 'give-paynimo'),
                    'id' => 'paynimo_hashalgo',
                    'type' => 'select',
                    'class' => 'chosen_select',
                    'css' => 'min-width:350px;',
                    'description' => __( 'Choose Hashing Algorithm.', 'give-paynimo' ),
                    'default' => 'SHA3-512',
                    'desc_tip' => true,
                    'options' => array(
                    'SHA3-512' => __( 'SHA3-512', 'give-paynimo' ),
                    'SHA3-256' => __( 'SHA3-256', 'give-paynimo' ),
                    ),
                ),
                array(
                    'title' => __('Paynimo Key', 'give-paynimo'),
                    'id' => 'paynimo_key',
                    'type' => 'text',
                    'desc_tip' => true,
                    'placeholder' => __( 'Encryption Key', 'give-paynimo' ),
                    'description' => __('Encryption Key'),
                ),
                array(
                    'title' => __('Paynimo IV', 'give-paynimo'),
                    'id' => 'paynimo_iv',
                    'type' => 'text',
                    'desc_tip' => true,
                    'placeholder' => __( 'Encryption IV', 'give-paynimo' ),
                    'description' => __('Encryption IV'),
                ),
                array(
                    'title' => __('Paynimo Webservice Locator', 'give-paynimo'),
                    'id' => 'paynimo_webservice_locator',
                    'type' => 'select',
                    'class' => 'chosen_select',
                    'css' => 'min-width:350px;',
                    'description' => __( 'Choose Webservice Locator.', 'give-paynimo' ),
                    'default' => 'Test',
                    'desc_tip' => true,
                    'options' => array(
                        // 'Test'          => __( 'TEST', 'give-paynimo' ),
                        'Live' => __( 'LIVE', 'give-paynimo' ),
                    ),
                ),
                array(
                    'title' => __('Paynimo Merchant Scheme Code', 'give-paynimo'),
                    'id' => 'paynimo_merchant_scheme_code',
                    'type' => 'text',
                    'desc_tip' => true,
                    'placeholder' => __( 'Merchant Scheme Code', 'give-paynimo' ),
                    'description' => __('Merchant Scheme Code'),
                ),
                array(
                    'title' => __('Paynimo Success Message', 'give-paynimo'),
                    'id' => 'paynimo_success_msg',
                    'type' => 'textarea',
                    'desc_tip' => true,
                    'default' => 'Thank you for your donation! Your transaction is successful.',
                    'description' => __('Success Message'),
                ),
                array(
                    'title' => __('Paynimo Decline Message', 'give-paynimo'),
                    'id' => 'paynimo_decline_msg',
                    'type' => 'textarea',
                    'desc_tip' => true,
                    'default' => 'Thank you for visiting us. However, the transaction has been declined.',
                    'description' => __('Decline Message'),
                ),
                array(
                    'title' => __('Paynimo Redirect Message', 'give-paynimo'),
                    'id' => 'paynimo_redirect_msg',
                    'type' => 'textarea',
                    'desc_tip' => true,
                    'default' => 'Thank you for your donation. We are now redirecting you to Paynimo to make payment.',
                    'description' => __('Redirect Message'),
                ),
                array(
                    'id' => 'give_paynimo_payments_setting',
                    'type' => 'sectionend',
                ),
            );
        }// End if().

        return $settings;
    }
}

Give_Paynimo_Gateway_Settings::get_instance()->setup_hooks();
