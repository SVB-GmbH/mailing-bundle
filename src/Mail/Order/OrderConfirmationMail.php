<?php
namespace SVB\Mailing\Mail\Order;

use SVB\Mailing\Mail\AbstractMail;

class OrderConfirmationMail extends AbstractMail
{
    public static function getTemplateId(): int
    {
        return 2419829;
    }

    public function valid(): bool
    {
        return true;
    }
}
