<?php
/*
 * Copyright (C) 2005 Alfresco, Inc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

 * As a special exception to the terms and conditions of version 2.0 of 
 * the GPL, you may redistribute this Program in connection with Free/Libre 
 * and Open Source Software ("FLOSS") applications as described in Alfresco's 
 * FLOSS exception.  You should have recieved a copy of the text describing 
 * the FLOSS exception, and it is also available here: 
 * http://www.alfresco.com/legal/licensing"
 */

require_once 'Store.php';
require_once 'ChildAssociation.php';
require_once 'Association.php';
require_once 'NamespaceMap.php';
require_once 'ContentData.php';
require_once 'VersionHistory.php';
require_once 'Version.php';

class Node extends BaseObject 
{
    private $_session;
    private $_store;
    private $_id;
    private $_type;
    private $_aspects;
    private $_properties;
    private $_children;
    private $_parents;
    private $_primaryParent;
    private $_isNewNode;
    private $_associations;
    private $_versionHistory;    
    private $origionalProperties;
    private $addedAspects;
    private $removedAspects;
    private $addedChildren;
    private $addedParents;
    private $addedAssociations;
    private $removedAssociations;
    private $_folderPath = NULL;     
    private $_RestNode = null;

    /**
     * Constructor
     */
    public function __construct($session, $store, $id) 
    {
        $this->_session = $session;
        $this->_store = $store;
        $this->_id = $id;    
        $this->_isNewNode = false;
        $this->addedChildren = array();
        $this->addedParents = array();
        $this->addedAssociations = array();
    }

    /**
     * Util method to create a node from a web service node structure.
     */
    public static function createFromWebServiceData($session, $webServiceNode) 
    {
        $scheme = $webServiceNode->reference->store->scheme;
        $address = $webServiceNode->reference->store->address;
        $id = $webServiceNode->reference->uuid;

        $store = $session->getStore($address, $scheme);
        $node = $session->getNode($store, $id);
        $node->populateFromWebServiceNode($webServiceNode);
        
        return $node;
    }
    
    public static function createFromRestData($session, $restNode) {
        $scheme = "workspace";
        $address = "SpacesStore";
        $id = str_replace("workspace://SpacesStore/","",$restNode->nodeRef);

        $store = $session->getStore($address, $scheme);
        $node = $session->getNode($store, $id);

        return $node;
    }
    
    public function setPropertyValues($properties,$populate=true)
    {
        // Check that the properties of the node have been populated
        if ($populate)
            $this->populateProperties();
        
        // Set the property values
        foreach ($properties as $name=>$value)
        {
            //$name = $this->expandToFullName($name);
            $this->_properties[$name] = $value;
        }        
    }
    
    public function setContent($property, $mimetype=null, $encoding=null, $content=null)
    {
        list($property) = $this->_session->namespaceMap->getFullNames(array($property));
        $contentData = new ContentData($this, $property, $mimetype, $encoding);
        if ($content != null)
        {
            $contentData->content = $content;
        }
        $this->_properties[$property] = $contentData;
        
        return $contentData;
    }
    
    public function getContent() {
        $contentData = $Node->cm_content;
        if ($contentData instanceof ContentData) {
            return $contentData;
        }
        return null;
    }
    
    public function hasAspect($aspect)
    {
        list($aspect) = $this->_session->namespaceMap->getFullNames(array($aspect));
        $this->populateProperties();
        return in_array($aspect, $this->_aspects);
    }
    
    public function addAspect($aspect, $properties = null)
    {
        list($aspect) = $this->_session->namespaceMap->getFullNames(array($aspect));        
        $this->populateProperties();
        
        if (in_array($aspect, $this->_aspects) == false)
        {
            $this->_aspects[] = $aspect;
            if ($properties != null)
            {
                foreach ($properties as $name=>$value)
                {
                    $name = $this->_session->namespaceMap->getFullName($name);
                    $this->_properties[$name] = $value;
                }
            }
            
            $this->remove_array_value($aspect, $this->removedAspects);
            $this->addedAspects[] = $aspect;    
        }            
    }
    
    public function removeAspect($aspect)
    {
        list($aspect) = $this->_session->namespaceMap->getFullNames(array($aspect));
        $this->populateProperties();    
        
        if (in_array($aspect, $this->_aspects) == true)
        {        
            $this->remove_array_value($aspect, $this->_aspects);
            $this->remove_array_value($aspect, $this->addedAspects);    
            $this->removedAspects[] = $aspect;
        }
    }
    
