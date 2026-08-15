<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when finalizing a deal would push an item or package past its quota.
 */
class QuotaExceededException extends RuntimeException {}
