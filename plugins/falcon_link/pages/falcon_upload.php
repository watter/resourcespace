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

# check permissions (error message is not pretty but they shouldn't ever arrive at this page unless entering a URL manually)
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
        
	
$template_tags = getvalescaped("template_tags","");
$template_text = getvalescaped("template_text","");
        
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
	   
    <input type="submit" value="<?php echo $lang["falcon_link_button_text"]; ?>" onClick="return centralSpacePost(this,true);"/>
	
	
</form> 
	
<?php

include "../../../include/footer.php";
	
?>
