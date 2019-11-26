<?php

namespace Tnt\Giftcard\Events;

use Oak\Dispatcher\Event;
use Tnt\Giftcard\Model\Giftcard;

abstract class GiftcardEvent extends Event
{
    /**
     * @var Giftcard $giftcard
     */
    private $giftcard;

    /**
     * GiftcardEvent constructor.
     * @param Giftcard $giftcard
     */
    public function __construct(Giftcard $giftcard)
    {
        $this->giftcard = $giftcard;
    }

    /**
     * @return Giftcard
     */
    public function getGiftcard(): Giftcard
    {
        return $this->giftcard;
    }
}