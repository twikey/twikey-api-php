<?php declare(strict_types=1);

namespace Twikey\Api;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Twikey\Api\Callback\DocumentCallback;
use Twikey\Api\Callback\InvoiceCallback;
use Twikey\Api\Callback\PaylinkCallback;
use Twikey\Api\Callback\TransactionCallback;

class TwikeyTest extends TestCase
{
    private static string $APIKEY;
    private static string $CT;
    private static ClientInterface $http_client;

    /**
     * @before
     */
    public function setup(): void
    {
        if (getenv('TWIKEY_API_KEY') == "") {
            $this->markTestSkipped(
                'The TWIKEY_API_KEY is not available.'
            );
        }
        if (getenv('CT') == "") {
            $this->markTestSkipped(
                'The CT (Profile to use) is not available.'
            );
        }

        self::$APIKEY = getenv('TWIKEY_API_KEY');
        self::$CT = getenv('CT');
        self::$http_client = new Client([
            'http_errors' => false,
            'debug' => false
        ]);

    }

    public function testCreateDocument()
    {
        if (!self::$APIKEY)
            throw new InvalidArgumentException('Invalid apikey');

        $twikey = new Twikey(self::$http_client,self::$APIKEY,"https://api.beta.twikey.com","sdk-php-test/".Twikey::VERSION);
        $data = array(
            "ct" => self::$CT, // see Settings > Profile to use
            "email" => "john@doe.com",
            "firstname" => "John",
            "lastname" => "Doe",
            "l" => "en",
            "address" => "Abbey road",
            "city" => "Liverpool",
            "zip" => "1526",
            "country" => "BE",
            "mobile" => "", // Optional
            "companyName" => "", // Optional
            "form" => "", // Optional
            "vatno" => "", // Optional
            "iban" => "", // Optional
            "bic" => "", // Optional
            "mandateNumber" => "", // Optional
            "contractNumber" => "", // Optional
        );

        $contract = $twikey->document->create($data);
        $this->assertIsString($contract->url);
        $this->assertIsString($contract->mndtId);
        $this->assertIsString($contract->key);

        $twikey->document->feed(new SampleDocumentCallback(), "", ["id","mandate","person","cancelled_mandate"]);

        // Get the customer link
        if (getenv('MNDTNUMBER') == "") {
            $this->markTestSkipped(
                'The mndtNumber is not available.'
            );
        }
        $customerAccess = $twikey->document->customeraccess(getenv('MNDTNUMBER'));
        $this->assertIsString($customerAccess->url);

        $twikey->document->getPdf(getenv('MNDTNUMBER'));

        // Remove the document again
        $twikey->document->cancel($contract->mndtId, "cancel");
    }

    public function testCreateTransaction()
    {
        if (!self::$APIKEY)
            throw new InvalidArgumentException('Invalid apikey');

        if (getenv('MNDTNUMBER') == "") {
            $this->markTestSkipped(
                'The mndtNumber is not available.'
            );
        }

        $twikey = new Twikey(self::$http_client,self::$APIKEY,"https://api.beta.twikey.com","sdk-php-test/".Twikey::VERSION);
        $data = array(
            "mndtId" => getenv('MNDTNUMBER'),
            "message" => "Test Message",
            "ref" => "Merchant Reference",
            "amount" => 10.00, // 10 euro
            "place" => "Here"
        );

        $tx = $twikey->transaction->create($data);
        $this->assertIsNumeric($tx->Entries[0]->id);
        $this->assertIsNumeric($tx->Entries[0]->contractId);
        $this->assertNotEmpty($tx->Entries[0]->date);
    }

    public function testSubscription()
    {
        if (!self::$APIKEY)
            throw new InvalidArgumentException('Invalid apikey');

        if (getenv('MNDTNUMBER') == "") {
            $this->markTestSkipped(
                'The mndtNumber is not available.'
            );
        }

        $twikey = new Twikey(self::$http_client,self::$APIKEY,"https://api.beta.twikey.com","sdk-php-test/".Twikey::VERSION);
        $data = array(
            "mndtId" => getenv('MNDTNUMBER'),
            "message" => "Message to customer",
            "ref" => "PhpTestRef",
            "amount" => 10.00, // 10 euro
            "recurrence" => "1m", // every month
            "start" => "2030-01-01"
        );
        $subscription = $twikey->subscription->create($data);
        $this->assertIsNumeric($subscription->id);
        $this->assertIsNumeric($subscription->amount);
        $this->assertNotEmpty($subscription->message);
        $this->assertNotEmpty($subscription->ref);
        $this->assertEquals("2030-01-01", $subscription->start);
        $twikey->subscription->cancel(getenv('MNDTNUMBER'), $subscription->ref);
    }

    public function testTransactionFeed()
    {
        if (!self::$APIKEY)
            throw new InvalidArgumentException('Invalid apikey');

        $twikey = new Twikey(self::$http_client,self::$APIKEY,"https://api.beta.twikey.com","sdk-php-test/".Twikey::VERSION);
        $count = $twikey->transaction->feed(new class implements TransactionCallback{
            public function handle($transaction)
            {
                Assert::assertIsInt($transaction->id);
                Assert::assertIsString($transaction->date);
                Assert::assertIsString($transaction->state);
            }
        });
        $this->assertIsNumeric($count);
    }

