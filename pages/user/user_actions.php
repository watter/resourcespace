<?php

include "../../include/db.php";
include_once "../../include/general.php";
include_once "../../include/search_functions.php";
include_once "../../include/request_functions.php";
include_once "../../include/action_functions.php";
include "../../include/authenticate.php";

if(!$actions_enable){exit("access denied");}
$modal=$resource_view_modal;

include "../../include/header.php";

?>
  <h1><?php echo $lang["actions_myactions"]?></h1>
  <p><?php echo $lang["actions_introtext"] ?></p>
  
<?php if ($user_preferences){?>
<div class="VerticalNav">
<a href="<?php echo $baseurl_short?>pages/user/user_preferences.php" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET . "&nbsp;" . $lang["userpreferences"];?></a>
</div>
<?php }

$actiontypes=array();
if($actions_resource_review){$actiontypes[]="resourcereview";}
if($actions_account_requests && checkperm("u")){$actiontypes[]="userrequest";}
if($actions_resource_requests && checkperm("R")){$actiontypes[]="resourcerequest";}
$updatedactiontypes=hook("updateactiontypes",'',array($actiontypes));
if(is_array($updatedactiontypes)){$actiontypes=$updatedactiontypes;}

$actiontype=getvalescaped("actiontype",''); // Set to ascertain if we are filtering on type
$offset=getvalescaped("offset",0);
$order_by=getvalescaped("actions_order_by","date");
$sort=getvalescaped("actions_sort","DESC");
$valid_order_bys=array("date","ref","description","type");
if (!in_array($order_by,$valid_order_bys)) {$order_by="date";$sort="DESC";} 
$revsort = ($sort=="ASC") ? "DESC" : "ASC";

$all_actions = get_user_actions(false,$actiontype,$order_by,$sort);


# pager
$jumpcount=1;
$per_page=getval("per_page_list",$default_perpage_list);
$results=count($all_actions);
$totalpages=ceil($results/$per_page);
$curpage=floor($offset/$per_page)+1;
$totalpages=ceil($results/$per_page);

$url_params=array(
	"offset"=>$offset,	
    "actions_order_by"=>$order_by,
    "actions_sort"=>$sort,
	"actiontype"=>$actiontype,
    "per_page"=>$per_page,
    "paging"=>true
   );

$url=generateURL($baseurl . "/pages/user/user_actions.php",$url_params);

if(count($actiontypes)>1)
	{
	?>
	<div class="BasicsBox">
		
		<form id="FilterActions" class="FormFilter" method="post" action="<?php echo $url ?>">
			<fieldset>
				<legend><?php echo $lang['filter_label']; ?></legend>  
				<div class="tickset">
					<div class="Inline">
						<select name="actiontype" id="actiontype" onChange="this.form.submit();">
							<option value=""<?php if ($actiontype == '') { echo " selected"; } ?>><?php echo $lang["all"]; ?></option>
							<?php
							foreach($actiontypes as $action_type_option){
								?>
								<option value="<?php echo $action_type_option; ?>"<?php if ($actiontype == $action_type_option) { echo " selected"; } ?>><?php echo $lang["actions_type_" . $action_type_option]; ?></option>
								<?php
							}
							?>
						</select>
					</div>
				</div>
			</fieldset>
		</form>
	
	</div>
	<div class="clearerleft"> </div>
	<?php
	}
else
	{
	?>
	<div class="spacer"> </div>
	<?php
	}
	?>
<div class="TopInpageNav"><div class="TopInpageNavLeft">
  
	<div class="InpageNavLeftBlock"><?php echo $lang["actions-total"] . ": <strong>" . $results; ?> </strong></div>
	<div class="InpageNavLeftBlock"><?php echo $lang["resultsdisplay"]?>:
	<?php 
	for($n=0;$n<count($list_display_array);$n++){?>
	<?php if ($per_page==$list_display_array[$n]){?><span class="Selected"><?php echo $list_display_array[$n]?></span><?php } else { ?><a href="<?php echo generateURL($baseurl . "/pages/user/user_actions.php",$url_params,array("per_page_list"=>$list_display_array[$n])) ?>" onClick="return CentralSpaceLoad(this);"><?php echo $list_display_array[$n]?></a><?php } ?>&nbsp;|
	<?php } ?>
	<?php if ($per_page==99999){?><span class="Selected"><?php echo $lang["all"]?></span><?php } else { ?><a href="<?php echo $url; ?>&per_page_list=99999" onClick="return CentralSpaceLoad(this);"><?php echo $lang["all"]?></a><?php } ?>
	</div>
	</div> <?php pager(false); ?>
	<div class="clearerleft"></div>
</div>
<div class="clearerleft"> </div>


