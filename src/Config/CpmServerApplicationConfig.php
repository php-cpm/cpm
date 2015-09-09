<?php
/**
 *
 *
 * User: zouyi
 * Date: 2015-08-30 09:10
 */
use Webmozart\Console\Config\DefaultApplicationConfig;
use Webmozart\Console\Api\Args\Format\Argument;

class CpmServerApplicationConfig extends DefaultApplicationConfig
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('Cpm-Server')
            ->setVersion('1.0.0')
            ->beginCommand('build')
                ->setDescription('download metadata from packagist.org')
                ->setHandler(new BuildCommandHandler())
            ->end()
            ->beginCommand('download')
            ->setDescription('download two from packagist.org')
            ->setHandler(new DownloadCommandHandler())
            ->end()
        ;
    }
}