<?php
/*
=====================================================
 Advanced Search - ehmedP
-----------------------------------------------------
 http://okmedia.az/
-----------------------------------------------------
 Copyright (c) 2024 Ehmedli Ehmed
=====================================================
 File: /engine/ajax/advancedsearch/advancedsearch.php
=====================================================
*/

require_once ( ADVANCED_SERARCH_DIR . "/Auth/auth.php");
require_once ( ADVANCED_SERARCH_DIR . "/modules/filters_validation.php");
require_once ( ADVANCED_SERARCH_DIR . "/Builders/SearchParamsBuilder.php");

$filters = require_once(ADVANCED_SERARCH_DIR . "/modules/filters.php");

try {
    $inputData = json_decode(file_get_contents('php://input'), true);
    
    $validatedData = validateAndProcessFilters($inputData, $filters);

    $params = (new SearchParamsBuilder())
        ->setId($validatedData['id'])
        ->setKeywords(isset($validatedData['keywords']) ? implode('|', $validatedData['keywords']) : '')
        ->setSearchIn($validatedData['searchin'])
        ->setExtraFields($validatedData['extrafields'])
        ->setExtraFieldMatch($validatedData['extrafieldMatch'])
        ->setCategories($validatedData['category'])
        ->setSubcats($validatedData['subcats'])
        ->setSort($validatedData['sort'])
        ->setOrder($validatedData['order'])
        ->setReleaseDate($validatedData['relasedate'])
        ->setReleaseDateDir($validatedData['relasedateDir'])
    ->build();
    
    $result = searchInDb($params);

    print_result($result);

} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
