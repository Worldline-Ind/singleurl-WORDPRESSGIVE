<?php
// Exit if access directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title><?php echo esc_html__( 'Process Paynimo API Response', 'give-paynimo' ); ?></title>
    </head>
    <body>
<?php
    $paynimo_settings = give_get_settings();
    $response = $_POST;
    if(is_array($response)) {
        $str = $response['msg'];
    } else if(is_string($response) && strstr($response, 'msg=')) {
        $outputStr = str_replace('msg=', '', $response);
        $outputArr = explode('&', $outputStr);
        $str = $outputArr[0];
    } else {
        $str = $response;
    }

    $transactionResponseBean = new TransactionResponseBean();

    $transactionResponseBean->setResponsePayload($str);
    $transactionResponseBean->setKey($paynimo_settings['paynimo_key']);
    $transactionResponseBean->setIv($paynimo_settings['paynimo_iv']);

    $response = $transactionResponseBean->getResponsePayload();

    $response1 = explode('|', $response);
    $firstToken = explode('=', $response1[0]);
    $status = $firstToken[1];
    $donation_data = Give()->session->get( 'give_purchase' );
    $donation_id = $donation_data['donation_id'];
    if ( ! empty( $donation_id ) ) {
        try {
            $donation = new Give_Payment( $donation_id );

            if ( $donation->ID && $donation->status !== 'completed' ) {
                // Process each payment status.
                if($status == '300')
                    Give_Paynimo_API::process_success( $donation->ID, $response1);
                else
                    Give_Paynimo_API::process_failure( $donation->ID, $response1);
            }
        } catch ( Exception $e ) {
            error_log( print_r( $e->getMessage(), true ) . "\n", 3, WP_CONTENT_DIR . '/debug.log' );
        }
    }
    wp_redirect( home_url( '/' ) );
    exit();
?>
    </body>
</html>
