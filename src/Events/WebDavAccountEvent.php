<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;

abstract class WebDavAccountEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a WebDAV account event for listeners interested in account lifecycle changes.
     *
     * @param  Model  $record  WebDAV account model affected by the lifecycle action.
     * @param  string  $action  Lifecycle action represented by the concrete event.
     */
    public function __construct(
        public readonly Model $record,
        public readonly string $action,
    ) {
    }

    /**
     * Dispatch the concrete event and the base event channel for generic lifecycle listeners.
     */
    public function dispatchForListeners(): void
    {
        Event::dispatch($this);
        Event::dispatch(self::class, [$this]);
    }
}
