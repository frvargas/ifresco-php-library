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
    set_include_path(realpath(__DIR__)."/../../");
    require_once "../../ClassLoader.php";

    require_once('../config.php');

    try {
        $RestAuth = new RESTAuthentication($repositoryUrl);
        print_r($RestAuth->login($userName, $password));
    }
    catch (Exception $e) {
        krumo::debug($e);
    }
    
?>