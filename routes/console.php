<?php

use Illuminate\Support\Facades\Schedule;

// Finishes rounds whose timer reached zero even when nobody is browsing.
Schedule::command('competition:tick')->everyMinute()->withoutOverlapping();
