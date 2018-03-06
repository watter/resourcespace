<?php

function HookFalcon_linkViewAfterresourceactions()
	{
	# Adds a Youtube link to the view page.
	global $baseurl, $lang, $ref, $access, $resource, $falcon_link_restypes;
	
	if ($access==0 && in_array($resource["resource_type"],$falcon_link_restypes))
		{
		echo "<li><a href='$baseurl/plugins/falcon_link/pages/falcon_upload.php?resource=$ref' onclick='CentralSpacePost(this,true);'><i class='fa fa-share-square'></i>&nbsp;" . $lang["falcon_link_publish"] . "</a></li>";
		}
	}
