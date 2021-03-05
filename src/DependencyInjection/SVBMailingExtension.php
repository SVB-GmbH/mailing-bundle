<?php

namespace SVB\Mailing\DependencyInjection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Mailjet\Client;
use SVB\Mailing\Mail\MailInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class SVBMailingExtension extends ConfigurableExtension
{
    public const MAIL_IDENTIFIERS_PARAMETER_NAME = 'svb_mailing.mail_identifiers';

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new MailerConfiguration();
    }

    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $container->setParameter(self::MAIL_IDENTIFIERS_PARAMETER_NAME, []);
        $container->setParameter('svb_mailing.tries_count', $mergedConfig['tries_count']);
        $container->setParameter('svb_mailing.database.table_main', $mergedConfig['database']['table_main']);
        $container->setParameter('svb_mailing.database.table_data', $mergedConfig['database']['table_data']);
        $container->setDefinition(
            'svb_mailing.database_connection',
            (new Definition(Connection::class, [['url' => $mergedConfig['database']['url']]]))
                ->setFactory([DriverManager::class, 'getConnection'])
        );
        $container->setDefinition('svb_mailing.mailjet_client.v31', new Definition(
            Client::class,
            [
                $mergedConfig['connectors']['mailjet']['mailjet_api_key'] ?? '',
                $mergedConfig['connectors']['mailjet']['mailjet_api_secret'] ?? '',
                true,
                ['version' => 'v3.1']
            ]
        ));
        $container->setDefinition('svb_mailing.mailjet_client.v30', new Definition(
            Client::class,
            [
                $mergedConfig['connectors']['mailjet']['mailjet_api_key'] ?? '',
                $mergedConfig['connectors']['mailjet']['mailjet_api_secret'] ?? '',
                true,
                ['version' => 'v3']
            ]
        ));

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
