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
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class SVBMailingExtension extends ConfigurableExtension
{
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new MailerConfiguration();
    }

    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $container->setDefinition(
            'svb_mailing.database_connection',
            (new Definition(Connection::class, [['url' => $mergedConfig['database_dsn']]]))
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

        $container->registerForAutoconfiguration(MailInterface::class)->addTag('svb_mailing.mail');

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $taggedServices = $container->findTaggedServiceIds('svb_mailing.mail');

        $map = [];
        foreach ($taggedServices as $id => $tags) {
            $classname = $container->getDefinition($id)->getClass();
            if (class_exists($classname) && is_subclass_of($classname, MailInterface::class)) {
                $map[$classname::getIdentifier()] = $classname;
            }
        }
        $container->getDefinition('svb_mailing.mail_repository')->addMethodCall('setMailIdentifierMap', [$map]);
    }
}