    public function createChild($type, $associationType, $associationName)
    {        
        $temp = $associationName;
        
        list($type, $associationType, $associationName) = $this->_session->namespaceMap->getFullNames(array($type, $associationType, $associationName));
        
        
        $id = $this->_session->nextSessionId();
        $newNode = new Node($this->_session, $this->_store, $id);    
        $childAssociation = new ChildAssociation($this, $newNode, $associationType, $associationName, true);
        
        $newNode->_isNewNode = true;
        
        $newNode->_properties = array();
        $newNode->_aspects = array();
        $newNode->_properties = array();
        $newNode->_children = array();
        $newNode->origionalProperties = array();
        $newNode->addedAspects = array();
        $newNode->removedAspects = array();
        $newNode->removedAssociations = array();
        
        $newNode->_type = $type;
        $newNode->_parents = array(); 
        $newNode->addedParents = array($this->__toString() => $childAssociation);
        $newNode->_primaryParent = $this;
        
        $this->addedChildren[$newNode->__toString()] = $childAssociation;        
        
        $this->_session->addNode($newNode);
        
        return $newNode;                    
    }
    
    public function addChild($node, $associationType, $associationName)
    {
        list($associationType, $associationName) = $this->_session->namespaceMap->getFullNames(array($associationType, $associationName));
        
        $childAssociation = new ChildAssociation($this, $node, $associationType, $associationName, false);
        $this->addedChildren[$node->__toString()] = $childAssociation;
        $node->addedParents[$this->__toString()] = $childAssociation;
    }
    
    public function removeChild($childAssociation)
    {
        
    }
    
    public function addAssociation($to, $associationType)
    {
   
        list($associationType) = $this->_session->namespaceMap->getFullNames(array($associationType));
        
        $association = new Association($this, $to, $associationType);
        $this->addedAssociations[$to->__toString()] = $association;    
        
        $this->remove_array_value($association, $this->removedAssociations);                   
    }
    
    public function removeAssociation($association)
    {
        if (in_array($association, $this->_associations) == true)
        {    
            $this->remove_array_value($association, $this->_associations);
            $this->remove_array_value($association, $this->addedAssociations);    
            $this->removedAssociations[] = $association;
        } 
              
    }
    
    public function removeAssociationOfType($typeName) {

        $typeName = $this->_session->namespaceMap->getFullName($typeName);        
        $Assocs = $this->getAssociations();

        foreach ($Assocs as $Assoc) {
            $Type = $Assoc->getType();
            
            if ($Type==$typeName) {         
                $this->removeAssociation($Assoc);
            }
                   
        }
        
    }

    
    public function hasAssociation($to,$associationType) {
        list($associationType) = $this->_session->namespaceMap->getFullNames(array($associationType));     
        
        if ($this->_associations == null) {
            $this->populateAssociations();
        }
        
        $association = new Association($this, $to, $associationType);    
        return (in_array($association, $this->_associations));
              
    }
    
    public function checkOut($description=null) {
        $ParentDestination = $this->getPrimaryParent();

        $client = WebServiceFactory::getAuthoringService($this->_session->repository->connectionUrl, $this->_session->ticket);

        $result = $client->checkout(
            array("items" => array("nodes" => $this->__toArray()),
                  "comments" => array("name" => "description", "value" => $description),
                  "keepCheckedOut" => false,
                  "destination"=>array(
                    "store"=>$this->_store->__toArray(),
                    "uuid"=>$ParentDestination->getId(),
                    "associationType"=>'{http://www.alfresco.org/model/content/1.0}contains',
                    "childName"=>'checkout',
                  )   
            ));     
      

        $workingCopiesId = $result->checkoutReturn->workingCopies->uuid;   
        $workingCopiesStoreScheme = $result->checkoutReturn->workingCopies->store->scheme;  
        $workingCopiesStoreAddress = $result->checkoutReturn->workingCopies->store->address;  
        $workingCopiesPath = $result->checkoutReturn->workingCopies->path;  
        
        return new Node($this->_session, new Store($this->_session, $workingCopiesStoreAddress, $workingCopiesStoreScheme), $workingCopiesId);                
    }
    
