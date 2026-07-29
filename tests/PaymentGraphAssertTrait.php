<?php

namespace PaymentGraph\Tests;

use PaymentGraph\PaymentGraph;

trait PaymentGraphAssertTrait
{
  protected function assertPaymentGraph(string $expected, PaymentGraph $graph): void
  {
    self::assertEquals($expected, $this->formatGraph($graph));
  }

  private function formatGraph(PaymentGraph $graph): string
  {
    $table = [
      [
        'date',
        'principal',
        'interest',
        'interest debt',
        'adm. fee',
        'adm. fee debt',
        'contract fee',
        'other',
      ]
    ];

    foreach ($graph->getPayments() as $payment) {
      $table[] = [
        $payment->getDate(),
        $this->formatAmount($payment->getPrincipal()),
        $this->formatAmount($payment->getNewInterest()),
        $this->formatAmount($payment->getInterestDebt()),
        $this->formatAmount($payment->getNewAdministrativeFee()),
        $this->formatAmount($payment->getAdministrativeFeeDebt()),
        $this->formatAmount($payment->getContractFee()),
        $this->formatAmount($payment->getOther()),
      ];
    }

    $columnWidths = [];
    $nonEmptyColumns = [];
    foreach ($table as $rowIndex => $row) {
      foreach ($row as $columnIndex => $value) {
        $columnWidths[$columnIndex] = max($columnWidths[$columnIndex] ?? 0, strlen($value));
        if ($rowIndex > 0 && $value !== '') {
          $nonEmptyColumns[$columnIndex] = true;
        }
      }
    }
    $columnWidths = array_filter($columnWidths);

    $lines = [];
    foreach ($table as $row) {
      $parts = [];
      foreach ($row as $columnIndex => $value) {
        if (isset($nonEmptyColumns[$columnIndex])) {
          $parts[] = str_pad($value, $columnWidths[$columnIndex], ' ', STR_PAD_LEFT);
        }
      }
      $lines[] = rtrim(implode(' | ', $parts));
    }

    $lines[] = sprintf('Monthly payment: %.2f', $graph->getMonthlyPayment());
    $lines[] = sprintf('Annual percent: %.4f', $graph->getAnnualPercent());
    if ($graph->getAdministrativeFeePercent() > 0) {
      $lines[] = sprintf('Administrative fee percent: %.5f', $graph->getAdministrativeFeePercent());
    }
    $lines[] = sprintf('APR: %.2f', $graph->getApr());

    return implode("\n", $lines);
  }

  private function formatAmount(float $amount): string
  {
    return $amount !== 0.0 ? sprintf('%.2f', $amount) : '';
  }
}
