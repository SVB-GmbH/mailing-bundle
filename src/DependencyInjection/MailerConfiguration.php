<?php

namespace SVB\Mailing\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class MailerConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('svb_mailing');

        $treeBuilder->getRootNode()
            ->children()
                ->integerNode('tries_count')->defaultValue(5)->end()
                ->arrayNode('database')
                    ->children()
                        ->scalarNode('url')->end()
                        ->scalarNode('table_main')->defaultValue('svb_mails')->end()
                        ->scalarNode('table_data')->defaultValue('svb_mails_data')->end()
                    ->end()
                ->end()
                ->arrayNode('connectors')
                ->children()
                    ->arrayNode('mailjet')
                        ->children()
                            ->scalarNode('mailjet_api_key')->end()
                            ->scalarNode('mailjet_api_secret')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