    public function checkIn($description=null,$major=false) {
        $ParentDestination = $this->getPrimaryParent();    
           
        if ($major == false)
            $versionType = "MINOR";
        else
            $versionType = "MAJOR";                 
        
        $client = WebServiceFactory::getAuthoringService($this->_session->repository->connectionUrl, $this->_session->ticket);
        $result = $client->checkin(
            array("items" => array("nodes" => $this->__toArray()),
                  "comments" => array(array("name" => "versionType", "value" => $versionType),array("name" => "description", "value" => $description)),
                  "keepCheckedOut" => false
            ));           

        $checkinId = $result->checkinReturn->checkedIn->uuid;   
        $checkinStoreScheme = $result->checkinReturn->checkedIn->store->scheme;  
        $checkinStoreAddress = $result->checkinReturn->checkedIn->store->address;  
        
        return new Node($this->_session, new Store($this->_session, $checkinStoreAddress, $checkinStoreScheme), $checkinId);
    }

    
    public function cancelCheckout() {
        $client = WebServiceFactory::getAuthoringService($this->_session->repository->connectionUrl, $this->_session->ticket);
        $result = $client->cancelCheckout(
            array("items" => array("nodes" => $this->__toArray())));
    }
    
    public function createVersion($description=null, $major=false)
    {
        // We can only create a version if there are no outstanding changes for this node
        if ($this->isDirty() == true)
        {
            throw new Exception("You must save any outstanding modifications before a new version can be created.");
        }

        if ($major == false)
            $versionType = "MINOR";
        else
            $versionType = "MAJOR";
            

        $client = WebServiceFactory::getAuthoringService($this->_session->repository->connectionUrl, $this->_session->ticket);
        $result = $client->createVersion(
            array("items" => array("nodes" => $this->__toArray()),
                  "comments" => array(array("name" => "versionType", "value" => $versionType),array("name" => "description", "value" => $description)),
                  "versionChildren" => false));            
    
        // Clear the properties and aspects
        $this->_properties = null;
        $this->_aspects = null;                                             
                  
        // Get the version details 
        // TODO get some of the other details too ...
        $versionId = $result->createVersionReturn->versions->id->uuid;
        $versionStoreScheme = $result->createVersionReturn->versions->id->store->scheme;
        $versionStoreAddress = $result->createVersionReturn->versions->id->store->address;        
        
        // Create the version object to return          
        return new Version($this->_session, new Store($this->_session, $versionStoreAddress, $versionStoreScheme), $versionId);                                
    }
    
    private function isDirty()
    {
        $result = true;
        if ($this->_isNewNode == false &&
            count($this->getModifiedProperties()) == 0 &&
            ($this->addedAspects == null || count($this->addedAspects) == 0) &&
            ($this->removedAssociations == null || count($this->removedAssociations) == 0) &&
            ($this->addedChildren == null || count($this->addedChildren) == 0) &&
            ($this->addedAssociations == null || count($this->addedAssociations) == 0))
        {
            $result = false;
        }
        return $result;
    }
    
    public function __get($name)
    {
        $fullName = $this->_session->namespaceMap->getFullName($name);
        if ($fullName != $name)
        {
            $this->populateProperties();    
            if (array_key_exists($fullName, $this->_properties) == true)
            {
                return $this->_properties[$fullName];
            }    
            else
            {    
                return null;    
            }     
        }    
        else
        {
            return parent::__get($name);
        }
    }
    
    public function __set($name, $value)
    {
        $fullName = $this->_session->namespaceMap->getFullName($name);
        if ($fullName != $name)
        {
            $this->populateProperties();
            $this->_properties[$fullName] = $value;
            
            // Ensure that the node and property details are stored on the contentData object
            if ($value instanceof ContentData)
            {
                $value->setPropertyDetails($this, $fullName);    
            }
        }
        else
        {
            parent::__set($name, $value);
        }
    }
    
    public function __isset($name) {
        $fullName = $this->_session->namespaceMap->getFullName($name);
        if ($fullName != $name)
        {
            $this->populateProperties();    
            if (array_key_exists($fullName, $this->_properties) == true)
            {
                return true;
            }    
            else
            {    
                return false;    
            }     
        }    
        else
        {
            return false;
        }
    }

