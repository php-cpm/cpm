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
    public $downpackgist = "https://packagist.org";
    public $packages_json = '/packages.json';
    public $package_json = "/p/%package%$%hash%.json";
    public $repository_path = "packagist";
    public $server_url="http://10.1.169.16:12801/";
    public function handle(Args $args, IO $io, Command $command)
    {
        /**
         * in all  && not in limit
         * download
         * into repo
         */
        $this->downloadNewRepo();

        /**
         * 1.初始化
         */
//        $limit_array = $this->getLimitArray() ? $this->getLimitArray(): $this->rebuildLimitArray();
//
//        $this->doDownLoad($limit_array);
//        $this->buildNewPackagesJson();
        return 0;
    }
    public function get404Array(){
        $data = R::find("norepo");
        foreach($data as $v){
            $array[] = $v->name;
        }
        return $array;
    }
    public function downloadNewRepo(){
        $output = new ConsoleOutput();
        $all = $this->getProviderArray();
        $limit404 = $this->get404Array();
        $limit = $this->rebuildLimitArray();
//        $limit = $this->getLimitArray();
        $count  = 0;
        $all_count = count($all)-count($limit404);
        foreach($all as $k=>$v){
            if(in_array($k,$limit404)){
                echo "404";
                continue;
            }
            $count++;

            $output->writeln($count."/".$all_count );

            if(array_key_exists($k,$limit) && $limit[$k]['sha256']==$v['sha256']){

                echo "local : $k ";
                $output->writeln("done!");
                continue;
                $down = $this->getLocalJson($k,$v['sha256']);
                if(!$down) continue;
                $this->saveToRepo($down,$v['sha256']);
            }else{
                $down = $this->download($k);
                if(!$down) continue;
                $package_file = $this->downPackage($k,$v['sha256']);
                $this->saveToRepo($down,$v['sha256']);

            }
            $output->writeln("done!");
        }
        echo count($all)-$count;
    }
    public function getLocalJson($file,$sha256){
//        $url = $this->packagist."/p/".$file.'$'.$sha256.".json";
        $path = $this->repository_path."/download/".$file.".json";
        $path2 = $this->repository_path."/packages/".$file.".json";
        $fs = new Filesystem();
        if($fs->exists($path) && $fs->exists($path2)){
            $json = file_get_contents($path);
            $json2 = file_get_contents($path2);
            if(hash('sha256',$json2) == $sha256 ){

                return json_decode($json,TRUE);
            }else{

                $down = $this->download($file);
                if(!$down)return '';
                $package_file = $this->downPackage($file,$sha256);
                echo "update :".$file." \r\n";
                return $down;
            }

        }
    }
    public function downPackage($file,$sha256){

        $url = $this->packagist."/p/".$file.'$'.$sha256.".json";
        $path = $this->repository_path."/packages/".$file.".json";
        $fs = new Filesystem();
        $result = HttpSendLong($url);
        if($result->code != 200){
            return null;
        }
        $json = $result->raw_body;
        $fs->dumpFile($path,$json);
        return json_decode($json,TRUE);
    }
    public function download($file){

        echo "start downloading :".$this->downpackgist . '/packages/'.$file.'.json'." \r\n";
        $result = HttpSendProxy($this->downpackgist . '/packages/'.$file.'.json');

        if($result->code != 200){
            R::findOrCreate("norepo",array(
                "name"=>$file,
            ));
            echo "404";
            return null;
        }
        $json = $result->raw_body;
        $download = json_decode($json, TRUE);

        $fs = new Filesystem();
        $fs->dumpFile($this->repository_path."/download/".$file.".json", $json);
        return $download;
    }
    public function saveToRepo($download,$sha256){
        if(!isset($download['package'])){
            print_r($download);
            print_r($sha256);
            exit;
        }
        $package = $download['package'];
        $data = R::findOrCreate("repo",array(
            "name" => $package['name'],
            "project" => substr($package['name'],0,strpos($package['name'],'/')),
        ));

        $versions = array_values($package['versions']);
        $version =$versions[0];
        $table_package = R::dispense("repo") ;
        $table_package->id = $data->id;
        $table_package->description = $package['description'];
        $table_package->type = $package['type'];
        $table_package->time = strtotime($package['time']);
//        $table_package->keywords = implode(',',$version['keywords']);
        $table_package->download_total = $package['downloads']['total'];
        $table_package->download_monthly = $package['downloads']['monthly'];
        $table_package->favers = $package['favers'];
        $table_package->sha256 = $sha256;
        R::store($table_package);
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
            $key = trim(strtolower($key));
            if(!$key)continue;
            $limit_array[$key] = $all_array[$key];
        }
        $fs = new Filesystem();
        $fs->dumpFile($this->repository_path.'/p/limit.json',json_encode($limit_array));
        return $limit_array;
    }
    public function getLimitArray(){
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

        $fs = new Filesystem();
        $fs->dumpFile($this->repository_path . "/packages-packagist.json", $json);
    }
    public function buildProvidersJson($providers){
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

        $providers = $this->getProviderFileList();

        $retArray['providers'] = array();
        foreach ($providers as $file) {

            $provider_file_path = $this->repository_path."/" .$file;

            if(!file_exists($provider_file_path)){
                $this->buildProvidersJson($providers);
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
        $provider_includes = $this->getLimitArray();
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
    public function getProviderIncludesFileList(){
//        if(!file_exists($this->repository_path."/packages-packagist.json")){
            $this->downloadPackagesJson();
//        }
        $file = file_get_contents($this->repository_path . "/packages-packagist.json");
        $array = json_decode($file,TRUE);
        return $array['provider-includes'];
    }
    public function getProviderFileList(){

        $retArray = array();
        $provider_includes = $this->getProviderIncludesFileList();
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
        return \Httpful\Request::get($url)->timeout(300)->useProxy('10.199.75.12', '8080')->send();
    }
    return \Httpful\Request::get($url)->timeout(60)->send();
}
function HttpSendProxy($url)
{
        return \Httpful\Request::get($url)->timeout(10)->useProxy('127.0.0.1', '7070',null,null,null,\Httpful\Proxy::SOCKS5)->send();
}
function HttpSend($url)
{
    if (USE_PROXY) {
        return \Httpful\Request::get($url)->timeout(15)->useProxy('10.199.75.12', '8080')->send();
    }
    try{

        return \Httpful\Request::get($url)->followRedirects(true)->expectsJson()->timeout(15)->send();

    }catch (Exception $e){
        try{

            return \Httpful\Request::get($url)->followRedirects(true)->expectsJson()->timeout(5)->send();
        }catch (Exception $e){

        }


    }
}