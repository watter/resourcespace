<?php
include "../../../include/db.php";
include_once "../../../include/general.php";
include "../../../include/authenticate.php";
include "../../../include/search_functions.php";
include "../../../include/resource_functions.php";
//include "../include/falcon_functions.php";

$ref = getvalescaped("resource","");
if ($ref == "")
    {
    $ref=getvalescaped("state","");
    }

# Load access level
$access=get_resource_access($ref);
if ($access!=0) 
		{
		exit($lang["falcon_link_access_denied"]);
		}

if($falcon_link_url_field > 0)
	{
	$falcon_url = get_data_by_field($ref, $falcon_link_url_field);
	}

if (trim($falcon_link_api_key) == "" || count($falcon_link_restypes) < 1)
		{
        $linkadd = checkperm('a') ? array("<a href='$baseurl/plugins/falcon_link/pages/setup.php'>","</a>") : array("","");
        echo sprintf($lang["falcon_link_notconfigured"] . "%s$baseurl/plugins/falcon_link/pages/setup.php$s",$linkadd);
        }
        

if (getval("save","") != "")
    {
    $falcon_base_url    = "https://api.falcon.io";
    $template_text      = getvalescaped("template_text","");
    $template_tags      = getvalescaped("template_tags","");
    $resource           = get_resource_data($ref);
    
    // Check that file actually exists
    $check = get_resource_path($ref,true,'',false,$resource['file_extension']);
    if(!file_exists($check))
        {
        // Error - file does not exist
        exit("ERROR - FILE DOES NOT EXIST");
        }
    
    $resource_url       = str_replace($baseurl_short,"/",$baseurl . get_resource_path($ref,false,'',false,$resource['file_extension']) . "&k=" . $key);
    $filename           = get_download_filename($ref, '', -1, $resource['file_extension']);
    $key                = generate_resource_access_key($ref,$userref,0,0,$username . 'user@falcon.io');
    
    $falcon_query_params = array(
    'apikey'    => $falcon_link_api_key
    );
    
    $hide_real_filepath = true; // Set so that Falcon doesn't use the real filestore path. This allows access to be revoked from ResourceSpace if necessary
    
    $falcon_post_params = json_encode(array(
        'tags'      => explode(",",$template_tags),
        'content'   => array(
            'picture' => array(
                'message'           => $template_text,
                'url'               => $resource_url,
                'originalPicture'   => $resource_url,
                'fileName'          => $filename
                )
            )
        ));
    
    //exit($falcon_post_params);

    $create_url = generateURL($falcon_base_url . "/publish/publishing/templates", $falcon_query_params);
    
    $curl = curl_init($create_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json;charset=utf-8"));
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS,$falcon_post_params);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0 );
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2 );
    
    $curl_response  = curl_exec($curl);
    $curl_info      = curl_getinfo($curl);
   
    if ($curl_info['http_code'] != 200)
        {
        exit("ERROR: -<br />" . print_r($curl_info));
        }    
        
    $response = json_decode($curl_response, true );
    exit("COMPLETE: -<br />" . print_r($response));
    
    // If ok, update resource with Falcon Content Pool ref
    // Redirect to resource view/collection search page with message to advise of success
    $onload_message = array("title" => "","text" => $lang["ok"]);
    ?>
    <script>
		jQuery(document).ready(function(){
			ModalLoad('<?php echo $login_url?>',true);
		});
	</script>
    <?php
    }
else
    {
    $template_text  = get_data_by_field($ref,$falcon_link_text_field);
    
    $template_tags  = "";
    foreach ($falcon_link_tag_fields as $falcon_link_tag_field)
        {
        $resource_keywords  =  get_data_by_field($ref,$falcon_link_tag_field);
        $template_tags     .=  ($template_tags != "" ? "," : "") . $resource_keywords;
        }
    }


     
include "../../../include/header.php";

?>
<div class="Question">
    <p> 
    <?php echo $lang["falcon_link_existingurl"] . "<p>";
    if ($falcon_url != "")
        {
        echo $falcon_url;
        echo "</p><div class=\"FormIncorrect\"><p><br>" . $lang["falcon_link_already_published"] . "</p></div>";
        }
    else
        {
        echo $lang["falcon_link_not_uploaded"];
        }?>
    </p>
</div>

<form action="<?php echo $baseurl ?>/plugins/falcon_link/pages/falcon_upload.php" method="post">
    <input type="hidden" name="resource" value="<?php echo htmlspecialchars($ref); ?>" />
    
    <div class="Question" >
		<label for="template_text"><?php echo $lang["falcon_link_template_description"] ?></label>
		<textarea class="stdwidth" rows="6" columns="50" id="template_text" name="template_text"><?php echo htmlspecialchars($template_text); ?></textarea>
		<br>
	</div>
    <div class="Question" >
		<label for="template_tags"><?php echo $lang["falcon_link_template_tags"] ?></label>
		<textarea class="stdwidth" rows="6" columns="50" id="template_tags" name="template_tags"><?php echo htmlspecialchars($template_tags); ?></textarea>
		<br>
	</div>
    
	<div class="QuestionSubmit">
        <input type="submit" name="save" value="<?php echo $lang["falcon_link_button_text"]; ?>" onClick="return centralSpacePost(this,true);"/>
        <div class="clearerleft" ></div>
   </div>
	
	
</form> 
	
<?php

include "../../../include/footer.php";
	
?>
