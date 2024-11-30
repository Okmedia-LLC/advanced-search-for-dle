<?php
// filters.php

return [
    'id' => [
        'default' => -1,
        'validate' => function ($value) {
            if ($value === -1) return $value;
            if (!is_numeric($value)) {
                throw new Exception('"id" must be a numeric value.');
            }
            return (int)$value;
        },
    ],
    'keywords' => [
        'default' => [],
        'validate' => function ($value) {
            if (empty($value)) return [];
            if (!is_array($value)) throw new Exception('"keywords" must be an array.');
    
            return array_map(function ($keyword) {
                $keyword = trim($keyword);
    
                if (strlen($keyword) === 0 || strlen($keyword) > 45)
                    throw new Exception('Each "keyword" must be between 1 and 45 characters.');
    
                return htmlspecialchars($keyword, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }, $value);
        },
    ],
    'searchin' => [
        'default' => '',
        'valid' => ['all', 'title', 'shortcontent', 'fullcontent'],
        'validate' => function ($value, $valid) {
            if (empty($value)) return '';
            
            if (!in_array($value, $valid)) {
                throw new Exception('Invalid "searchin" value. Accepted values: ' . implode(', ', $valid) . '.');
            }
            return $value;
        },
    ],
    'extrafields' => [
        'default' => [],
        'validate' => function ($value) {
            if (empty($value)) return [];
            
            if (!is_array($value) || count($value) === 0) {
                throw new Exception('"extrafields" must be a non-empty array when "searchin" is "extrafields".');
            }

            foreach ($value as $field) {
                if (!is_array($field) || count($field) !== 1) {
                    throw new Exception('Each "extrafield" must be an associative array with one key-value pair.');
                }
            }
            return $value;
        },
    ],
    'extrafieldMatch' => [
        'default' => 'some',
        'valid' => ['every', 'some'],
        'validate' => function ($value, $valid) {
            if (empty($value)) return '';
            if (!in_array($value, $valid)) 
                throw new Exception('"extrafieldMatch" must be either "every" or "some".');
                
            return $value;
        },
    ],
    'category' => [
        'validate' => function ($value) {
            if (empty($value)) return [];
            
            if (!is_array($value)) {
                throw new Exception('"category" must be an array.');
            }

            foreach ($value as $category) {
                if (!categoryExists($category) || !categoryHasPosts($category)) {
                    throw new Exception('Invalid or blank category.');
                }
            }
            return $value;
        },
    ],
    'subcats' => [
        'default' => false,
        'validate' => function ($value) {
            if (!is_bool($value)) {
                throw new Exception('"subcats" must be a boolean value.');
            }
            return $value;
        },
    ],
    'sort' => [
        'default' => 'relasedate',
        'valid' => ['title', 'relasedate', 'shortcontent'],
        'validate' => function ($value, $valid) {
            if (!in_array($value, $valid)) {
                throw new Exception('Invalid "sort" value. Accepted values: ' . implode(', ', $valid) . '.');
            }
            return $value;
        },
    ],
    'order' => [
        'default' => 'asc',
        'valid' => ['asc', 'desc'],
        'validate' => function ($value, $valid) {
            if (!in_array($value, $valid)) {
                throw new Exception('"order" must be "asc" or "desc".');
            }
            return $value;
        },
    ],
    'relasedate' => [
        'default' => '',
        'validate' => function ($value) {
            if ($value !== 'all' && strtotime($value) > time()) {
                throw new Exception('A future date cannot be entered.');
            }
            return htmlspecialchars($value);
        },
    ],
    'relasedateDir' => [
        'default' => '',
        'valid' => ['up', 'down'],
        'validate' => function ($value, $valid) {
            if ($value && !in_array($value, $valid)) {
                throw new Exception('Invalid "relasedateDir" value. Accepted: ' . implode(', ', $valid) . '.');
            }
            return $value;
        },
    ],
];
