<?php
require_once "../include/db.php";
require_once "../include/general.php";
require_once "../include/authenticate.php";if (!checkperm("d")&&!(checkperm('c') && checkperm('e0'))) {exit ("Permission denied.");}

include "../include/header.php";
?>


<div class="BasicsBox"> 
  <h1><?php echo $lang["mycontributions"]?></h1>
  <p><?php echo text("introtext")?></p>

	<div class="VerticalNav">
	<ul>

	<li><a onClick="return CentralSpaceLoad(this,true);"
	<?php
				#We need to point to the right upload sequence based on $upload_then_edit
				if ($upload_then_edit==1){?>
						href="<?php echo $baseurl_short?>pages/upload_plupload.php">
				<?php }
				else {?>
						href="<?php echo $baseurl_short?>pages/edit.php?ref=-<?php echo urlencode($userref) ?>&uploader=plupload"><?php 
				}?>
	<?php echo $lang["addresourcebatchbrowser"];?></a>
    </li>
<?php
foreach(get_workflow_states() as $workflow_state)
    {
    $bypass_e_permission_check = false;
    if($show_user_contributed_resources && $workflow_state == 0)
        {
        $bypass_e_permission_check = true;
        }

    if((!$bypass_e_permission_check && !checkperm("e{$workflow_state}")) || checkperm("z{$workflow_state}"))
        {
        continue;
        }

    $ws_a_href = generateURL(
        "{$baseurl_short}pages/search.php",
        array(
            'search' => "!contributions{$userref}",
            'archive' => $workflow_state,
        ));
    $ws_a_text = str_replace('%workflow_state_name', $lang["status{$workflow_state}"], $lang["view_my_contributions_ws"]);
    ?>
    <li><a href="<?php echo $ws_a_href; ?>" onClick="return CentralSpaceLoad(this, true);"><?php echo htmlspecialchars($ws_a_text); ?></a></li>
    <?php
    }

    hook('custommycontributionlink');
    ?>
	</ul>
	</div>
	
  </div>
<?php
include "../include/footer.php";