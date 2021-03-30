import './extension/sw-settings-index';
import './extension/sw-order';
import './page/noval-payment-settings';
import './components/noval-payment-credentials';
import './components/noval-payment-configuration';

import deDE from './snippet/de_DE.json';
import enGB from './snippet/en_GB.json';

const { Module } = Shopware;

Module.register('noval-payment', {
    type: 'plugin',
    name: 'NovalnetPayment',
    title: 'noval-payment.module.title',
    description: 'noval-payment.module.description',
    version: '1.0.5',
    targetVersion: '1.0.5',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        settings: {
            component: 'noval-payment-settings',
            path: 'settings',
            meta: {
                parentPath: 'sw.settings.index'
            }
        },
    }
});
