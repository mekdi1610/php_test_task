<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class CommissionTest extends TestCase
{
    // Test for processTransactions
    public function testProcessTransactions()
    {
        $client = $this->createMock(Client::class);
        $client->method('get')
            ->willReturnCallback(function ($url) {
                if (strpos($url, 'lookup.binlist.net') !== false) {
                    return new Response(200, [], json_encode(['country' => ['alpha2' => 'DE']]));
                } elseif (strpos($url, 'api.exchangeratesapi.io') !== false) {
                    return new Response(200, [], json_encode(['rates' => ['USD' => 1.1]]));
                }
            });

        $processor = new Commission($client);
        $filePath = __DIR__ . '/test_input.txt';
        file_put_contents($filePath, '{"bin":"45717360","amount":"100.00","currency":"EUR"}');
        ob_start();
        $processor->processTransactions($filePath);
        $output = ob_get_clean();
        $this->assertEquals("1.00\n", $output);

        unlink($filePath);
    }

    // Test for parseTransactions
    public function testParseTransactions()
    {
        $processor = new Commission(new Client());

        $filePath = __DIR__ . '/test_input.txt';
        file_put_contents($filePath, '{"bin":"45717360","amount":"100.00","currency":"EUR"}' . "\n" . '{"bin":"516793","amount":"50.00","currency":"USD"}');

        $transactions = $processor->parseTransactions($filePath);

        $this->assertCount(2, $transactions);
        $this->assertEquals('45717360', $transactions[0]['bin']);
        $this->assertEquals('516793', $transactions[1]['bin']);

        unlink($filePath);
    }

    // Test for getCountryCode
    public function testGetCountryCode()
    {
        $client = $this->createMock(Client::class);
        $client->method('get')
            ->willReturn(new Response(200, [], json_encode(['country' => ['alpha2' => 'DE']])));

        $processor = new Commission($client);

        $countryCode = $processor->getCountryCode('45717360');
        $this->assertEquals('DE', $countryCode);
    }

    // Test for getExchangeRate
    public function testGetExchangeRate()
    {
        $client = $this->createMock(Client::class);
        $client->method('get')
            ->willReturn(new Response(200, [], json_encode(['rates' => ['USD' => 1.1]])));

        $processor = new Commission($client);

        $exchangeRate = $processor->getExchangeRate('USD');
        $this->assertEquals(1.1, $exchangeRate);
    }

    // Test for calculateAmountFixed
    public function testCalculateAmountFixed()
    {
        $processor = new Commission(new Client());

        $amountFixed = $processor->calculateAmountFixed(100, 'USD', 1.1);
        $this->assertEqualsWithDelta(90.91, $amountFixed, 0.01);
    }

    // Test for calculateCommission
    public function testCalculateCommission()
    {
        $processor = new Commission(new Client());

        $commissionEu = $processor->calculateCommission(100, true);
        $this->assertEquals(1.00, $commissionEu);

        $commissionNonEu = $processor->calculateCommission(100, false);
        $this->assertEquals(2.00, $commissionNonEu);
    }

    // Test for isEu
    public function testIsEu()
    {
        $processor = new Commission(new Client());

        $isEu = $processor->isEu('DE');
        $this->assertTrue($isEu);

        $isNonEu = $processor->isEu('US');
        $this->assertFalse($isNonEu);
    }
}
