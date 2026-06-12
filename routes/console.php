<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('monitor:check')->everyMinute();
Schedule::command('monitor:ssl-check')->twiceDaily(8, 20);
Schedule::command('api:health-check')->everyFiveMinutes();
