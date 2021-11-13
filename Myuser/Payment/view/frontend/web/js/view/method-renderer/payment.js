define([
    "Magento_Payment/js/view/payment/cc-form",
    "jquery",
    "Magento_Checkout/js/model/quote",
    "Magento_Payment/js/model/credit-card-validation/validator",
    "userpayjs",
], function (Component, $, quote) {
    "use strict";
    return Component.extend({
        defaults: {
            template: "Myuser_Payment/payment/payment",
        },
        getCode: function () {
            return "myuserpayment";
        },
        getPublicKey: function () {
            return window.checkoutConfig.payment.myuser.apikeypublic;
        },
        isActive: function () {
            return true;
        },
        validate: function () {
            var $form = $("#" + this.getCode() + "-form");
            return $form.validation() && $form.validation("isValid");
        },
        getSubtotal: function () {
            var totals = quote.totals();
            return (totals ? totals : quote)["subtotal"];
        },
        getGrandTotal: function () {
            var totals = quote.totals();
            console.log(totals);
            var style = "body{}";
            MyUserPay.setKey(this.getPublicKey());
            element_num = MyUserPay.createElement("#a", {
                style: style,
                amount: (totals ? totals : quote)["base_grand_total"] * 100,
            });
            return (totals ? totals : quote)["base_grand_total"];
        },
    });
});
