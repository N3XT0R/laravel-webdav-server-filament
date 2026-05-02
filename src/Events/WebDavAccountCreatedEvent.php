<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Events;

use Illuminate\Database\Eloquent\Model;

final class WebDavAccountCreatedEvent extends WebDavAccountEvent
{
    public const ACTION = 'created';

    /**
     * Create an event for a newly persisted WebDAV account.
     *
     * @param  Model  $record  Created WebDAV account model.
     */
    public function __construct(Model $record)
    {
        parent::__construct($record, self::ACTION);
    }
}
