<?php

namespace PaymentGraph\Tests;

use Codeception\Attribute\DataProvider;
use PaymentGraph\Dates\RegularDatesGenerator;
use PaymentGraph\PaymentGraphBuilder;
use PaymentGraph\PaymentGraphLimits;
use Tests\Unit\BaseTest;

class LimitsTest extends BaseTest
{
  use PaymentGraphAssertTrait;

  #[DataProvider('dataProvider')]
  public function testSchedule(PaymentGraphLimits $limits, string $expected): void
  {
    $graph = (new PaymentGraphBuilder())
      ->setAmount(1000)
      ->setStartDate('2025-10-02')
      ->setDates(new RegularDatesGenerator(1, 'month', 10))
      ->setPeriods(6)
      ->setAnnualPercent(20)
      ->setAdministrativeFeePercent(0.03)
      ->setContractFee(50)
      ->setContractFeeDate('2025-10-02')
      ->setInterestByFullAmount(true)
      ->setLimits($limits)
      ->build();

    $this->assertPaymentGraph($expected, $graph);
  }

  public function dataProvider(): iterable
  {
    yield [
      new PaymentGraphLimits(apr: 70, minInterestPercent: 9.9),
      <<<TEXT
              date | principal | interest | adm. fee | contract fee
        2025-10-02 |           |          |          |        50.00
        2025-11-10 |    162.07 |    12.76 |    11.70 |
        2025-12-10 |    167.71 |     9.82 |     9.00 |
        2026-01-10 |    167.09 |    10.14 |     9.30 |
        2026-02-10 |    167.09 |    10.14 |     9.30 |
        2026-03-10 |    168.97 |     9.16 |     8.40 |
        2026-04-10 |    167.07 |    10.14 |     9.30 |
        Monthly payment: 186.53
        Annual percent: 11.7812
        Administrative fee percent: 0.03000
        APR: 69.99
        TEXT
    ];

    yield [
      new PaymentGraphLimits(apr: 50, minInterestPercent: 9.9),
      <<<TEXT
              date | principal | interest | adm. fee | contract fee
        2025-10-02 |           |          |          |        50.00
        2025-11-10 |    163.70 |    10.73 |     5.07 |
        2025-12-10 |    167.35 |     8.25 |     3.90 |
        2026-01-10 |    166.94 |     8.53 |     4.03 |
        2026-02-10 |    166.94 |     8.53 |     4.03 |
        2026-03-10 |    168.16 |     7.70 |     3.64 |
        2026-04-10 |    166.91 |     8.53 |     4.03 |
        Monthly payment: 179.50
        Annual percent: 9.9000
        Administrative fee percent: 0.01300
        APR: 49.86
        TEXT
    ];

    yield [
      new PaymentGraphLimits(apr: 30, minInterestPercent: 9.9),
      <<<TEXT
              date | principal | interest | contract fee
        2025-10-02 |           |          |        30.31
        2025-11-10 |    164.65 |    10.73 |
        2025-12-10 |    167.13 |     8.25 |
        2026-01-10 |    166.85 |     8.53 |
        2026-02-10 |    166.85 |     8.53 |
        2026-03-10 |    167.68 |     7.70 |
        2026-04-10 |    166.84 |     8.53 |
        Monthly payment: 175.38
        Annual percent: 9.9000
        APR: 30.00
        TEXT
    ];

    yield [
      new PaymentGraphLimits(apr: 10, minInterestPercent: 9.9),
      <<<TEXT
              date | principal | interest
        2025-11-10 |    165.50 |     6.22
        2025-12-10 |    166.93 |     4.79
        2026-01-10 |    166.78 |     4.94
        2026-02-10 |    166.78 |     4.94
        2026-03-10 |    167.25 |     4.47
        2026-04-10 |    166.76 |     4.94
        Monthly payment: 171.72
        Annual percent: 5.7425
        APR: 10.00
        TEXT
    ];
  }
}
