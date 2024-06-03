<?php
/**
 * Do not print cc field in donation form.
 *
 * Note: We do not need credit card field in donation form but we need billing detail fields.
 *
 * @since 1.0.0
 *
 * @param $form_id
 *
 * @return bool
 */
function give_paynimo_cc_form_callback( $form_id ) {

    if ( give_is_setting_enabled( give_get_option( 'paynimo_billing_details' ) ) ) {
        give_default_cc_address_fields( $form_id );

        return true;
    }

    return false;
}

add_action( 'give_paynimo_cc_form', 'give_paynimo_cc_form_callback' );
