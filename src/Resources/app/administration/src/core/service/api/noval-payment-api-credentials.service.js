const ApiService = Shopware.Classes.ApiService;

class NovalPaymentApiCredentialsService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'noval-payment') {
        super(httpClient, loginService, apiEndpoint);
    }

    validateApiCredentials(clientId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .get(
                `_action/${this.getApiBasePath()}/validate-api-credentials`,
                {
                    params: { clientId },
                    headers: headers
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

export default NovalPaymentApiCredentialsService;
