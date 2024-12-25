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

define('SECRET_KEY_FILE', ENGINE_DIR . "/data/advancedsearch.json");

function getSecretKey(): string {
    $data = json_decode(file_get_contents(SECRET_KEY_FILE), true);
    return $data['secretkey'] ?? "Secret Key not exists!";
}

function generateKey(): string {
    return implode('-', str_split(substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 4)), 0, 19), 4));
}

/**
 * Performs a search in the database based on provided parameters.
 *
 * @param array $params Parameters for search.
 * @return array Search results.
 */
function searchInDb(array $params): array {
    global $db;

    $id = $params['id'];
    $keywords = $params['keywords'];
    $searchin = $params['searchin'];
    $extrafields = $params['extrafields'];
    $extrafieldMatch = $params['extrafieldMatch'];
    $categories = $params['categories'];
    $subcats = $params['subcats'];
    $sort = $params['sort'];
    $order = $params['order'];
    $relasedate = $params['relasedate'];
    $relasedateDir = $params['relasedateDir'];

    $queryParts = [];
    $queryParts[] = "SELECT p.`id`, p.`title`, p.`autor`, p.`alt_name`, p.`category`, p.`date`, p.`descr`, p.`metatitle`, p.`keywords`, p.`full_story`, p.`short_story`, p.`xfields`";
    $queryParts[] = "FROM `" . PREFIX . "_post` p WHERE 1=1";

    if (isset($params['id']) && $params['id'] !== -1) {
        $queryParts[] = "AND p.`id` = ". $id;
    } else {

        // Keywords filter
        if (!empty($keywords)) {
            $queryParts[] = buildKeywordSearchQuery($keywords, $searchin);
        }
    
        // Extra fields filter
        if (!empty($extrafields)) {
            $queryParts[] = buildExtraFieldsQuery($extrafields, $extrafieldMatch);
        }
    
        // Categories filter
        if (!empty($categories)) {
            $queryParts[] = buildCategoryFilterQuery($categories, $subcats);
        }
    
        // Release date filter
        if (!empty($relasedate)) {
            $queryParts[] = "AND p.`date` " . ($relasedateDir === 'desc' ? '<=' : '>=') . " '" . $db->safesql($relasedate) . "'";
        }
    
        // Sorting
        if (!empty($sort)) {
            $queryParts[] = buildSortingQuery($sort, $order);
        }
    }

    // Execute the query
    $finalQuery = implode(' ', $queryParts);
    $rows = $db->super_query($finalQuery, 1);
    
    return fillRowForPost($rows);
}

/**
 * Builds the query for sort search.
 */
function buildSortingQuery(string $sort, string $order): string {
    global $db;

    if (empty($sort)) {
        return '';
    }
    
    switch ($sort) {
        case 'title':
            $resultSort = 'title';
        case 'fullcontent':
            $resultSort = 'full_story';
        case 'shortcontent':
            $resultSort = 'short_story';
        case 'relasedate':
        default:
            $resultSort = 'date';
    }

    $direction = ($order === 'asc') ? 'ASC' : 'DESC';
    return "ORDER BY " . $resultSort . " $direction";
}


/**
 * Builds the query for keyword-based search.
 */
function buildKeywordSearchQuery(string $keywords, string $searchin): string {
    switch ($searchin) {
        case 'title':
            return "AND p.title REGEXP '$keywords'";
        case 'fullcontent':
            return "AND p.full_story REGEXP '$keywords'";
        case 'shortcontent':
            return "AND p.short_story REGEXP '$keywords'";
        case 'all':
        default:
            return "AND (p.title REGEXP '$keywords' OR p.short_story REGEXP '$keywords' OR p.full_story REGEXP '$keywords')";
    }
}

/**
 * Builds the query for extra fields search.
 */
function buildExtraFieldsQuery(array $extrafields, string $match): string {
    $queries = [];
    foreach ($extrafields as $extrafield) {
        foreach ($extrafield as $key => $value) {
            $queries[] = "p.xfields LIKE '%$key|$value%'";
        }
    }

    if (!empty($queries)) {
        return "AND (" . implode($match === 'every' ? ' AND ' : ' OR ', $queries) . ")";
    }

    return '';
}

/**
 * Builds the query for category filtering.
 */
function buildCategoryFilterQuery($categories, bool $subcats): string {
    global $db;

    $categoriesList = is_array($categories) ? implode(',', array_map('intval', $categories)) : intval($categories);

    if ($subcats) {
        $childCategories = getChildCategories($categoriesList);
        $categoriesList = implode('|', array_merge([$categoriesList], $childCategories));
    }

    return "AND p.category REGEXP '(^|,)($categoriesList)(,|$)'";
}

/**
 * Fetches all child categories for the given categories.
 */
function getChildCategories(string $categories): array {
    global $db;

    $categoriesArray = explode(',', $categories);
    $allChildCategories = fetchChildCategories($categoriesArray);

    while (!empty($allChildCategories)) {
        $newChildCategories = fetchChildCategories($allChildCategories);
        if (empty($newChildCategories)) break;

        $allChildCategories = array_unique(array_merge($allChildCategories, $newChildCategories));
    }

    return $allChildCategories;
}

