/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['app-config', 'config', 'websocket-manager'], function(AppConfig, Config, WebsocketManager) {

    'use strict';

    var WEBSOCKET_APP_NAME = 'admin',
        MESSAGE_HANDLER_NAME = 'sulu_content.collaboration';

    return {
        /**
         * @method initialize
         */
        initialize: function() {
            this.client = WebsocketManager.getClient(WEBSOCKET_APP_NAME, true);

            this.sendEnterMessage();
            this.onMessageHandler();
        },

        /**
         * @method onMessageHandler
         */
        onMessageHandler: function() {
            // this.client.onMessage(function(data) {
            //     console.log(data);
            // }.bind(this));
        },

        /**
         * @method sendEnterMessage
         */
        sendEnterMessage: function() {
            this.client.send(MESSAGE_HANDLER_NAME, {
                command: 'enter',
                id: this.options.id,
                webspace: this.options.webspace,
                userId: this.options.userId
            });
        }
    };
});
