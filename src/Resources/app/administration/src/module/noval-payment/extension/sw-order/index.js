const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

import template from './sw-order.html.twig';
import './sw-order.scss';

Component.override('sw-order-detail-base', {
    template,

    data() {
        return {
            disableButtons: false,
            novalnetComments: '',
            isVerified : false,
            isNovalnetPayment : false
        };
    },

    watch: {
        orderId: {
            deep: true,
            handler() {
                if (!this.orderId) {
                    this.setNovalnetPayment(null);
                    return;
                } else if( this.isVerified ) {
                    return;
                }
                const orderRepository = this.getOrderRepository();
                orderRepository.get(this.orderId, Context.api, this.transactionCriteria()).then((order) => {
                    this.isVerified =   true;                    
                    if( order.hasOwnProperty('transactions') && order.transactions ) {
                        let isNovalnet  =   false;
                        let comments    =    '';
                        let translation	=	this.$tc('noval-payment.module.comments');
                        order.transactions.map(function(transaction) {
                            if ( 
                                   transaction.hasOwnProperty('customFields') 
                                && transaction.customFields.hasOwnProperty('novalnet_comments') 
                                && transaction.customFields.novalnet_comments                                
                                ) {
                                    isNovalnet = true;
                                    if(comments != '')
                                    {
										comments  += "<dt>" + translation + "</dt>";
									}
                                    comments   += transaction.customFields.novalnet_comments.split("/ ").join("<br />");
                                    

                                }
                        });
                        if( isNovalnet ) {
                            this.novalnetComments  = comments;
                            this.setNovalnetPayment( true );
                        }                        
                    } else {
                        this.setNovalnetPayment( null );
                    }
                })
            },
            immediate: true
        }
    },

    methods: {     

        setNovalnetPayment( novalnetPayment ) {
            if( novalnetPayment ) {
                this.isNovalnetPayment = novalnetPayment;
            }
        },

        getOrderRepository() {
            return this.repositoryFactory.create('order');
        },

        transactionCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('transactions');
            return criteria;
        }        
    }
});
