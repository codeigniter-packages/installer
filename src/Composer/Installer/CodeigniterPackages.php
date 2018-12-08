<?php

namespace Composer\Installer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

class CodeigniterPackages extends LibraryInstaller{

    public function supports($packageType){
        return in_array($packageType, array('codeigniter-view','codeigniter-model','codeigniter-library','codeigniter-helper'));
    }

        protected function installCode(PackageInterface $package){
        parent::installCode($package);
        $this->postInstallActions($package);
    }

    protected function postInstallActions($package){
        
        $extra = $package->getExtra();

        if(in_array($package->getType(),array('codeigniter-model','codeigniter-library')) && !isset($extra['main'])){
            throw new \InvalidArgumentException("extra's main is require");
        }

        
        $configPath=dirname(rtrim($this->composer->getConfig()->get('vendor-dir'), '/')).'/application/config/packages.php';
            
            // @HACK to work around the security check in CI config files
        if (!defined('BASEPATH')){
            define('BASEPATH', 1);
        }
        
        if (file_exists($configPath)){
            @include($configPath);
        }else{
            $config=array();
        }
        
        if(in_array($package->getType(),array('codeigniter-model','codeigniter-library'))){
            $config[$package->getPrettyName()]=array(
                'path'=>$this->getInstallPath($package).'/'.$extra['main'],
                'type'=>$package->getType(),
                'class_name'=>str_replace('.php','',$extra['main'])
            );
        }else{
            foreach(glob($this->getInstallPath($package).'/*.php') as $item_filename){
                //获取文件的名称并去除.php后缀
                $config[$package->getPrettyName().':'.rtrim(basename($item_filename),'.php')]=array(
                    'path'=>$item_filename,
                    'type'=>$package->getType()
                );
            }
        }
        
        $str_tmp="<?php\r\n"; //得到php的起始符。$str_tmp将累加
        $str_tmp.="defined('BASEPATH') OR exit('No direct script access allowed');\r\n";
        //http://stackoverflow.com/questions/18342477/save-array-to-php-file
        file_put_contents($configPath,$str_tmp.'$config='. var_export($config, true) . ';');
    }

}
