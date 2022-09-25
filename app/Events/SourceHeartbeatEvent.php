<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Class SourceHeartbeatEvent
 * @package App\Events
 */
class SourceHeartbeatEvent extends Event
{
    public const BAD_HEALTH = 1;
    public const GOOD_HEALTH = 0;

    public int $health;
    public int $status;

    /**
     * SourceHeartbeatEvent constructor.
     * @param int|null $health
     * @param int|null $status
     */
    public function __construct(?int $health, ?int $status)
    {
        $this->health = $health ?? self::BAD_HEALTH;
        $this->status = $status ?? 0;
    }
}
