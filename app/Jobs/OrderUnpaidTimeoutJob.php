<?php

namespace App\Jobs;

use App\Exceptions\BusinessException;
use App\Services\Orders\OrderService;
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

        $delayTime = now()->addSeconds(5);
//        $delayTime = now()->addMinutes(
//            intval(SystemService::getInstance()->getOrderUnpaidTimeoutValue())
//        );
        $this->delay($delayTime);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            OrderService::getInstance()->systemCancel($this->userId, $this->orderId);
        } catch (BusinessException $e) {
        }
    }
}
