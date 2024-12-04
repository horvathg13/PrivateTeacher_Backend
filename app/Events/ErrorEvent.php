<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ErrorEvent
{
    use Dispatchable;

    public function __construct(public $user, public $procedureName, public $status, public $message, public $debugBacktrace)
    {
    }
}
