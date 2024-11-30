<?php 
/*
=====================================================
 Advanced Search - ehmedP
-----------------------------------------------------
 http://okmedia.az/
-----------------------------------------------------
 Copyright (c) 2024 Ehmedli Ehmed
=====================================================
 File: /engine/inc/advancedsearch.php
=====================================================
*/

if (!defined('DATALIFEENGINE') or !defined('LOGGED_IN')) {
	header("HTTP/1.1 403 Forbidden");
	header('Location: ../../');
	die("Hacking attempt!");
}

$headingTitle = "Advanced search";
$icon = "<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"mr-5\" style=\"display:inline-block;\" width=\"16\" viewBox=\"0 0 512 512\"><path d=\"M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z\"/></svg>";

$version = [
	'name'      => 'Advanced Sarch',
	'descr'     => 'Allows advanced search in any section of the site',
	'version'   => '0.1.2',
	'changelog' => [],
	'id'        => 'advancedsearch',
];

require_once(ROOT_DIR . "/advancedsearch/modules/functions.advancedsearch.php");

$advancedsearch = array();
if (file_exists(ENGINE_DIR . '/data/' . $version['id'] . '.json'))
	$advancedsearch = json_decode(file_get_contents(ENGINE_DIR . '/data/' . $version['id'] . '.json'), true);
	
foreach ($advancedsearch as $name => $value) {
	$advancedsearch[$name] = htmlspecialchars(strip_tags(stripslashes(trim(urldecode($value)))));
}

function showContent(): void {
    global $headingTitle, $config, $version;
    
    $secretkey = getSecretKey();
    
    echo <<<HTML
    <form action="?mod={$version['id']}" method="post" name="optionsbar" id="optionsbar">
		<div class="panel panel-default">
			<div class="panel-heading">
				{$version['name']}
			</div>
				<div class="table-responsive">
				    <table class="table table-xs table-hover">
						<tbody>
						    <tr>
                                <td class="col-xs-6 col-sm-6 col-md-5">
                                    <h6 class="media-heading text-semibold"> Secret Key </h6>
                                    <span class="text-muted text-size-small hidden-xs"> 
                                        Unique access key. The key is generated by a random algorithm.
                                    </span>
                                </td>
                                <td class="col-xs-6 col-sm-6 col-md-7">
                                    <input type="text" class="form-control mr-1" name="secretkey" id="secretkey" value="{$secretkey}">
                                    <input type="button" class="btn bg-teal-400 btn-sm btn-raised" id="genKey" value="Generate key">
                                </td>
                            </tr>
					 	</tbody>
					</table>
				</div>
				
				<script>
					$(() => {
                        $('#genKey').on('click', function() {
                            $.ajax({
                                url: '{$config['http_home_url']}{$config['admin_path']}?mod={$version['id']}&action=keygenerate',
                                method: 'GET',
                                data: $('#optionsbar').serializeArray(),
                                success: function(data) {
                                    $('#secretkey').val(data);
                                    
                                    $.ajax({
                                        url: '{$config['http_home_url']}{$config['admin_path']}?mod={$version['id']}&action=save',
                                        method: 'POST',
                                        data: $('#optionsbar').serializeArray(),
                                        success: function(data) {
                                            console.log(data);
                                        }
                                    });
                                }
                            });
                        });
                    });

                </script>

HTML;
}

$action = (empty($action)) ? $_GET['action'] : $action;

echoheader("$icon<span class=\"text-semibold\">{$version['name']} (v{$version['version']})</span>", $version['name']);

switch ($action) {
    
    case 'keygenerate':
		if ($_GET) {
			ob_end_clean();
			echo generateKey();
		}
		return false;

		break;
	
	case 'save': 
        if($_POST) {
            ob_end_clean();
            
            $secretKey = htmlspecialchars(strip_tags(stripslashes(trim(urldecode($_POST['secretkey'])))));
            
            $advancedsearch = [];
            if (file_exists(ENGINE_DIR . '/data/' . $version['id'] . '.json')) {
                $advancedsearch = json_decode(file_get_contents(ENGINE_DIR . '/data/' . $version['id'] . '.json'), true);
            }
            
            $advancedsearch['secretkey'] = $secretKey;
            
            file_put_contents(ENGINE_DIR . '/data/' . $version['id'] . '.json', json_encode($advancedsearch, JSON_PRETTY_PRINT));
    
            print_r( $_POST );
        }
        return false;
        
        break;
    
  default:
    showContent();
    break;
    
}

echofooter();

?>