define(function(require) {
    'use strict';

    var SidebarRecentEmailsComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var EmailNotificationCollection =
        require('oroemail/js/app/models/email-notification/email-notification-collection');

    SidebarRecentEmailsComponent = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.model = options.model;
            if ('unreadEmailsCount' in this.model.module) {
                this.model.set({unreadEmailsCount: this.model.module.unreadEmailsCount});
            }
            this.model.on('change:settings', function(model, settings) {
                model.emailNotificationCollection.setLimit(settings.limit);
            });
            this.model.emailNotificationCollection = new EmailNotificationCollection([]);
            this.model.emailNotificationCollection.fetch();
            this.model.emailNotificationCollection.on('sync', this.onCollectionSync, this);
        },

        onCollectionSync: function() {
            this.model.set({
                unreadEmailsCount: this.model.emailNotificationCollection.unreadEmailsCount || ''
            });
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.model.emailNotificationCollection.off('sync', this.onCollectionSync, this);
            delete this.model;
            this.model.emailNotificationCollection.dispose();
            SidebarRecentEmailsComponent.__super__.dispose.call(this);
        }
    });

    return SidebarRecentEmailsComponent;
});
