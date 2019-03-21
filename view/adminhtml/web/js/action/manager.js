/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */

define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function (
    $,
    alert,
    $t
) {
    'use strict';

    var callbacks = [],

        /**
         * Perform asynchronous request to server.
         *
         * @param url
         * @param data
         * @param type
         * @param redirectUrl
         * @param isGlobal
         * @param contentType
         * @param messageContainer
         * @returns {*}
         */
        action = function (url, type, data, redirectUrl, isGlobal, contentType, messageContainer) {
            url = url || (data.hasOwnProperty('url') ? data.url : '');
            type = type || 'POST';
            data = data || {};
            isGlobal = isGlobal === undefined ? true : isGlobal;
            contentType = contentType || 'json';
            // @TODO - use message container to display message response instead of popup confirmation
            messageContainer = messageContainer || {};

            return $.ajax({
                url: url,
                type: type,
                data: data,
                global: isGlobal,
                dataType: contentType,
                showLoader: true
            }).done(function (response) {
                if (response.errors) {
                    alert({
                        content: $t(response.message)
                    });
                    callbacks.forEach(function (callback) {
                        callback(data);
                    });
                } else {
                    callbacks.forEach(function (callback) {
                        callback(data);
                    });
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    } else if (response.redirectUrl) {
                        window.location.href = response.redirectUrl;
                    } else if (response.needShowResponse) {
                        alert({
                            title: $t('Response'),
                            content: $t(response.message)
                        });
                    } else {
                        location.reload();
                    }
                }
            }).fail(function () {
                alert({
                    content: $t('Request failed. Please try again later.')
                });
                callbacks.forEach(function (callback) {
                    callback(data);
                });
            });
        };

    /**
     * @param {Function} callback
     */
    action.registerCallback = function (callback) {
        callbacks.push(callback);
    };

    return action;
});
