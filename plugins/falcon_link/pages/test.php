<?php
include "../../../include/db.php";
include_once "../../../include/general.php";
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}
include "../../../include/search_functions.php";
include "../../../include/resource_functions.php";


function table_cell($data)
    {
    $return = "<table border='1'>";
    foreach ($data as $key => $value)
        {
        $return .= "<tr><td>$key</td><td>";
        if (is_array($value))
            {
            $return .= table_cell($value);
            }
        else
            {
            $return .= ($key == 'picture') ? "<img src='" . $value . "' style='height:150px;width: 150px;'/>" : $value;
            }
        $return .= "</td><tr>";
        }
    $return .= "</tr></table>";
    return($return);
    }
  
  
if (trim($falcon_link_api_key) == "" || count($falcon_link_restypes) < 1)
		{
        $linkadd = checkperm('a') ? array("<a href='$baseurl/plugins/falcon_link/pages/setup.php'>","</a>") : array("","");
        echo sprintf($lang["falcon_link_notconfigured"] . "%s$baseurl/plugins/falcon_link/pages/setup.php$s",$linkadd);
        }
        
$falcon_url = "https://api.falcon.io";

$falcon_params = array(
    'apikey'    => $falcon_link_api_key,
    'since'     => '2018-01-30',
    'until'     => '2018-03-30',
    'statuses'  => 'draft,published',
    'networks'  => 'facebook,twitter',
    'channels'  => '',
    'tags'      => '',
    'includeArchived' => 'false',
    'contentTypes' => 'all'
    );


$template_url = generateURL($falcon_url . "/publish/templates", $falcon_params);
$curl = curl_init($template_url);
curl_setopt( $curl, CURLOPT_HEADER, "Content-Type:application/x-www-form-urlencoded" );
curl_setopt( $curl, CURLOPT_POST, 0);
curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 0 );
curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 2 );

$curl_response = curl_exec($curl);

$response = json_decode($curl_response, true );

include "../../../include/header.php";
//exit(print_r($response));
echo "Templates: -";
echo table_cell($response);



