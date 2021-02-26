<?php

namespace SVB\Mailing;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Exception;
use SVB\Mailing\Mail\MailInterface;

class MailRepository
{
    private const STATUS_CREATED = 'created';
    private const STATUS_WAIT = 'wait';
    private const STATUS_FAILED = 'failed';
    private const STATUS_SUCCESS = 'success';

    private const FINAL_STATUS = [self::STATUS_SUCCESS, self::STATUS_FAILED];

    /** @var Connection */
    private $connection;

    /** @var array */
    private $mailIdentifierMap = [];

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function setMailIdentifierMap(array $map)
    {
        $this->mailIdentifierMap = $map;
    }

    /**
     * @throws Exception
     */
    public function logMail(MailInterface $mail, string $identifier = '')
    {
        $this->connection->exec(sprintf(
            'INSERT INTO mail (mail_alias, receiver, last_sent, tries, api_identifier, status) VALUES (\'%s\', \'%s\', %s, %d, \'%s\', \'%s\')',
            $mail::getIdentifier(),
            $mail->getRecipient(),
            !empty($identifier) ? 'NOW()' : 'NULL',
            !empty($identifier) ? 1 : 0,
            $identifier,
            !empty($identifier) ? self::STATUS_WAIT : self::STATUS_CREATED
        ));
        $this->connection->exec(sprintf(
            'INSERT INTO mail_data (mail_id, data) VALUES (\'%d\', \'%s\')',
                $this->connection->lastInsertId(),
                json_encode($mail->getData())
        ));
    }

    public function getUnhandledMails(int $limit): array
    {
        $statement = $this->connection->prepare(sprintf(
            'SELECT m.*, md.* FROM mail m LEFT JOIN mail_data md ON md.mail_id = m.id WHERE m.status NOT IN (%s) LIMIT %d',
            implode(',', array_map(function($value) { return '\'' . $value . '\''; }, self::FINAL_STATUS)),
            $limit
        ));
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * @throws Exception
     */
    public function markMailAsSucceeded(int $mailId)
    {
        $this->markMailAs($mailId, self::STATUS_SUCCESS);
    }

    /**
     * @throws Exception
     */
    public function markMailAsFailed(int $mailId)
    {
        $this->markMailAs($mailId, self::STATUS_FAILED);
    }

    /**
     * @throws Exception
     */
    private function markMailAs(int $mailId, string $status)
    {
        $this->connection->exec(sprintf('UPDATE mail SET status = %s WHERE id = %d', $status, $mailId));
    }

    public function cleanupOldData(): void
    {
        $this->connection->exec(sprintf(
            'DELETE mail_data md LEFT JOIN mail m ON m.id = md.mail_id WHERE m.last_sent < \'%s\' AND m.status IN (%s) AND m.last_sent < \'%s\'',
            (new \DateTime('1 year ago'))->format('Y-m-d H:i:s'),
            implode(',', array_map(function($value) { return '\'' . $value . '\''; }, self::FINAL_STATUS)),
            (new \DateTime('3 hours ago'))->format('Y-m-d H:i:s')
        ));
    }

    public function getMailFromDatabaseResult(array $mailRow): ?MailInterface
    {
        if (!array_key_exists($mailRow['mail_alias'], $this->mailIdentifierMap)) {
            return null;
        }

        if (empty($mailRow['data'])) {
            return null;
        }

        $class = $this->mailIdentifierMap[$mailRow['mail_alias']];
        if (!is_subclass_of($class, MailInterface::class)) {
            return null;
        }

        return new $class($mailRow['receiver'], json_decode(utf8_encode($mailRow['data']) ?? [], true));
    }

    /**
     * @throws Exception
     */
    public function increaseMailTries(int $mailId, int $currentTries): void
    {
        $this->connection->exec(sprintf('UPDATE mail SET tries = %d WHERE id = %d', $currentTries + 1, $mailId));
    }

    /**
     * @throws Exception
     */
    public function updateMailApiIdentifier(int $mailId, string $apiIdentifier)
    {
        $this->connection->exec(sprintf('UPDATE mail SET api_identifier = \'%s\' WHERE id = %d', $apiIdentifier, $mailId));
    }
}
