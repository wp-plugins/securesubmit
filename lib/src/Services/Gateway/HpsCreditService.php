<?php

class HpsCreditService extends HpsSoapGatewayService
{
    public function __construct(HpsServicesConfig $config = null)
    {
        parent::__construct($config);
    }

    public function authorize($amount, $currency, $cardOrToken, $cardHolder = null, $requestMultiUseToken = false, $details = null, $txnDescriptor = null, $allowPartialAuth = false, $cpcReq = false)
    {
        HpsInputValidation::checkCurrency($currency);
        $this->_currency = $currency;
        $this->_amount = HpsInputValidation::checkAmount($amount);

        $xml = new DOMDocument();
        $hpsTransaction = $xml->createElement('hps:Transaction');
        $hpsCreditAuth = $xml->createElement('hps:CreditAuth');
        $hpsBlock1 = $xml->createElement('hps:Block1');

        $hpsBlock1->appendChild($xml->createElement('hps:AllowDup', 'Y'));
        $hpsBlock1->appendChild($xml->createElement('hps:AllowPartialAuth', ($allowPartialAuth ? 'Y' : 'N')));
        $hpsBlock1->appendChild($xml->createElement('hps:Amt', $amount));
        if ($cardHolder != null) {
            $hpsBlock1->appendChild($this->_hydrateCardHolderData($cardHolder, $xml));
        }
        if ($details != null) {
            $hpsBlock1->appendChild($this->_hydrateAdditionalTxnFields($details, $xml));
        }
        if ($txnDescriptor != null && $txnDescriptor != '') {
            $hpsBlock1->appendChild($xml->createElement('hps:TxnDescriptor', $txnDescriptor));
        }

        $cardData = $xml->createElement('hps:CardData');
        if ($cardOrToken instanceof HpsCreditCard) {
            $cardData->appendChild($this->_hydrateManualEntry($cardOrToken, $xml));
        } else {
            $tokenData = $xml->createElement('hps:TokenData');
            $tokenData->appendChild($xml->createElement('hps:TokenValue', $cardOrToken->tokenValue));
            $cardData->appendChild($tokenData);
        }
        $cardData->appendChild($xml->createElement('hps:TokenRequest', ($requestMultiUseToken) ? 'Y' : 'N'));
        if ($cpcReq) {
            $hpsBlock1->appendChild($xml->createElement('hps:CPCReq', 'Y'));
        }

        $hpsBlock1->appendChild($cardData);
        $hpsCreditAuth->appendChild($hpsBlock1);
        $hpsTransaction->appendChild($hpsCreditAuth);

        return $this->_submitTransaction($hpsTransaction, 'CreditAuth', (isset($details->clientTransactionId) ? $details->clientTransactionId : null), $cardOrToken);
    }

    public function capture($transactionId, $amount = null, $gratuity = null, $clientTransactionId = null, $directMarketData = null)
    {
        $xml = new DOMDocument();
        $hpsTransaction = $xml->createElement('hps:Transaction');
        $hpsCreditAddToBatch = $xml->createElement('hps:CreditAddToBatch');

        $hpsCreditAddToBatch->appendChild($xml->createElement('hps:GatewayTxnId', $transactionId));
        if ($amount != null) {
            $amount = sprintf("%0.2f", round($amount, 3));
            $hpsCreditAddToBatch->appendChild($xml->createElement('hps:Amt', $amount));
        }
        if ($gratuity != null) {
            $hpsCreditAddToBatch->appendChild($xml->createElement('hps:GratuityAmtInfo', $gratuity));
        }

        if ($directMarketData != null && $directMarketData->invoiceNumber != null) {
            $hpsCreditAddToBatch->appendChild($this->_hydrateDirectMarketData($directMarketData, $xml));
        }

        $hpsTransaction->appendChild($hpsCreditAddToBatch);
        $response = $this->doTransaction($hpsTransaction);
        $this->_processChargeGatewayResponse($response, 'CreditAddToBatch');

        return $this->get($transactionId);
    }

