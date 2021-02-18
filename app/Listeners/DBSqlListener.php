<?php

namespace App\Listeners;

use DateTime;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;

class DBSqlListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(QueryExecuted $event)
    {
        if (!app()->environment(['testing', 'local'])) {
            return;
        }

        $sql = $event->sql;
        $bindings = $event->bindings;
        $time = $event->time;

        $bindings = array_map(function ($binding) {
            if (is_string($binding)) {
                return "'$binding'";
            } else {
                if ($binding instanceof DateTime) {
                    return $binding->format("'Y-m-d H:i:s'");
                }
            }

            return $binding;
        }, $bindings);

        $sql = str_replace('?', '%s', $sql);
        $sql = sprintf($sql, ...$bindings);

        Log::info('SQL LOG ------', compact('sql', 'time'));

        if (app()->environment(['testing'])) {
            echo $sql.PHP_EOL;
        }
    }
}
