<?php

namespace PaymentGraph\Util;

class EdgeFinder
{
  private const
    TO_LEFT = -1,
    TO_RIGHT = 1;

  private \Generator $generator;
  private mixed $leftEdgePayload = null;
  private mixed $rightEdgePayload = null;

  public function __construct(
    private float $min,
    private float $max,
    private float $startValue,
    private int $precision,
  ) {
    $this->generator = $this->createGenerator();
  }

  public function getCurrent(): ?float
  {
    return $this->generator->current();
  }

  public function isValid(): bool
  {
    return $this->generator->valid();
  }

  public function toLeft(mixed $payload = null): void
  {
    $this->rightEdgePayload = $payload;
    $this->generator->send(self::TO_LEFT);
  }

  public function toRight(mixed $payload = null): void
  {
    $this->leftEdgePayload = $payload;
    $this->generator->send(self::TO_RIGHT);
  }

  public function isAtLeftBound(): bool
  {
    return $this->generator->getReturn()[0] === $this->min;
  }

  public function isAtRightBound(): bool
  {
    return $this->generator->getReturn()[1] === $this->max;
  }

  public function getLeftEdgePayload(): mixed
  {
    return $this->leftEdgePayload;
  }

  public function getRightEdgePayload(): mixed
  {
    return $this->rightEdgePayload;
  }

  private function createGenerator(): \Generator
  {
    $findBounds = $this->findBounds($this->min, $this->max, $this->startValue);
    yield from $findBounds;

    [$left, $right] = $findBounds->getReturn();
    $binarySearch = $this->binarySearch($left, $right);
    yield from $binarySearch;

    return $binarySearch->getReturn();
  }

  private function findBounds(float $min, float $max, float $startValue): \Generator
  {
    $left = $right = null;
    $nextValue = $startValue;
    $step = 16;

    while (true) {
      $response = yield $nextValue;
      if ($response === self::TO_LEFT) {
        $right = $nextValue;
      } else {
        $left = $nextValue;
      }

      if ($left === null) {
        $nextValue = round($startValue - $step * 10 ** -$this->precision, $this->precision);
        if ($nextValue < $min) {
          $left = $min;
        }
      } elseif ($right === null) {
        $nextValue = round($startValue + $step * 10 ** -$this->precision, $this->precision);
        if ($nextValue > $max) {
          $right = $max;
        }
      }

      if ($left !== null && $right !== null) {
        break;
      }

      $step *= 16;
    }

    return [$left, $right];
  }

  private function binarySearch(float $left, float $right): \Generator
  {
    while ($right - $left > 1.5 * 10 ** -$this->precision) {
      $nextValue = round(($left + $right) / 2 - 0.25 * 10 ** -$this->precision, $this->precision);
      $response = yield $nextValue;
      if ($response === self::TO_LEFT) {
        $right = $nextValue;
      } else {
        $left = $nextValue;
      }
    }

    return [$left, $right];
  }
}
