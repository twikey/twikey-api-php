<?php declare(strict_types=1);

namespace Twikey\Api;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Twikey\Api\Callback\DocumentCallback;
use Twikey\Api\Callback\TransactionCallback;

class TwikeyTest extends TestCase
{
    private static string $APIKEY;
    private static string $CT;
    private static ClientInterface $httpClient;

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
                'The CT (template) is not available.'
            );
        }

        self::$APIKEY = getenv('TWIKEY_API_KEY');
        self::$CT = getenv('CT');
        self::$httpClient = new Client([
            'http_errors' => false,
            'debug' => false
        ]);
    }

    public function testCreateDocument()
    {
        if (!self::$APIKEY)
            throw new InvalidArgumentException('Invalid apikey');

        $twikey = new Twikey(self::$httpClient,self::$APIKEY,true);
        $data = array(
            "ct" => self::$CT, // see Settings > Template
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
            "form" => "",
            "vatno" => "",
            "iban" => "",
            "bic" => "",
            "mandateNumber" => "",
            "contractNumber" => "",
        );

        $contract = $twikey->document->create($data);
        $this->assertIsString($contract->url);
        $this->assertIsString($contract->mndtId);
        $this->assertIsString($contract->key);

        $twikey->document->feed(new SampleDocumentCallback());

        // Remove the document again
        $twikey->document->cancel($contract->mndtId, "cancel");
    }

    public function testCreateTransaction()
    {
        if (!self::$APIKEY)
            throw new InvalidArgumentException('Invalid apikey');

        if (!self::$APIKEY)
            throw new InvalidArgumentException('Invalid apikey');

        if (getenv('mndtNumber') == "") {
            $this->markTestSkipped(
                'The mndtNumber is not available.'
            );
        }

        $twikey = new Twikey(self::$httpClient,self::$APIKEY,true);
        $data = array(
            "mndtId" => getenv('mndtNumber'),
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

    public function testTransactionFeed()
    {
        if (!self::$APIKEY)
            throw new InvalidArgumentException('Invalid apikey');

        $twikey = new Twikey(self::$httpClient,self::$APIKEY,true);
        $count = $twikey->transaction->feed(new class implements TransactionCallback{
            public function handle($transaction)
            {
                print("Transaction " . $transaction->id . ' @ '. $transaction->date . ' has '. $transaction->state . "\n");

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
}

class SampleDocumentCallback implements DocumentCallback {

    function handleNew($update)
    {
        $kv = array();
        foreach($update->Mndt->SplmtryData as $attribute){
            $kv[$attribute->Key] = $attribute->Value;
        }

        print("New " . $update->Mndt->MndtId . ' @ '. $update->EvtTime . "\n");
    }

    function handleUpdate($update)
    {
        $kv = array();
        foreach($update->Mndt->SplmtryData as $attribute){
            $kv[$attribute->Key] = $attribute->Value;
        }

        $rsn = $update->AmdmntRsn->Rsn;
        switch ($update->AmdmntRsn->Rsn) {
            case '_T50': { /* AccountChanged, */ break; }
            case '_T51': { /* AddressChanged, */ break; }
            case '_T52': { /* MandateNumberChanged */ break; }
            case '_T53': { /* Name changed */ break; }
            case '_T54': { /* Email changed */ break; }
            case '_T55': { /* Mobile changed */ break; }
            case '_T56': { /* Language changed */ break; }
            default:
                # code...
                break;
        }
        print("Update: " . $update->Mndt->MndtId . ' -> '. $rsn . ' @ '. $update->EvtTime . "\n");
    }

    function handleCancel($update)
    {
        $rsn = $update->CxlRsn->Rsn;
        print("Cancel: " . $update->OrgnlMndtId . ' -> '. $rsn . ' @ '. $update->EvtTime . "\n");
    }
}
