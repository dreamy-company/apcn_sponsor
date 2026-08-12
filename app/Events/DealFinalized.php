<?php

namespace App\Events;

use App\Models\Deal;
use Illuminate\Foundation\Events\Dispatchable;

class DealFinalized
{
    use Dispatchable;

    public function __construct(public readonly Deal $deal) {}
}
