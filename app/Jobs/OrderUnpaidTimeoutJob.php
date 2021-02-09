<?php

namespace App\Jobs;

use App\Services\Orders\OrderService;
use App\Services\SystemService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OrderUnpaidTimeoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $userId;

    private $orderId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $userId, int $orderId)
    {
        $this->userId = $userId;
        $this->orderId = $orderId;

//        $this->delay(
//            now()->addMinutes(
//                intval(SystemService::getInstance()->getOrderUnpaidTimeoutValue())
//            )
//        );
        $this->delay(now()->addSeconds(5));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        OrderService::getInstance()->cancel($this->userId, $this->orderId);
    }
}
