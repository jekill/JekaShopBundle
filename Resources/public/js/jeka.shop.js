var Jeka = Jeka || {};
Jeka.Shop = Jeka.Shop || {};


Jeka.Shop.updateCartInfo = function (quantity, totalAmount) {
    $(".cart_quantity").html(quantity);
    $('.cart_amount').html(totalAmount);
    if (Number(quantity)>0)
    {
        $('.cart .checkout a').removeClass('disabled');
    }
};


Jeka.Shop.bindPlusMinusButtons = function (fromCart) {
    $("a.quantity_change").live('click',function () {

        var inc = $(this).hasClass('plus') ? 1 : -1;
        var $quantity;
        if (fromCart) {
            $quantity = $(this).parents('tr').find('input.quantity');
        }
        else {
            $quantity = $(".buy-product input.quantity");
        }

        var val = parseInt($quantity.val());
        val += inc;
        if (val < 1) val = 1;
        $quantity.val(val);
        $quantity.change();

        return false;
    });
}

Jeka.Shop.initLightBox = function () {
    $("a[rel^='prettyPhoto']").prettyPhoto({
        social_tools:false,
        default_width:600,
        default_height:400
    });
}

Jeka.Shop.Forms = {};

Jeka.Shop.Forms.toCartAjaxEvent = function () {
    $form = $(this);
    var data = $form.serialize();
    var prodId = $form.find('input[name="product_id"]').val();
    var formId = 'to-cart-form_' + prodId;

    var submitButton = $('#' + formId + ' input[type="submit"]').attr('disabled', 'disabled');
    window.setTimeout(function () {
        submitButton.removeAttr('disabled')
    }, 2000);
    $('#' + formId + ' .action-info').html('<div class="jk-process">Добавление в корзину...</div>');
    $.post($form.attr('action'), data, function (success) {
        $('#' + formId + ' .action-info-js').html(success);
    });
    return false;
}
Jeka.Shop.bindToCartForm = function () {

    Jeka.Shop.bindPlusMinusButtons();
    $('.jk-to-cart-form').submit(Jeka.Shop.Forms.toCartAjaxEvent);
}

