<?php

namespace App\Console\Commands;

use App\Models\Round;
use App\Services\ScoreService;
use Illuminate\Console\Command;

class RecalculateScores extends Command
{
    protected $signature = 'competition:recalculate-scores {round? : Round id (defaults to all rounds)}';

    protected $description = 'Rebuild cached leader scores from accepted registrations (the source of truth)';

    public function handle(ScoreService $scores): int
    {
        $rounds = $this->argument('round') ? Round::whereKey($this->argument('round'))->get() : Round::all();
        foreach ($rounds as $round) {
            $n = $scores->recalculateRound($round);
            $this->info("Round #{$round->id}: recalculated {$n} leaders");
        }

        return self::SUCCESS;
    }
}
