<?php
namespace SVB\Mailing;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use SVB\Mailing\Connector\ConnectorInterface;
use SVB\Mailing\Exception\ConnectorNotFoundException;
use SVB\Mailing\Exception\MailDataInvalidException;
use SVB\Mailing\Exception\MailingException;
use SVB\Mailing\Mail\MailInterface;
use SVB\Mailing\Repository\MailRepository;

/**
 * @internal
 * @covers \SVB\Mailing\Mailer
 */
class MailerTest extends MockeryTestCase
{
    public function testInitialization()
    {
        $mailer = new Mailer([], M::mock(MailRepository::class));

        $this->assertInstanceOf(Mailer::class, $mailer);
    }

    public function testSendMailThrowsExceptionWhenMailIsInvalid()
    {
        $mailMock = M::mock(MailInterface::class);

        $mailMock->shouldReceive('valid')->withNoArgs()->once()->andReturnFalse();

        $mailer = new Mailer([], M::mock(MailRepository::class));

        $this->expectException(MailDataInvalidException::class);
        $this->expectExceptionMessage('Mail data validation failed, no error specified!');

        $mailer->sendMail($mailMock);
    }

    public function testGetConnectorThrowsExceptionWhenConnectorUnavailable()
    {
        $mailMock = M::mock(MailInterface::class);

        $mailMock->shouldReceive('valid')->withNoArgs()->once()->andReturnTrue();
        $mailMock->shouldReceive('getConnector')->withNoArgs()->once()->andReturn(ConnectorInterface::class);

        $mailer = new Mailer([], M::mock(MailRepository::class));

        $this->expectException(ConnectorNotFoundException::class);
        $this->expectExceptionMessage('Connector SVB\Mailing\Connector\ConnectorInterface not found');

        $mailer->sendMail($mailMock);
    }

    public function testSendSpooledMailOnlyLogsMailInDatabase()
    {
        $mailMock = M::mock(MailInterface::class);
        $connectorMock = M::mock(ConnectorInterface::class);
        $mailRepositoryMock = M::mock(MailRepository::class);

        $mailMock->shouldReceive('valid')->withNoArgs()->once()->andReturnTrue();
        $mailMock->shouldReceive('getConnector')->withNoArgs()->once()->andReturn(get_class($connectorMock));
        $mailRepositoryMock->shouldReceive('logMail')->with($mailMock)->once()->andReturnNull();

        $mailer = new Mailer([$connectorMock], $mailRepositoryMock);

        $mailer->sendMail($mailMock, true);
    }

    public function testFailedSendOfUnspooledMailLogsMailAsNoRequestHasHappenedInDatabase()
    {
        $mailMock = M::mock(MailInterface::class);
        $connectorMock = M::mock(ConnectorInterface::class);
        $mailRepositoryMock = M::mock(MailRepository::class);

        $mailMock->shouldReceive('valid')->withNoArgs()->once()->andReturnTrue();
        $mailMock->shouldReceive('getConnector')->withNoArgs()->once()->andReturn(get_class($connectorMock));
        $connectorMock->shouldReceive('sendMail')->with($mailMock)->once()->andThrow(TestMailingException::class);
        $mailRepositoryMock->shouldReceive('logMail')->with($mailMock)->once()->andReturnNull();


        $mailer = new Mailer([$connectorMock], $mailRepositoryMock);

        $mailer->sendMail($mailMock);
    }

    public function testSuccessfulSendOfUnspooledMailWithoutMessageIdentifierDoesNotLogMessageInDatabase()
    {
        $mailMock = M::mock(MailInterface::class);
        $connectorMock = M::mock(ConnectorInterface::class);
        $mailRepositoryMock = M::mock(MailRepository::class);

        $mailMock->shouldReceive('valid')->withNoArgs()->once()->andReturnTrue();
        $mailMock->shouldReceive('getConnector')->withNoArgs()->once()->andReturn(get_class($connectorMock));
        $connectorMock->shouldReceive('sendMail')->with($mailMock)->once()->andReturn('');

        $mailer = new Mailer([$connectorMock], $mailRepositoryMock);

        $mailer->sendMail($mailMock);
    }

    public function testSuccessfulSendOfUnspooledMailWithMessageIdentifierLogsMessageInDatabaseWithIdentifier()
    {
        $testMessageIdentifier = 'Message-1';

        $mailMock = M::mock(MailInterface::class);
        $connectorMock = M::mock(ConnectorInterface::class);
        $mailRepositoryMock = M::mock(MailRepository::class);

        $mailMock->shouldReceive('valid')->withNoArgs()->once()->andReturnTrue();
        $mailMock->shouldReceive('getConnector')->withNoArgs()->once()->andReturn(get_class($connectorMock));
        $connectorMock->shouldReceive('sendMail')->with($mailMock)->once()->andReturn($testMessageIdentifier);
        $mailRepositoryMock->shouldReceive('logMail')->with($mailMock, $testMessageIdentifier)->once()->andReturnNull();

        $mailer = new Mailer([$connectorMock], $mailRepositoryMock);

        $mailer->sendMail($mailMock);
    }
}

class TestMailingException extends MailingException
{
}
