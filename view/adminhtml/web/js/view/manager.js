/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */

define([
    'jquery',
    'ko',
    'uiElement',
    'underscore',
    'MageCloud_CloudflareManager/js/action/manager',
    'MageCloud_CloudflareManager/js/model/modal',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'mage/translate'
], function (
    $,
    ko,
    Element,
    _,
    managerAction,
    modalManager,
    modal,
    alert,
    confirm,
    $t
) {
    'use strict';

    var config = window.cloudflareManager;

    return Element.extend({
        modalWindow: null,
        isLoading: ko.observable(false),
        urlValidationErrors: ko.observableArray(),

        defaults: {
            template: 'MageCloud_CloudflareManager/manager',
            formKey: config.hasOwnProperty('formKey') ? config.formKey : FORM_KEY,
            url: config.url,
            urls: '',
            statefull: {
                urls: true // save value in cache?
            }
        },

        /**
         * Initializes
         *
         * @returns {exports}
         */
        initialize: function () {
            var self = this;

            this._super();
            managerAction.registerCallback(function (data) {
                self.isLoading(false);
            });

            return this;
        },

        /**
         * Initializes observable properties.
         *
         * @returns {exports}
         */
        initObservable: function () {
            this._super()
                .track([
                    'urls'
                ]);

            this._urls = ko.pureComputed({
                read: ko.getObservable(this, 'urls'),

                /**
                 * validates textarea field prior to updating 'value' property.
                 */
                write: function (value) {
                    this.urls = value;
                    this._urls.notifySubscribers(value);
                },

                owner: this
            });

            return this;
        },

        /**
         * Init modal window
         * @param element
         */
        setModalElement: function (element) {
            var self = this,
                config;

            if (modalManager.modalWindow == null) {
                config = {
                    buttons: [
                        {
                            text: $t('Check Service Status'),
                            class: 'action primary',
                            click: function (event) {
                                self.checkState(event);
                            }
                        },
                        {
                            text: $t('Purge By URLs'),
                            class: 'action primary',
                            click: function (event) {
                                self.purgeByUrl(event);
                            }
                        },
                        {
                            text: $t('Purge All'),
                            class: 'action primary',
                            click: function (event) {
                                self.purgeAll(event);
                            }
                        }
                    ]
                };
                modalManager.createPopUp(config, element);
            }
        },

        /**
         * Show modal window
         */
        showModal: function () {
            if (this.modalWindow) {
                $(this.modalWindow).modal('openModal');
            } else {
                alert({
                    content: $t('Cloudflare Manager is unavailable.')
                });
            }
        },

        /**
         * @returns {*}
         * @private
         */
        _validateUrls: function(urls) {
            if (_.isEmpty(urls)) {
                return false;
            }

            // clear observable to prevent duplication entry
            this.urlValidationErrors.removeAll();
            var self = this,
                isValid = false;
            _.each(urls, function(url, index) {
                url = url.replace(/^\s+/, '').replace(/\s+$/, '');
                isValid = (/^(http|https):\/\/(([A-Z0-9]([A-Z0-9_-]*[A-Z0-9]|))(\.[A-Z0-9]([A-Z0-9_-]*[A-Z0-9]|))*)(:(\d+))?(\/[A-Z0-9~](([A-Z0-9_~-]|\.)*[A-Z0-9~]|))*\/?(.*)?$/i).test(url);
                if (!isValid) {
                    self.urlValidationErrors.push(url);
                }
            });
        },

        /**
         * @param event
         */
        checkState: function (event) {
            var url = this.url.state,
                target = $(event.currentTarget);

            this.isLoading(true);
            managerAction(url, 'POST', {
                'form_key': this.formKey
            });

            return false;
        },

        /**
         * @param event
         * @returns {boolean}
         */
        purgeByUrl: function (event) {
            var self = this,
                urls = '',
                validationResult = [],
                url = this.url.purgeByUrl;

            event.stopPropagation();

            if (_.isEmpty(this.urls)) {
                alert({
                    content: $.mage.__('Please provide at least one URL to perform this operation.')
                });
                return false;
            }

            this._validateUrls(this.urls.split('\n'));
            if (!_.isEmpty(this.urlValidationErrors())) {
                _.each(this.urlValidationErrors(), function (url) {
                    urls += '<strong>' + url + '</strong>' + '<br/>'
                });
                alert({
                    content: $t('Please provide a valid URL for records below. ' +
                        'Protocol is required (http:// or https://):' + "<br/>" + urls)
                });
                return false;
            }

            _.each(this.urls.split('\n'), function (url) {
                urls += url + '<br/>'
            });

            confirm({
                content: $.mage.__('Are you sure do you want to purge cache for URLs: ' + "<br/>" +
                    '<strong>' + urls + '</strong>'),
                actions: {
                    /** @inheritdoc */
                    confirm: function () {
                        self.isLoading(true);
                        managerAction(url, 'POST', {
                            'urls': self.urls,
                            'form_key': self.formKey
                        });
                    },

                    /** @inheritdoc */
                    always: function (e) {
                        e.stopImmediatePropagation();
                    }
                }
            });

            return false;
        },
        /**
         * @param event
         */
        purgeAll: function (event) {
            var self = this,
                url = this.url.purgeAll,
                configurationUrl = this.url.configuration,
                target = $(event.currentTarget),
                note = '<strong>NOTE: </strong>' + 'You can <a href="' + configurationUrl +'">configure</a> manager ' +
                    'to purge assets automatically after Flush Cache Storage in Magento.';

            confirm({
                title: $.mage.__('Confirm purge everything'),
                content: $.mage.__('Purge all cached files. Purging your cache may slow your website ' +
                    'temporarily.' + '<br/><br/>' + note),
                actions: {
                    /** @inheritdoc */
                    confirm: function () {
                        self.isLoading(true);
                        managerAction(url, 'POST', {
                            'form_key': self.formKey
                        });
                    },

                    /** @inheritdoc */
                    always: function (e) {
                        e.stopImmediatePropagation();
                    }
                }
            });

            return false;
        },

        /**
         * @TODO - not implemented
         * @returns {boolean}
         */
        getRecentlyPurgedUrlList: function () {
            if (!this.urls) {
                return false;
            }

            var urls = this.urls.split('\n'),
                result = [];

            _.each(urls, function(url, index) {
                result[index] = {
                    url: url
                };
            });

            return [];
        },

        /**
         * @TODO - not implemented
         * @param url
         */
        addToList: function (url) {
            var urls = this.urls.split('\n'),
                result = [];

            return false;
        },

        /**
         * @TODO - not implemented
         * @param url
         */
        removeFromList: function (url) {
            var urls = this.urls.split('\n'),
                result = [];

            return false;
        }
    });
});
