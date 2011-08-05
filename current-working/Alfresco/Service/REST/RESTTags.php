<?php

class RESTTags extends BaseObject {
    private $_restClient = null;
    
    private $_repository;
    private $_session;
    private $_store;
    private $_ticket;
    private $_connectionUrl;
    
    public $namespaceMap;
    
    public function __construct($repository, $store, $session) {
        $this->_repository = $repository;
        $this->_store = $store;
        $this->_session = $session;
        $this->_ticket = $this->_session->getTicket();
        
        $this->namespaceMap = new NamespaceMap();
        
        $this->_connectionUrl = $this->_repository->connectionUrl;
        $this->_connectionUrl = str_replace("api","service",$this->_connectionUrl);
        $this->setRESTClient();
    }  
    
    public function GetNodeForTag($tag="",$format="json") {
        $result = array();
        $tag = rawurlencode($tag);
        $this->_restClient->createRequest($this->_connectionUrl."/api/tags/workspace/SpacesStore/$tag/nodes?format=$format","GET");

        $this->_restClient->sendRequest();

        $result = $this->workWithResult($this->_restClient->getResponse(),$format);    

        return $result;
    }  
    
    public function GetNodeTags($id="",$format="json") {
        $result = array();

        
        $this->_restClient->createRequest($this->_connectionUrl."/api/node/workspace/SpacesStore/$id/tags?format=$format","GET");

        $this->_restClient->sendRequest();

        $result = $this->workWithResult($this->_restClient->getResponse(),$format);    

        return $result;
    }  
    
    
    public function AddNodeTags($id,$tags=array(),$format="json") {
        $result = array();

        $postArr = json_encode($tags);                             
        $this->_restClient->createRequest($this->_connectionUrl."/api/node/workspace/SpacesStore/$id/tags?format=$format","POST",$postArr,"json");

        $this->_restClient->sendRequest();

        $result = $this->workWithResult($this->_restClient->getResponse(),$format);    

        return $result;
    }
    
    public function GetSiteTags($SiteName="",$format="json") {
        $result = array();
        $SiteName = rawurlencode($SiteName);
        
        $this->_restClient->createRequest($this->_connectionUrl."/api/tagscopes/site/$SiteName/tags?format=$format","GET");

        $this->_restClient->sendRequest();

        $result = $this->workWithResult($this->_restClient->getResponse(),$format);    

        return $result;
    }  
    
    public function GetSiteContainerTags($SiteName="",$container="",$format="json") {
        $result = array();
        $SiteName = rawurlencode($SiteName);
        
        $this->_restClient->createRequest($this->_connectionUrl."/api/tagscopes/site/$SiteName/$container/tags?format=$format","GET");

        $this->_restClient->sendRequest();

        $result = $this->workWithResult($this->_restClient->getResponse(),$format);    

        return $result;
    }  
    
    public function GetAllTags($filter="",$format="json") {
        $result = array();

        $this->_restClient->createRequest($this->_connectionUrl."/api/tags/workspace/SpacesStore?tf=$filter&format=$format","GET");
        $this->_restClient->sendRequest();

        $result = $this->workWithResult($this->_restClient->getResponse(),$format);    
        
        return $result;
    }  
    
    
    private function setRESTClient() {
        if ($this->_restClient == null) {
            //$this->_restClient = new RESTClient("",$this->_repository->getUsername(),$this->_repository->getPassword());    
            $this->_restClient = new RESTClient($this->_session->getTicket());    
        }
    }
    
    private function workWithResult($resultGet,$format) {
        switch ($format) {
            case "json":
                $result = json_decode($resultGet); 
                if (!$result) {
                    
                    $resultGet = str_replace("\r\n","",$resultGet);
                    $resultGet = str_replace("\t","",$resultGet);                
                    $resultGet = preg_replace("/([a-zA-Z0-9\s\.\!\?]+)/is","\"$1\"",$resultGet);      

                    $result = json_decode($resultGet); 
                }
            break;
            default:
                
                break;
        }
        return $result;
    }
}

?>
