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

function searchInDb($keyword, $searchin, $categories, $sort, $order, $relasedate, $relasedateDir): array {
    global $db;
    
    $prepare = "id, title, autor, alt_name, category, date, descr, metatitle, keywords, full_story, short_story, xfields";
    
    $final_query = "SELECT $prepare FROM `". PREFIX ."_post` p WHERE 1=1";

    if (!empty($keyword)) {
        if ($searchin === 'all') {
            $final_query .= " AND (p.title LIKE '%" . $db->safesql($keyword) . "%' OR p.short_story LIKE '%" . $db->safesql($keyword) . "%' OR p.full_story LIKE '%" . $db->safesql($keyword) . "%')";
        } else if ($searchin === 'title') {
            $final_query .= " AND p.title LIKE '%" . $db->safesql($keyword) . "%'";
        } elseif ($searchin === 'fullcontent') {
            $final_query .= " AND p.full_story LIKE '%" . $db->safesql($keyword) . "%'";
        } elseif ($searchin === 'shortcontent') {
            $final_query .= " AND p.short_story LIKE '%" . $db->safesql($keyword) . "%'";
        }
    }

    if (!empty($categories)) {
        $categoriesList = is_array($categories) ? implode(',', array_map('intval', $categories)) : intval($categories);
        
        $final_query .= " AND p.category IN ($categoriesList)";
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
        
        $dataRow["category"] = $row["category"];
        
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

function fillRowForStatic($rows): array {
    global $config;
    $dataRows = [];
    
    foreach($rows as $row) {
        $dataRow = [];
        
        $dataRow["id"] = $row["id"];
        
        $dataRow["title"] = htmlspecialchars( $row["title"] );
        
        $dataRow["alt_name"] = htmlspecialchars( $row["alt_name"] );
        
        $dataRow["autor"] = htmlspecialchars( $row["autor"] );
        
        $dataRow["descr"] = htmlspecialchars( $row["autor"] );
        
        $dataRow["category"] = $row["category"];
        
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

function categoryExists($categoryId) {
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
