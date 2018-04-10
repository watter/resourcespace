<?php
include "../../../include/db.php";
include_once "../../../include/general.php";
include "../../../include/authenticate.php";
include_once "../../../include/search_functions.php";
include_once "../../../include/resource_functions.php";
include_once "../../../include/collections_functions.php";
include_once "../../../include/render_functions.php";
include_once "../include/falcon_link_functions.php";

$ref        = getval("resource",0,true);
$collection = getval("collection",0,true);
$action     = getval("falcon_action","publish");
$saveform   = getval("save","") != "";
$published  = array();
$publishedresources = 0;
$errormessages = array();

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

if (trim($falcon_link_api_key) == "" || count($falcon_link_restypes) < 1)
	{
    $linkadd = checkperm('a') ? array("<a href='$baseurl/plugins/falcon_link/pages/setup.php'>","</a>") : array("","");
    echo sprintf($lang["falcon_link_notconfigured"] . "%s$baseurl/plugins/falcon_link/pages/setup.php%s",$linkadd);
    }

foreach($resources as $resource)
	   {
	   $resid = $resource["ref"];
	   $falconid = get_data_by_field($resid,$falcon_link_id_field);
	   if(trim($falconid) != "")
		    {
		    $published[$resid] = $falconid;
			$publishedresources++;
	        }
	   else
			{
			$results[$resid] = $lang["falcon_link_resource_not_published"];
			
			// Check that files actually exists
			$resourcedata = get_resource_data($resid);
			$check = get_resource_path($resid,true,'',false,$resourcedata['file_extension']);
			if(!file_exists($check))
				{
				$results[$resid] .= " (" . $lang["resourcenotfound"] . ")";
				}
			}
	   }
        	
if ($saveform)
    {
    if(strtolower($action) == "publish")
        {
		// Get posted values when publishing individual resources as we can override
		$template_text      = getvalescaped("template_text","");
		$template_tags      = getvalescaped("template_tags","");
        $success = falcon_link_publish($resources,$template_text,$template_tags);
		if($success["success"])
            {
            //$onload_message = array("title" => $lang["falcon_link_log_share"],"text" => $lang["falcon_link_publish_success"]);
			$message = $lang["falcon_link_publish_success"];
			foreach($success["results"] as $resid => $resresult)
				{
				$results[$resid] = $resresult;
				}
            }
        }
    elseif(strtolower($action) == "archive")
        {
        $success = falcon_link_archive($resources);  // If ok, update resource with Falcon Content Pool ref
        // Redirect to resource view/collection search page with message to advise of success
        if($success["success"])
            {
            //$onload_message = array("title" => $lang["falcon_link_archived"],"text" => $lang["falcon_link_archived_success"]);            
            $message = $lang["falcon_link_archived_success"];
			}
			
		foreach($success["results"] as $resid => $resresult)
				{
				$results[$resid] = $resresult;
				}
        }
    
	if(count($success["errors"]) > 0)
		{
		//$onload_message = array("title" => $lang["error"],"text" => $lang["falcon_link_error_falcon_api"] . ":-<br />" . implode("<br />" , $success["errors"]));
		$errormessages[] = $lang["falcon_link_error_falcon_api"] . ":-<br />" . implode("<br />" , $success["errors"]);
		}
    }

include "../../../include/header.php";

if($collection==0)
	{
	echo "<a href='" . $baseurl_short . "pages/view.php?ref=" . $resid . "' onClick='return CentralSpaceLoad(this,true);'>" . LINK_CARET_BACK . $lang["backtoresourceview"] . "</a></p>";
	}
else
	{
	echo "<a href='" . $baseurl_short . "?c=" . $collection . "' onClick='return CentralSpaceLoad(this,true);'>" . LINK_CARET_BACK . $lang["view_all_resources"] . "</a></p>";		
	}
?>


<div class="BasicsBox"> 
    <h1><?php echo ($action == "publish") ? $lang["falcon_link_publish"] : $lang["falcon_link_archive"] ?></h1>


