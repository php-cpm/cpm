<?php
include 'vendor/autoload.php';


use RedBeanPHP\R;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;
R::setup( 'sqlite:db/ppm.db' );

$output = new ConsoleOutput();
$style = new OutputFormatterStyle('white', 'black', array('bold'));
if( !isset($argv[1])){
    $output->writeln(Cpm\message::USAGE);
    exit;
}
$q = $argv[1];
$datas = R::findAll( 'repo', ' name LIKE ? order by download_total desc' ,['%'.$q.'%']) ;
$a = array();
foreach($datas as $data){
    $output->getFormatter()->setStyle('bold', $style);
    $output->writeln('<bold>'.$data->name.'</bold>'.' '.$data->description);
    $output->writeln($data->keywords);
}
