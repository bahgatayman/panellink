<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown inside a shared-session-open or check-in transaction when the
 * post-write capacity re-check finds the room over capacity, so the
 * caller's DB::transaction() rolls back automatically. The SharedSession
 * counterpart to BookingCapacityExceededException — kept distinct since it
 * signals a different subsystem (live occupancy) found the problem.
 */
class SharedSessionCapacityExceededException extends RuntimeException {}
