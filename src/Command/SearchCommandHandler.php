<?php
use Webmozart\Console\Api\Args\Args;
use Webmozart\Console\Api\Command\Command;
use Webmozart\Console\Api\IO\IO;

use RedBeanPHP\R;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;

use Webmozart\Console\UI\Component\Table;

class SearchCommandHandler
{
    public function handle(Args $args, IO $io, Command $command)
    {
        $table = new Table();

        $table->setHeaderRow(array('Name', 'Description'));


        $output = new ConsoleOutput();
        $style = new OutputFormatterStyle('white', 'black', array('bold'));
        if( $args->getArgument('package')==''){
            $output->writeln(Cpm\message::USAGE);
            exit;
        }
        $limit = $args->getOption('limit');
        $limit_str = $limit ? 'limit '.$limit:'';
        $q = $args->getArgument('package');
        $datas = R::findAll( 'repo', ' name LIKE ? order by download_monthly desc,favers desc,download_total desc'.$limit_str ,['%'.$q.'%']) ;

        foreach($datas as $data){
            $output->getFormatter()->setStyle('bold', $style);
//            $output->writeln('<bold>'.$data->name.'</>'.' '.$data->description);
//            $output->writeln($data->keywords);

            $table->addRow(array("(".$data->favers.")".$data->name, $data->description));
        }

        $table->render($io);
        return 0;
    }
}