    /**
     * toString method.  Returns node as a node reference style string.
     */
    public function __toString() 
    {
        return Node::__toNodeRef($this->_store, $this->id);
    }
    
    public static function __toNodeRef($store, $id)
    {
        return $store->scheme . "://" . $store->address . "/" . $id;    
    }

    
    public function __toArray()
    {
        return array("store" => $this->_store->__toArray(),
                     "uuid" => $this->_id);
    }
    
    public function getDetailUrl()
    {
        $repoUrl = $this->_session->repository->connectionUrl;
        $repoUrl = str_replace("api/","",$repoUrl);
        $repoUrl = str_replace("api","",$repoUrl);
        if (!empty($this->_session->ticket))
            $ticket = "?ticket=".$this->_session->ticket;
        else
            $ticket = "";
        $detailUrl = $repoUrl."navigate/showDocDetails/".$this->_store->scheme."/".$this->_store->address."/".$this->id.$ticket;
        return $detailUrl;
    }
    
    public function getShareDetailUrl()
    {
        $repoUrl = $this->_session->repository->connectionUrl;
        $repoUrl = str_replace("api/","",$repoUrl);
        $repoUrl = str_replace("api","",$repoUrl);
        $repoUrl = str_replace("alfresco/","share/",$repoUrl);
        $repoUrl = str_replace("alfresco","share/",$repoUrl);
        //if (!empty($this->_session->ticket))
        //    $ticket = "&ticket=".$this->_session->ticket;
        //else
        //    $ticket = "";
        $detailUrl = $repoUrl."page/document-details?nodeRef=".$this->_store->scheme."://".$this->_store->address."/".$this->id;
        return $detailUrl;
    }
    
    public function getShareSpaceUrl()
    {
        $repoUrl = $this->_session->repository->connectionUrl;
        $repoUrl = str_replace("api/","",$repoUrl);
        $repoUrl = str_replace("api","",$repoUrl);
        $repoUrl = str_replace("alfresco/","share/",$repoUrl);
        $repoUrl = str_replace("alfresco","share/",$repoUrl);
        //if (!empty($this->_session->ticket))
        //    $ticket = "&ticket=".$this->_session->ticket;
        //else
        //    $ticket = "";
        $detailUrl = $repoUrl."page/folder-details?nodeRef=".$this->_store->scheme."://".$this->_store->address."/".$this->id;
        return $detailUrl;
    }
    
    public function getSpaceUrl()
    {
        $repoUrl = $this->_session->repository->connectionUrl;
        $repoUrl = str_replace("api/","",$repoUrl);
        $repoUrl = str_replace("api","",$repoUrl);
        if (!empty($this->_session->ticket))
            $ticket = "?ticket=".$this->_session->ticket;
        else
            $ticket = "";
echo $this->_store->schema;
        $detailUrl = $repoUrl."n/showSpaceDetails/".$this->_store->scheme."/".$this->_store->address."/".$this->id.$ticket;
        return $detailUrl;
    }
    
    public function getIconUrl()
    {
        $repoUrl = $this->_session->repository->connectionUrl;
        $repoUrl = str_replace("api/","",$repoUrl);
        $repoUrl = str_replace("api","",$repoUrl);

        if (empty($this->app_icon) || $this->app_icon == null) { 
            $fileName = $this->cm_name;
            $folder = "filetypes";
            if (preg_match("/.*\.(.*)/eis",$fileName,$find)) {
                
                $imgName = strtolower($find[1]);    
                
            }
            elseif ($this->getType() == "{http://www.alfresco.org/model/content/1.0}folder") {
                $imgName = "space-icon-default-16";
                $folder = "icons";
            }
            else {
                $imgName = "_default";  
                
            }
            
                 
            $iconUrl = $repoUrl."/images/$folder/$imgName.gif";          
        }
        else {
            $imgName = $this->app_icon;
            if (preg_match("/space-icon.*/eis",$imgName))
                $imgName .="-16";  
            
            $iconUrl = $repoUrl."/images/icons/$imgName.gif";    
            
        }
        //FB::log("APP ".$this->app_icon." | ".$imgName ." | ".$this->cm_name." | ".$this->getType());
        return $iconUrl;
    }
  
    public function getSession()
    {
        return $this->_session;
    }
  
    public function getStore() 
    {
        return $this->_store;
    }

    public function getId() 
    {
        return $this->_id;
    }
    