    public function charge($amount, $currency, $cardOrToken, $cardHolder = null, $requestMultiUseToken = false, $details = null, $txnDescriptor = null, $allowPartialAuth = false, $cpcReq = false, $directMarketData = null)
    {
        HpsInputValidation::checkCurrency($currency);
        $this->_currency = $currency;
        $this->_amount = HpsInputValidation::checkAmount($amount);

        $xml = new DOMDocument();
        $hpsTransaction = $xml->createElement('hps:Transaction');
        $hpsCreditSale = $xml->createElement('hps:CreditSale');
        $hpsBlock1 = $xml->createElement('hps:Block1');

        $hpsBlock1->appendChild($xml->createElement('hps:AllowDup', 'Y'));
        $hpsBlock1->appendChild($xml->createElement('hps:AllowPartialAuth', ($allowPartialAuth ? 'Y' : 'N')));
        $hpsBlock1->appendChild($xml->createElement('hps:Amt', $amount));
        if ($cardHolder != null) {
            $hpsBlock1->appendChild($this->_hydrateCardHolderData($cardHolder, $xml));
        }
        if ($details != null) {
            $hpsBlock1->appendChild($this->_hydrateAdditionalTxnFields($details, $xml));
        }
        if ($txnDescriptor != null && $txnDescriptor != '') {
            $hpsBlock1->appendChild($xml->createElement('hps:TxnDescriptor', $txnDescriptor));
        }

        $cardData = $xml->createElement('hps:CardData');
        if ($cardOrToken instanceof HpsCreditCard) {
            $cardData->appendChild($this->_hydrateManualEntry($cardOrToken, $xml));
        } else {
            $tokenData = $xml->createElement('hps:TokenData');
            $tokenData->appendChild($xml->createElement('hps:TokenValue', $cardOrToken->tokenValue));
            $cardData->appendChild($tokenData);
        }
        if ($cpcReq) {
            $hpsBlock1->appendChild($xml->createElement('hps:CPCReq', 'Y'));
        }
        $cardData->appendChild($xml->createElement('hps:TokenRequest', ($requestMultiUseToken) ? 'Y' : 'N'));

        if ($directMarketData != null && $directMarketData->invoiceNumber != null) {
            $hpsBlock1->appendChild($this->_hydrateDirectMarketData($directMarketData, $xml));
        }

        $hpsBlock1->appendChild($cardData);
        $hpsCreditSale->appendChild($hpsBlock1);
        $hpsTransaction->appendChild($hpsCreditSale);

        return $this->_submitTransaction($hpsTransaction, 'CreditSale', (isset($details->clientTransactionId) ? $details->clientTransactionId : null), $cardOrToken);
    }

    public function recurring($schedule, $amount, $cardOrTokenOrPMKey, $cardHolder = null, $oneTime = false, $details = null)
    {
        $this->_amount = HpsInputValidation::checkAmount($amount);

        $xml = new DOMDocument();
        $hpsTransaction = $xml->createElement('hps:Transaction');
        $hpsRecurringBilling = $xml->createElement('hps:RecurringBilling');
        $hpsBlock1 = $xml->createElement('hps:Block1');

        $hpsBlock1->appendChild($xml->createElement('hps:AllowDup', 'Y'));
        $hpsBlock1->appendChild($xml->createElement('hps:Amt', $amount));
        if ($cardHolder != null) {
            $hpsBlock1->appendChild($this->_hydrateCardHolderData($cardHolder, $xml));
        }
        if ($details != null) {
            $hpsBlock1->appendChild($this->_hydrateAdditionalTxnFields($details, $xml));
        }

        if ($cardOrTokenOrPMKey instanceof HpsCreditCard) {
            $cardData = $xml->createElement('hps:CardData');
            $cardData->appendChild($this->_hydrateManualEntry($cardOrTokenOrPMKey, $xml));
            $hpsBlock1->appendChild($cardData);
        } else if ($cardOrTokenOrPMKey instanceof HpsTokenData) {
            $cardData = $xml->createElement('hps:CardData');
            $tokenData = $xml->createElement('hps:TokenData');
            $tokenData->appendChild($xml->createElement('hps:TokenValue', $cardOrTokenOrPMKey->tokenValue));
            $cardData->appendChild($tokenData);
            $hpsBlock1->appendChild($cardData);
        } else {
            $hpsBlock1->appendChild($xml->createElement('hps:PaymentMethodKey', $cardOrTokenOrPMKey));
        }

        $id = $schedule;
        if ($schedule instanceof HpsPayPlanSchedule) {
            $id = $schedule->scheduleIdentifier;
        }
        $recurringData = $xml->createElement('hps:RecurringData');
        $recurringData->appendChild($xml->createElement('hps:ScheduleID', $id));
        $recurringData->appendChild($xml->createElement('hps:OneTime', ($oneTime ? 'Y' : 'N')));

        $hpsBlock1->appendChild($recurringData);
        $hpsRecurringBilling->appendChild($hpsBlock1);
        $hpsTransaction->appendChild($hpsRecurringBilling);

        return $this->_submitTransaction($hpsTransaction, 'RecurringBilling', (isset($details->clientTransactionId) ? $details->clientTransactionId : null), $cardOrTokenOrPMKey);
    }

