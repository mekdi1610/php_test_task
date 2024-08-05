# Introduction

This project is a test task. It calculates commissions for transactions based on the bin country and exchange rates. The application reads transaction data from a file (input.txt), determines the country of the bin, converts the transaction amount based on exchange rates, and calculates the commission.

## Requirements

- PHP 7.4 or higher
- Composer
- PHPUnit
- GuzzleHTTP

## Installation

1. Clone the repository:
   git clone https://github.com/your-username/commission-calculation.git
   cd commission-calculation

2. Install dependencies using Composer:
   composer install

## Usage
1. Run the main code: To run the commission calculation, use the following command:
php src/Commission.php input.txt

## Tests
Unit tests are written using PHPUnit. To run the tests, use the following command:
./vendor/bin/phpunit .\tests\CommissionTest.php
