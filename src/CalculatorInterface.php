<?php

namespace PaymentGraph\Util;

interface CalculatorInterface
{
    public function calculate(Parameters $params): PaymentGraph;
}
