<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a deal is finalized while its payment terms do not sum to the
 * agreed final price (BR-08). Drafts may be unbalanced; finalized deals may not.
 */
class UnbalancedPaymentTermsException extends RuntimeException {}
