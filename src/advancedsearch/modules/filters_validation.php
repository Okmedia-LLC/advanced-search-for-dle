<?php

/*
=====================================================
 Advanced Search - ehmedP
-----------------------------------------------------
 http://okmedia.az/
-----------------------------------------------------
 Copyright (c) 2024 Ehmedli Ehmed
=====================================================
 File: /engine/ajax/advancedsearch/modules/filters_validation.php
=====================================================
*/

function validateAndProcessFilters($inputData, $filters) {
    
    $validatedData = [];
    
    foreach ($filters as $key => $filter) {
        $value = $inputData[$key] ?? ($filter['default'] ?? null);

        if (isset($filter['required']) && $filter['required'] && $value === null) {
            throw new Exception("Missing required field: $key");
        }

        try {
            $value = $filter['validate']($value, $filter['valid'] ?? null);
        } catch (Throwable $e) {
            throw new Exception("Validation error for key: $key. Error: " . $e->getMessage());
        }

        $validatedData[$key] = $value;
    }

    return $validatedData;
}