    public function cpcEdit($transactionId, $cpcData)
    {
        $xml = new DOMDocument();
        $hpsTransaction = $xml->createElement('hps:Transaction');
        $hpsPosCreditCPCEdit = $xml->createElement('hps:CreditCPCEdit');
        $hpsPosCreditCPCEdit->appendChild($xml->createElement('hps:GatewayTxnId', $transactionId));
        $hpsPosCreditCPCEdit->appendChild($this->_hydrateCPCData($cpcData, $xml));
        $hpsTransaction->appendChild($hpsPosCreditCPCEdit);

        return $this->_submitTransaction($hpsTransaction, 'CreditCPCEdit');
    }

    public function edit($transactionId, $amount = null, $gratuity = null, $clientTransactionId = null)
    {
        $xml = new DOMDocument();
        $hpsTransaction = $xml->createElement('hps:Transaction');
        $hpsCreditTxnEdit = $xml->createElement('hps:CreditTxnEdit');

        $hpsCreditTxnEdit->appendChild($xml->createElement('hps:GatewayTxnId', $transactionId));
        if ($amount != null) {
            $amount = sprintf('%0.2f', round($amount, 3));
            $hpsCreditTxnEdit->appendChild($xml->createElement('hps:Amt', $amount));
        }
        if ($gratuity != null) {
            $hpsCreditTxnEdit->appendChild($xml->createElement('hps:GratuityAmtInfo', $gratuity));
        }

        $hpsTransaction->appendChild($hpsCreditTxnEdit);
        $trans = $this->_submitTransaction($hpsTransaction, 'CreditTxnEdit', $clientTransactionId);

        $trans->responseCode = '00';
        $trans->responseText = '';

        return $trans;
    }

    public function get($transactionId)
    {
        if ($transactionId <= 0) {
            throw new HpsArgumentException('Invalid Transaction Id');
        }

        $xml = new DOMDocument();
        $hpsTransaction = $xml->createElement('hps:Transaction');
        $hpsReportTxnDetail = $xml->createElement('hps:ReportTxnDetail');
        $hpsReportTxnDetail->appendChild($xml->createElement('hps:TxnId', $transactionId));
        $hpsTransaction->appendChild($hpsReportTxnDetail);

        return $this->_submitTransaction($hpsTransaction, 'ReportTxnDetail');
    }

    public function listTransactions($startDate, $endDate, $filterBy = null)
    {
        $this->_filterBy = $filterBy;
        date_default_timezone_set("UTC");
        $dateFormat = 'Y-m-d\TH:i:s.00\Z';
        $current = new DateTime();
        $currentTime = $current->format($dateFormat);

        HpsInputValidation::checkDateNotFuture($startDate);
        HpsInputValidation::checkDateNotFuture($endDate);

        $xml = new DOMDocument();
        $hpsTransaction = $xml->createElement('hps:Transaction');
        $hpsReportActivity = $xml->createElement('hps:ReportActivity');
        $hpsReportActivity->appendChild($xml->createElement('hps:RptStartUtcDT', $startDate));
        $hpsReportActivity->appendChild($xml->createElement('hps:RptEndUtcDT', $endDate));
        $hpsTransaction->appendChild($hpsReportActivity);

        return $this->_submitTransaction($hpsTransaction, 'ReportActivity');
    }

    public function refund($amount, $currency, $cardData, $cardHolder = null, $details = null)
    {
        HpsInputValidation::checkCurrency($currency);
        $this->_currency = $currency;
        $this->_amount = HpsInputValidation::checkAmount($amount);

        $xml = new DOMDocument();
        $hpsTransaction = $xml->createElement('hps:Transaction');
        $hpsCreditReturn = $xml->createElement('hps:CreditReturn');
        $hpsBlock1 = $xml->createElement('hps:Block1');

        $hpsBlock1->appendChild($xml->createElement('hps:AllowDup', 'Y'));
        $hpsBlock1->appendChild($xml->createElement('hps:Amt', $amount));
        if ($cardData instanceof HpsCreditCard) {
            $cardDataElement = $xml->createElement('hps:CardData');
            $cardDataElement->appendChild($this->_hydrateManualEntry($cardData, $xml));
            $hpsBlock1->appendChild($cardDataElement);
        } else if ($cardData instanceof HpsTokenData) {
            $cardDataElement = $xml->createElement('hps:CardData');
            $tokenData = $xml->createElement('hps:TokenData');
            $tokenData->appendChild($xml->createElement('hps:TokenValue', $cardData->tokenValue));
            $cardDataElement->appendChild($tokenData);
            $hpsBlock1->appendChild($cardDataElement);
        } else {
            $hpsBlock1->appendChild($xml->createElement('hps:GatewayTxnId', $cardData));
        }
        if ($cardHolder != null) {
            $hpsBlock1->appendChild($this->_hydrateCardHolderData($cardHolder, $xml));
        }
        if ($details != null) {
            $hpsBlock1->appendChild($this->_hydrateAdditionalTxnFields($details, $xml));
        }

        $hpsCreditReturn->appendChild($hpsBlock1);
        $hpsTransaction->appendChild($hpsCreditReturn);

        return $this->_submitTransaction($hpsTransaction, 'CreditReturn', (isset($details->clientTransactionId) ? $details->clientTransationId : null));
    }

