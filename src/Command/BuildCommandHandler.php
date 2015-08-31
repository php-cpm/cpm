<?php
use Webmozart\Console\Api\Args\Args;
use Webmozart\Console\Api\Command\Command;
use Webmozart\Console\Api\IO\IO;

use RedBeanPHP\R;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;

use Webmozart\Console\UI\Component\Table;

define('USE_PROXY',1);
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Console\Helper\ProgressBar;

date_default_timezone_set('PRC');
class BuildCommandHandler
{
    public $packagist = 'http://packagist.phpcomposer.com';//https://packagist.org
    public $packages_json = '/packages.json';
    public $package_json = "/p/%package%$%hash%.json";
    public $repository_path = "packagist";
    public function handle(Args $args, IO $io, Command $command)
    {

        $limit_array = $this->getLimitList() ? $this->getLimitList(): $this->rebuildLimitArray();
//        echo count($all_array)."\r\n";
//        echo count($limit_array);
        $this->doDownLoad($limit_array);
        $this->buildNewPackagesJson();
        return 0;
    }
    public function buildNewPackagesJson(){
        $limit_json = file_get_contents($this->repository_path.'/p/limit.json');
        $data['sha256'] = hash('sha256',$limit_json);
        $package_json = file_get_contents($this->repository_path . "/packages-packagist.json");
        $package = json_decode($package_json,TRUE);

        $package['provider-url'] = '/packages/%package%.json';
        $package['provider-includes'] = array();
        $package['provider-includes']['p/limit.json'] = $data;
        $fs = new Filesystem();
        $fs->dumpFile($this->repository_path.$this->packages_json,json_encode($package));

    }
    public function doDownLoad($limit_array){
        $output = new ConsoleOutput();
//        $bar = new ProgressBar($output,count($limit_array));
//        $bar->start();
//        $bar->setBarWidth(100);
          $i = 0;
          $count = count($limit_array);
//        $bar->setFormat('debug');
        foreach($limit_array as $file => $data){
            $url = $this->packagist."/p/".$file.'$'.$data['sha256'].".json";
            $path = $this->repository_path."/packages/".$file.".json";
            $fs = new Filesystem();
            if($fs->exists($path)){
                if(hash('sha256',file_get_contents($path)) == $data['sha256'] ){

                    $i++;
                    $output->writeln($i.'/'.$count.' skip');
                    continue;
                }else{

                $output->writeln('try again :'.$file);
                }

//                $bar->advance();
            }
            $fetch_data = HttpSend($url);
            $data = $fetch_data->raw_body;
            if($fetch_data->code =='404'){
                $output->writeln('no file :'.$file);
//                $bar->advance();
                continue;
            }
            $i++;
            $output->writeln($i.'/'.$count);

            $fs->dumpFile($path,$data);

//            $bar->advance();

        }

//        $bar->finish();
        $output->writeln('');


    }
    public function rebuildLimitArray(){
        $all_array = $this->getProviderArray();
        $limit_array = $this->getPackagesFromdb();
        $limit_array = $this->mkFileList($limit_array,$all_array);
        return $limit_array;
    }
    public function mkFileList($limit_array,$all_array){
        foreach($limit_array as $key => $al){
            $key = strtolower($key);
            $limit_array[$key] = $all_array[$key];
        }
        $fs = new Filesystem();
        $fs->dumpFile($this->repository_path.'/p/limit.json',json_encode($limit_array));
        return $limit_array;
    }
    public function getLimitList(){
        if(!file_exists($this->repository_path.'/p/limit.json')){
            return false;
        }
        $json = file_get_contents($this->repository_path.'/p/limit.json');
        return json_decode($json,TRUE);
    }
    public function downloadPackagesJson(){

        $result = HttpSendLong($this->packagist . $this->packages_json);
        $json = $result->raw_body;
        $provider = json_decode($json, TRUE);
        $provider['fetch_packagist_time'] = time();
        $provider['fetch_packagist_date'] = date("Y-m-d H:i:s");
        $provider_includes = $provider['provider-includes'];
//        $provider['provider-includes'] = array();
//        foreach ($provider_includes as $file_name => $data) {
//            $provider['provider-includes'][str_replace("$%hash%", '', $file_name)] = $data;
//        }
        $json = json_encode($provider);
        file_put_contents($this->repository_path . "/packages-packagist.json", $json);
    }
    public function buildProvidersJson(){
        $providers = $this->getProviderFile();
        foreach ($providers as $file) {
            $provider_file_path = $this->repository_path."/" .$file;

            $json_url = $this->packagist . "/" . $file;
            $json = HttpSendLong($json_url);
            $fs = new Filesystem();
            $fs->dumpFile($provider_file_path, $json->raw_body);

            echo $file . "  TO  " . $provider_file_path . "\r\n";
        }
    }
    public function getProviderArray(){

        $providers = $this->getProviderFile();

        $retArray['providers'] = array();
        foreach ($providers as $file) {

            $provider_file_path = $this->repository_path."/" .$file;

            if(!file_exists($provider_file_path)){
                $this->buildProvidersJson();
            }
            $file = file_get_contents($provider_file_path);
            $array = json_decode($file,TRUE);
            $retArray['providers'] += $array['providers'];
        }
        return $retArray['providers'];
    }
    public function getPackageFile(){

        $retArray = [];
        $provider_includes = $this->getProviderArray();
        foreach ($provider_includes as $file_name => $data) {
            $file = str_replace("%hash%", $data['sha256'], $file_name);
            $retArray[] = $file;

        }
        return $retArray;
    }
    public function getLimitFile(){

        $retArray = array();
        $provider_includes = $this->getLimitList();
        foreach ($provider_includes as $file_name => $data) {
//            if(!isset($data['sha256'])){
//                echo $file_name;
//                continue;
//            }
//            $file = str_replace("%hash%", $data['sha256'], $file_name);
            $file =  'packages/'.$file_name.'$'.$data['sha256'].'.json';
            $retArray[] = $file;

        }
        return $retArray;
    }
    public function getPackagesFromdb(){

       # R::dispense("repo") ;
        $retArray = array();
        $datas = R::find("repo","");
        foreach($datas as $data){

            $retArray[strtolower($data->name)] =array();
        }

        return $retArray;
    }
    public function getProviderIncludes(){
        if(!file_exists($this->repository_path."/packages-packagist.json")){
            $this->downloadPackagesJson();
        }
        $file = file_get_contents($this->repository_path . "/packages-packagist.json");
        $array = json_decode($file,TRUE);
        return $array['provider-includes'];
    }
    public function getProviderFile(){

        $retArray = array();
        $provider_includes = $this->getProviderIncludes();
        foreach ($provider_includes as $file_name => $data) {
            $file = str_replace("%hash%", $data['sha256'], $file_name);
            $retArray[] = $file;

        }
        return $retArray;
    }
}

function HttpSendLong($url)
{
    if (USE_PROXY) {
        echo 1;
        return \Httpful\Request::get($url)->timeout(300)->useProxy('10.199.75.12', '8080')->send();
    }
    return \Httpful\Request::get($url)->timeout(60)->send();
}
function HttpSend($url)
{
    if (USE_PROXY) {
        return \Httpful\Request::get($url)->timeout(15)->useProxy('10.199.75.12', '8080')->send();
    }
    return \Httpful\Request::get($url)->timeout(5)->send();
}