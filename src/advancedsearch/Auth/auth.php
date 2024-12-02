<?php 
/*
=====================================================
 Advanced Search - ehmedP
-----------------------------------------------------
 http://okmedia.az/
-----------------------------------------------------
 Copyright (c) 2024 Ehmedli Ehmed
=====================================================
 File: /engine/ajax/advancedsearch/modules/auth.php
=====================================================
*/

$headers = apache_request_headers();

if ( !in_array(getSecretKey(), array_values($headers)) ) {
    http_response_code(403);
    die(json_encode(['error' => 'Invalid API Key']));
}