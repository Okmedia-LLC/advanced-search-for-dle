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
include_once(ENGINE_DIR . "/modules/advancedsearch/functions.advancedsearch.php");

$headers = apache_request_headers();
if (($headers['Api-Key'] ?? '') !== getSecretKey()) {
    http_response_code(403);
    die(json_encode(['error' => 'Invalid API Key']));
}

$inputData = json_decode(file_get_contents('php://input'), true);

$keyword = htmlspecialchars($inputData['keyword'] ?? '');
if (strlen($keyword) > 45) {
    die(json_encode(['error' => '"keyword" must be between 1 and 45 characters.']));
}

$validSearchin = ['all', 'title', 'shortcontent', 'fullcontent'];
$searchin = $inputData['searchin'] ?? 'all';

if (!isset($validSearchin[$searchin]) && !in_array($searchin, $validSearchin)) {
    echo json_encode(['error' => 'Invalid "searchin" value. Accepted values: ' . implode(', ', $validSearchin) . '.']);
    exit;
}

$categories = $inputData['category'] ?? [];
foreach ($categories as $category) {
    if (!categoryExists($category) || !categoryHasPosts($category)) {
        die(json_encode(['error' => 'Invalid or blank category: ' . htmlspecialchars($category)]));
    }
}

$validSort = ['title' => 'title', 'relasedate' => 'date', 'shortcontent' => 'short_story'];
$sort = $validSort[$inputData['sort'] ?? 'date'] ?? 'date';
if (!isset($validSort[$sort])) {
    echo json_encode(['error' => 'Invalid "sort" value. Accepted values: ' . implode(', ', array_keys($validSort)) . '.']);
    exit;
}

$order = in_array($inputData['order'] ?? 'asc', ['asc', 'desc']) ? $inputData['order'] : 'asc';

$relasedate = isset($inputData['relasedate']) ? htmlspecialchars($inputData['relasedate']) : 'all';
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

// $responseData = [
//     'keyword' => $keyword,
//     'searchin' => $searchin,
//     'categories' => $categories,
//     'sort' => $sort,
//     'order' => $order,
//     'relasedate' => $relasedate,
//     'relasedateDir' => $relasedateDir
// ];

// echo json_encode($responseData);

$resultData = searchInDb($keyword, $searchin, $categories, $sort, $order, $relasedate, $relasedateDir);
print_result($resultData);
