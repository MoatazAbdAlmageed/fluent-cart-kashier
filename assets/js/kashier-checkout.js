jQuery(document).ready(function ($) {
    var $btn = $('.fct_place_order_btn');
    if ($btn.is(':disabled')) {
        $btn.prop('disabled', false);
        $btn.removeAttr('disabled');
    }
});
