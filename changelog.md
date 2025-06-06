# Changelog - Computop Paymet Connector for Magento 2

### 1.2.1
* Hotfix: Changed status in Cronjob to pending_payment

### 1.2.0
* Added new order status handling "pending payment"
* Add extended cancel condition in the cronjob and set final cronjob timing

### 1.1.1
* Fixed digital goods orders for several payment methods

### 1.1.0
* Removed Giropay payment method
* Added Back-/Cancel-URLs to API requests and payment cancel handling
* Moved template parameter from Data to unencrypted

### 1.0.9
* Introducing iDeal V2
* Added PayType parameter for GMO acquirer
* Fixed problem with currencies without decimals

### 1.0.8
* Added language to creditcard form

### 1.0.7
* Fixed problem with config being taken from wrong scope for backend actions like capture and refund

### 1.0.6
Released 2024-09-16
* Added test mode for PayPal Express
* Added configuration for PayPal Express Livemode

### 1.0.5
Released 2024-09-11
* Added new payment method Amazon Pay
* Added new payment method easycredit
* Added use od order increment id as transaction id, where possible

### 1.0.4
Released 2024-08-05
* Added new mandatory parameters to credit card request

### 1.0.3
Released 2024-07-01
* Refactored RefNr and TransID for compatibility with iDeal 2.0

### 1.0.2
Released 2024-06-17
* Fixed issue with attribute identity

### 1.0.1
Released 2024-06-15
* Compatibility with Magento 2.4.7 established

### 1.0.0
Released 2024-06-11
* Initial Release
