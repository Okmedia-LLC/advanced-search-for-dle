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

if (!isset($headers['Api-Key']) || $headers['Api-Key'] !== getSecretKey()) {
    header('HTTP/1.1 403 Forbidden');
    die(json_encode(['error' => 'Invalid API Key']));
}

$inputData = json_decode(file_get_contents('php://input'), true);

$keyword = isset($inputData['keyword']) ? htmlspecialchars($inputData['keyword']) : '';

if (isset($inputData['keyword']) && (strlen($keyword) < 1 || strlen($keyword) > 45)) {
    echo json_encode(['error' => '"keyword" must be between 1 and 45 characters.']);
    exit;
}

$searchin = isset($inputData['searchin']) ? htmlspecialchars($inputData['searchin']) : 'all';
$validSearchin = ['all', 'title', 'shortcontent', 'fullcontent'];

if (!in_array($searchin, $validSearchin)) {
    echo json_encode(['error' => 'Invalid "searchin" value. Accepted values: ' . implode(', ', $validSearchin) . '.']);
    exit;
}

$categories = isset($inputData['category']) ? $inputData['category'] : [];
if (!empty($categories)) {
    foreach ($categories as $category) {
        if (!categoryExists($category) || !categoryHasPosts($category)) {
            echo json_encode(['error' => 'Invalid or blank category: ' . htmlspecialchars($category)]);
            exit;
        }
    }
}

$sort = isset($inputData['sort']) ? htmlspecialchars($inputData['sort']) : 'date';
$validSort = ['title', 'relasedate', 'shortcontent'];
if (isset($inputData['sort']) && !in_array($sort, $validSort)) {
    echo json_encode(['error' => 'Invalid "sort" value. Accepted values: ' . implode(', ', $validSort) . '.']);
    exit;
}

$order = isset($inputData['order']) ? htmlspecialchars($inputData['order']) : 'asc';
if (!in_array($order, ['asc', 'desc'])) {
    echo json_encode(['error' => 'Invalid "order" value. Accepted: asc, desc.']);
    exit;
}

$relasedate = isset($inputData['relasedate']) ? htmlspecialchars($inputData['relasedate']) : 'all';
if ($relasedate !== 'all' && strtotime($relasedate) > time()) {
    echo json_encode(['error' => 'A future date cannot be entered.']);
    exit;
}

$relasedateDir = isset($inputData['relasedateDir']) ? htmlspecialchars($inputData['relasedateDir']) : '';
if ($relasedateDir && !in_array($relasedateDir, ['up', 'down'])) {
    echo json_encode(['error' => 'Invalid "relasedateDir" value. Accepted: up, down.']);
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