    public function reverse($cardData, $amount, $currency, $details = null)
    {
        HpsInputValidation::checkCurrency($currency);
        $this->_currency = $currency;
        $this->_amount = HpsInputValidation::checkAmount($amount);

        $xml = new DOMDocument();
        $hpsTransaction = $xml->createElement('hps:Transaction');
        $hpsCreditReversal = $xml->createElement('hps:CreditReversal');
        $hpsBlock1 = $xml->createElement('hps:Block1');

        $hpsBlock1->appendChild($xml->createElement('hps:Amt', $amount));
        $cardDataElement = null;
        if ($cardData instanceof HpsCreditCard) {
            $cardDataElement = $xml->createElement('hps:CardData');
            $cardDataElement->appendChild($this->_hydrateManualEntry($cardData, $xml));
        } else if ($cardData instanceof HpsTokenData) {
            $cardDataElement = $xml->createElement('hps:CardData');
            $tokenData = $xml->createElement('hps:TokenData');
            $tokenData->appendChild($xml->createElement('hps:TokenValue', $cardData->tokenValue));
            $cardDataElement->appendChild($tokenData);
        } else {
            $cardDataElement = $xml->createElement('hps:GatewayTxnId', $cardData);
        }
        $hpsBlock1->appendChild($cardDataElement);
        if ($details != null) {
            $hpsBlock1->appendChild($this->_hydrateAdditionalTxnFields($details, $xml));
        }

        $hpsCreditReversal->appendChild($hpsBlock1);
        $hpsTransaction->appendChild($hpsCreditReversal);

        return $this->_submitTransaction($hpsTransaction, 'CreditReversal', (isset($details->clientTransactionId) ? $details->clientTransactionId : null));
    }

    public function verify($cardOrToken, $cardHolder = null, $requestMultiUseToken = false, $clientTransactionId = null)
    {
        $xml = new DOMDocument();
        $hpsTransaction = $xml->createElement('hps:Transaction');
        $hpsCreditAccountVerify = $xml->createElement('hps:CreditAccountVerify');
        $hpsBlock1 = $xml->createElement('hps:Block1');

        if ($cardHolder != null) {
            $hpsBlock1->appendChild($this->_hydrateCardHolderData($cardHolder, $xml));
        }

        $cardData = $xml->createElement('hps:CardData');
        if ($cardOrToken instanceof HpsCreditCard) {
            $cardData->appendChild($this->_hydrateManualEntry($cardOrToken, $xml));
        } else {
            $tokenData = $xml->createElement('hps:TokenData');
            $tokenData->appendChild($xml->createElement('hps:TokenValue', $cardOrToken->tokenValue));
            $cardData->appendChild($tokenData);
        }
        $cardData->appendChild($xml->createElement('hps:TokenRequest', ($requestMultiUseToken) ? 'Y' : 'N'));

        $hpsBlock1->appendChild($cardData);
        $hpsCreditAccountVerify->appendChild($hpsBlock1);
        $hpsTransaction->appendChild($hpsCreditAccountVerify);

        return $this->_submitTransaction($hpsTransaction, 'CreditAccountVerify', $clientTransactionId);
    }

    public function void($transactionId, $clientTransactionId = null)
    {
        $xml = new DOMDocument();
        $hpsTransaction = $xml->createElement('hps:Transaction');
        $hpsCreditVoid = $xml->createElement('hps:CreditVoid');
        $hpsCreditVoid->appendChild($xml->createElement('hps:GatewayTxnId', $transactionId));
        $hpsTransaction->appendChild($hpsCreditVoid);

        return $this->_submitTransaction($hpsTransaction, 'CreditVoid', $clientTransactionId);
    }

