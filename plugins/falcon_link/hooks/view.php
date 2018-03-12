<?php

function HookFalcon_linkViewAfterresourceactions()
	{
	# Adds a Youtube link to the view page.
	global $baseurl, $lang, $ref, $access, $resource, $falcon_link_restypes,$falcon_link_permitted_extensions;
	
	if ($access==0 && in_array($resource["resource_type"],$falcon_link_restypes) &&  in_array(strtolower($resource["file_extension"]),$falcon_link_permitted_extensions))
		{
		echo "<li><a href='$baseurl/plugins/falcon_link/pages/falcon_upload.php?resource=$ref' onclick='CentralSpacePost(this,true);'><i class='fa fa-share-square'></i>&nbsp;" . $lang["falcon_link_publish"] . "</a></li>";
		}
	}
