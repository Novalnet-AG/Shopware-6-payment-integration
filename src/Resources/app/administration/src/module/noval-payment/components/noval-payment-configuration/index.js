import template from './novalnet-payment-configuration.html.twig';

const { Component } = Shopware;

Component.register('noval-payment-configuration', {
    template,
	
	name: 'NovalnetPaymentConfiguration',
	
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
        }
    },
    
    data() {
        return {
           selected: 'capture'
        };
    },
    
    computed: {
		onholdOptions() {
            return [
                {
                    id: 'capture',
                    name: this.$tc('noval-payment.settingForm.paymentSettings.onHold.capture')
                },
                {
                    id: 'authroize',
                    name: this.$tc('noval-payment.settingForm.paymentSettings.onHold.authroize')
                }
            ];
        }
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
        }
    }

});	