    public function testLinkFeed()
    {
        if (!self::$APIKEY)
            throw new InvalidArgumentException('Invalid apikey');

        $twikey = new Twikey(self::$http_client,self::$APIKEY,"https://api.beta.twikey.com","sdk-php-test/".Twikey::VERSION);
        $link = $twikey->link->create([
            "email" => "no-reply@twikey.com",
            "message" => "Payment last month",
            "amount" => "100",
        ]);

        $this->assertIsNumeric($link->id);
        $twikey->link->get($link->id);
        $twikey->link->remove($link->id);

        $count = $twikey->link->feed(new class implements PaylinkCallback {
            public function handle($link)
            {
                Assert::assertIsInt($link->id);
                Assert::assertIsFloat($link->amount);
                Assert::assertIsString($link->state);
            }
        });
        $this->assertIsNumeric($count);
    }

    public function testWebhook()
    {
        $this->assertTrue(Twikey::validateWebhook('1234', "abc=123&name=abc", "55261CBC12BF62000DE1371412EF78C874DBC46F513B078FB9FF8643B2FD4FC2"));
    }

    public function testValidateSignature()
    {
        $websiteKey = "BE04823F732EDB2B7F82252DDAF6DE787D647B43A66AE97B32773F77CCF12765";
        $doc = "MYDOC";
        $status = "ok";
        $signatureInOutcome = "8C56F94905BBC9E091CB6C4CEF4182F7E87BD94312D1DD16A61BF7C27C18F569";

        $this->assertTrue(Twikey::validateSignature($websiteKey, $doc, $status, "", $signatureInOutcome));
    }

    public function testCreateInvoice()
    {
        if (!self::$APIKEY)
            throw new InvalidArgumentException('Invalid apikey');

        $twikey = new Twikey(self::$http_client,self::$APIKEY,"https://api.beta.twikey.com","sdk-php-test/".Twikey::VERSION);
        $customer = array(
            "email" => "john@doe.com",
            "firstname" => "John",
            "lastname" => "Doe",
            "l" => "en",
            "address" => "Abbey road",
            "city" => "Liverpool",
            "zip" => "1526",
            "country" => "BE",
            "mobile" => "",
            "companyName" => "",
        );
        $invoicedate = date("Y-m-d");
        $duedate = date("Y-m-d", time() + 30*24*3600);
        $invoice = array(
            "number" => "INVOICE-123",
            "title" => "Invoice March",
            "remittance" => "123456789123",
            "ct" => self::$CT, // see Settings > Profile to use
            "amount" => 100,
            "date" => $invoicedate,
            "duedate" => $duedate,
            "customer" => $customer,
        );

        $invoice = $twikey->invoice->create(json_encode($invoice,JSON_FORCE_OBJECT));
        $this->assertIsString($invoice->url);
        $this->assertIsString($invoice->id);

        $invoice = $twikey->invoice->get($invoice->id);
        $this->assertIsString($invoice->url);
        $this->assertIsString($invoice->id);

        $twikey->invoice->feed(new SampleInvoiceCallback(), "3091701");

    }

}

class SampleDocumentCallback implements DocumentCallback {

    public function start($position, $number_of_updates)
    {
        Assert::assertIsString($position);
        Assert::assertIsInt($number_of_updates);
    }

    function handleNew($mandate,$evtTime)
    {
        $kv = array();
        foreach($mandate->SplmtryData as $attribute){
            $kv[$attribute->Key] = $attribute->Value;
        }
        Assert::assertIsString($mandate->MndtId);
        Assert::assertIsString($evtTime);
    }

    function handleUpdate($originalMandateNumber,$mandate,$reason,$evtTime)
    {
        $kv = array();
        foreach($mandate->SplmtryData as $attribute){
            $kv[$attribute->Key] = $attribute->Value;
        }

        $rsn = $reason->Rsn;
        switch ($reason->Rsn) {
            case '_T50': { $rsn = "AccountChanged,"; break; }
            case '_T51': { $rsn = "AddressChanged,"; break; }
            case '_T52': { $rsn = "MandateNumberChanged"; break; }
            case '_T53': { $rsn = "Name changed"; break; }
            case '_T54': { $rsn = "Email changed"; break; }
            case '_T55': { $rsn = "Mobile changed"; break; }
            case '_T56': { $rsn = "Language changed"; break; }
            default:
                # code...
                break;
        }
        Assert::assertIsString($mandate->MndtId);
        Assert::assertIsString($rsn);
        Assert::assertIsString($evtTime);
    }

    function handleCancel($mandateNumber, $mandate, $reason, $evtTime)
    {
        $rsn = $reason->Rsn;
        Assert::assertIsString($mandateNumber);
        Assert::assertIsString($rsn);
        if($mandate){ // requires the cancelled_mandate include
            Assert::assertIsString($mandate->MndtId);
        }
        Assert::assertIsString($evtTime);
    }
}

class SampleInvoiceCallback implements InvoiceCallback {

    public function start($position, $number_of_updates)
    {
        Assert::assertIsString($position);
        Assert::assertIsInt($number_of_updates);
    }

    public function handle($invoice)
    {
        Assert::assertIsObject($invoice);
        Assert::assertIsString($invoice->number);
        Assert::assertIsString($invoice->state);
    }
}
