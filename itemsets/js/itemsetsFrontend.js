/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
if (typeof jQuery === 'undefined') {
    alert("Для корректной работы плагина \"Товары-комплекты\" подключите библиотеку jQuery (For correct work of the plugin \"Product-sets\" use the jQuery library)");
} else {
    (function ($) {
        $.itemsetsFrontend = {
            url: null,
            allowCheckout: null,
            locale: 'en_US',
            features: {},
            translate: function (message) {
                if (typeof this.messages[this.locale] !== 'undefined' && this.messages[this.locale][message]) {
                    return this.messages[this.locale][message];
                }
                return message;
            },
            initCart: function (options) {
                this.url = options.url || '';
                this.allowCheckout = options.allowCheckout || null;
                this.locale = options.locale || this.locale;
                var errorIds = options.errorIds || null;
                var errorItemIds = options.errorItemIds || null;

                if (errorIds) {
                    this.showErrorIds(errorIds);
                    if (!this.allowCheckout) {
                        $("input[name='checkout']").attr('disabled', 'disabled').addClass("disabled");
                        $(".quickorder-button-cart").hide();
                    }
                }
                if (errorItemIds) {
                    this.showErrorItemIds(errorItemIds);
                    if (!this.allowCheckout) {
                        $("input[name='checkout']").attr('disabled', 'disabled').addClass("disabled");
                        $(".quickorder-button-cart").hide();
                    }
                }

            },
            init: function () {
                // Обновляем значение скидки в случае наличия артикулов и изменения количества товаров
                $(document).on("change", ".sku-feature", function () {
                    $.itemsetsFrontend.skuChangeAction($(this));
                });
                $(document).on("click", "#product-skus input[type=radio], .skus input[type=radio]", function () {
                    $.itemsetsFrontend.skuChangeAction($(this));
                });
            },
            initProduct: function () {
                var form = $("#cart-form").length ? $("#cart-form") : $("#cart-form-dialog");
                // Если выбрали товар, которого нету, то скрываем кнопку быстрого заказа
                var skus = form.find("#product-skus input[type=radio]").length ? form.find("#product-skus input[type=radio]") : form.find(".skus input[type=radio]");

                if (skus.length) {
                    $.itemsetsFrontend.skuChangeAction(skus.filter(':checked'));
                }
                var skuFeature = form.find(".sku-feature");
                if (skuFeature.length) {
                    $.itemsetsFrontend.skuChangeAction(skuFeature);
                }
            },
            skuChangeAction: function (elem) {
                var insideForm = elem.closest("form");
                var itemsetsForm = elem.closest(".itemsets-product-form");
                var form = insideForm.length ? insideForm : (itemsetsForm ? itemsetsForm : $("#cart-form"));
                var productId = form.find("input[name='product_id']").val();
                if ($(".itemsets-block-" + productId + " .itemsets-skus-block").length) {
                    $(".itemsets-block-" + productId + " .itemsets-skus-block").hide();
                    var skuId = $.itemsetsFrontend.getSkuID(form);
                    if (skuId && $(".itemsets-sku-" + skuId).length) {
                        $(".itemsets-block-" + productId).show();
                        $(".itemsets-sku-" + skuId).show();
                    } else {
                        $(".itemsets-block-" + productId).hide();
                    }
                }
            },
            getSkuID: function (form) {
                var skuId = '';
                form = form ? form : $(document);
                var productId = form.find("input[name='product_id']").val();
                if (form.find("#product-skus").length) {
                    skuId = form.find("#product-skus input[type=radio]:checked").val();
                } else if (form.find(".skus").length) {
                    skuId = form.find(".skus input[type=radio]:checked").val();
                }

                if (form.find(".sku-feature").length) {
                    var key = "";
                    form.find(".sku-feature").each(function () {
                        key += $(this).data('feature-id') + ':' + $(this).val() + ';';
                    });
                    if (productId && typeof $.itemsetsFrontend.features[productId] !== 'undefined') {
                        var sku = $.itemsetsFrontend.features[productId][key];
                        if (sku) {
                            skuId = sku.id;
                        }
                    }
                }

                return skuId;
            },
            showErrorIds: function (errorIds) {
                // Если имеются проблемные товары-комплекты
                if (errorIds) {
                    $.each(errorIds, function (i, v) {
                        $.itemsetsFrontend.printError(v, $.itemsetsFrontend.translate("Not enough product-set items in stocks. Please, remove one of the product-sets or reduce the quantity"));
                    });
                }
            },
            showErrorItemIds: function (errorItemIds) {
                // Если имеются проблемные товары-комплекты
                if (errorItemIds) {
                    $.each(errorItemIds, function (i, v) {
                        var text = $.itemsetsFrontend.translate("This product is contained in the product-set and it's not enough in stocks. Please, remove it or reduce it on {0} PCs.");
                        $.itemsetsFrontend.printError(v, String.itemsetsPluginFormat(text, v.limit));
                    });
                }
            },
            printError: function (object, errormsg) {
                $.each(object.cart_id, function (i, v) {
                    var quantityField = $("input[name='quantity[" + v + "]']");
                    // Проверяем существование товара в корзине
                    if (quantityField.length) {
                        var block = quantityField.closest("[data-id=\"" + v + "\"]");
                        if (block.length) {
                            var html = '<div class="itemsets-errorfld">' + errormsg + '</div>';
                            block.append(html);
                        }
                    }
                });
            },
            // Событие, срабатывающее при изменении количества товаров в корзине
            quantityChange: function (input) {
                if (!input) {
                    return false;
                }
                var quantity = input.val();
                if (quantity > 0 && quantity) {
                    this.refreshCart({cart_id: input.attr("name").match(/\d+/)[0], quantity: quantity});
                }
            },
            // Событие, срабатывающее при удалении товаров из корзины
            cartDelete: function (btn) {
                if (!btn) {
                    return false;
                }
                var cartId = btn.closest("[data-id]").attr("data-id");
                if (cartId) {
                    this.refreshCart({cart_id: cartId, action: 'delete'});
                }
            },
            refreshCart: function (params) {
                var errorfld = $(".itemsets-errorfld");
                if (errorfld.length) {
                    errorfld.html(this.translate("Recounting...") + " <i class='itemsets-pl loader'></i>");
                }
                $.post($.itemsetsFrontend.url, params, function (response) {
                    $("input[name='checkout']").removeAttr('disabled').removeClass("disabled");
                    if (response.status == 'ok' && response.data) {
                        errorfld.remove();
                        if (typeof response.data.error_ids !== 'undefined' || typeof response.data.error_item_ids !== 'undefined') {
                            $.itemsetsFrontend.showErrorIds(response.data.error_ids);
                            $.itemsetsFrontend.showErrorItemIds(response.data.error_item_ids);
                            if (!$.itemsetsFrontend.allowCheckout) {
                                $("input[name='checkout']").attr('disabled', 'disabled').addClass("disabled");
                                $(".quickorder-button-cart").hide();
                            }
                        } else {
                            $(".quickorder-button-cart").show();
                        }
                    }
                }, "json");
            }
        };
    })(jQuery);
    if (!String.itemsetsPluginFormat) {
        String.itemsetsPluginFormat = function (format) {
            var args = Array.prototype.slice.call(arguments, 1);
            return format.replace(/{(\d+)}/g, function (match, number) {
                return typeof args[number] != 'undefined'
                        ? args[number]
                        : match
                        ;
            });
        };
    }
}