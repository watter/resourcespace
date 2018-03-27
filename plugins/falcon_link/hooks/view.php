<?php

function HookFalcon_linkViewAfterresourceactions()
	{
	# Adds a Falcon link to the view page.
	global $baseurl, $usergroup, $lang, $ref, $access, $resource, $falcon_link_restypes,$falcon_link_permitted_extensions, $falcon_link_url_field, $falcon_link_usergroups;
	
	if (in_array($usergroup, $falcon_link_usergroups) && $access==0 && in_array($resource["resource_type"],$falcon_link_restypes) && in_array(strtolower($resource["file_extension"]),$falcon_link_permitted_extensions))
		{
		$falconurl = get_data_by_field($ref,$falcon_link_url_field);
        if(trim($falconurl) == "")
			{
			echo "<li><a href='$baseurl/plugins/falcon_link/pages/falcon_link.php?resource=$ref' onclick='CentralSpacePost(this,true);'><i class='fa fa-share-square'></i>&nbsp;" . $lang["falcon_link_publish"] . "</a></li>";
			}
		else
			{
			echo "<li><a href='$baseurl/plugins/falcon_link/pages/falcon_link.php?resource=$ref&archive=true' onclick='CentralSpacePost(this,true);'><i class='fa fa-share-square'></i>&nbsp;" . $lang["falcon_link_archive"] . "</a></li>";
			}
		}
	}