    private function _processChargeGatewayResponse($response, $expectedType)
    {
        $gatewayRspCode = (isset($response->Header->GatewayRspCode) ? $response->Header->GatewayRspCode : null);
        $transactionId = (isset($response->Header->GatewayTxnId) ? $response->Header->GatewayTxnId : null);

        if ($gatewayRspCode == '0') {
            return;
        }

        if ($gatewayRspCode == '30') {
            try {
                $this->reverse($transactionId, $this->_amount, $this->_currency);
            } catch (Exception $e) {
                throw new HpsGatewayException(
                    HpsExceptionCodes::GATEWAY_TIMEOUT_REVERSAL_ERROR,
                    'Error occurred while reversing a charge due to HPS gateway timeout',
                    $e
                );
            }
        }

        HpsGatewayResponseValidation::checkResponse($response, $expectedType);
    }

    private function _processChargeIssuerResponse($response, $expectedType)
    {
        $transactionId = (isset($response->Header->GatewayTxnId) ? $response->Header->GatewayTxnId : null);
        $item = $response->Transaction->$expectedType;

        if ($item != null) {
            $responseCode = (isset($item->RspCode) ? $item->RspCode : null);
            $responseText = (isset($item->RspText) ? $item->RspText : null);

            if ($responseCode != null) {
                // check if we need to do a reversal
                if ($responseCode == '91') {
                    try {
                        $this->reverse($transactionId, $this->_amount, $this->_currency);
                    } catch (HpsGatewayException $e) {
                        // if the transaction wasn't found; throw the original timeout exception
                        if ($e->details->gatewayResponseCode == 3) {
                            HpsIssuerResponseValidation::checkResponse($transactionId, $responseCode, $responseText);
                        }
                        throw new HpsCreditException(
                            $transactionId,
                            HpsExceptionCodes::ISSUER_TIMEOUT_REVERSAL_ERROR,
                            'Error occurred while reversing a charge due to HPS issuer timeout',
                            $e
                        );
                    } catch (HpsException $e) {
                        throw new HpsCreditException(
                            $transactionId,
                            HpsExceptionCodes::ISSUER_TIMEOUT_REVERSAL_ERROR,
                            'Error occurred while reversing a charge due to HPS issuer timeout',
                            $e
                        );
                    }
                }
                HpsIssuerResponseValidation::checkResponse($transactionId, $responseCode, $responseText);
            }
        }
    }

    private function _submitTransaction($transaction, $txnType, $clientTxnId = null, $cardData = null)
    {
        try {
            $response = $this->doTransaction($transaction, $clientTxnId);
        } catch (HpsException $e) {
            if ($e->innerException != null && $e->innerException->getMessage() == 'gateway_time-out') {
                if (in_array($txnType, array('CreditSale', 'CreditAuth'))) {
                    try {
                        $this->reverse($cardData, $this->_amount, $this->_currency);
                    } catch (Exception $e) {
                        throw new HpsGatewayException('0', HpsExceptionCodes::GATEWAY_TIMEOUT_REVERSAL_ERROR);
                    }
                }
                throw new HpsException('An error occurred and the gateway has timed out', 'gateway_timeout', $e, 'gateway_timeout');
            }
            throw $e;
        }

        $this->_processChargeGatewayResponse($response, $txnType);
        $this->_processChargeIssuerResponse($response, $txnType);

        $rvalue = null;
        switch ($txnType) {
            case 'ReportTxnDetail':
                $rvalue = HpsReportTransactionDetails::fromDict($response, $txnType);
                break;
            case 'ReportActivity':
                $rvalue = HpsReportTransactionSummary::fromDict($response, $txnType, $this->_filterBy);
                break;
            case 'CreditSale':
                $rvalue = HpsCharge::fromDict($response, $txnType);
                break;
            case 'CreditAccountVerify':
                $rvalue = HpsAccountVerify::fromDict($response, $txnType);
                break;
            case 'CreditAuth':
                $rvalue = HpsAuthorization::fromDict($response, $txnType);
                break;
            case 'CreditReturn':
                $rvalue = HpsRefund::fromDict($response, $txnType);
                break;
            case 'CreditReversal':
                $rvalue = HpsReversal::fromDict($response, $txnType);
                break;
            case 'CreditVoid':
                $rvalue = HpsVoid::fromDict($response, $txnType);
                break;
            case 'CreditCPCEdit':
                $rvalue = HpsCPCEdit::fromDict($response, $txnType);
                break;
            case 'CreditTxnEdit':
                $rvalue = HpsTransaction::fromDict($response, $txnType);
                break;
            case 'RecurringBilling':
                $rvalue = HpsRecurringBilling::fromDict($response, $txnType);
                break;
            default:
                break;
        }

        return $rvalue;
    }
}