    public function getIsNewNode()
    {
        return $this->_isNewNode;
    }

    public function getType() 
    {
        $this->populateProperties();    
        return $this->_type;
    }
    
    public function setType($type) 
    {
        $this->_type = $type;
    }
    
    public function getAspects() 
    {
        $this->populateProperties();
        return $this->_aspects;
    }
    
    public function getProperties()
    {
        $this->populateProperties();
        return $this->_properties;
    }
    
    public function setProperties($properties,$populate=true)
    {
        if ($populate)
            $this->populateProperties();
        $this->_properties = $properties;    
    }
    
    /**
     * Accessor for the versionHistory property.
     * 
     * @return    VersionHistory    the versionHistory for the node, null is none
     */
    public function getVersionHistory()
    {
        if ($this->_versionHistory == null)
        {
            $this->_versionHistory = new VersionHistory($this);
        }
        return $this->_versionHistory;
    }
    
    public function getChildren()
    {
        if ($this->_children == null)
        {
            $this->populateChildren();
        }
        return $this->_children + $this->addedChildren;
    }
    
    public function getParents()
    {
        if ($this->_parents == null)
        {
            $this->populateParents();
        }
        return $this->_parents + $this->addedParents;
    }
    
    public function getPrimaryParent()
    {
        if ($this->_primaryParent == null)
        {
            $this->populateParents();
        }
        return $this->_primaryParent;    
    }
    
    public function getAssociations()
    {
        if ($this->_associations == null)
        {
            $this->populateAssociations();
        }
        return $this->_associations + $this->addedAssociations;
    }
    
    /** Methods used to populate node details from repository */
    
    private function populateProperties()
    {
        if ($this->_isNewNode == false && $this->_properties == null)
        {
            $result = $this->_session->repositoryService->get(array (
                    "where" => array (
                        "nodes" => array(
                            "store" => $this->_store->__toArray(),
                            "uuid" => $this->_id))));      
            $this->populateFromWebServiceNode($result->getReturn);
        }    
    }
    
    
    private function populateFromRestNode($webServiceNode)
    {
        /*$this->_properties = array();
        $this->addedAspects = array();
        $this->removedAspects = array();
        $this->removedAssociations = array();*/
        
    }
    
    private function populateFromWebServiceNode($webServiceNode)
    {
        $this->_type = $webServiceNode->type;

        // Get the aspects
        $this->_aspects = array();
        $aspects = $webServiceNode->aspects;
        if (is_array($aspects) == true)
        {
            foreach ($aspects as $aspect)
            {
                $this->_aspects[] = $aspect;
            }
        }
        else
        {
            $this->_aspects[] = $aspects;    
        }        

        // Set the property values
        // NOTE: do we need to be concerned with identifying whether this is an array or not when there is
        //       only one property on a node
        $this->_properties = array();
        foreach ($webServiceNode->properties as $propertyDetails) 
        {
            $name = $propertyDetails->name;
            $isMultiValue = $propertyDetails->isMultiValue;
            $value = null;
            if ($isMultiValue == false)
            {
                $value = $propertyDetails->value;
                if ($this->isContentData($value) == true)
                {
                    $value = new ContentData($this, $name);
                }
            }
            else
            {
                $value = $propertyDetails->values;                
            }
            $this->_properties[$name] = $value;
                        
        }    
        
        $this->origionalProperties = $this->_properties;    
        $this->addedAspects = array();
        $this->removedAspects = array();
        $this->removedAssociations = array();
        
    }    
    
    private function populateChildren()
    {
        // TODO should do some sort of limited pull here    
        $result = $this->_session->repositoryService->queryChildren(array("node" => $this->__toArray()));        
        $resultSet = $result->queryReturn->resultSet;
        
        $children = array();
        $map = $this->resultSetToMap($resultSet);
        foreach($map as $value)
        {
            $id = $value["{http://www.alfresco.org/model/system/1.0}node-uuid"];
            $store_scheme = $value["{http://www.alfresco.org/model/system/1.0}store-protocol"];
            $store_address = $value["{http://www.alfresco.org/model/system/1.0}store-identifier"];
            $assoc_type = $value["associationType"];
            $assoc_name = $value["associationName"];
            $isPrimary = $value["isPrimary"];
            $nthSibling = $value["nthSibling"];
            
            $child = $this->_session->getNode(new Store($this->_session, $store_address, $store_scheme), $id);
            $children[$child->__toString()] = new ChildAssociation($this, $child, $assoc_type, $assoc_name, $isPrimary, $nthSibling);
        }
        
        $this->_children = $children;    
    }
    
