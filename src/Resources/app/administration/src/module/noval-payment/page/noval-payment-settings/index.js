import template from './noval-payment-settings.html.twig';
import './noval-payment-settings.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('noval-payment-settings', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
    ],

    inject: [
        'repositoryFactory',
        'NovalPaymentApiCredentialsService'
    ],
    
    data() {
        return {
            isLoading: false,
            isValidating: false,
            isSaveSuccessful: false,
            isValidateSuccessful: false,
            clientIdFilled: false,
            clientSecretFilled: false,
            config: {}
        };
    },
    
    computed: {
        
        validateButtonDisabled() {
            return this.isLoading || this.isValidating;
        }
    },
    
    watch: {
        config: {
            handler() {
                const defaultConfig = this.$refs.configComponent.allConfigs.null;
                const salesChannelId = this.$refs.configComponent.selectedSalesChannelId;

                if (salesChannelId === null) {
                    this.clientIdFilled = !!this.config['NovalnetPayment.settings.clientId'];
                } else {
                    this.clientIdFilled = !!this.config['NovalnetPayment.settings.clientId']
                        || !!defaultConfig['NovalnetPayment.settings.clientId'];
                    this.clientSecretFilled = !!this.config['NovalnetPayment.settings.clientSecret'];
                }
            },
            deep: true
        }
    },
    
    methods: {
		
		onSave() {
			
			if(this.config['NovalnetPayment.settings.sepa.dueDate'] != '' && (this.config['NovalnetPayment.settings.sepa.dueDate'] < 2 || this.config['NovalnetPayment.settings.sepa.dueDate'] > 14))
            {
				this.createNotificationError({
					title: this.$tc('noval-payment.settingForm.titleError'),
					message: this.$tc('noval-payment.settingForm.paymentSettings.sepa.dueDate.error')
				});
				
				return;
			}
			
			this.isSaveSuccessful = false;
            this.isLoading = true;
			
			this.checkBackendConfiguration();
            
            this.$refs.configComponent.save().then((res) => {
                this.isLoading = false;
                this.isSaveSuccessful = true;

                if (res) {
                    this.config = res;
                }
            
            }).catch(() => {
                this.isLoading = false;
            });
		},
		
		getConfigValue(field) {
            const defaultConfig = this.$refs.configComponent.allConfigs.null;
            const salesChannelId = this.$refs.configComponent.selectedSalesChannelId;

            if (salesChannelId === null) {
                return this.config[`NovalnetPayment.settings.${field}`];
            }

            return this.config[`NovalnetPayment.settings.${field}`]
                || defaultConfig[`NovalnetPayment.settings.${field}`];
        },
		
		checkBackendConfiguration() {
			const me = this;
			const clientId = this.getConfigValue('clientId');
			if(clientId == undefined || clientId == '')
			{
				this.createNotificationError({
							title: this.$tc('noval-payment.settingForm.titleError'),
							message: this.$tc('noval-payment.settingForm.emptyMessage')
					});
					
				return;
			}
			this.NovalPaymentApiCredentialsService.validateApiCredentials(clientId).then((response) => {
                
                if(response.serverResponse == undefined || response.serverResponse == '')
                {
					this.createNotificationError({
							title: this.$tc('noval-payment.settingForm.titleError'),
							message: this.$tc('noval-payment.settingForm.emptyMessage')
					});
					
					return;
				}
				
                const status = response.serverResponse.status;
                if(status != 100)
                {
					this.createNotificationError({
							title: this.$tc('noval-payment.settingForm.titleError'),
							message: response.serverResponse.config_result
					});
					
					return;
				}else
				{	
					response.tariffResponse.forEach(((tariff) => {
						if(this.config['NovalnetPayment.settings.tariff'] == undefined || this.config['NovalnetPayment.settings.tariff'] == '')
						{
							this.config['NovalnetPayment.settings.tariff'] = tariff.id;
						}
					}));
					
					this.config['NovalnetPayment.settings.vendorId']	= response.serverResponse.vendor;
					this.config['NovalnetPayment.settings.authCode']	= response.serverResponse.auth_code;
					this.config['NovalnetPayment.settings.productId']	= response.serverResponse.product;
					this.config['NovalnetPayment.settings.accessKey']	= response.serverResponse.access_key;
 					this.$refs.configComponent.save().then((res) => {
							this.isLoading = false;
							this.isSaveSuccessful = true;

							if (res) {
								this.config = res;
							}
            
					}).catch(() => {
							this.isLoading = false;
					});
							
					this.createNotificationSuccess({
						title: this.$tc('noval-payment.settingForm.titleSuccess'),
						message: this.$tc('noval-payment.settingForm.successMessage')
					});
					
					setTimeout(this.$router.go, 3000);
					
					return;
				}
            }).catch((errorResponse) => {
                    this.createNotificationError({
                        title: this.$tc('noval-payment.settingForm.titleError'),
                        message: this.$tc('noval-payment.settingForm.errorMessage')
                    });
                    this.isTesting = false;
                    this.isTestSuccessful = false;
            });
		},
		
		onValidate() {
			
			this.checkBackendConfiguration();
			this.isValidating = false;
            this.isValidateSuccessful = true;
		},
		
	},

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },
});
