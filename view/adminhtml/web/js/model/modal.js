/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */

define([
    'jquery',
    'Magento_Ui/js/modal/modal'
], function ($, modal) {
    'use strict';

    return {
        modalWindow: null,

        /**
         * Create popUp window for provided element
         *
         * @param config
         * @param {HTMLElement} element
         */
        createPopUp: function (config, element) {
            $.extend(config, {
                'title': 'Cloudflare Manager',
                'type': 'slide',
                'modalClass': 'cloudflare-manager-container',
                'responsive': true,
                'innerScroll': true,
                'trigger': '#cloudflare_manager'
            });

            this.modalWindow = element;
            modal(config, $(this.modalWindow));
        },

        /**
         * Show modal window
         */
        showModal: function () {
            $(this.modalWindow).modal('openModal');
        },

        /**
         * Show modal window
         */
        closeModal: function () {
            $(this.modalWindow).modal('closeModal');
        }
    };
});