    private function populateAssociations()
    {
        // TODO should do some sort of limited pull here
        $result = $this->_session->repositoryService->queryAssociated(array("node" => $this->__toArray(),
                                                                            "association" => array("associationType" => null,
                                                                                                   "direction" => null)));
        $resultSet = $result->queryReturn->resultSet;
        
        $associations = array();
        $map = $this->resultSetToMap($resultSet);
        foreach($map as $value)
        {
            $id = $value["{http://www.alfresco.org/model/system/1.0}node-uuid"];
            $store_scheme = $value["{http://www.alfresco.org/model/system/1.0}store-protocol"];
            $store_address = $value["{http://www.alfresco.org/model/system/1.0}store-identifier"];
            $assoc_type = $value["associationType"];
            
            $to = $this->_session->getNode(new Store($this->_session, $store_address, $store_scheme), $id);
            $associations[$to->__toString()] = new Association($this, $to, $assoc_type);
        }
        
        $this->_associations = $associations;    
    }
    
    private function populateParents()
    {        
        // TODO should do some sort of limited pull here
        $result = $this->_session->repositoryService->queryParents(array("node" => $this->__toArray()));        
        $resultSet = $result->queryReturn->resultSet;
        
        $parents = array();
        $map = $this->resultSetToMap($resultSet);
        foreach($map as $value)
        {
            $id = $value["{http://www.alfresco.org/model/system/1.0}node-uuid"];
            $store_scheme = $value["{http://www.alfresco.org/model/system/1.0}store-protocol"];
            $store_address = $value["{http://www.alfresco.org/model/system/1.0}store-identifier"];
            $assoc_type = $value["associationType"];
            $assoc_name = $value["associationName"];
            $isPrimary = $value["isPrimary"];
            $nthSibling = $value["nthSibling"];
            
            $parent = $this->_session->getNode(new Store($this->_session, $store_address, $store_scheme), $id);
            if ($isPrimary == "true" or $isPrimary == true)
            {
                $this->_primaryParent = $parent;
            }
            $parents[$parent->__toString()] = new ChildAssociation($parent, $this, $assoc_type, $assoc_name, $isPrimary, $nthSibling);
        }
        
        $this->_parents = $parents;
    }
    
    public function onBeforeSave(&$statements)
    {
        if ($this->_isNewNode == true)
        {
            $childAssociation = $this->addedParents[$this->_primaryParent->__toString()];        
            
            $parentArray = array();
            $parent = $this->_primaryParent;
            if ($parent->_isNewNode == true)
            {
                $parentArray["parent_id"] = $parent->id;
                $parentArray["associationType"] = $childAssociation->type;
                $parentArray["childName"] = $childAssociation->name;
            }
            else
            {
                $parentArray["parent"] = array(
                                            "store" => $this->_store->__toArray(),
                                            "uuid" => $this->_primaryParent->_id,
                                            "associationType" => $childAssociation->type,
                                            "childName" => $childAssociation->name);
            }
                
            $this->addStatement($statements, "create",
                                array("id" => $this->_id) +
                                $parentArray +
                                array(    
                                    "type" => $this->_type,
                                    "property" => $this->getPropertyArray($this->_properties)));     
        }
        else
        {
            // Add the update statement for the modified properties
            $modifiedProperties = $this->getModifiedProperties();        
            if (count($modifiedProperties) != 0)
            {                    
                $this->addStatement($statements, "update", array("property" => $this->getPropertyArray($modifiedProperties)) + $this->getWhereArray());
            }
            
            // TODO deal with any deleted properties
        }
        
        // Update any modified content properties
        if ($this->_properties != null)
        {
            foreach($this->_properties as $name=>$value)
            {
                if (($value instanceof ContentData) && $value->isDirty == true)
                {
                    $value->onBeforeSave($statements, $this->getWhereArray());
                }
            }
        }        
        
        // Add the addAspect statements
        if ($this->addedAspects != null)
        {
            foreach($this->addedAspects as $aspect)
            {
                $this->addStatement($statements, "addAspect", array("aspect" => $aspect) + $this->getWhereArray());
            }
        }
        
        // Add the removeAspect
        if ($this->removedAspects != null)
        {
            foreach($this->removedAspects as $aspect)
            {
                $this->addStatement($statements, "removeAspect", array("aspect" => $aspect) + $this->getWhereArray());
            }
        }
        
        // Add non primary children
        foreach($this->addedChildren as $childAssociation)
        {
            if ($childAssociation->isPrimary == false)
            {
                
                $assocDetails = array("associationType" => $childAssociation->type, "childName" => $childAssociation->name);
                
                $temp = array();
                if ($childAssociation->child->_isNewNode == true)
                {
                    $temp["to_id"] = $childAssociation->child->_id;
                    $temp = $temp + $assocDetails;
                }    
                else
                {
                    $temp["to"] = array(
                                    "store" => $this->_store->__toArray(),
                                    "uuid" => $childAssociation->child->_id) + 
                                    $assocDetails;    
                }
                $temp = $temp + $this->getWhereArray();
                $this->addStatement($statements, "addChild", $temp);
            }
        }
        
        // Add associations
        foreach($this->addedAssociations as $association)
        {
            $temp = array("association" => $association->type);
            $temp = $temp + $this->getPredicateArray("from", $this) + $this->getPredicateArray("to", $association->to);
            $this->addStatement($statements, "createAssociation", $temp);
        }

        
        if ($this->removedAssociations != null)
        {
            foreach($this->removedAssociations as $association)
            {
                $temp = array("association" => $association->type);
                $temp = $temp + $this->getPredicateArray("from", $this) + $this->getPredicateArray("to", $association->to);
                $this->addStatement($statements, "removeAssociation", $temp);
           }
        }
    }
    