/**
 * Fetches direct child categories for the given category IDs.
 */
function fetchChildCategories(array $categoryIds): array {
    global $db;

    $categoryIds = implode(',', array_map('intval', $categoryIds));
    $query = "SELECT id FROM `" . PREFIX . "_category` WHERE parentid IN ($categoryIds)";
    $result = $db->query($query);

    $childCategories = [];
    while ($row = $result->fetch_assoc()) {
        $childCategories[] = (int)$row['id'];
    }

    return $childCategories;
}

/**
 * Processes rows for posts and prepares them for output.
 */
function fillRowForPost($rows): array {
    global $config;
    $dataRows = [];

    foreach ($rows as $row) {
        $dataRows[] = [
            "id" => $row["id"],
            "title" => htmlspecialchars($row["title"]),
            "alt_name" => htmlspecialchars($row["alt_name"]),
            "autor" => htmlspecialchars($row["autor"]),
            "descr" => htmlspecialchars($row["descr"]),
            "category" => getCategorySimpleInfo($row["category"]),
            "date" => $row["date"],
            "images" => getAllImages($row),
            "full_link" => getPostLink($row),
            "keywords" => array_map('htmlspecialchars', explode(',', $row["keywords"])),
            "metatitle" => htmlspecialchars($row["metatitle"]),
            "xfields" => xfieldsdataload_advancedsearch($row["xfields"]),
            "short_story" => stripslashes($row["short_story"]),
            "full_story" => stripslashes($row["full_story"])
        ];
    }

    return $dataRows;
}

function getPostLink(array $row): string {
    global $config;
    $full_link = "";
    
    if( $config['allow_alt_url'] ) {
			
		if( $config['seo_type'] == 1 OR $config['seo_type'] == 2  ) {
			
			if( $row['category'] and $config['seo_type'] == 2 ) {

				$cats_url = getCategoryUrl((int) $row['id']);
				
				if($cats_url) {
					
					$full_link = $config['http_home_url'] . $cats_url . "/" . $row['id'] . "-" . $row['alt_name'] . ".html";
					
				} else $full_link = $config['http_home_url'] . $row['id'] . "-" . $row['alt_name'] . ".html";
			
			} else {
				
				$full_link = $config['http_home_url'] . $row['id'] . "-" . $row['alt_name'] . ".html";
			
			}
		
		} else {
			
			$full_link = $config['http_home_url'] . date( 'Y/m/d/', $row['date'] ) . $row['alt_name'] . ".html";
		}
	
	}
	
	return $full_link;
}

function getAllImages( array $row ) {
    
    $shortContent = stripslashes($row['short_story']);

	$images = array();
	preg_match_all('/(img|src)=("|\')[^"\'>]+/i', $shortContent.$row['xfields'], $media);
	$data=preg_replace('/(img|src)("|\'|="|=\')(.*)/i',"$3",$media[0]);

	foreach($data as $url) {
		$info = pathinfo($url);
		if (isset($info['extension'])) {
			if ($info['filename'] == "spoiler-plus" OR $info['filename'] == "spoiler-minus" OR strpos($info['dirname'], 'engine/data/emoticons') !== false) continue;
			$info['extension'] = strtolower($info['extension']);
			if (($info['extension'] == 'jpg') || ($info['extension'] == 'jpeg') || ($info['extension'] == 'gif') || ($info['extension'] == 'png') || ($info['extension'] == 'bmp') || ($info['extension'] == 'webp') || ($info['extension'] == 'avif')) array_push($images, $url);
		}
	}
	
	return $images;
}

function print_result(array $resultData): void {
    echo json_encode(
            $resultData
        );
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

function xfieldsdataload_advancedsearch($id) {
	
	if( !is_string($id) OR !$id ) return array();
	
	$xfieldsdata = explode( "||", $id );
	
	foreach ( $xfieldsdata as $xfielddata ) {
		list ( $xfielddataname, $xfielddatavalue ) = explode( "|", $xfielddata );
		$xfielddataname = str_replace( "&#124;", "|", $xfielddataname );
		$xfielddataname = str_replace( "__NEWL__", "\r\n", $xfielddataname );
		$xfielddatavalue = str_replace( "&#124;", "|", $xfielddatavalue );
		$xfielddatavalue = str_replace( "__NEWL__", "\r\n", $xfielddatavalue );
        $xfielddatavalue = stripslashes( $xfielddatavalue );
		$data[$xfielddataname] = trim($xfielddatavalue);
	}
	
	return $data;
}

function categoryExists($category) {
    global $db;
    return (bool) $db->super_query("SELECT 1 FROM `" . PREFIX . "_category` WHERE id = '" . intval($category) . "' LIMIT 1");
}

function categoryHasPosts($category) {
    global $db;
    return (bool) $db->super_query("SELECT posi FROM `" . PREFIX . "_category` WHERE id = '" . intval($category) . "' LIMIT 1");
}
