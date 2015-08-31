<?php
/**
 *
 *
 * User: zouyi
 * Date: 2015-08-30 09:10
 */
use Webmozart\Console\Config\DefaultApplicationConfig;
use Webmozart\Console\Api\Args\Format\Argument;

class CpmApplicationConfig extends DefaultApplicationConfig
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('Cpm')
            ->setVersion('1.0.0')
            ->beginCommand('search')
                ->addArgument('package', Argument::OPTIONAL, 'Package Name/Description/Keywords')
                ->addOption('limit','l',Argument::OPTIONAL,'limit result numbers',null,'limitValue')
                ->setDescription('Search all packages from packagist.org')
                ->setHandler(new SearchCommandHandler())
            ->end()
            ->beginCommand('download')
            ->addArgument('package', Argument::OPTIONAL, 'Package Name/Description/Keywords')
            ->setDescription('Search all packages from packagist.org')
            ->setHandler(new SearchCommandHandler())
            ->end()
        ;
    }
}