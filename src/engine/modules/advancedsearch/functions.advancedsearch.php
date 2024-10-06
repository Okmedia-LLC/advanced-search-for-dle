<?php 
/*
=====================================================
 Advanced Search - ehmedP
-----------------------------------------------------
 http://okmedia.az/
-----------------------------------------------------
 Copyright (c) 2024 Ehmedli Ehmed
=====================================================
 File: /engine/modules/advancedsearch/functions.advancedsearch.php
=====================================================
*/

if(!defined('DATALIFEENGINE')) {
	header( "HTTP/1.1 403 Forbidden" );
	header ( 'Location: ../../' );
	die( "Hacking attempt!" );
}

function getSecretKey(): string {
    $data = json_decode(file_get_contents(ENGINE_DIR . "/data/advancedsearch.json"), true);
    return $data['secretkey'] ?? "Secret Key not exists!";
}

function generateKey(): string {
    return implode('-', str_split(substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 4)), 0, 19), 4));
}

function searchInDb(
    string $keywords, string $searchin, 
    array $extrafields, string $extrafieldMatch,
    array $categories, bool $subcats, 
    string $sort, string $order, 
    string $relasedate, string $relasedateDir
): array {
    
    global $db;
    
    $prepare = "id, title, autor, alt_name, category, date, descr, metatitle, keywords, full_story, short_story, xfields";
    
    $final_query = "SELECT $prepare FROM `". PREFIX ."_post` p WHERE 1=1";

    if (!empty($keywords)) {
        switch ($searchin) {
            case 'all':
                $final_query .= " AND (p.title REGEXP '$keywords' OR p.short_story REGEXP '$keywords' OR p.full_story REGEXP '$keywords')";
                break;
            case 'title':
                $final_query .= " AND p.title REGEXP '$keywords'";
                break;
            case 'fullcontent':
                $final_query .= " AND p.full_story REGEXP '$keywords'";
                break;
            case 'shortcontent':
                $final_query .= " AND p.short_story REGEXP '$keywords'";
                break;
            case 'extrafields':
                $extrafieldQueries = [];

                foreach ($extrafields as $extrafield) {
                    foreach ($extrafield as $key => $value) {
                        $extrafieldQueries[] = "p.xfields LIKE '%$key|$value%'";
                    }
                }
    
                if (!empty($extrafieldQueries)) {
                    if ($extrafieldMatch === 'some') {
                        $final_query .= " AND (" . implode(' OR ', $extrafieldQueries) . ")";
                    } elseif ($extrafieldMatch === 'every') {
                        $final_query .= " AND (" . implode(' AND ', $extrafieldQueries) . ")";
                    }
                }
                
                break;
            default: break;
        }
    }

    if (!empty($categories)) {
        $categoriesList = is_array($categories) ? implode(',', array_map('intval', $categories)) : intval($categories);

        if ($subcats) {
            $childCategories = getChildCategories($categoriesList);
            $categoriesList = implode(',', array_merge([$categoriesList], $childCategories));
        }
        
        $final_query .= " AND FIND_IN_SET(p.category, '$categoriesList')";
    }

    if (!empty($relasedate)) {
        $final_query .= " AND p.date " . ($relasedateDir === 'desc' ? '<=' : '>=') . " '" . $db->safesql($relasedate) . "'";
    }

    if (!empty($sort)) {
        $final_query .= " ORDER BY " . $db->safesql($sort) . " " . ($order === 'asc' ? 'ASC' : 'DESC');
    }

    $rows = $db->super_query($final_query, 1);
    
    $results = fillRowForPost($rows);
    
    return $results;
}

function getChildCategories(string $categories): array {
    global $db;
    $childCategories = [];

    $categories_array = explode(',', $categories);
    
    function fetchChildCategories($categoryIds) {
        global $db;
        $childCategories = [];

        $categoryIds = implode(',', array_map('intval', $categoryIds));
        
        $final_query = "SELECT id FROM `" . PREFIX . "_category` WHERE parentid IN ($categoryIds)";
        $result = $db->query($final_query);
        
        while ($row = $result->fetch_assoc()) {
            $childCategories[] = (int) $row['id'];
        }

        return $childCategories;
    }

    $childCategories = fetchChildCategories($categories_array);
    
    $allChildCategories = $childCategories;
    $previousCount = 0;

    while (!empty($childCategories)) {
        $newChildCategories = fetchChildCategories($childCategories);
        
        if (count($newChildCategories) === 0) {
            break;
        }
        
        $allChildCategories = array_merge($allChildCategories, $newChildCategories);
        
        $allChildCategories = array_unique($allChildCategories);
        
        $childCategories = $newChildCategories;
    }

    return $allChildCategories;
}

