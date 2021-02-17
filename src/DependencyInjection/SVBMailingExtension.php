<?php

namespace SVB\Mailing\DependencyInjection;

use Mailjet\Client;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class SVBMailingExtension extends ConfigurableExtension
{
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new MailerConfiguration();
    }

    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $container->setDefinition('svb_mailer.mailjet_client', new Definition(
            Client::class,
            [
                $mergedConfig['mailjet_api_key'] ?? '',
                $mergedConfig['mailjet_api_secret'] ?? '',
                true,
                ['version' => 'v3.1']
            ]
        ));

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
