<p align="center">
  <img src="https://cdn.twikey.com/img/logo.png" height="64"/>
</p>
<h1 align="center">Twikey API client for PHP</h1>

Want to allow your customers to pay in the most convenient way, then Twikey is right what you need.

Recurring or occasional payments via (Recurring) Credit Card, SEPA Direct Debit or any other payment method by bringing 
your own payment service provider or by leveraging your bank contract.

Twikey offers a simple and safe multichannel solution to negotiate and collect recurring (or even occasional) payments.
Twikey has integrations with a lot of accounting and CRM packages. It is the first and only provider to operate on a
European level for Direct Debit and can work directly with all major Belgian and Dutch Banks. However you can use the
payment options of your favorite PSP to allow other customers to pay as well.

## Requirements ##

To use the Twikey API client, the following things are required:

+ Get yourself a [Twikey account](https://www.twikey.com).
+ PHP >= 5.6
+ Up-to-date OpenSSL (or other SSL/TLS toolkit)

## Composer Installation ##

By far the easiest way to install the Twikey API client is to require it
with [Composer](http://getcomposer.org/doc/00-intro.md).

    $ composer require twikey/twikey-api-php:^0.1.0

    {
        "require": {
            "twikey/twikey-api-php": "^0.1.0"
        }
    }

## How to create anything ##

The api works the same way regardless if you want to create a mandate, a transaction, an invoice or even a paylink.
the following steps should be implemented:

1. Use the Twikey API client to create or import your item.

2. Once available, our platform will send an asynchronous request to the configured webhook
   to allow the details to be retrieved. As there may be multiple items ready for you a "feed" endpoint is provided
   which acts like a queue that can be read until empty till the next time.

3. The customer returns, and should be satisfied to see that the action he took is completed.

Find our full documentation online on [api.twikey.com](https://api.twikey.com).

## Getting started ##

Initializing the Twikey API client using your preferred Http client (eg. [gruzzle](https://docs.guzzlephp.org/en/stable/)) 
and configure your API key which you can find in the [Twikey merchant interface](https://www.twikey.com).

```php
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Twikey\Api;

$httpClient = new Client([
    'http_errors' => false,
    'debug' => false
])

$twikey = new Twikey($httpClient,$APIKEY);
``` 

## Documents

Invite a customer to sign a SEPA mandate using a specific behaviour template (ct) that allows you to configure 
the behaviour or flow that the customer will experience.

```php
$invite = $twikey->document->create([
    "ct" => $ct
    "email" => "john@doe.com",
    "firstname" => "John",
    "lastname" => "Doe",
]);
```

_After creation, the link is available for signing and ideally you store the mandatenumber for future usage (eg. sending transactions)._

```php
// store $invite->mndtId for this customer
header("Location: " . $invite->url);
```

### Feed

```php 
$twikey->document->feed(new class implements DocumentCallback {
   function handleNew($update)
   {
       print("New " . $update->Mndt->MndtId . ' @ '. $update->EvtTime . "\n");
   }

   function handleUpdate($update)
   {
       $rsn = $update->AmdmntRsn->Rsn;
       print("Update: " . $update->Mndt->MndtId . ' -> '. $rsn . ' @ '. $update->EvtTime . "\n");
   }

   function handleCancel($update)
   {
       $rsn = $update->CxlRsn->Rsn;
       print("Cancel: " . $update->OrgnlMndtId . ' -> '. $rsn . ' @ '. $update->EvtTime . "\n");
   }
}
);
```

## Transactions

Send new transactions and act upon feedback from the bank.

```php
$invite = $twikey->transaction->create([
   "mndtId" => "CORERECURRENTNL16318",
   "message" => "Test Message",
   "ref" => "Merchant Reference",
   "amount" => 10.00, // 10 euro
   "place" => "Here"
]);
```

### Feed

```php 
$count = $twikey->transaction->feed(new class implements TransactionCallback{
   public function handle($transaction)
   {
       print("Transaction " . $transaction->id . ' @ '. $transaction->date . ' has '. $transaction->state . "\n");
   }
});
```

## Webhook ##

When wants to inform you about new updates about documents or payments a `webhookUrl` specified in your api settings be called.  

```php
$queryString = decode($_SERVER['QUERY_STRING'])
$signatureHeader = $_SERVER['HTTP_X_SIGNATURE']

Twikey::validateWebhook($APIKEY, "abc=123&name=abc", $queryString, $signatureHeader)

```

## API documentation ##

If you wish to learn more about our API, please visit the [Twikey Api Page](https://api.twikey.com).
API Documentation is available in English.

## Want to help us make our API client even better? ##

Want to help us make our API client even better? We
take [pull requests](https://github.com/twikey/twikey-api-php/pulls). 

## Support ##

Contact: [www.twikey.com](https://www.twikey.com)