    private function addStatement(&$statements, $statement, $body)
    {        
        $result = array();    
        if (array_key_exists($statement, $statements) == true)    
        {
            $result = $statements[$statement];
        }
        $result[] = $body;
        $statements[$statement] = $result;
    }
    
    private function getWhereArray()
    {
        return $this->getPredicateArray("where", $this);
    }
    
    private function getPredicateArray($label, $node)
    {
        if ($node->_isNewNode == true)
        {
            return array($label."_id" => $node->_id);    
        }
        else
        {
            return array(
                    $label => array(
                         "nodes" => $node->__toArray()
                         ));                         
        }    
    }
    
    private function getPropertyArray($properties)
    {
        $result = array();
        foreach ($properties as $name=>$value)
        {    
            // Ignore content properties
            if (($value instanceof ContentData) == false)
            {
                  // DONE need to support multi values
                if (is_array($value)) {
                    $result[] = array(
                        "name" => $name,
                        "isMultiValue" => TRUE,
                        "values" => $value
                    );
                } else {
                    $result[] = array(
                        "name" => $name,
                        "isMultiValue" => FALSE,
                        "value" => $value
                    );
                }
            }
        }
        return $result;
    }
    
    private function getModifiedProperties()
    {
        $modified = $this->_properties;
        $origional = $this->origionalProperties;
        $result = array();
        if ($modified != null)
        {
            foreach ($modified as $key=>$value)
            {
                // Ignore content properties
                if (($value instanceof ContentData) == false)
                {
                    if (array_key_exists($key, $origional) == true)
                    {
                        // Check to see if the value have been modified
                        if ($value != $origional[$key])
                        {
                            $result[$key] = $value;
                        }
                    }    
                    else
                    {            
                        $result[$key] = $value;
                    }
                }
            }
        }
        return $result;
    }
    
