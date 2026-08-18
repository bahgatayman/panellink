<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown inside a booking-creation/update transaction when the post-write
 * capacity re-check finds the room over capacity, so the caller's
 * DB::transaction() rolls back automatically. Not a validation error — this
 * is the defense-in-depth backstop that catches an overbooking even if the
 * upstream row lock didn't serialize the request the way it should have.
 */
class BookingCapacityExceededException extends RuntimeException {}
