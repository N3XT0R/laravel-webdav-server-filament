<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Events;

use Illuminate\Database\Eloquent\Model;

final class WebDavAccountDeletedEvent extends WebDavAccountEvent
{
    public const ACTION = 'deleted';

    /**
     * Create an event for a deleted WebDAV account.
     *
     * @param  Model  $record  Deleted WebDAV account model.
     */
    public function __construct(Model $record)
    {
        parent::__construct($record, self::ACTION);
    }
}
