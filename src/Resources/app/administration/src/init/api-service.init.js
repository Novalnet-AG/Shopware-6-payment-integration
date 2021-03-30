import NovalPaymentApiCredentialsService
    from '../../src/core/service/api/noval-payment-api-credentials.service';

const { Application } = Shopware;

Application.addServiceProvider('NovalPaymentApiCredentialsService', (container) => {
    const initContainer = Application.getContainer('init');

    return new NovalPaymentApiCredentialsService(initContainer.httpClient, container.loginService);
});

