<?php

/*
=====================================================
 Advanced Search - ehmedP
-----------------------------------------------------
 http://okmedia.az/
-----------------------------------------------------
 Copyright (c) 2024 Ehmedli Ehmed
=====================================================
 File: /engine/ajax/advancedsearch/modules/init.php
=====================================================
*/

error_reporting(E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE);
ini_set('error_reporting', E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE);

header("Content-type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Api-Key");

if (!defined('DATALIFEENGINE')) {
    header("HTTP/1.1 403 Forbidden");
    header('Location: ../../');
    die("Hacking attempt!");
}

require_once(ENGINE_DIR . '/classes/mysql.php');
require_once(ENGINE_DIR . '/data/dbconfig.php');
require_once(ENGINE_DIR . '/data/config.php');

require_once(ADVANCED_SERARCH_DIR . "/modules/functions.advancedsearch.php");
require_once(ADVANCED_SERARCH_DIR . "/advancedsearch.php");