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
class Timer {
      private $starttime = NULL;
      private $endtime = NULL;
      private $lasttime = NULL;
      
      private function getMicrotime (){ 
         list($usec, $sec) = explode(' ',microtime()); 
         return ((float)$usec + (float)$sec); 
      }
      
      private function clear () {
         $this->starttime = NULL;
         $this->endtime = NULL;
      }
      
      public function start () {
         $this->clear();
         $this->starttime = $this->getMicrotime();
      }
      
      public function stop() {
         $this->endtime = $this->getMicrotime();
         $this->lasttime = $this->endtime - $this->starttime;
         $this->clear();
      }
      
      public function getTime ($precissions = 4) {
         $multiply = '1';
         for($i=0; $i <$precissions; $i++) {
            $multiply .= '0';
         }
         $time = $this->lasttime * $multiply;
         $time = round($time);
         $time = $time / $multiply;
         return $time;
      }
}




    set_include_path(realpath(__DIR__)."/../../");
    require_once "../../ClassLoader.php";

    // Specify the connection details
    $repositoryUrl = "http://testalf.may.co.at:8080/alfresco/api";
    $userName = "admin";
    $password = "admin";

    // Authenticate the user and create a session
    $repository = new Repository($repositoryUrl);
    $ticket = $repository->authenticate($userName, $password);
    $session = $repository->createSession($ticket);

    // Create a reference to the 'SpacesStore'
    $spacesStore = new SpacesStore($session);

    // Get the company home node
    $companyHome = $spacesStore->companyHome;
    
    $searchKeyWord = "Acrobat";

    $timer = new Timer();
    $timer->start();
    $DocLib = new RESTDoclib($repository,$spacesStore,$session);
    echo "<pre>";
    $count = $DocLib->Search($searchKeyWord,"cm:name",50);
    $timer->stop();
    echo "Search Rest Documents - ".count($count->items)."<br>";
    print'----------- Zeit in sec: '.$timer->getTime()."<br>";
    
    $timer->start();
    $count = $session->filteredQuery($spacesStore,"TEXT:$searchKeyWord",50);
    
    $timer->stop();
    echo "Search Lucene Documents - ".count($count)."<br>";
    print'----------- Zeit in sec: '.$timer->getTime()."<br>";

    
?>

