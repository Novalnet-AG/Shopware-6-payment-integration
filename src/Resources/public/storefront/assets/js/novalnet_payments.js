/** 
* 
* Novalnet payment plugin
*
* NOTICE OF LICENSE
* 
* This source file is subject to Novalnet End User License Agreement
* DISCLAIMER
* 
* @author Novalnet AG
* @copyright Copyright (c) Novalnet
* @license https://www.novalnet.de/payment-plugins/kostenlos/lizenz
* @link https://www.novalnet.de
*
* This free contribution made by request.
*
* If you have found this script useful a small
* recommendation as well as a comment on merchant
*
*/
 
var novalnetTargetOrgin = 'https://secure.novalnet.de';
function novalnetIframe()
{   
    //Default iframe style
        var novalnetDefaultLabel = $('#CreditcardDefaultLabel').val();
        var novalnetDefaultInput = $('#CreditcardDefaultInput').val();
        
        if($('#CreditcardDefaultCss').val() == '')
            var novalnetDefaultCss = 'body{color: #8798a9;font-family:Helvetica,Arial,sans-serif;font-weight: 500;}input{border-radius: 3px;background-clip: padding-box;box-sizing: border-box;line-height: 1.1875rem;padding: .625rem .625rem .5625rem .625rem;box-shadow: inset 0 1px 1px #dadae5;background: #f8f8fa;border: 1px solid #dadae5;border-top-color: #cbcbdb;color: #8798a9;text-align: left;font: inherit;letter-spacing: normal;margin: 0;word-spacing: normal;text-transform: none;text-indent: 0px;text-shadow: none;display: inline-block;height:40px;font-family:Helvetica,Arial,sans-serif;font-weight: 500;}input:focus{background-color: white;font-family:Helvetica,Arial,sans-serif;font-weight: 500;}';
        else
            var novalnetDefaultCss   = $('#CreditcardDefaultCss').val();
        
        var textObj   = {
            card_holder: {
                labelText: '',
                inputText: '',
            },
            card_number: {
                labelText: '',
                inputText: '',
            },
            expiry_date: {
                labelText: '',
                inputText: '',
            },
            cvc: {
                labelText: '',
                inputText: '',
            },
            cvcHintText: '',
            errorText: '',
        };
        
    var request = {
            callBack    : 'createElements',
            customText: textObj,
            customStyle : {
                labelStyle : novalnetDefaultLabel,
                inputStyle : novalnetDefaultInput,
                styleText  : novalnetDefaultCss,
                card_holder : {
                    labelStyle : '',
                    inputStyle : '',
                },
                card_number : {
                    labelStyle : '',
                    inputStyle : '',
                },
                expiry_date : {
                    labelStyle : '',
                    inputStyle : '',
                },
                cvc : {
                    labelStyle : '',
                    inputStyle : '',
                },                
            },
        };
        var iframe = $('#nnIframe')[0];
        iframeContent = iframe.contentWindow ? iframe.contentWindow : iframe.contentDocument.defaultView;
        iframeContent.postMessage(request, novalnetTargetOrgin);
        iframeContent.postMessage({callBack : 'getHeight'}, novalnetTargetOrgin);
}

        if ( window.addEventListener) {
                window.addEventListener('message', function (e) {
                      addEvent(e);
                }, false);
        } else {
            window.attachEvent('onmessage', function (e) {
                addEvent(e);
            });
        }
function addEvent(e)
{
        if (e.origin === 'https://secure.novalnet.de') {
          var data = Function('"use strict";return (' + e.data + ')')();
            if (data['callBack'] == 'getHash') {
                e.preventDefault();
                if (data['error_message'] != undefined) {                    
                    show_error_message(data['error_message']); 
                    
                } else {
                    $('#novalnet_cc_hash').val(data['hash']);
                    $('#novalnet_cc_uniqueid').val(data['unique_id']);
                    $('#confirmOrderForm').submit();
                    $('#confirmFormSubmit').prop('disabled', true);

                }
            } else if (data['callBack'] == 'getHeight') {
                $('#nnIframe').attr('height',data['contentHeight']);
                e.preventDefault();
            }
        }
}
    
