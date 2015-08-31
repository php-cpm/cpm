<?php
use Webmozart\Console\Api\Args\Args;
use Webmozart\Console\Api\Command\Command;
use Webmozart\Console\Api\IO\IO;

use RedBeanPHP\R;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;

use Webmozart\Console\UI\Component\Table;

class InstallCommandHandler
{
    public function handle(Args $args, IO $io, Command $command)
    {
        $output = new ConsoleOutput();
        $style = new OutputFormatterStyle('white', 'black', array('bold'));
        if( $args->getArgument('package')==''){
            $output->writeln(Cpm\message::USAGE);
            exit;
        }
        if(!file_exists('composer.json')){
            $output->writeln(Cpm\message::NOComposerJSON);
        }
        $json = file_get_contents('composer.json');
        $array = json_decode($json,TRUE);
        $datas = $array['require'];
        foreach($datas as $data){
            $output->getFormatter()->setStyle('bold', $style);
            $output->writeln('<bold>'.$data->name.'</>'.' '.$data->description);
            $output->writeln($data->keywords);

        }

        return 0;
    }
}