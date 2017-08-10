/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module
 * to newer versions in the future.
 *
 * @copyright 2017 La Poste
 * @license   Open Software License ("OSL") v. 3.0
 */
define(
    [
        'jquery',
        'Magento_Checkout/js/action/set-shipping-information',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/select-shipping-method',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/translate',
        'mage/url'
    ],
    function (
        $,
        setShippingInformationAction,
        stepNavigator,
        quote,
        selectShippingMethod,
        fullScreenLoader,
        $t,
        url
    ) {
        'use strict';

        return function (target) {
            return target.extend({
                /**
                 * Override the setShippingInformation method to redirect the customer to the external gateway
                 * instead of the payment page when the colissimosimplicite method is selected.
                 */
                setShippingInformation: function () {
                    var shippingMethod = quote.shippingMethod();
                    var shippingAddress = quote.shippingAddress();

                    if (shippingMethod.carrier_code !== 'colissimosimplicite') {
                        return this._super();
                    }

                    if (this.validateShippingInformation()) {
                        setShippingInformationAction()
                            .done($.proxy(function () {
                                // Start the loading animation
                                fullScreenLoader.startLoader();

                                // Check the gateway availability before deciding what to do next
                                $.get(this.getGatewayStatusUrl())
                                    .done($.proxy(this.redirectToGateway, this))
                                    .fail(function () {
                                        fullScreenLoader.stopLoader();
                                    });
                            }, this));
                    }
                },

                /**
                 * Redirect to the appropriate gateway (web or mobile),
                 * or go to the next step if the gateway is not available.
                 */
                redirectToGateway: function (responseData) {
                    // Update method title
                    if (responseData.methodTitle) {
                        var shippingMethod = window.checkoutConfig.selectedShippingMethod;
                        if (shippingMethod) {
                            shippingMethod.method_title = responseData.methodTitle;
                            selectShippingMethod(shippingMethod);
                        }
                    }

                    if (responseData.isOffline) {
                        // Colissimo gateway is offline, go to payment step
                        fullScreenLoader.stopLoader();
                        stepNavigator.next();
                    } else if (this.isMobile()) {
                        // Redirect the the mobile gateway (mobile / tablet experience)
                        this.redirectToMobileGateway();
                    } else {
                        // Display the IFrame containing the Colissimo form (desktop experience)
                        this.redirectToWebGateway();
                    }
                },

                /**
                 * Redirect to the Colissimo mobile gateway.
                 */
                redirectToMobileGateway: function () {
                    var onXhrSuccess = function (formHtml) {
                        this.addElementToDom(formHtml, $('#shipping-method-buttons-container'));
                    };

                    // Disable shipping method inputs
                    $('#co-shipping-method-form .radio').prop('disabled', true);

                    // Hide continue button
                    $('#shipping-method-buttons-container .action.continue').hide();

                    // Redirect to the mobile gateway
                    $.get(url.build('colissimosimplicite/form/mobile'))
                        .success($.proxy(onXhrSuccess, this))
                        .always(fullScreenLoader.stopLoader());
                },

                /**
                 * Display the IFrame containing the Colissimo form.
                 */
                redirectToWebGateway: function () {
                    // Disable shipping method inputs
                    $('#co-shipping-method-form .radio').prop('disabled', true);

                    // Hide continue button
                    $('#shipping-method-buttons-container .action.continue').hide();

                    // Create the IFrame containing the contents of the web gateway
                    var iframeContainer = this.createIFrame();

                    // Add the IFrame to the DOM
                    this.addElementToDom(iframeContainer, $('#shipping-method-buttons-container'));

                    fullScreenLoader.stopLoader();
                },

                /**
                 * Create the IFrame containing the Colissimo form.
                 */
                createIFrame: function () {
                    // Create the IFrame container
                    var iframeContainer = $('<div>', {
                        id: 'colissimosimplicite-iframe-container',
                        css: {width: '572px'}
                    });

                    // Create the IFrame
                    var formUrl = url.build('colissimosimplicite/form/web');
                    var iframe = $('<iframe>', {
                        frameBorder: 0,
                        src: formUrl,
                        css: {width: '572px', height: '1100px', marginBottom: '10px'}
                    }).appendTo(iframeContainer);

                    // Create the cancel button
                    var button = $('<button>', {
                        id: 'colissimosimplicite-cancel',
                        'class': 'button',
                        type: 'button',
                        css: {marginBottom: '10px'}
                    });

                    button.click($.proxy(this.cancelIFrame, this))
                        .append('<span>' + $t('Cancel') + '</span>')
                        .appendTo(iframeContainer);

                    return iframeContainer;
                },

                /**
                 * Event handler for the IFrame cancel button.
                 */
                cancelIFrame: function () {
                    // Remove the IFrame
                    var iframeContainer = $('#colissimosimplicite-iframe-container');
                    if (iframeContainer.length) {
                        iframeContainer.remove();
                    }

                    // Enable shipping method inputs
                    $('#co-shipping-method-form .radio').prop('disabled', false);

                    // Show continue button
                    $('#shipping-method-buttons-container .action.continue').show();
                },

                /**
                 * Add an element to the DOM.
                 */
                addElementToDom: function (element, container) {
                    var elementId = $(element).attr('id');
                    var oldElement = elementId ? $('#' + elementId) : undefined;

                    if (oldElement && oldElement.length == 1) {
                        oldElement.replaceWith(element);
                    } else {
                        $(container).prepend(element);
                    }
                },

                /**
                 * Check whether the user device is a mobile.
                 */
                isMobile: function () {
                    var maxWidth = window.checkoutConfig.colissimoWidthBreakpoint || '768px';
                    return window.matchMedia('only screen and (max-width: ' + maxWidth + ')').matches;
                },

                /**
                 * Get the URL of the gateway availability check.
                 */
                getGatewayStatusUrl: function () {
                    var gatewayStatusUrl = this.isMobile()
                        ? 'colissimosimplicite/status/mobile'
                        : 'colissimosimplicite/status/web';

                    return url.build(gatewayStatusUrl);
                }
            });
        };
    }
);