    public function onAfterSave($idMap)
    {
        if (array_key_exists($this->_id, $idMap ) == true)
        {
            $uuid = $idMap[$this->_id];
            if ($uuid != null)
            {
                $this->_id = $uuid;
            }
        }
        
        if ($this->_isNewNode == true)
        {
            $this->_isNewNode = false;
            
            // Clear the properties and aspect 
            $this->_properties = null;
            $this->_aspects = null;
        }
        
        // Update any modified content properties
        if ($this->_properties != null)
        {
            foreach($this->_properties as $name=>$value)
            {
                if (($value instanceof ContentData) && $value->isDirty == true)
                {
                    $value->onAfterSave();
                }
            }
        }
        
        $this->origionalProperties = $this->_properties;
        
        if ($this->_aspects != null)
        {
            // Calculate the updated aspect list
            if ($this->addedAspects != null)
            {            
                $this->_aspects = $this->_aspects + $this->addedAspects;
            }
            if ($this->removedAspects != null)
            {
                foreach ($this->_aspects as $aspect)
                {
                    if (in_array($aspect, $this->removedAspects) == true)
                    {                    
                        $this->remove_array_value($aspect, $this->_aspects);
                    }
                }
            }
        } 
        $this->addedAspects = array();
        $this->removedAspects = array();
        $this->removedAssociations = array();         
        
        if ($this->_parents != null)
        {
            $this->_parents = $this->_parents + $this->addedParents;
        }
        $this->addedParents = array();
        
        if ($this->_children != null)
        {
            $this->_children = $this->_children + $this->addedChildren;
        }
        $this->addedChildren = array();        
        
        if ($this->_associations != null)
        {
            $this->_associations = $this->_associations + $this->addedAssociations;
        }
        $this->addedAssociations = array();
    }
    
    public function getFolderPath($extractCompanyHome=false) {
        if (!$this->_folderPath) {

            $result = '';
            $currentFolder = $this;
            while (4 != 5) {
                
                $parentFolder = $currentFolder->getPrimaryParent();
               
                if (!$parentFolder) {
                    break;
                }

                $parentType = $parentFolder->getType();
                if ($parentType == '{http://www.alfresco.org/model/system/1.0}store_root') {
                    break;
                }

                $parentProps = $parentFolder->getProperties();
                $folderName = $parentProps['{http://www.alfresco.org/model/content/1.0}name'];

                
                $result = '/' . $folderName . $result;
                    
                $currentFolder = $parentFolder;
            }
            
            if ($extractCompanyHome==true) {

                $result = str_replace("/".$this->_store->companyHome->cm_name."/","",$result)."/";    
            }
                
            $this->_folderPath = $result;
        }
        return $this->_folderPath;
    }

    public function getRestNode() {
        $RestNode = new RESTNode($this->_session->repository,$this->_store,$this->_session);
        $this->_RestNode = $RestNode->GetNode($this->id);
        return $this->_RestNode;
    }
    
    public function getPermissions() {
        if ($this->_RestNode == null) {
            $this->getRestNode();
        }
        
        $Permissions = $this->_RestNode->item->permissions;
        return $Permissions;
    }
    
    public function isWorkingCopy() {
        if ($this->_RestNode == null) {
            $this->getRestNode();
        }
        $Custom = $this->_RestNode->item->custom;
        if (!empty($Custom)) {
            if ($Custom->isWorkingCopy == true)
                return true;
            else
                return false;
        }
    }
    
    public function isCheckedOut() {
        if ($this->_RestNode == null) {
            $this->getRestNode();
        }
        $lockedBy = $this->_RestNode->item->lockedBy;
        $lockedByUser = $this->_RestNode->item->lockedByUser;

        if (!empty($lockedBy) && !empty($lockedByUser)) {
            return true;
        }
        else {
            return false;
        }
    }
    
    public function checkedOutBy() {
        if ($this->_RestNode == null) {
            $this->getRestNode();
        }
        $lockedBy = $this->_RestNode->item->lockedBy;
        $lockedByUser = $this->_RestNode->item->lockedByUser;

        if (!empty($lockedBy) && !empty($lockedByUser)) {
            return $lockedByUser;
        }
        else {
            return false;
        }
    }
    
    public function getWorkingCopy() {
        if ($this->_RestNode == null) {
            $this->getRestNode();
        }
        $Custom = $this->_RestNode->item->custom;
        if (!empty($Custom)) {
            if ($Custom->isWorkingCopy == true) {
                return $this->__toString();
            }
            else if ($Custom->hasWorkingCopy == true) {
                return $Custom->workingCopyNode;
            }
            else
                return null;
        }
    }
    
    public function getCheckedoutOriginal() {
        if ($this->_RestNode == null) {
            $this->getRestNode();
        }
        $Custom = $this->_RestNode->item->custom;
        if (!empty($Custom)) {
            if ($Custom->isWorkingCopy == true) {
                return $Custom->workingCopyOriginal;
            }
            else if ($Custom->hasWorkingCopy == true) {
                return $this->__toString();
            }
            else
                return null;
        }
    }
}
?>