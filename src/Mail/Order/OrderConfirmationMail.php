<?php
namespace SVB\Mailing\Mail\Order;

use SVB\Mailing\Connector\MailjetConnector;
use SVB\Mailing\Exception\MailingException;
use SVB\Mailing\Mail\AbstractMail;

class OrderConfirmationMail extends AbstractMail
{
    public static function getIdentifier(): string
    {
        return 'order_confirmation';
    }

    public static function getTemplateId(string $languageIso2 = 'en'): int
    {
        return 2499087;#2431962;
    }

    /**
     * @throws MailingException
     */
    public function valid(): bool
    {
        if (!array_key_exists('order', $this->getData()) || !is_array($this->getData()['order']) || empty($this->getData()['order'])) {
            throw new MailingException('The "order" data has to be an array containing order items.');
        }

        return true;
    }

    public static function getConnector(): string
    {
        return MailjetConnector::class;
    }
}
