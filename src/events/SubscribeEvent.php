<?php

namespace aelvan\mailchimpsubscribe\events;

use yii\base\Event;

class SubscribeEvent extends Event
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
}