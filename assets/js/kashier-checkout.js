jQuery(document).ready(function($) {
    // Function to check and enable button
    function checkKashierButton() {
        var selectedMethod = $('input[name="payment_method"]:checked').val();
        
        // If no method is checked, maybe it's a single method or hidden input
        if (!selectedMethod) {
            // Try to find if kashier is the only one or active class
            if ($('.fluent_cart_payment_method_kashier').hasClass('active') || $('.fc-payment-method-kashier').hasClass('active')) {
                selectedMethod = 'kashier';
            }
        }

        if (selectedMethod === 'kashier') {
            var $btn = $('#fluent_cart_order_btn');
            if ($btn.is(':disabled')) {
                $btn.prop('disabled', false);
                $btn.removeAttr('disabled');
                // console.log('Kashier: Enabled Place Order button');
            }
        }
    }

    // Check on change
    $(document).on('change', 'input[name="payment_method"]', function() {
        checkKashierButton();
    });

    // Check periodically in case of dynamic updates (FluentCart might re-render)
    setInterval(checkKashierButton, 500);
});
