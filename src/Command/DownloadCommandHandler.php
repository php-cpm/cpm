<?php
use Webmozart\Console\Api\Args\Args;
use Webmozart\Console\Api\Command\Command;
use Webmozart\Console\Api\IO\IO;

use RedBeanPHP\R;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;

use Webmozart\Console\UI\Component\Table;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Console\Helper\ProgressBar;

date_default_timezone_set('PRC');
class DownloadCommandHandler
{
    public $packagist = "https://packagist.org";
    public $packages_json = '/packages.json';
    public $package_json = "/p/%package%$%hash%.json";
    public $repository_path = "packagist";
    public $server_url="http://10.1.169.16:12801/";
    public function handle(Args $args, IO $io, Command $command)
    {
        /**
         * test
         */
        /**
         * 1.初始化
         */
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
        $package['mirrors'] = array();
        $package['mirrors'][] = array(
            'dist-url'=>$this->server_url."files/%package%/%reference%.%type%",
            'preferred'=>true
        );
        $package['providers-url'] = '/packages/%package%.json';
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
//            $package = $this->repository_path."/packages/".$file.".json";
            $versions = $this->repository_path."/download/".$file.".json";
//            $package = json_decode(file_get_contents($package),true);
            $download = json_decode(file_get_contents($download),true);
            if(!$download){

                    $i++;
                    $output->writeln($i.'/'.$count.' skip');
                    continue;


//                $bar->advance();
            }
            $i++;
            $versions = array_values($download['package']['versions']);
            if(!isset($versions[0]['require'])){
                $output->writeln($i.'/'.$count.' skip');
                continue;
            }
            $output->writeln($i.'/'.$count);
            $package_name = strtolower($download['package']['name']);
            $project_name = substr($package_name,0,strpos($package_name,'/'));
//            echo strpos($package_name,'/').' '.$package_name."\r\n";exit;
            if(isset($versions[0]['require'] ))
            foreach($versions[0]['require'] as $k=>$v){

                R::findOrCreate("CpmRequire",array(
                    "package" => $package_name,
                    "project" => $project_name,
                    "require" => strtolower($k),
                    "version" => strtolower($v),
                ));
            }
            if(isset($versions[0]['require-dev'] ))
            foreach($versions[0]['require-dev'] as $k=>$v){
                R::findOrCreate("CpmRequireDev",array(
                    "package" => $package_name,
                    "project" => $project_name,
                    "require" => strtolower($k),
                    "version" => strtolower($v),
                ));
            }
            if(isset($versions[0]['keywords'] ))
            foreach($versions[0]['keywords'] as $v){
                R::findOrCreate("CpmKeyword",array(
                    "package" => $package_name,
                    "project" => $project_name,
                    "keyword" => strtolower($v),
                ));
            }
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
