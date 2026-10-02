<?php

namespace App\Exceptions;

use App\Support\Duration;
use RuntimeException;

/** A package can't cover the requested minutes (V1: packages must fully cover a booking). */
class InsufficientPackageBalanceException extends RuntimeException
{
    public function __construct(public readonly int $remainingMinutes)
    {
        parent::__construct(__('app.packages.errors.insufficient', ['remaining' => Duration::plain($remainingMinutes)]));
    }
}
