<?php

namespace App\Modules\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class AsyncEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected object $event;
    public int $tries = 3;
    public int $maxExceptions = 3;
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(object $event)
    {
        $this->event = $event;
        $this->onQueue(config('core.events.queue_connection', 'database'));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Event::dispatch($this->event);
            
            Log::info('Async event processed successfully', [
                'event' => get_class($this->event),
                'attempt' => $this->attempts(),
            ]);
        } catch (\Exception $e) {
            Log::error('Async event processing failed', [
                'event' => get_class($this->event),
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Async event job failed permanently', [
            'event' => get_class($this->event),
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        $delay = config('core.events.retry_delay', 5);
        return [$delay, $delay * 2, $delay * 4];
    }
}
