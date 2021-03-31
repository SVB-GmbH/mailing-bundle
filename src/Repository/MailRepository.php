<?php

namespace SVB\Mailing\Repository;

use DateTime;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Exception;
use SVB\Mailing\Mail\MailInterface;

class MailRepository
{
    private const STATUS_CREATED = 'created';
    private const STATUS_WAIT = 'wait';
    private const STATUS_FAILED = 'failed';
    private const STATUS_SUCCESS = 'success';

    public const DEFAULT_TABLE_NAME_MAIN = 'svb_mails';
    public const DEFAULT_TABLE_NAME_DATA = 'svb_mails_data';

    private const FINAL_STATUS = [self::STATUS_SUCCESS, self::STATUS_FAILED];

    /** @var Connection */
    private $connection;

    /** @var array */
    private $mailIdentifierMap = [];

    /** @var string */
    private $mainTableName = self::DEFAULT_TABLE_NAME_MAIN;

    /** @var string */
    private $dataTableName = self::DEFAULT_TABLE_NAME_DATA;

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function setTables(string $mainTableName, string $dataTableName)
    {
        $this->mainTableName = $mainTableName;
        $this->dataTableName = $dataTableName;
    }

    public function setMailIdentifierMap(array $map)
    {
        $this->mailIdentifierMap = $map;
    }

    public function getMailIdentifierMap(): array
    {
        return $this->mailIdentifierMap;
    }

    /**
     * @throws Exception
     */
    public function logMail(MailInterface $mail, string $apiIdentifier = '')
    {
        $this->connection->exec(sprintf(
            'INSERT INTO %s (mail_alias, receiver, last_sent, tries, api_identifier, status, locale, identifier) VALUES (\'%s\', \'%s\', %s, %d, \'%s\', \'%s\', \'%s\', \'%s\')',
            $this->mainTableName,
            $mail::getTemplateAlias(),
            $mail->getRecipient(),
            !empty($apiIdentifier) ? 'NOW()' : 'NULL',
            !empty($apiIdentifier) ? 1 : 0,
            $apiIdentifier,
            !empty($apiIdentifier) ? self::STATUS_WAIT : self::STATUS_CREATED,
            $mail->getLocale(),
            $mail->getIdentifier()
        ));
        $this->connection->exec(sprintf(
            'INSERT INTO %s (mail_id, data) VALUES (\'%d\', \'%s\')',
                $this->dataTableName,
                $this->connection->lastInsertId(),
                json_encode($mail->getData())
        ));
    }

    public function getUnhandledMails(int $limit): array
    {
        $statement = $this->connection->prepare(sprintf(
            'SELECT m.*, md.data FROM %s m LEFT JOIN %s md ON md.mail_id = m.id WHERE m.status NOT IN (%s) LIMIT %d',
            $this->mainTableName,
            $this->dataTableName,
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
        $this->connection->exec(sprintf(
            'UPDATE %s SET status = \'%s\' WHERE id = %d',
            $this->mainTableName,
            $status,
            $mailId
        ));
    }

    public function cleanupOldData(): void
    {
        $this->connection->exec(sprintf(
            'DELETE md FROM %s md JOIN %s m ON m.id = md.mail_id WHERE m.last_sent < \'%s\' AND m.status IN (%s) AND m.last_sent < \'%s\'',
            $this->dataTableName,
            $this->mainTableName,
            (new DateTime('1 year ago'))->format('Y-m-d H:i:s'),
            implode(',', array_map(function($value) { return '\'' . $value . '\''; }, self::FINAL_STATUS)),
            (new DateTime('3 hours ago'))->format('Y-m-d H:i:s')
        ));
    }

    public function getMailFromDatabaseResult(array $mailRow): ?MailInterface
    {
        return $this->constructMailObject($mailRow['mail_alias'], $mailRow['data'], $mailRow['receiver'], $mailRow['locale'], $mailRow['identifier']);
    }

    public function constructMailObject(string $mailIdentifier, string $jsonData, string $receiver, string $locale, string $identifier): ?MailInterface
    {
        if (!array_key_exists($mailIdentifier, $this->mailIdentifierMap)) {
            return null;
        }

        if (empty($jsonData) || empty($locale)) {
            return null;
        }

        $data = json_decode(utf8_encode($jsonData), true);
        if (!is_array($data)) {
            return null;
        }

        $class = $this->mailIdentifierMap[$mailIdentifier];
        if (!is_subclass_of($class, MailInterface::class)) {
            return null;
        }

        return new $class($receiver, $data, $locale, $identifier);
    }

    /**
     * @throws Exception
     */
    public function increaseMailTries(int $mailId, int $currentTries): void
    {
        $this->connection->exec(sprintf(
            'UPDATE %s SET tries = %d WHERE id = %d',
            $this->mainTableName,
            $currentTries + 1,
            $mailId
        ));
    }

    /**
     * @throws Exception
     */
    public function updateMailApiIdentifier(int $mailId, string $apiIdentifier)
    {
        $this->connection->exec(sprintf(
            'UPDATE %s SET api_identifier = \'%s\' WHERE id = %d',
            $this->mainTableName,
            $apiIdentifier,
            $mailId
        ));
    }
}
