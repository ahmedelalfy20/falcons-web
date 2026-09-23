<?php

namespace App\Events;

use App\Models\Round;
use Illuminate\Foundation\Events\Dispatchable;

class RoundStateChanged
{
    use Dispatchable;

    public function __construct(public Round $round) {}
}
