<?php
/**
 * Process donation via the Paynimo payment gateway.
 *
 * @since 1.0
 *
 * @param $donation_data
 */
function give_process_paynimo_payment( $donation_data ) {
    $paynimo_settings = give_get_settings();
    if ( ! wp_verify_nonce( $donation_data['gateway_nonce'], 'give-gateway' ) ) {
        wp_die( esc_html__( 'Nonce verification has failed.', 'give-paynimo' ), esc_html__( 'Error', 'give-paynimo' ), array(
            'response' => 403,
        ) );
    }

    $form_id  = intval( $donation_data['post_data']['give-form-id'] );
    $price_id = isset( $donation_data['post_data']['give-price-id'] ) ? $donation_data['post_data']['give-price-id'] : '';

    // Collect payment data.
    $donation_payment_data = array(
        'price'           => $donation_data['price'],
        'give_form_title' => $donation_data['post_data']['give-form-title'],
        'give_form_id'    => $form_id,
        'give_price_id'   => $price_id,
        'date'            => $donation_data['date'],
        'user_email'      => $donation_data['user_email'],
        'purchase_key'    => $donation_data['purchase_key'],
        'currency'        => give_get_currency(),
        'user_info'       => $donation_data['user_info'],
        'status'          => 'pending',
        'gateway'         => 'paynimo',
    );

    // Record the pending payment.
    $payment = give_insert_payment( $donation_payment_data );

    // Verify donation payment.
    if ( !$payment ) {
        // Record the error.
        give_record_gateway_error(
            esc_html__( 'Payment Error', 'give-paynimo' ),
            /* translators: %s: payment data */
            sprintf(
                esc_html__( 'Payment creation failed before process Paynimo gateway. Payment data: %s', 'give-paynimo' ),
                json_encode( $donation_payment_data )
            ),
            $payment
        );
        // Problems? Send back.
        give_send_back_to_checkout( '?payment-mode=' . $donation_data['post_data']['give-gateway'] );
    } else {
        wp_redirect( home_url( "/?process_paynimo_payment=processing&donation={$payment}&form-id={$form_id}" ) );
    }

    // Send to success page.
    wp_redirect( home_url( "/?process_paynimo_payment=processing&donation={$payment}&form-id={$form_id}" ) );
    exit();
}

add_action( 'give_gateway_paynimo', 'give_process_paynimo_payment' );
