<?php
/*
 * This file is part of the sfPackageMakerPlugin package.
 * (c) 2009 Cedric Lombardot <cedric.lombardot@spyrit.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package    symfony.runtime.addon
 * @author     Cedric Lombardot <cedric.lombardot@spyrit.net>
 * @version    SVN: $Id$
 */

class sfPakePackageMakerPlugin extends sfPluginBaseTask{
	
	protected function configure(){
			$this->addArguments(array(
	      new sfCommandArgument('plugin_name', sfCommandArgument::REQUIRED, 'The plugin name'),
	    ));
		$this->addOptions(
		array(
			new sfCommandOption('skelton_path', 'p', sfCommandOption::PARAMETER_REQUIRED, 'To set an other skelton path for the file', null),
			new sfCommandOption('plugin_version', 'v', sfCommandOption::PARAMETER_REQUIRED, 'If you want to set a realease number', null),
			new sfCommandOption('stability', 's', sfCommandOption::PARAMETER_REQUIRED, 'The stability of this new realease', null),
			new sfCommandOption('changelog', 'c', sfCommandOption::PARAMETER_REQUIRED, 'The changement for this new Realease', null),
		    new sfCommandOption('license_uri','uri',sfCommandOption::PARAMETER_REQUIRED,'The license uri default MIT','http://www.symfony-project.com/license'),
		    new sfCommandOption('license_name','l',sfCommandOption::PARAMETER_REQUIRED,'The name of the license default MIT','MIT'),
		));
	
		
		$this->aliases = array('plugin-build-package');
    	$this->namespace = 'plugin';
   		$this->name = 'build-package';
   		
   		$this->briefDescription = 'create package.xml files from a skeleton';
   		
   		$this->detailedDescription = <<<EOF
   		This plugin will preparaten your package by analysing all the file to be packaged into 
   		a good pear package

   		[ ./symfony plugin:build-package PLUGIN_NAME ]
   		[ ./symfony plugin-build-package PLUGIN_NAME ]
   		
   		* The plugin name is the name of your plugin folder
   		* At the root of the plugin folder you have to create a copy of the xml file 
   			./sfPackageMakerPlugin/skelton.xml
   		The name of this file is by default package-YOUR_PLUGIN_NAME.xml
   		
   		I recommand you to set options in this tag to spend less time
   		
   		[ ./symfony plugin:build-package -v="PLUGIN_VERSION" -s="STABILITY" -c="CHANGELOG FOR THE RELASE" PLUGIN_NAME ]
   		
   		If you use this method, the tack will change the skelton XML by Adding the changelog ...
   		
   		After you will ony have to do 
   		
   		cd plugins
   		pear package ./[PLUGIN_NAME]/package.xml
   		
   		
EOF;

	}
	
  protected function execute($arguments = array(), $options = array())
  {
 		/*
 		 * The skeltonPath
 		 */
  		$skelton=($options['skelton_path']!="")?$options['skelton_path']:'package-'.$arguments['plugin_name'].'.xml'; 	
  		$skelton=sfConfig::get('sf_plugins_dir').DIRECTORY_SEPARATOR.$skelton;
  		
  		if (!file_exists($skelton)){
  		 	throw new sfCommandException(sprintf('Skeleton "%s" not found.', $skelton));
  		}
  		
  	
  		$skeletonContents=file_get_contents($skelton);
  		
  		$xml = new SimpleXMLElement($skeletonContents);
  		$xml->date = date('Y-m-d');
  		$xml->name = $arguments['plugin_name'];

  		
  	$lfile=sfConfig::get('sf_plugins_dir').DIRECTORY_SEPARATOR.$xml->name.DIRECTORY_SEPARATOR.'LICENSE';
  		if (!file_exists($lfile)){
  			$fp=fopen($lfile,'w');
  			fputs($fp,$options['license_name'].'  '.$options['license_uri']);
  			fclose($fp);
  		}
  		$rfile=sfConfig::get('sf_plugins_dir').DIRECTORY_SEPARATOR.$xml->name.DIRECTORY_SEPARATOR.'README';
  		if (!file_exists($rfile)){
  			$fp=fopen($rfile,'w');
  			fputs($fp,'== '.$arguments['plugin_name'].' ==
### Description 
 '.$xml->description.'
 
### How To Install
 
 YOUR README HERE
 
 
 ');
  			fclose($fp);
  		}
  		
  		if(($options['plugin_version'])){
  			$xml->version->release=$options['plugin_version'];
  			$xml->version->api=$options['plugin_version'];
  		}
  		
  		if(!is_null($options['stability'])){
  			$xml->stability->release=$options['stability'];
  			$xml->stability->api=$options['stability'];
  		}
  		
  		if($options['changelog']!=""){
  			$release=$xml->changelog->addChild('release');
  			
  			//Version
  			$version=$release->addChild('version');
  			$v_r=$version->addChild('release',$options['plugin_version']);
  			$api=$version->addChild('api',$options['plugin_version']);
  			
  			//stability
  			$version=$release->addChild('stability');
  			$v_r=$version->addChild('release',$options['stability']);
  			$api=$version->addChild('api',$options['stability']);
  			
  			//Licence <license uri="http://www.symfony-project.com/license">MIT license</license> 
  			$license_uri=$release->addChild('license',$options['license_name']);
  			$license_uri->addAttribute('uri',$options['license_uri']);
  			
  			//Date
  			$date=$release->addChild('date',$xml->date);
  			
  			//Notes
  			$notes=$release->addChild('notes',$options['changelog']);
  			
  			
  			/*
  			 * For the changelog we will replace the skelton
  			 */
  			$xml->asXML($skelton);
  		}
  		
  		$xml->license=$options['license_name'];
  		$xml->license->addAttribute('uri',$options['license_uri']);
  			
  			
  		$xml->contents = '';
  		
  		$rootDir = $xml->contents->addChild('dir');
  		$rootDir->addAttribute('name', '/');
  
 		$this->describe_dir(sfConfig::get('sf_plugins_dir').DIRECTORY_SEPARATOR.$arguments['plugin_name'], $rootDir, true);
  
 		
  		
  		
	    $packagePath = sfConfig::get('sf_plugins_dir').DIRECTORY_SEPARATOR.$xml->name.DIRECTORY_SEPARATOR."package.xml";
  
 		#pake_echo($rootDir->asXML());
  		$xml->asXML($packagePath);
  		#echo $xml->asXML();
  		
  }
  
 private function describe_dir($path, &$dirNode, $firstTime)
	{
  	if (!($d = @opendir($path)) === false)
  	while ($f = readdir($d))
  	{
    if ((substr($f,0,1) != '.') && ($f != 'package.xml'))
      if (is_dir($dir = $path.DIRECTORY_SEPARATOR.$f))
      {
        $child = $dirNode->addChild('dir');
        $child->addAttribute('name',$f);
        $this->describe_dir($dir, $child, false);
      }
      else
      {
        $child = $dirNode->addChild('file');
        $child->addAttribute('name', $f);
        $child->addAttribute('role', 'data');
      }
  }  
  
}
	
}
?>