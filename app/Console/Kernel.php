<?php
declare(strict_types=1);

namespace App\Console;

use App\Services\Orders\OrderService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->call(function () {
            OrderService::getInstance()->autoConfirm();
        })
            ->dailyAt('3:00') // 每天 3:00 执行
            ->runInBackground() // 后台执行，不阻塞其他人串行任务
            ->name('auto_confirm')
            ->onOneServer(); // 只在一台服务器执行，如果有多台服务器在执行定时任务

        $schedule->call(function () {
            Log::info('test auto_confirm');
        })->everyMinute()->runInBackground()->name('auto_confirm_log')->onOneServer();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