<div class="BasicsBox">
  <div class="Listview" id="<?php echo ($resource_view_modal?"Modal":"CentralSpace") ?>_resource_actions">
	  <table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
		  <tr class="ListviewTitleStyle">
			  <td><?php if ($order_by=="date"       ) {?><span class="Selected"><?php } ?><a href="<?php echo generateURL($baseurl . "/pages/user/user_actions.php",$url_params,array("offset"=>0,"actions_sort"=>urlencode($revsort),"actions_order_by"=>"date")) ?>"        onClick="return CentralSpaceLoad(this);"><?php echo $lang["date"]; ?></a></td>
			  <td><?php if ($order_by=="ref"        ) {?><span class="Selected"><?php } ?><a href="<?php echo generateURL($baseurl . "/pages/user/user_actions.php",$url_params,array("offset"=>0,"actions_sort"=>urlencode($revsort),"actions_order_by"=>"ref")) ?>"         onClick="return CentralSpaceLoad(this);"><?php echo $lang["property-reference"]; ?></a></td>
			  <td><?php if ($order_by=="description") {?><span class="Selected"><?php } ?><a href="<?php echo generateURL($baseurl . "/pages/user/user_actions.php",$url_params,array("offset"=>0,"actions_sort"=>urlencode($revsort),"actions_order_by"=>"description")) ?>" onClick="return CentralSpaceLoad(this);"><?php echo $lang["description"]; ?></a></td>
			  <td><?php if ($order_by=="type"       ) {?><span class="Selected"><?php } ?><a href="<?php echo generateURL($baseurl . "/pages/user/user_actions.php",$url_params,array("offset"=>0,"actions_sort"=>urlencode($revsort),"actions_order_by"=>"type")) ?>"        onClick="return CentralSpaceLoad(this);"><?php echo $lang["type"]; ?></a></td>
			  <td><div class="ListTools"><?php echo $lang["tools"]?></div></td>
		  </tr>
  <?php
  
  if ($results==0)		
	  {
	  echo $lang['actions_noactions'];
	  }
  else
	  {
	  for ($n=$offset;(($n<$results) && ($n<($offset+$per_page)));$n++)
		  {
		  $actionlinks=hook("actioneditlink",'',array($all_actions[$n]));
		  if($actionlinks)
			{
			$actioneditlink=$actionlinks["editlink"];
			$actionviewlink=$actionlinks["viewlink"];
			}
		  else
			{
			$actioneditlink = '';
			$actionviewlink = '';  
			}
		  
		  if($all_actions[$n]["type"]=="resourcereview")
			{
			$actioneditlink = $baseurl_short . "pages/edit.php";
			$actionviewlink = $baseurl_short . "pages/view.php";
			}
		  elseif($all_actions[$n]["type"]=="resourcerequest")
			{
			$actioneditlink = $baseurl_short . "pages/team/team_request_edit.php";
			}
		  elseif($all_actions[$n]["type"]=="userrequest")
			{
			$actioneditlink = $baseurl_short  . "pages/team/team_user_edit.php";
			} 
		  
		  $linkparams["ref"] = $all_actions[$n]["ref"];
		  $linkparams["disablenav"]="true";
		  if($modal){$linkparams["modal"]="true";}
		  
		  $editlink=($actioneditlink=='')?'':generateURL($actioneditlink,$linkparams);
		  $viewlink=($actionviewlink=='')?'':generateURL($actionviewlink,$linkparams);
		  ?>
			<tr>
				<td><?php echo nicedate($all_actions[$n]["date"],true); ?></td>
				<td><a href="<?php echo $editlink; ?>" onClick="actionreload=true;return <?php echo $modal ? 'Modal' : 'CentralSpace'; ?>Load(this,true);" ><?php echo $all_actions[$n]["ref"]; ?></a></td>
				<td><?php echo tidy_trim(TidyList($all_actions[$n]["description"]),$list_search_results_title_trim) ; ?></td>
				<td><?php echo $lang["actions_type_" . $all_actions[$n]["type"]]; ?></td>
				<td>
					<div class="ListTools">
					  <?php if($editlink!=""){?><a aria-hidden="true" href="<?php echo $editlink; ?>" onClick="actionsreload=true;return <?php echo $modal ? 'Modal' : 'CentralSpace'; ?>Load(this,true);" class="maxLink fa fa-pencil" title="<?php echo $lang["action-edit"]; ?>"></a><?php } ?>
					  <?php if($viewlink!=""){?><a aria-hidden="true" href="<?php echo $viewlink; ?>" onClick="actionsreload=true;return <?php echo $modal ? 'Modal' : 'CentralSpace'; ?>Load(this,true);" class="maxLink fa fa-expand" title="<?php echo $lang["view"]; ?>"></a><?php } ?>
					</div>
				</td>
			</tr>
		  <?php
		  } // End of $all_actions loop
	  }
  ?></table>
  </div>
</div> <!-- End of BasicsBox -->
<script>
actionsreload=false;
jQuery('#CentralSpace').on('ModalClosed',function(e,url){
	if(ajaxinprogress!=true && typeof(actionsreload)!=="undefined" && actionsreload==true && window.location.href.indexOf('/pages/user/user_actions.php')!=-1){
			actionsreload=false;
			CentralSpaceLoad('<?php echo $url; ?>',false);
		}
	});		
</script>
<?php
include "../../include/footer.php";
