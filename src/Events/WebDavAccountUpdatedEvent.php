<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Events;

use Illuminate\Database\Eloquent\Model;

final class WebDavAccountUpdatedEvent extends WebDavAccountEvent
{
    public const ACTION = 'updated';

    /**
     * Create an event for an updated WebDAV account.
     *
     * @param  Model  $record  Updated WebDAV account model.
     */
    public function __construct(Model $record)
    {
        parent::__construct($record, self::ACTION);
    }
}
