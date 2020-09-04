<?php

namespace aelvan\mailchimpsubscribe\events;

use yii\base\Event;

class DeleteEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $audienceId;

    /**
     * @var bool
     */
    public $permanent;
}