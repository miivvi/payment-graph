<?php

namespace PaymentGraph\Calculator;

use PaymentGraph\Exception\MonthlyPaymentTooLargeException;
use PaymentGraph\Exception\MonthlyPaymentTooSmallException;
use PaymentGraph\Exception\PaymentGraphException;
use PaymentGraph\Parameters;
use PaymentGraph\PaymentGraph;
use PaymentGraph\Util\EdgeFinder;

class AnnualPercentCalculator implements CalculatorInterface
{
  public function __construct(private CalculatorInterface $realCalculator)
  {
  }

  public function calculate(Parameters $params): PaymentGraph
  {
    if ($params->getAnnualPercent() > 0) {
      throw new \InvalidArgumentException('Annual percent is already specified');
    }

    $edgeFinder = new EdgeFinder(0, 100, $this->guessStartValue($params), 4);
    $internalParams = clone $params;
    $internalParams->setApr(round($internalParams->getApr(), 2));

    while ($edgeFinder->isValid()) {
      $annualPercent = $edgeFinder->getCurrent();
      $internalParams->setAnnualPercent($annualPercent);

      try {
        $graph = $this->realCalculator->calculate($internalParams);

        if ($internalParams->getApr() && $graph->isAprGreaterThan($internalParams->getApr())) {
          $edgeFinder->toLeft($graph);
        } else {
          $edgeFinder->toRight($graph);
        }
      } catch (MonthlyPaymentTooSmallException) {
        $edgeFinder->toLeft();
      } catch (MonthlyPaymentTooLargeException) {
        $edgeFinder->toRight();
      }
    }

    $result = $edgeFinder->getLeftEdgePayload();
    if ($result === null) {
      throw match (true) {
        $edgeFinder->isAtLeftBound()  => MonthlyPaymentTooSmallException::create($params),
        $edgeFinder->isAtRightBound() => MonthlyPaymentTooLargeException::create($params),
        default                       => PaymentGraphException::create($params, 'Cannot create schedule'),
      };
    }

    return $result;
  }

  private function guessStartValue(Parameters $params): float
  {
    $aprCoefficient = 1 + $params->getApr() / 100;

    return round(100 * 12 * ($aprCoefficient ** (30 / 365) - 1), 4);
  }
}
