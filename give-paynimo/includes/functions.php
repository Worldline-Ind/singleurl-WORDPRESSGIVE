<?php
/**
 * Check if the Paynimo payment gateway is active or not.
 *
 * @since 1.0.0
 * @return bool
 */
function give_is_paynimo_active() {
    $give_settings = give_get_settings();
    $is_active     = false;

    if (
        array_key_exists( 'paynimo', $give_settings['gateways'] )
        && ( 1 == $give_settings['gateways']['paynimo'] )
    ) {
        $is_active = true;
    }

    return $is_active;
}


/**
 * Get payment method label.
 *
 * @since 1.0
 * @return string
 */
function give_paynimo_get_payment_method_label() {
    return ( give_get_option( 'paynimo_payment_method_label', false ) ?  give_get_option( 'paynimo_payment_method_label', '' ) : __( 'Paynimo', 'give-paynimo' ) );
}