$(document).ready(function() {
	NovalnetUtility.setBirthDateFormat('DD/MM/YYYY');
    $('#error_message').hide();
    $( "#nn_sepa_birth_date" ).datepicker({ dateFormat: 'dd/mm/yy', changeMonth: true, changeYear: true, yearRange: '1920:' + new Date().getFullYear().toString() });
    $( "#nn_invoice_birth_date" ).datepicker({ dateFormat: 'dd/mm/yy', changeMonth: true, changeYear: true, yearRange: '1920:' + new Date().getFullYear().toString() });
    $('#nn_iban').keyup(function (event) {
                           this.value = this.value.toUpperCase();
                           var field = this.value;
                           var value = "";
                           for(var i = 0; i < field.length;i++){
                                   if(i <= 1){
                                           if(field.charAt(i).match(/^[A-Za-z]/)){
                                                   value += field.charAt(i);
                                           }
                                   }
                                   if(i > 1){
                                           if(field.charAt(i).match(/^[0-9]/)){
                                                   value += field.charAt(i);
                                           }
                                   }
                           }
                           field = this.value = value;
    });
    $('#sepa_mandate').click(function () {
        $('#sepa_mandate_details_desc').toggle();
    });
          

$("#confirmOrderForm").submit(function (evt) {
    var selected_payment_id = $("#novalnetPaymentMethod").val();
    var nn_cc_paymentid = $("#novalnetccid").val();
    if(nn_cc_paymentid != undefined && nn_cc_paymentid == selected_payment_id && $('#novalnet_cc_hash').val() == '')
    {
        evt.preventDefault();
        evt.stopImmediatePropagation();
        gethash();
    }
    
    var nn_sepa_paymentid = $("#novalnetsepaid").val();
    var sepa_birth_date = $("#nn_sepa_birth_date").val();
    if(nn_sepa_paymentid != undefined && nn_sepa_paymentid == selected_payment_id)
    {
        var age = validate_age(sepa_birth_date);
        if($('#nn_iban').val() == '' || $('#nn_bank_account_holder').val() == '')
        {           
            show_error_message($('#invalid_account').val());
            evt.preventDefault();
        }
        if ($('#guarantee_sepa').val() == '1') {
            
            if($('#guarantee_sepa_condition').val() == '')
            {
                $('#sepa_guarantee_success').val(0);
            }
            
            if ($('#guarantee_force_sepa').val() != '1' && $('#guarantee_sepa_condition').val() == '') {
                                
                show_error_message($('#guarantee_error_sepa').val());               
                evt.preventDefault();                               
            } else if (isNaN(age) && $('#guarantee_force_sepa').val() != '1' && sepa_birth_date != undefined && $('#company').val() == '') {                            
                show_error_message($('#age_empty').val());              
                evt.preventDefault();               
            } else if ((NovalnetUtility.validateDateFormat(sepa_birth_date) == false) && jQuery('#guarantee_force_sepa').val() != '1' && sepa_birth_date != undefined && $('#company').val() == '') { 
				show_error_message($('#invalid_age_error').val());              
                evt.preventDefault(); 
			} else if ((age < 18) && jQuery('#guarantee_force_sepa').val() != '1' && sepa_birth_date != undefined && $('#company').val() == '') {
                show_error_message($('#age_error').val());              
                evt.preventDefault();               
            } else {
                $('#confirmOrderForm').submit(); 
                $('#confirmFormSubmit').prop('disabled', true);
            }
        }
    }
    
    var nn_invoice_paymentid = $("#novalnetinvoiceid").val();
    var invoice_birth_date = $("#nn_invoice_birth_date").val();
    if(nn_invoice_paymentid != undefined && nn_invoice_paymentid == selected_payment_id)
    {
        var age = validate_age(invoice_birth_date);
        if ($('#guarantee_invoice').val() == '1') {
            
            if($('#guarantee_invoice_condition').val() == '')
            {
                $('#invoice_guarantee_success').val(0);
            }
            
            if ($('#guarantee_force_invoice').val() != '1' && $('#guarantee_invoice_condition').val() == '') {
        
                show_error_message($('#guarantee_error_invoice').val());
                evt.preventDefault();               
            } else if (isNaN(age) && $('#guarantee_force_invoice').val() != '1' && invoice_birth_date != undefined && $('#company').val() == '') {
                show_error_message($('#age_empty').val());
                evt.preventDefault();
            } else if ((NovalnetUtility.validateDateFormat(invoice_birth_date) == false) && jQuery('#guarantee_force_invoice').val() != '1' && invoice_birth_date != undefined && $('#company').val() == '') { 
				
				show_error_message($('#invalid_age_error').val());              
                evt.preventDefault(); 
			} else if ((age < 18) && $('#guarantee_force_invoice').val() != '1' && invoice_birth_date != undefined && $('#company').val() == '') {
                
                show_error_message($('#age_error').val());
                evt.preventDefault();
            } else {
                $('#confirmOrderForm').submit(); 
                $('#confirmFormSubmit').prop('disabled', true);
            }
        }
    }
    });
});

    // Function to retrieve hash from iframe
    function gethash() {
        var iframe= $('#nnIframe')[0].contentWindow ? $('#nnIframe')[0].contentWindow : $('#nnIframe')[0].contentDocument.defaultView;
        iframe.postMessage(JSON.stringify({callBack: 'getHash'}), novalnetTargetOrgin);
    }
    
    function show_error_message(message)
    {           
        $('#error_message').text(message);
        $('#error_message').show();
        $('html,body').scrollTop(0);

    }
    
    function validate_age(DOB) {
		var today = new Date();
        
        if(DOB == undefined || DOB == '')
		{
			return NaN;
		}
        
        var birthDate = DOB.split("/");
		var age = today.getFullYear() - birthDate[2];
		var m = today.getMonth() - birthDate[1];
		m = m + 1
		if (m < 0 || (m == '0' && today.getDate() < birthDate[0])) {
			age--;
		}
		return age;
	}
    
    function sepaHolderFormat(evt)
    {
        var key = ( 'which' in evt ) ? evt.which : evt.keyCode;
        if (!((key == 38) || (key == 45) || (key == 46) || (key == 32) || (key >= 65 && key <= 90) || (key >= 97 && key <= 122))) {
            evt.preventDefault();
        }
    }
