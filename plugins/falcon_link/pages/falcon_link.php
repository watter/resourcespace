<?php
include "../../../include/db.php";
include_once "../../../include/general.php";
include "../../../include/authenticate.php";
include_once "../../../include/search_functions.php";
include_once "../../../include/resource_functions.php";
include_once "../../../include/collections_functions.php";
include_once "../include/falcon_link_functions.php";

$ref        = getval("resource",0,true);
$collection = getval("collection",0,true);
$action     = getval("action","publish");
$saveform   = getval("save","") != "";
$published  = array();

// Check access
if($ref != 0)
    {
    $access = get_resource_access($ref);
    $resources = array();
    $resources[]["ref"] = $ref;
    }
elseif($collection != 0)
    {
    $access = collection_min_access($collection);
    $result = do_search("!collection" . $collection,'','','',-1,'',false,0,false,false,'',false,false,true);
    if(count($result) == 0)
        {
        exit($lang["noresourcesfound"]);        
        }
    $resources = $result;
    //exit(print_r($resources));
    }
else
    {
    exit($lang["noresourcesfound"]);  
    }
    
if ($access != 0 || !in_array($usergroup, $falcon_link_usergroups)) 
	{
	exit($lang["falcon_link_access_denied"]);
    }

/* 
if($falcon_link_url_field > 0)
	{
	$falcon_url = get_data_by_field($resource, $falcon_link_url_field);
	}
*/

if (trim($falcon_link_api_key) == "" || count($falcon_link_restypes) < 1)
	{
    $linkadd = checkperm('a') ? array("<a href='$baseurl/plugins/falcon_link/pages/setup.php'>","</a>") : array("","");
    echo sprintf($lang["falcon_link_notconfigured"] . "%s$baseurl/plugins/falcon_link/pages/setup.php%s",$linkadd);
    }

if ($saveform)
    {
    if(strtolower($action) == "publish")
        {
        $template_text      = getvalescaped("template_text","");
        $template_tags      = getvalescaped("template_tags","");
            
        $success = falcon_link_publish($resources,$template_text,$template_tags);
        // If ok, update resource with Falcon Content Pool ref
        // Redirect to resource view/collection search page with message to advise of success
        if($success["success"])
            {
            $onload_message = array("title" => $lang["falcon_link_log_share"],"text" => $lang["falcon_link_publish_success"]);
            ?>
            <script>
                jQuery(document).ready(function()
                    {
                    styledAlert('<?php echo $onload_message["title"] . "','" . $onload_message["text"] ?>',true);
                    });
            </script>
            <?php
            }
        }
    elseif(strtolower($action) == "archive")
        {
        $success = falcon_link_archive($resources);  // If ok, update resource with Falcon Content Pool ref
        // Redirect to resource view/collection search page with message to advise of success
        if($success["success"])
            {
            $onload_message = array("title" => $lang["falcon_link_archived_success"],"text" => $lang["falcon_link_archived_success"]);
            
            }
        }
    if(!$success["success"])
        {
        if(count($success["errors"]) > 0)
            {
            $onload_message = array("title" => $lang["error"],"text" => $lang["falcon_link_error_falcon_api"] . ":-<br />" . implode("<br />" , $success["errors"]));
            }
        }
    }
elseif($collection == 0 && $ref !=0)
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

<div class="BasicsBox"> 
    <h1><?php echo ($action == "publish") ? $lang["falcon_link_publish"] : $lang["falcon_link_archive"] ?></h1>


<?php

if(!$saveform && $action == "publish")
    {
    foreach($resources as $resource)
        { 
        $falconurl = get_data_by_field($resource["ref"],$falcon_link_url_field);
        if(trim($falconurl) != "")
            {
            $published[$resource["ref"]] = $falconurl;
            }
        }
        
    if (count($published) > 0)
        {
        if($collection == 0)
            {
            echo "</p><div class=\"FormIncorrect\"><p><br>" . htmlspecialchars($lang["falcon_link_already_published"]) . "</p></div>";
            }
        else
            {
            echo "</p><div class=\".PageInformal\"><p><br>" . htmlspecialchars($lang["falcon_link_resources_already_published"]) . implode(",",$published) . "</p></div>";                
            }
        } 
    }
elseif($action == "archive")
    {?>
    <div class="Question">
        <p> 
        <?php echo $lang["falcon_link_existingurl"] . $falconurl . "<p>";
         if($collection == 0)
                {
                echo "</p><div class=\"FormIncorrect\"><p><br>" . htmlspecialchars($lang["falcon_link_already_published"]) . "</p></div>";
                }
            else
                {
                echo "</p><div class=\".PageInformal\"><p><br>" . htmlspecialchars($lang["falcon_link_resources_already_published"]) . implode(",",$published) . "</p></div>";                
                }?>         
        </p>
    </div>
    <?php
    }?>

    
<form action="<?php echo $baseurl ?>/plugins/falcon_link/pages/falcon_link.php" method="post">

    <?php
    if($collection != 0)
        {?>
        <input type="hidden" name="collection" value="<?php echo htmlspecialchars($collection); ?>"
        <?php
        foreach($resources as $resource)
            {
            // Show a row for each resource to publish    
            $falconurl = get_data_by_field($resource["ref"],$falcon_link_url_field);
            if(trim($falconurl) != "")
                {
                $published[] = $resource["ref"];
                }
            }
        }
    else
        {?>
        <input type="hidden" name="resource" value="<?php echo htmlspecialchars($ref); ?>">
        <div class="Question" >
            <label for="template_text"><?php echo htmlspecialchars($lang["falcon_link_template_description"]) ?></label>
            <textarea class="stdwidth" rows="6" columns="50" id="template_text" name="template_text"><?php echo htmlspecialchars($template_text); ?></textarea>
            <br />
        </div>
        <div class="Question" >
            <label for="template_tags"><?php echo htmlspecialchars($lang["falcon_link_template_tags"]) ?></label>
            <textarea class="stdwidth" rows="6" columns="50" id="template_tags" name="template_tags"><?php echo htmlspecialchars($template_tags); ?></textarea>
            <br />
        </div>
        <?php
        }?>
            
	<div class="QuestionSubmit">
        <label for="submit"></label>
        <input type="submit" name="save" value="<?php echo $lang["falcon_link_button_text"]; ?>" onClick="return CentralSpacePost(this,true);"/>
        <div class="clearerleft" ></div>
   </div>
    
   
</form>
	
</div><!-- End of BasicsBox -->
	
<?php

include "../../../include/footer.php";
	
?>
