<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;

class Commission
{
    public $binListUrl = 'https://lookup.binlist.net/';
    public $exchangeRateUrl = 'https://api.exchangeratesapi.io/latest';
    public $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function processTransactions($filePath)
    {
        try {
            $transactions = $this->parseTransactions($filePath);

            foreach ($transactions as $transaction) {
                $bin = $transaction['bin'];
                $amount = $transaction['amount'];
                $currency = $transaction['currency'];

                $isEu = $this->isEu($this->getCountryCode($bin));
                $rate = $this->getExchangeRate($currency);

                $amntFixed = $this->calculateAmountFixed($amount, $currency, $rate);
                $commission = $this->calculateCommission($amntFixed, $isEu);

                echo number_format($commission, 2, '.', '') . "\n";
            }
        } catch (\Exception $e) {
            echo "Error processing transactions: " . $e->getMessage() . "\n";
        }
    }

    public function parseTransactions($filePath)
    {
        $transactions = [];
        try {
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new \Exception("Failed to read file: $filePath");
            }

            foreach (explode("\n", $content) as $row) {
                if (empty($row)) {
                    continue;
                }
                $transactions[] = json_decode($row, true);
            }
        } catch (\Exception $e) {
            echo "Error parsing transactions: " . $e->getMessage() . "\n";
        }
        return $transactions;
    }

    public function getCountryCode($bin)
    {
        try {
            $response = $this->client->get($this->binListUrl . $bin);
            $data = json_decode($response->getBody(), true);
            return $data['country']['alpha2'] ?? 'UNKNOWN';
        } catch (\Exception $e) {
            return 'UNKNOWN';
        }
    }

    public function getExchangeRate($currency)
    {
        try {
            $response = $this->client->get($this->exchangeRateUrl);
            $data = json_decode($response->getBody(), true);
            return $data['rates'][$currency] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function calculateAmountFixed($amount, $currency, $rate)
    {
        try {
            if ($currency === 'EUR' || $rate == 0) {
                return $amount;
            }
            return $amount / $rate;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function calculateCommission($amount, $isEu)
    {
        try {
            $rate = $isEu ? 0.01 : 0.02;
            return ceil($amount * $rate * 100) / 100;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function isEu($countryCode)
    {
        $euCountries = [
            'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR',
            'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL',
            'PT', 'RO', 'SE', 'SI', 'SK'
        ];
        return in_array($countryCode, $euCountries);
    }
}

if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $client = new Client(['verify' => false]);
    $processor = new Commission($client);
    $processor->processTransactions($argv[1]);
}