function fillRowForPost($rows): array {
    global $config;
    $dataRows = [];
    
    foreach($rows as $row) {
        $dataRow = [];
        
        $dataRow["id"] = $row["id"];
        
        $dataRow["title"] = htmlspecialchars( $row["title"] );
        
        $dataRow["alt_name"] = htmlspecialchars( $row["alt_name"] );
        
        $dataRow["autor"] = htmlspecialchars( $row["autor"] );
        
        $dataRow["descr"] = htmlspecialchars( $row["autor"] );
        
        $dataRow["category"] = getCategorySimpleInfo($row["category"]);
        
        $dataRow["date"] = $row["date"];
        
        $dataRow["keywords"] = [];
        
        foreach($row['keywords'] as $keyword) {
            $dataRow["keywords"][] = htmlspecialchars( $keyword );
        }
        
        $dataRow["metatitle"] = htmlspecialchars( $row["metatitle"] );
        
        $dataRow["xfields"] = xfieldsdataload_advancedsearch( $row["xfields"] );
        
        $dataRow["short_story"] = stripslashes( $row["short_story"] );
        
        $dataRow["full_story"] = stripslashes( $row["full_story"] );
        
        $dataRows[] = $dataRow;
    }
    
    return $dataRows; 
}

function getCategorySimpleInfo(string $categories): array {
    global $db;

    $categories_array = array_map('intval', explode(',', $categories));

    if (empty($categories_array)) {
        return [];
    }

    $in_clause = implode(',', $categories_array);
    
    $prepare = "id, parentid, name, alt_name";

    $final_query = "SELECT $prepare FROM `". PREFIX ."_category` WHERE id IN ($in_clause)";

    $result = $db->query($final_query);
    
    if (!$result) {
        return [];
    }

    $category_objects = [];

    while ($row = $result->fetch_assoc()) {
        $category_objects[] = (object) [
            'id' => (int) $row['id'],
            'parentid' => (int) $row['parentid'],
            'name' => $row['name'],             
            'alt_name' => $row['alt_name'],
            'link' => getCategoryUrl((int) $row['id'])
        ];
    }

    return $category_objects;
}

function getCategoryUrl(int $id): string {
    global $db, $config;
    
    if ($id <= 0) return "";

    $final_query = "SELECT id, parentid, alt_name FROM `" . PREFIX . "_category` WHERE id = $id";
    $result = $db->query($final_query);

    if (!$result) return "";

    $cat_info = $result->fetch_assoc();
    if (!$cat_info) return "";

    $url = htmlspecialchars($cat_info['alt_name']);
    $parent_id = (int)$cat_info['parentid'];

    while ($parent_id > 0) {
        $parent_query = "SELECT id, parentid, alt_name FROM `" . PREFIX . "_category` WHERE id = $parent_id";
        $parent_result = $db->query($parent_query);

        if (!$parent_result) break;

        $parent_info = $parent_result->fetch_assoc();
        if (!$parent_info) break;

        $url = htmlspecialchars($parent_info['alt_name']) . "/" . $url;
        $parent_id = (int)$parent_info['parentid'];
    }
    
    $url = $config['http_home_url']. $url;

    return $url;
}

function categoryExists($categoryId): bool {
    global $db;
    
    if (!is_numeric($categoryId) || $categoryId <= 0) {
        return false;
    }
    
    $result = $db->super_query("SELECT COUNT(*) AS count FROM `" . PREFIX . "_category` WHERE `id` = " . intval($categoryId));
    
    return $result['count'] > 0;
}

function xfieldsdataload_advancedsearch($id) {
	
	if( !is_string($id) OR !$id ) return array();
	
	$xfieldsdata = explode( "||", $id );
	
	foreach ( $xfieldsdata as $xfielddata ) {
		list ( $xfielddataname, $xfielddatavalue ) = explode( "|", $xfielddata );
		$xfielddataname = str_replace( "&#124;", "|", $xfielddataname );
		$xfielddataname = str_replace( "__NEWL__", "\r\n", $xfielddataname );
		$xfielddatavalue = str_replace( "&#124;", "|", $xfielddatavalue );
		$xfielddatavalue = str_replace( "__NEWL__", "\r\n", $xfielddatavalue );
		$data[$xfielddataname] = trim($xfielddatavalue);
	}
	
	return $data;
}

function categoryHasPosts($categoryId) {
    global $db;

    if (!is_numeric($categoryId) || $categoryId <= 0) {
        return false;
    }

    $result = $db->super_query("SELECT COUNT(*) AS count FROM `" . PREFIX . "_post` WHERE `category` = " . intval($categoryId));
    
    return $result['count'] > 0;
}

function print_result(array $resultData): void {
    echo json_encode(
            $resultData
        );
}