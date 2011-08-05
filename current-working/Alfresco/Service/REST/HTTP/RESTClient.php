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

//set_include_path(get_include_path()."/Alfresco/Service/REST/HTTP/PEAR_PACK/");

class RESTClient {

    private $root_url = "";
    private $curr_url = "";
    private $user_name = "";
    private $password = "";
    private $ticket = "";
    private $response = "";
    private $responseBody = "";
    private $req = null;

    public function __construct($ticket="") {
        $this->ticket = $ticket;
        return true;
    }

    public function createRequest($url, $method, $arr = null, $arrFormat = null) {
        $this->curr_url = $url;
        if (!empty($this->ticket)) {
           if (preg_match("/\?/is",$this->curr_url))
            $this->curr_url .= "&alf_ticket=".$this->ticket;
           else
            $this->curr_url .= "?alf_ticket=".$this->ticket;
      
        }
        $this->req = new HTTP_Request($this->curr_url);
        if ($this->user_name != "" && $this->password != "") {
           $this->req->setBasicAuth($this->user_name, $this->password);
        }        

        switch($method) {
            case "GET":
                $this->req->setMethod(HTTP_REQUEST_METHOD_GET);
                break;
            case "POST":
                $this->req->setMethod(HTTP_REQUEST_METHOD_POST);
                if ($arrFormat == "json") {
                    $this->req->addHeader("Content-Type","application/json");
                    $this->req->addRawPostData($arr);

                }
                else
                    $this->addPostData($arr);
                break;
            case "PUT":
                $this->req->setMethod(HTTP_REQUEST_METHOD_PUT);
                if ($arrFormat == "json") {
                    $this->req->addHeader("Content-Type","application/json");
                    $this->req->addRawPostData($arr);
                }
                else
                    $this->addPostData($arr);
                // to-do
                break;
            case "DELETE":
                $this->req->setMethod(HTTP_REQUEST_METHOD_DELETE);
                // to-do
                break;
        }
    }
    
    public function addPostFile($inputName, $fileName, $contentType = 'application/octet-stream') {
        $this->req->addFile($inputName, $fileName, $contentType);
    }

    private function addPostData($arr) {
        if ($arr != null) {
            foreach ($arr as $key => $value) {
                $this->req->addPostData($key, $value);
            }
        }
    }

    public function sendRequest() {
        $this->response = $this->req->sendRequest();

        if (PEAR::isError($this->response)) {
            echo $this->response->getMessage();
            die();
        } else {
            $this->responseBody = $this->req->getResponseBody();

        }
    }

    public function getResponse() {
        return $this->responseBody;
    }


}
?>