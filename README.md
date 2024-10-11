Tilta Fintech GmbH - Magento 2 payment extension
================================================

| Extension | Tilta as payment method for Magento 2                   |
|-----------|---------------------------------------------------------|
| Author    | Tilta Fintech GmbH, [WEBiDEA](https://www.webidea24.de) |
| Link      | [https://www.tilta.io](https://www.tilta.io)            |
| Mail      | [support@tilta.io](mailto:support@tilta.io)             |

This extension provides the integration of the payment provider "Tilta" in Magento 2.

## Introduction about Tilta

Tilta offers a white-labeled payment infrastructure, enabling B2B eCommerce shops to offer various payment options under
their own brand.

With Tilta, you can configure your own framework for payments. Decide which payment methods you want to offer, from
pay-now options like direct debit & direct transfer over pay-later options like invoice payment (7-120 days due date) or
installments (up to 180 days).

The relationship with your buyers is crucial for your success, therefore Tilta doesn’t want to intervene in the buyer's
journey.

Every payment method is white-labeled and leads to an end-to-end buyer journey.

As Tilta positions itself as infrastructure, you have full control over the payment methods.

You can choose which buyers can select which payment methods, how long the due dates for invoice purchases are, or which
fee buyers need to pay for the service.

Buyers love Tilta’s pre-approved financing facility, as it gives additional security and assurance during procurement.

As of today, Tilta can provide financing facilities of up to 250.000 € per buyer.
This limit can be provided with the help of Tilta’s new underwriting process, which not only includes financial
information but also the customer’s behavior on your platform.

## Installation

We highly recommend installing the extension via Composer to ensure that you're loading the precise version tailored to
your Magento setup.

If Composer is unfamiliar territory for you please contact your developer/agency.

### Installation via Composer (recommend)

Add the extension by executing the following command in the root of your project.

```
composer require tilta/magento-2-payment-module
```

Upgrade you application. This will automatically install the extension & clear the cache

```
./bin/magento setup:upgrade
```

If you are not in the developer mode, you have to build your static content by running the following command. Please
also have a look into
the [Magento documentation](https://experienceleague.adobe.com/en/docs/commerce-operations/configuration-guide/cli/static-view/static-view-file-deployment).

```
./bin/magento setup:static-content:deploy
```

#### Update the extension via Composer (recommend)

By running the same steps as in the installation, you can update the extension to the latest compatible version.

### Configuration

TODO
