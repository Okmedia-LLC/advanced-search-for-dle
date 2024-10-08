<?php 
/*
=====================================================
 Advanced Search - ehmedP
-----------------------------------------------------
 http://okmedia.az/
-----------------------------------------------------
 Copyright (c) 2024 Ehmedli Ehmed
=====================================================
 File: /engine/ajax/advancedsearch.php
=====================================================
*/

error_reporting(E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE);
ini_set('error_reporting', E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE);

define('DATALIFEENGINE', true);
define('ROOT_DIR', substr(dirname(__FILE__), 0, -12));
define('ENGINE_DIR', ROOT_DIR . '/engine');

header("Content-type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Api-Key");

if (!defined('DATALIFEENGINE')) {
    header("HTTP/1.1 403 Forbidden");
    header('Location: ../../');
    die("Hacking attempt!");
}

include_once(ENGINE_DIR . '/classes/mysql.php');
include_once(ENGINE_DIR . '/data/dbconfig.php');
include_once(ENGINE_DIR . '/data/config.php');
include_once(ENGINE_DIR . "/modules/advancedsearch/functions.advancedsearch.php");

$headers = apache_request_headers();
if (($headers['Api-Key'] ?? '') !== getSecretKey()) {
    http_response_code(403);
    die(json_encode(['error' => 'Invalid API Key']));
}

$inputData = json_decode(file_get_contents('php://input'), true);

$keywords = $inputData['keyword'] ?? [];

if (!is_array($keywords) || count($keywords) === 0) {
    die(json_encode(['error' => '"keyword" must be a non-empty array.']));
}

$keywords = array_map(function($keyword) {
    $keyword = trim($keyword);
    
    if (strlen($keyword) === 0 || strlen($keyword) > 45) {
        die(json_encode(['error' => 'Each "keyword" must be between 1 and 45 characters.']));
    }
    
    return htmlspecialchars($keyword, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}, $keywords);

$regexPattern = implode('|', $keywords);

$validSearchin = ['all', 'title', 'shortcontent', 'fullcontent', 'extrafields'];
$searchin = $inputData['searchin'] ?? 'all';

if (!isset($validSearchin[$searchin]) && !in_array($searchin, $validSearchin)) {
    echo json_encode(['error' => 'Invalid "searchin" value. Accepted values: ' . implode(', ', $validSearchin) . '.']);
    exit;
}

$extrafields = $inputData['extrafields'] ?? [];
$extrafieldMatch = $inputData['extrafieldMatch'] ?? 'some';

if ($searchin === 'extrafields') {
    if (!is_array($extrafields) || count($extrafields) === 0) {
        die(json_encode(['error' => '"extrafields" must be a non-empty array when "searchin" is "extrafields".']));
    }

    foreach ($extrafields as $extrafield) {
        if (!is_array($extrafield) || count($extrafield) !== 1) {
            die(json_encode(['error' => 'Each "extrafield" must be an associative array with one key-value pair.']));
        }
    }
}

if (!in_array($extrafieldMatch, ['some', 'every'])) {
    die(json_encode(['error' => '"extrafieldMatch" must be either "some" or "every".']));
}

$categories = $inputData['category'] ?? [];
$validCategoryFound = false;

foreach ($categories as $category) {
    if (categoryExists($category) && categoryHasPosts($category)) {
        $validCategoryFound = true;
        break;
    }
}

if (!$validCategoryFound) {
    die(json_encode(['error' => 'Invalid or blank category.']));
}

$subcats = $inputData['subcats'] ?? false;

if (!is_bool($subcats)) {
    echo json_encode(['error' => '"subcats" must be a boolean value.']);
    exit;
}

$validSort = ['title' => 'title', 'relasedate' => 'date', 'shortcontent' => 'short_story'];
$sort = $validSort[$inputData['sort'] ?? 'date'] ?? 'date';
if (!isset($validSort[$sort])) {
    echo json_encode(['error' => 'Invalid "sort" value. Accepted values: ' . implode(', ', array_keys($validSort)) . '.']);
    exit;
}

$order = in_array($inputData['order'] ?? 'asc', ['asc', 'desc']) ? $inputData['order'] : 'asc';

$relasedate = isset($inputData['relasedate']) ? htmlspecialchars($inputData['relasedate']) : '';
if ($relasedate !== 'all' && strtotime($relasedate) > time()) {
    echo json_encode(['error' => 'A future date cannot be entered.']);
    exit;
}

$relasedateDir = htmlspecialchars($inputData['relasedateDir'] ?? '');
$validDirs = ['up', 'down'];
if ($relasedateDir && !in_array($relasedateDir, $validDirs)) {
    echo json_encode(['error' => 'Invalid "relasedateDir" value. Accepted: ' . implode(', ', $validDirs) . '.']);
    exit;
}

$resultData = searchInDb($regexPattern, $searchin, $extrafields, $extrafieldMatch, $categories, $subcats, $sort, $order, $relasedate, $relasedateDir);
print_result($resultData);
