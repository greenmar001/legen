/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
(function ($) {
    $.itemsets = {
        form: null,
        container: $("#s-content"),
        editor: {},
        init: function (options) {
            this.currency = options.currency || {};
            this.currencies = options.currencies || {};
            this.form = $("#itemsets-form");
            $.each($(".itemsets-table"), function () {
                $.itemsets.updatePrice($(this));
            });

            // Автозаполнение 
            $('.itemsets-autocomplete').each(function () {
                $.itemsets.initAutocomplete($(this));
            });

            if ($(".s-product-form.main .s-product-skus tbody tr").length > 1) {
                $("#itemsets-skus").css('display', 'block');
            } else {
                $("#itemsets-skus").hide();
            }

            // Сортировка  
            var upTimeout;
            $(document).on('click', '.itemsets-table .f-up', function () {
                var tr = $(this).closest("tr");
                var trBefore = tr.prev(".itemsets-item");
                if (trBefore.length) {
                    clearTimeout(upTimeout);
                    tr.addClass("selected").insertBefore(trBefore);
                    upTimeout = setTimeout(function () {
                        tr.removeClass("selected");
                    }, 1000);
                }
            });
            var downTimeout;
            $(document).on('click', '.itemsets-table .f-down', function () {
                var tr = $(this).closest("tr");
                var trAfter = tr.next(".itemsets-item").not(".itemsets-template");
                if (trAfter.length) {
                    clearTimeout(downTimeout);
                    tr.addClass("selected").insertAfter(trAfter);
                    downTimeout = setTimeout(function () {
                        tr.removeClass("selected");
                    }, 1000);
                }
            });

            // Удаление товара
            $(document).on('click', '.itemsets-table .f-itemsets-delete', function () {
                var t = $(this).closest(".itemsets-table");
                $(this).closest("tr").remove();
                $.itemsets.updatePrice(t);
                if (t.find(".itemsets-item").length == 1) {
                    t.find(".empty-set").show();
                    t.find(".is-empty-hide").hide();
                }
            });

            // Изменение количества товара, размера скидки и валюты
            $(document).on('change', '.itemsets-table .f-item-quantity, .itemsets-table .f-item-discount, .itemsets-table .f-item-currency', function () {
                $.itemsets.updatePrice($(this).closest(".itemsets-table"));
            });

            // Округление цены
            $('.round_price').on('change', function () {
                $.each($(".itemsets-table"), function () {
                    $.itemsets.updatePrice($(this));
                });
            });

            // Выбор склада
            $(document).on('change', '.itemsets-table .itemsets-stocks', function () {
                var that = $(this);
                var stockId = that.val();
                var tr = that.closest("tr");
                var selected = that.find("option[selected]");
                selected.removeAttr('selected');
                that.find(":selected").attr("selected", "selected");
                tr.find(".itemsets-stock-" + stockId).css('display', 'inline-block').siblings().hide();
            });

            // Настройка артикулов товара
            $("#itemsets-skus").click(function () {
                var that = $(this);

                if (that.hasClass("wait")) {
                    return false;
                }

                var i = that.find("i");
                var action = i.hasClass("merge") ? "merge" : "split";
                var oldText = that.find("span").text();
                var newText = that.attr("data-toggle");
                // Разделение комплектов по артикулам
                if (action == 'split') {
                    if ($(".s-product-form.main .s-product-skus tbody tr").length > 1) {
                        i.removeClass("split").addClass("merge");
                        var first = {};
                        $.each($(".s-product-form.main .s-product-skus tbody tr").not(".js-sku-settings"), function (i, sku) {
                            sku = $(sku);
                            var skuSku = sku.find(".s-sku input");
                            var skuName = sku.find(".s-name input");
                            var skuId = sku.attr("data-id");
                            if (i === 0) {
                                var itemsetsBlock = $(".itemsets-block");
                                $.each(itemsetsBlock.find(".itemsets-item").not(".itemsets-template"), function (i, tr) {
                                    tr = $(tr);
                                    var itemId = tr.attr("data-id");
                                    tr.find(".f-item-quantity").attr("name", "items[" + skuId + "][" + itemId + "][quantity]");
                                    tr.find(".f-item-discount").attr("name", "items[" + skuId + "][" + itemId + "][discount]");
                                    tr.find(".f-item-currency").attr("name", "items[" + skuId + "][" + itemId + "][currency]");
                                    tr.find(".itemsets-stocks").attr("name", "items[" + skuId + "][" + itemId + "][stock_id]");
                                    tr.closest("tbody").find(".stock-spread").attr("name", "settings[" + skuId + "]");
                                });
                                first = itemsetsBlock.clone();
                                itemsetsBlock.prepend("<div class='itemsets-sku-block'><span class='itemsets-sku-number'>" + (i + 1) + "</span> " + "(" + (skuSku.val() !== '' ? skuSku.val() : skuSku.attr("placeholder")) + ")" + " " + (skuName.val() !== '' ? skuName.val() : skuName.attr("placeholder")) + "</div>");
                                itemsetsBlock.find(".product_sku_id").val(skuId);
                            } else {
                                var clone = first.clone();
                                clone.prepend("<div class='itemsets-sku-block'><span class='itemsets-sku-number'>" + (i + 1) + "</span> " + "(" +(skuSku.val() !== '' ? skuSku.val() : skuSku.attr("placeholder")) + ")" + " " + (skuName.val() !== '' ? skuName.val() : skuName.attr("placeholder")) + "</div>");
                                clone.find(".product_sku_id").val(skuId);
                                clone.find(".itemsets-autocomplete").empty();
                                clone.find(".f-autocomplete-skus").prop('checked', false);
                                $.each(clone.find(".itemsets-item").not(".itemsets-template"), function (i, tr2) {
                                    tr2 = $(tr2);
                                    var itemId = tr2.attr("data-id");
                                    tr2.find(".f-item-quantity").attr("name", "items[" + skuId + "][" + itemId + "][quantity]");
                                    tr2.find(".f-item-discount").attr("name", "items[" + skuId + "][" + itemId + "][discount]");
                                    tr2.find(".f-item-currency").attr("name", "items[" + skuId + "][" + itemId + "][currency]");
                                    tr2.find(".itemsets-stocks").attr("name", "items[" + skuId + "][" + itemId + "][stock_id]");
                                    tr2.find(".stock-spread").attr("name", "settings[" + skuId + "]");
                                    tr2.closest("tbody").find(".stock-spread").attr("name", "settings[" + skuId + "]");
                                });
                                $(".itemsets-block:last").after(clone);
                                $.itemsets.initAutocomplete(clone.find(".itemsets-autocomplete"));
                            }
                        });
                        $("#itemsets-form .itemsets-split-set").val(0);
                    } else {
                        that.hide();
                    }
                } else {
                    var itemsetsBlock = $(".itemsets-block:first-child").clone();
                    itemsetsBlock.find(".itemsets-sku-block").remove();
                    itemsetsBlock.find(".stock-spread").attr("name", "settings[spread_stock]");
                    $(".itemsets-block").remove();
                    $("#itemsets-form").prepend(itemsetsBlock);
                    $.itemsets.initAutocomplete(itemsetsBlock.find(".itemsets-autocomplete"));
                    i.removeClass("merge").addClass("split");
                    $("#itemsets-form .itemsets-split-set").val(1);
                }
                that.attr("data-toggle", oldText).find("span").text(newText);
            });
        },
        initSettings: function () {
            // Сохранение формы настроек
            $("#itemsets-form input[type='submit']").click(function () {
                var btn = $(this);
                var form = btn.closest("form");
                var errormsg = form.find(".errormsg");
                var removeStatusIcon = function(btn) {
                    setTimeout(function() {
                        btn.next(".icon16").remove();  
                    }, 3000);
                };
                errormsg.text("");

                $('#itemsets-form .editor-content').waEditor('sync');

                btn.next("i.icon16").remove();
                btn.attr('disabled', 'disabled').after("<i class='icon16 loading temp-loader'></i>");

                $.each($.itemsets.editor, function (textarea, v) {
                    $('#' + textarea).val(v.getValue());
                });

                $.ajax({
                    url: "?plugin=itemsets&module=settings&action=save",
                    data: form.serializeArray(),
                    dataType: "json",
                    type: "post",
                    success: function (response) {
                        btn.removeAttr('disabled').next(".temp-loader").remove();
                        if (typeof response.errors !== 'undefined') {
                            if (typeof response.errors.messages !== 'undefined') {
                                $.each(response.errors.messages, function (i, v) {
                                    errormsg.append(v + "<br />");
                                });
                            }
                        } else if (response.status == 'ok' && response.data) {
                            btn.removeClass("yellow").addClass("green").after("<i class='icon16 yes'></i>");
                            removeStatusIcon(btn);
                        } else {
                            btn.after("<i class='icon16 no'></i>");
                        }
                    },
                    error: function () {
                        errormsg.text($_('Something wrong'));
                        btn.removeAttr('disabled').next(".temp-loader").remove();
                        btn.after("<i class='icon16 no'></i>");
                        removeStatusIcon(btn);
                    }
                });
                return false;
            });

            // Switcher
            $('#itemsets-form .switcher').iButton({
                labelOn: "", labelOff: "", className: 'mini'
            }).change(function () {
                var onLabelSelector = '.' + this.id + '-on-label',
                        offLabelSelector = '.' + this.id + '-off-label';
                var settingsField = $(this).closest('.field-group').next('.field-group');
                if (!this.checked) {
                    if (settingsField.length) {
                        settingsField.hide();
                    }
                    $(onLabelSelector).addClass('unselected');
                    $(offLabelSelector).removeClass('unselected');
                } else {
                    if (settingsField.length) {
                        settingsField.show();
                    }
                    $(onLabelSelector).removeClass('unselected');
                    $(offLabelSelector).addClass('unselected');
                }
            });

            // Показать / скрыть пользовательские стили
            $("#itemsets-form .show-styles").click(function () {
                var that = $(this);
                var oldText = that.find('b').text();
                var newText = that.attr('data-toggle');
                if (that.next().is(":visible")) {
                    that.next().hide();
                } else {
                    that.next().show();
                    if (!that.hasClass("inited")) {
                        waEditorUpdateSource({
                            'id': 'itemsets-css',
                            'ace_editor_container': 'itemsets-css-container'
                        });
                        that.addClass("inited");
                    }
                }
                that.attr('data-toggle', oldText).find("b").text(newText);
            });

            // Восстановление стилей по умолчанию
            $("#itemsets-form .restore-styles.css").click(function () {
                $.itemsets.restoreFile($(this), {
                    message: $_("Do you really want to restore original css styles?"),
                    action: 'resetCss',
                    id: 'itemsets-css'
                });
            });

            // Восстановление файлов локализации JS или системного файла шаблона
            $("#itemsets-form .restore-styles.template").click(function () {
                var that = $(this);
                var action = that.data('action');
                var message = that.data('message');
                var id = that.data('id');
                var template = that.data('template');
                $.itemsets.restoreFile(that, {
                    message: message,
                    action: action,
                    id: id,
                    template: template
                });
            });

            // Подгрузка системного файла шаблона или файла локализации
            $("#itemsets-form .show-template").click(function () {
                var className = $(this).data('class-name');
                var action = $(this).data('action');
                var template = $(this).data('template');
                $.itemsets.showTemplate($(this), {
                    action: action,
                    className: 'template-' + className,
                    template: template
                });
            });

            // Текстовый редактор
            $('.editor-content').waEditor({
                lang: wa_lang,
                saveButton: '#itemsets-save',
                toolbarFixedBox: false,
                uploadFields: {
                    '_csrf': wa_csrf
                }
            });

            // Появление / исчезание блоков
            $(".f-toggle-html").click(function () {
                var that = $(this);
                if (that.next().is(":visible")) {
                    that.next().hide();
                } else {
                    that.next().show();
                }
            });
        },
        initAutocomplete: function (input) {
            var table = input.closest(".itemsets-block").find(".itemsets-table");
            input.autocomplete({
                source: '?plugin=itemsets&action=autocomplete&forbid_id=' + $.product.path.id,
                minLength: 2,
                delay: 400,
                autoFocus: true,
                search: function () {
                    var skus = input.siblings('.skus-search').find(".f-autocomplete-skus").prop("checked");
                    if (skus) {
                        input.autocomplete('option', 'source', '?plugin=itemsets&action=autocomplete&with_skus=1&forbid_id=' + $.product.path.id);
                    } else {
                        input.autocomplete('option', 'source', '?plugin=itemsets&action=autocomplete&forbid_id=' + $.product.path.id);
                    }
                },
                select: function (event, ui) {
                    var that = $(this);
                    var itemId = ui.item.id + '-' + ui.item.sku_id;
                    if (ui.item && !table.find('.autocomplete-id-' + itemId).length) {
                        var skuId = table.find(".product_sku_id").val();
                        var clone = table.find(".itemsets-template").clone().removeClass("itemsets-template").addClass("autocomplete-id-" + itemId + "").attr("data-id", itemId);
                        clone.find(".f-item-quantity").attr("name", "items[" + skuId + "][" + itemId + "][quantity]").prop('disabled', false);
                        clone.find(".f-item-discount").attr("name", "items[" + skuId + "][" + itemId + "][discount]").prop('disabled', false);
                        clone.find(".f-item-currency").attr("name", "items[" + skuId + "][" + itemId + "][currency]").prop('disabled', false);
                        clone.find(".itemsets-stocks").attr("name", "items[" + skuId + "][" + itemId + "][stock_id]").prop('disabled', false);
                        clone.find(".f-item-name").attr("href", "#/product/" + ui.item.id + "/").find(".itemsets-active-stock").before(ui.item.value);
                        clone.find(".itemsets-stock-0").append(ui.item.icon).parent().after(ui.item.sku_name);
                        clone.find(".f-item-price").attr("data-price", parseFloat(ui.item.price).toFixed(2)).append(ui.item.price_str);
                        clone.find(".f-item-price").attr("data-price-original", ui.item.price);
                        table.find(".itemsets-template").before(clone);
                        $.itemsets.updatePrice(table);
                        $.itemsets.updateStocks(table, ui.item.sku_id, itemId);
                        table.find(".empty-set").hide();
                    }
                    that.val('');
                    return false;
                }
            });
        },
        updateStocks: function (table, skuId, elemId) {
            $("#itemsets-skus").addClass("wait");
            $.post("?plugin=itemsets&action=updateStocks", {sku_id: skuId}, function (response) {
                var tr = table.find(".autocomplete-id-" + elemId);
                if (response.status == 'ok' && response.data) {
                    var html = "";
                    var stocks = "";
                    $.each(response.data, function (stock_id, stock) {
                        html += "<span class='itemsets-stock-" + stock_id + "'>" + stock.icon + "</span>";
                        stocks += "<option value='" + stock_id + "'>" + stock.name + "</option>";
                    });
                }
                tr.find(".itemsets-active-stock").append(html);
                tr.find(".itemsets-stocks").append(stocks).show().next().remove();
                $("#itemsets-skus").removeClass("wait");
            }, "json");
        },
        showTemplate: function (that, options) {
            var action = options.action;
            var className = options.className;
            var template = options.template || null;
            var oldText = that.find('b').text();
            var newText = that.attr('data-toggle');
            if (!that.hasClass("loaded")) {
                that.after("<i class='icon16 loading'></i>");
                $.get('?plugin=itemsets&module=settings&action=' + action + (template ? '&template=' + template : ''), function (html) {
                    that.next().remove();
                    that.addClass("loaded").next().html(html).show();
                    var restoreLink = $("#itemsets-form .restore-styles." + className);
                    if (restoreLink.hasClass("hidden")) {
                        restoreLink.show();
                    }
                });
            } else {
                var scriptBlock = that.next();
                if (scriptBlock.is(":visible")) {
                    scriptBlock.hide();
                } else {
                    scriptBlock.show();
                }
            }
            that.attr('data-toggle', oldText).find("b").text(newText);
        },
        restoreFile: function (that, options) {
            var message = options.message;
            var action = options.action;
            var textareaId = options.id;
            var template = options.template || '';
            if (!confirm(message)) {
                return false;
            }
            that.after("<i class='icon16 loading'></i>");
            $.post("?plugin=itemsets&action=handler", {action: action, template: template}, function (response) {
                var i = that.next();
                i.removeClass("loading");
                if (response.status == 'ok' && response.data) {
                    i.addClass("yes");
                    var editor = $.itemsets.editor[textareaId];
                    editor.setValue(response.data);
                    setTimeout(function () {
                        that.hide();
                    }, 3000);
                } else {
                    i.addClass("no");
                }
                setTimeout(function () {
                    i.remove();
                }, 3000);
            }, "json");
        },
        updatePrice: function (table) {
            var price = 0, discount = 0;
            table = table.find("tbody");
            table.find(".itemsets-item").each(function () {
                var that = $(this);
                var itemPrice = parseFloat(that.find(".f-item-price").attr('data-price'));
                var itemQuantity = parseInt(that.find(".f-item-quantity").val());
                var discountField = that.find(".f-item-discount");
                var itemDiscount = parseFloat(discountField.val());
                var itemCurrency = that.find(".f-item-currency").val();

                if (isNaN(itemPrice)) {
                    itemPrice = 0;
                }
                if (isNaN(itemDiscount) || itemDiscount < 0) {
                    itemDiscount = 0;
                    discountField.val('0');
                }
                if (isNaN(itemQuantity) || itemQuantity <= 0) {
                    itemQuantity = 1;
                    that.find(".f-item-quantity").val('1');
                }

                price += itemPrice * itemQuantity;
                if (itemCurrency == '%') {
                    if (itemDiscount > 100) {
                        itemDiscount = 100;
                        discountField.val('100');
                    }
                    if (itemDiscount < 0) {
                        itemDiscount = 0;
                        discountField.val('0');
                    }
                    discount += itemPrice * itemQuantity * itemDiscount / 100;
                } else {
                    if (typeof $.itemsets.currencies[itemCurrency] !== 'undefined') {
                        itemDiscount *= $.itemsets.currencies[itemCurrency]['rate'];
                    }
                    discount += itemDiscount;
                }
            });
            if (table.find(".itemsets-item").length > 1) {
                var totalPrice = (price - discount < 0) ? 0 : price - discount;

                var type = $(".round_price:checked").val();
                if (type == 'ceil') {
                    totalPrice = Math.ceil(totalPrice);
                    price = Math.ceil(parseFloat(price));
                } else if (type == 'floor') {
                    totalPrice = Math.floor(totalPrice);
                    price = Math.floor(parseFloat(price));
                }

                table.find(".is-empty-hide").show();
                $(".empty-hide").show();
                table.find(".itemsets-total-price").html($.itemsets.currencyFormat(price, 1));
                table.find(".itemsets-discount-price").html((totalPrice ? $.itemsets.currencyFormat(totalPrice, 1) : 0));
            } else {
                table.find(".empty-set").show();
                table.find(".is-empty-hide").hide();
            }
        },
        isValidInput: function (evt, regexp) {
            var theEvent = evt || window.event;
            var key = theEvent.keyCode || theEvent.which;
            key = String.fromCharCode(key);
            var regex = regexp;
            if (!regex.test(key) && evt.charCode !== 0) {
                theEvent.returnValue = false;
                if (theEvent.preventDefault) {
                    theEvent.preventDefault();
                }
            }
        },
        currencyFormat: function (number, no_html) {
            // Format a number with grouped thousands
            //     // +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
            // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
            // +	 bugfix by: Michael White (http://crestidg.com)

            var i, j, kw, kd, km;
            var decimals = $.itemsets.currency.frac_digits;
            var dec_point = $.itemsets.currency.decimal_point;
            var thousands_sep = $.itemsets.currency.thousands_sep;

            // input sanitation & defaults
            if (isNaN(decimals = Math.abs(decimals))) {
                decimals = 2;
            }
            if (dec_point == undefined) {
                dec_point = ",";
            }
            if (thousands_sep == undefined) {
                thousands_sep = ".";
            }

            i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

            if ((j = i.length) > 3) {
                j = j % 3;
            } else {
                j = 0;
            }

            km = (j ? i.substr(0, j) + thousands_sep : "");
            kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
            kd = (decimals && (number - i) ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");

            var number = km + kw + kd;
            var s = no_html ? $.itemsets.currency.sign : $.itemsets.currency.sign_html;
            if (!this.currency.sign_position) {
                return s + $.itemsets.currency.sign_delim + number;
            } else {
                return number + $.itemsets.currency.sign_delim + s;
            }
        },
        save: function () {
            var form = this.form;
            var btn = $("#s-product-save-button");
            btn.attr('disabled', 'disabled');

            var errormsg = form.find(".errormsg");
            errormsg.html('');
            $.ajax({
                url: form.attr('action'),
                data: form.serializeArray(),
                dataType: "json",
                type: "post",
                success: function (response) {
                    btn.removeAttr('disabled');
                    if (typeof response.errors !== 'undefined') {
                        if (typeof response.errors.messages !== 'undefined') {
                            $.each(response.errors.messages, function (i, v) {
                                errormsg.append(v + "<br />");
                            });
                        }
                    } else if (response.status == 'ok' && response.data) {
                        $("#itemsets-tab").attr('data-updated', 1);
                    } else {
                        btn.after("<i class='icon16 no'></i>");
                    }
                },
                error: function () {
                    errormsg.text($_('Something wrong'));
                    btn.removeAttr('disabled');
                }
            });
            return false;
        },
        controlStocks: function () {
            // Пересчитывать остатки, когда имеются комплектующие
            if (parseInt($("#itemsets-tab").attr('data-count')) > 0 || $("#itemsets-tab").attr('data-updated')) {
                var itemsetsFormLength = $(".s-product-form.itemsetsPlugin").length;
                var that = $.itemsets;
                var r = Math.random();
                var updatePage = function () {
                    $.get('?module=product&id=' + $.product.path.id + "&r=" + r, function (html) {
                        that.container.find(".itemsets-overlay").remove();
                        $.product.refresh('success');
                        $(".s-product-form.main").html($(html).find(".s-product-form.main").children());
                        if (itemsetsFormLength) {
                            var len = $(".itemsets-item").not(".itemsets-template").length;
                            if (len > 0) {
                                $("#itemsets-tab").attr('data-count', len).parent().removeClass("grey");
                            } else {
                                $("#itemsets-tab").attr('data-count', 0).parent().addClass("grey");
                            }
                        }
                    });
                };

                // Если отключен контроль остатков, то спросить пользователя о необходимости пересчитать их
                var controlStocks = $("#itemsets-form .control_stocks:checked").val();
                if (((itemsetsFormLength && (controlStocks == '0' || controlStocks == '-1')) || !itemsetsFormLength) && $("#itemsets-tab").data('control-stocks')) {
                    that.container.append("<div class='itemsets-overlay'><span><div class='itemsets-big-loader'></div>" + $_("Saving") + "</span></div>");
                    if (!confirm($_('Recount stocks and change their quantity automatically?'))) {
                        updatePage();
                        return false;
                    }
                    $.post("?plugin=itemsets&action=handler", {action: 'forceRecountStocks', id: $.product.path.id}, function (response) {
                        updatePage();
                    }, "json");
                } else {
                    that.container.append("<div class='itemsets-overlay'><span><div class='itemsets-big-loader'></div>" + $_("Saving") + "</span></div>");
                    updatePage();
                }
            }
        }
    };

    if (typeof $.product !== 'undefined') {
        // Сохранение комплекта
        $.product.editTabItemsetsPluginSave = function () {
            $.itemsets.save();
        };

        // Обновление страницы с Основными данными. Происходит в случае, когда необходимо контролировать остатки на складах
        $.product.editTabItemsetsPluginSaved = function () {
            $.itemsets.controlStocks();
        };
        $.product.editTabMainSaved = function () {
            $.itemsets.controlStocks();
        };
        $.product.editTabDescriptionsSaved = function () {
            $.itemsets.controlStocks();
        };
//        $.product.editTabFeaturesSaved = function () {
//            $.itemsets.controlStocks();
//        };
//        $.product.editTabImagesSaved = function () {
//            $.itemsets.controlStocks();
//        };
//        $.product.editTabServicesSaved = function () {
//            $.itemsets.controlStocks();
//        };
//        $.product.editTabRelatedSaved = function () {
//            $.itemsets.controlStocks();
//        };
//        $.product.editTabPagesSaved = function () {
//            $.itemsets.controlStocks();
//        };
//        $.product.editTabReviewsSaved = function () {
//            $.itemsets.controlStocks();
//        };
//        $.product.editTabStockslogSaved = function () {
//            $.itemsets.controlStocks();
//        };
    }
})(jQuery);