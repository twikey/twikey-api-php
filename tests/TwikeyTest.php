<?php declare(strict_types=1);

namespace Twikey\Api;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Twikey\Api\Helper\DocumentCallback;
use Twikey\Api\Helper\TransactionCallback;

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
                'The MySQLi extension is not available.'
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
        $twikey->document->cancel($contract->mndtId);
    }

    public function testCreateTransaction()
    {
        if (!self::$APIKEY)
            throw new InvalidArgumentException('Invalid apikey');

        $twikey = new Twikey(self::$httpClient,self::$APIKEY,true);
        $data = array(
            "mndtId" => "CORERECURRENTNL16318",
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
