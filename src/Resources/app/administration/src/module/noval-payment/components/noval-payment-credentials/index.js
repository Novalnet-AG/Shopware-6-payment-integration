import template from './novalnet-payment-credentials.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('noval-payment-credentials', {
    template,
	
	mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
    ],
    
	name: 'NovalnetPaymentCredentials',
	
	props: {
        actualConfigData: {
            type: Object,
            required: true
        },
        allConfigs: {
            type: Object,
            required: true
        },
        selectedSalesChannelId: {
            required: true
        },
        clientIdFilled: {
            required: true
        },
        domain: {
            type: String,
            required: false,
            default: ''
        }
    },
    
    data() {
		const url = window.location .protocol + "//" + window.location.host + window.location.pathname;
		const generatedUrl = url.split("/admin").join("");
        return {
			currentUrl: generatedUrl + "/novalnet/callback",
			tariffOptions: [],
			allConfigs: {},
			shouldDisable: true
		}
	},
	
	inject: [
        'repositoryFactory',
        'NovalPaymentApiCredentialsService'
    ],
	
	watch: {
        actualConfigData: {
            handler(configData) {
                if (!configData) {
                    return;
                }

                this.$emit('input', configData);
            },
            deep: true
        }
    },

    computed: {
        actualConfigData: {
            get() {
                return this.allConfigs[this.selectedSalesChannelId];
            },
            set(config) {
                this.allConfigs = {
                    ...this.allConfigs,
                    [this.selectedSalesChannelId]: config
                };
            }
        }
    },
	
	created() {
        this.createdComponent();
    },
			
    methods: {
        checkTextFieldInheritance(value) {
            if (typeof value !== 'string') {
                return true;
            }

            return value.length <= 0;
        },

        checkBoolFieldInheritance(value) {
            return typeof value !== 'boolean';
        },
        
        checkNumberFieldInheritance(value) {
            return typeof value !== 'int';
        },
        
        createdComponent() {
			const me = this;
			if (this.domain && !this.actualConfigData) {
                this.readAll().then((values) => {
                    this.actualConfigData = values;
                });
            }


            const clientId = this.allConfigs[this.selectedSalesChannelId]['NovalnetPayment.settings.clientId'] || this.allConfigs['null']['NovalnetPayment.settings.clientId']  ;
		
			if(clientId != undefined && clientId != '')
			{
				this.NovalPaymentApiCredentialsService.validateApiCredentials(clientId).then((response) => {
					const status = response.serverResponse.status;
					if(status != 100)
					{	
						return;
					}else
					{	
						response.tariffResponse.forEach(((tariff) => {
							if(this.actualConfigData['NovalnetPayment.settings.tariff'] == undefined || this.actualConfigData['NovalnetPayment.settings.tariff'] == '')
							{
								this.actualConfigData['NovalnetPayment.settings.tariff'] = tariff.id;
							}
							me.tariffOptions.push({
								value: tariff.id,
								label: tariff.name
							});
						}));
						
						return;
					}
				}).catch((errorResponse) => {
					
				});
			}
		}
    }

});	
