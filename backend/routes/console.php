<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Scheduled publishing. A page dated in the future stays out of the public
 * queries until this command promotes it, so the site does not depend on
 * evaluating a publish date on every request.
 *
 * Locally:     php artisan schedule:work
 * On a server: * * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
 */
Schedule::command('pages:publish-due')
    ->everyMinute()
    ->withoutOverlapping();
