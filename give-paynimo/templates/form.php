<?php
// Exit if access directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!doctype html>
<html lang="en">
    <head>
        <title><?php _e( 'Process Donation with Paynimo payment gateways', 'give-paynimo' ); ?></title>
    </head>
    <body>
        <!-- Request -->
        <?php echo "hiee"; ?>
        <?php echo Give_Paynimo_API::get_form(); ?>

        <script language='javascript'>document.paynimoForm.submit();</script>
    </body>
</html>