<?php
if($publishedresources > 0 && $action == "publish" && !$saveform)
	{
	$errormessages[] = $lang["falcon_link_resources_already_published"];
	}
	
if(count($errormessages) > 0)
	{
	//echo "</p><div class='PageInformal'><p><br>" . htmlspecialchars($lang["falcon_link_resources_already_published"]) . "</p></div>";
	echo "</p><div class='PageInformal'><p><br>" . implode("<br />",$errormessages) . "</p></div>"; 
	}
	
if(isset($message))
	{
	echo "</p><div class='PageInformal'><p><br>" . $message . "</p></div>"; 
	}

echo "<div class='Listview'>";
echo "<table class='ListviewStyle'>";
echo "<tr class='ListviewTitleStyle'><td></td>";
if($action=="publish" && $collection != 0 && !$saveform)
	{
	// Show the desciption and tag headers if publishing a collection
	echo "<td>" . $lang["falcon_link_template_description"]	. "</td>";
	echo "<td>" . $lang["falcon_link_template_tags"]	. "</td>";
	}
echo "<td>" . $lang["status"].  "</td>";
echo "</tr>";
foreach($resources as $resource)
	{
	$resid = $resource["ref"];
	$imagedata = get_resource_data($resid);
	$img_url = get_resource_path($resid,false,"col");
	echo "<tr>";
	echo "<td>";
	render_resource_image($imagedata, $img_url,"col");
	echo "</td>";	
	$template_text  = get_data_by_field($resid,$falcon_link_text_field);
	$template_tags  = "";
	foreach ($falcon_link_tag_fields as $falcon_link_tag_field)
		{
		$resource_keywords  =  get_data_by_field($resid,$falcon_link_tag_field);
		$template_tags     .=  ($template_tags != "" ? "," : "") . $resource_keywords;
		}
	// Show the desciption and tags if publishing a collection
	if($action=="publish" && $collection != 0 && !$saveform)
		{
		echo "<td>" . htmlspecialchars($template_text) . "</td>";	
		echo "<td>" . htmlspecialchars($template_tags) . "</td>";
		}
	
	echo "<td>";
	
	if(isset($results[$resid]))
		{
		echo htmlspecialchars($results[$resid]);	
		}
	elseif(isset($published[$resid]))
		{
		$falconurl=str_replace("[id]",$published[$resid],$falcon_link_template_url);
		echo $lang["falcon_link_already_published"] . " <br />(<a href='" . $falconurl . "' target='_blank' title='" . $lang["falcon_link_view_in_falcon"] ."'>" . htmlspecialchars($published[$resid]) . "</a>)";
		}
	
	echo "</td>";
	echo "</tr>";
	}

echo "</table>";
echo "</div><!-- End of Listview -->";


if(!$saveform)
	{
	?>
	
		
	<form action="<?php echo $baseurl ?>/plugins/falcon_link/pages/falcon_link.php" id="falcon_link_form" method="post" onSubmit="return CentralSpacePost(this,true);" >
	
		<?php
		if($action == "publish")
			{
			if($collection == 0)
				{?>
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
				<input type="hidden" name="falcon_action" value="publish">
				<label for="submit"></label>
				<input type="submit" name="save" value="<?php echo htmlspecialchars($lang["falcon_link_publish_button_text"]); ?>" />
				<div class="clearerleft" ></div>
			</div>
			<?php
			}
		else
			{
			?>
			<div class="QuestionSubmit">
				<input type="hidden" name="falcon_action" value="archive">
				<label for="submit"></label>
				<input type="submit" name="save" value="<?php echo htmlspecialchars($lang["falcon_link_archive_button_text"]);?>" />
				<div class="clearerleft" ></div>
			</div>
			<?php
			}?>
		
		<input type="hidden" name="collection" value="<?php echo htmlspecialchars($collection); ?>" />
		<input type="hidden" name="resource" value="<?php echo htmlspecialchars($ref); ?>">
		<input type="hidden" name="save" value="true">
		
	   
	</form>
	<?php
	}
	?>
		
</div><!-- End of BasicsBox -->
	
<?php

include "../../../include/footer.php";
	
?>
