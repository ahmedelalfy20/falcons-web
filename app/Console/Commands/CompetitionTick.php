<?php

namespace App\Console\Commands;

use App\Enums\RoundStatus;
use App\Models\Round;
use App\Services\RoundService;
use Illuminate\Console\Command;

class CompetitionTick extends Command
{
    protected $signature = 'competition:tick';

    protected $description = 'Finish running rounds whose timer has expired';

    public function handle(RoundService $rounds): int
    {
        $expired = Round::where('status', RoundStatus::Running->value)->where('ends_at', '<=', now())->get();
        foreach ($expired as $round) {
            $rounds->syncExpired($round);
            $this->info("Finished round #{$round->id}");
        }

        return self::SUCCESS;
    }
}
