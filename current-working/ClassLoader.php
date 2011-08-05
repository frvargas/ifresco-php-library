<?php
 /**
 *
 * @package    ifresco PHP library
 * @author Dominik Danninger 
 * @website http://www.ifresco.at
 *
 * ifresco PHP library - extends Alfresco PHP Library
 * 
 * Copyright (c) 2011 Dominik Danninger, MAY Computer GmbH
 * 
 * This file is part of "ifresco PHP library".
 * 
 * "ifresco PHP library" is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * "ifresco PHP library" is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with "ifresco PHP library".  If not, see <http://www.gnu.org/licenses/>. (http://www.gnu.org/licenses/gpl.html)
 */  
class AutoLoader {
    public static function load($className){
        $directories = array(
          '',
          'krumo/',
          'Alfresco/',
          'Alfresco/Service/',
          'Alfresco/Service/REST/',
          'Alfresco/Service/REST/HTTP/PEAR_PACK/HTTP/',
          'Alfresco/Service/REST/HTTP/PEAR_PACK/NET/',
          'Alfresco/Service/REST/HTTP/PEAR_PACK/PEAR/',
          'Alfresco/Service/REST/HTTP/PEAR_PACK/',
          'Alfresco/Service/REST/HTTP/',
          'Alfresco/Service/WebService/'
        );

        $fileNameFormats = array(
          '%s.php',
          '%s.class.php',
          'class.%s.php',
          '%s.inc.php'
        );

        // this is to take care of the PEAR style of naming classes
        $path = str_ireplace('_', '/', $className);
        
        if(@include_once $path.'.php'){
            return;
        }
        
        
       
        foreach($directories as $directory){
            foreach($fileNameFormats as $fileNameFormat){
                $path = $directory.sprintf($fileNameFormat, $className);               
                $pathBefore = "../../".$directory.sprintf($fileNameFormat, $className);               
                if(file_exists($path)) {
                    include_once $path;
                    return;
                }
                elseif (file_exists($pathBefore)) {   
                    include_once $pathBefore;
                    return; 
                }
            }
        }
    }
}

spl_autoload_register(array("AutoLoader","load"));
?>