<?php

class Give_Paynimo_API {
    /**
     * Instance.
     *
     * @since  1.0.0
     * @access static
     * @var
     */
    static private $instance;

    /**
     * @var
     */
    static private $api_url;

    /**
     * @var
     */
    static private $merchant_key;

    /**
     * @var
     */
    static private $salt_key;

    /**
     * Singleton pattern.
     *
     * @since  1.0.0
     * @access private
     * Give_Paynimo_API constructor.
     */
    private function __construct() {
    }


    /**
     * Get instance.
     *
     * @since  1.0
     * @access static
     * @return static
     */
    static function get_instance() {
        if ( null === static::$instance ) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Setup params.
     *
     * @since  1.0.0
     * @access public
     * @return mixed
     */
    public function setup_params() {
        $paynimo_settings = give_get_settings();
        self::$merchant_key = $paynimo_settings['paynimo_key'];
        return self::$instance;
    }

    /**
     * Setup hooks.
     *
     * @since  1.0.0
     * @access public
     * @return mixed
     */
    public function setup_hooks() {
        add_filter( 'template_include', array( $this, 'show_paynimo_form_template' ) );
        add_filter( 'template_include', array( $this, 'show_paynimo_payment_success_template' ) );

        return self::$instance;
    }

    /**
     * Show paynimo form template.
     *
     * @since  1.0.0
     * @access public
     *
     * @param $template
     *
     * @return string
     */
    public function show_paynimo_form_template( $template ) {
        if ( isset( $_GET['process_paynimo_payment'] ) && 'processing' === $_GET['process_paynimo_payment'] ) {
            $template = GIVE_PAYNIMO_DIR . 'templates/form.php';
        }

        return $template;
    }

    /**
     * Show success template
     *
     * @since  1.0.0
     * @access public
     *
     * @param $template
     *
     * @return string
     */
    public function show_paynimo_payment_success_template( $template ) {
        if ($_POST && isset($_POST['msg'])) {
            $template = GIVE_PAYNIMO_DIR . 'templates/success.php';
        }
        return $template;
    }

    /**
     * Get form
     *
     * @since  1.0.0
     * @access public
     * @return string
     */
    public static function get_form() {
        $donation_data = Give()->session->get( 'give_purchase' );
        $paynimo_settings = give_get_settings();
        $donation_id   = absint( $_GET['donation'] );
        $form_id       = absint( $_GET['form-id'] );

        $form_url = trailingslashit( current( explode( '?', $donation_data['post_data']['give-current-url'] ) ) );

        $paynimo_args = array(
            'key'              => self::$merchant_key,
            'txnid'            => "{$donation_id}_" . date( 'ymds' ),
            'amount'           => give_sanitize_amount( $donation_data['post_data']['give-amount'] ),
            'firstname'        => $donation_data['post_data']['give_first'],
            'email'            => $donation_data['post_data']['give_email'],
            'phone'            => ( isset( $donation_data['post_data']['give_paynimo_phone'] ) ? $donation_data['post_data']['give_paynimo_phone'] : '' ),
            'productinfo'      => sprintf( __( 'This is a donation payment for %s', 'give-paynimo' ), $donation_id ),
            'surl'             => $form_url . '?process_paynimo_payment=success',
            'furl'             => $form_url . '?process_paynimo_payment=failure',
            'lastname'         => $donation_data['post_data']['give_last'],
            'udf1'             => $donation_id,
            'udf2'             => $form_id,
            'udf3'             => $form_url,
            'udf5'             => 'givewp',
        );

        // Pass address info if present.
        if ( give_is_setting_enabled( give_get_option( 'paynimo_billing_details' ) ) ) {
            $paynimo_args['address1'] = $donation_data['post_data']['card_address'];
            $paynimo_args['address2'] = $donation_data['post_data']['card_address_2'];
            $paynimo_args['city']     = $donation_data['post_data']['card_city'];
            $paynimo_args['state']    = $donation_data['post_data']['card_state'];
            $paynimo_args['country']  = $donation_data['post_data']['billing_country'];
            $paynimo_args['zipcode']  = $donation_data['post_data']['card_zip'];
        }

        $order =$paynimo_args;
        $order_id = $donation_data['post_data']['give-form-id'];
        $order_id = $order_id.'_'.date("ymds");
        $transactionRequestBean = new TransactionRequestBean(); 
        $merchant_txn_id = rand(1,1000000);
        $cur_date = date("d-m-Y");
        $returnUrl = give_get_success_page_uri();
        $transactionRequestBean->setMerchantCode($paynimo_settings['paynimo_merchant_code']);
        $transactionRequestBean->setHashAlgo($paynimo_settings['paynimo_hashalgo']);
        $transactionRequestBean->setRequestType($paynimo_settings['paynimo_request_type']);
        $transactionRequestBean->setMerchantTxnRefNumber($merchant_txn_id);
        $transactionRequestBean->setAmount($donation_data['price']);
        $transactionRequestBean->setWebServiceLocator('https://www.tpsl-india.in/PaymentGateway/TransactionDetailsNew.wsdl');
        $shoppingCartStr = $paynimo_settings['paynimo_merchant_scheme_code'].'_'.$donation_data['price'].'_0.0';
        $transactionRequestBean->setShoppingCartDetails($shoppingCartStr);
        if(isset($paynimo_args['firstname']) && isset($paynimo_args['lastname'])) {
            $transactionRequestBean->setCustomerName($paynimo_args['firstname']. " ". $paynimo_args['lastname']);
        }

        $transactionRequestBean->setReturnURL($returnUrl);
        $transactionRequestBean->setTxnDate($cur_date);
        $transactionRequestBean->setKey($paynimo_args['key']);
        $transactionRequestBean->setIv($paynimo_settings['paynimo_iv']);
        $transactionRequestBean->setUniqueCustomerId($form_id);
        $transactionRequestBean->setITC('email:'.$paynimo_args['email']);
        $transactionRequestBean->setEmail($paynimo_args['email']);
        $url = $transactionRequestBean->getTransactionToken();
        if (preg_match('/ERROR/', $url))
            $url = give_get_failed_transaction_uri('error_code=' . $url);

        /**
         * Filter the paynimo form arguments
         *
         * @since 1.0.0
         *
         * @param array $paynimo_args
         */
        $paynimo_args = apply_filters( 'give_paynimo_form_args', $paynimo_args );

        // Create input hidden fields.
        $paynimo_args_array = array();
        foreach ( $paynimo_args as $key => $value ) {
            $paynimo_args_array[] = '<input type="hidden" name="' . $key . '" value="' . $value . '"/>';
        }
        ob_start();
        ?>
        <form action="<?php echo $url; ?>" method="post" name="paynimoForm" style="display: none">
            <?php echo implode( '', $paynimo_args_array ); ?>
            <input type="submit" value="Submit"/>
        </form>
        <?php
        $form_html = ob_get_contents();
        ob_get_clean();
        return $form_html;
    }


    /**
     * Process Paynimo success payment.
     *
     * @since  1.0.0
     *
     * @access public
     *
     * @param int $donation_id
     */
    public static function process_success( $donation_id, $response ) {
        $donation = new Give_Payment($donation_id);
        $donation->__set('status', $donation->status);
        $transaction_id = explode('=', $response[5]);
        $donation->update_status( 'completed' );
        $donation->add_note( sprintf( __( 'Paynimo payment completed (Transaction id: %s)', 'give-paynimo' ), $transaction_id[1] ) );

        update_post_meta( $donation_id, 'paynimo_donation_response', $response );
        give_set_payment_transaction_id( $donation_id, $transaction_id[1] );
        give_send_to_success_page();
    }

    /**
     * Process Paynimo failure payment.
     *
     * @since  1.0.0
     *
     * @access public
     *
     * @param int $donation_id
     */
    public static function process_failure( $donation_id, $response ) {
        $donation = new Give_Payment( $donation_id );
        $transaction_id = explode('=', $response[5]);
        $error_msg = explode('=', $response[2]);
        $donation->update_status( 'failed' );
        $donation->add_note( sprintf( __( 'Paynimo payment failed (Transaction id: %s)', 'give-paynimo' ), $transaction_id[1] ) );

        update_post_meta( $donation_id, 'paynimo_donation_response', $response );
        give_set_payment_transaction_id( $donation_id, $transaction_id[1] );

        give_record_gateway_error(
            esc_html__( 'Paynimo Error', 'give-paynimo' ),
            esc_html__( 'The Paynimo Gateway returned an error while charging a donation.', 'give-paynimo' ) . '<br><br>' . sprintf( esc_attr__( 'Details: %s', 'give-paynimo' ), '<br>' . print_r( $error_msg[1], true ) ),
            $donation_id
        );

        wp_redirect( give_get_failed_transaction_uri() );
        exit();
    }
}

Give_Paynimo_API::get_instance()->setup_params()->setup_hooks();
