<?php 
if (!hook("renderresultlargethumb")) 
	{ ?>
	<!--Resource Panel-->
	<div class="ResourcePanelShellLarge" id="ResourceShell<?php echo htmlspecialchars($ref)?>"  <?php echo hook('resourcepanelshell_attributes')?>>
		<div class="ResourcePanelLarge ArchiveState<?php echo $result[$n]['archive'];?><?php hook('xlthumbsviewpanelstyle'); ?> ResourceType<?php echo $result[$n]['resource_type']; ?>">
    		<?php  
    		if ($resource_type_icons) 
    			{ ?>
     			<div class="ResourceTypeIcon IconResourceType<?php echo $result[$n]["resource_type"];  ?>"></div>
    			<?php 
    			} 
			if (!hook("renderimagelargethumb")) 
				{
				if(isset($watermark))
					{
					$use_watermark=check_use_watermark();
					}
				else
					{
					$use_watermark=false;	
					}
				?>
				<table border="0" class="ResourceAlignLarge icon_type_<?php echo $result[$n]["resource_type"]; ?> icon_extension_<?php echo $result[$n]['file_extension']; ?><?php if(!hook("replaceresourcetypeicon")){  if (in_array($result[$n]["resource_type"],$videotypes)) { ?> IconVideoLarge<?php } ?><?php } hook('searchdecorateresourcetableclass'); ?>">
				<?php hook("resourcetop")?>
				<tr>
				<td>
    				<?php   
    				$show_flv=false;
    				$use_mp3_player=false;
					if ((in_array($result[$n]["file_extension"],$ffmpeg_supported_extensions) || $result[$n]["file_extension"]=="flv") && $flv_player_xlarge_view)
						{ 
						$flvfile=get_resource_path($ref,true,"pre",false,$ffmpeg_preview_extension);
						if (!file_exists($flvfile)) 
							{$flvfile=get_resource_path($ref,true,"",false,$ffmpeg_preview_extension);}
						else if (!(isset($result[$n]['is_transcoding']) && $result[$n]['is_transcoding']!=0) && file_exists($flvfile) && (strpos(strtolower($flvfile),".".$ffmpeg_preview_extension)!==false)) 
							{$show_flv=true;}
						}
					else
						{
						// Set $use_mp3_player switch if appropriate
						$use_mp3_player = ($mp3_player_xlarge_view && !(isset($result[$n]['is_transcoding']) && $result[$n]['is_transcoding']==1) && ((in_array($result[$n]["file_extension"],$ffmpeg_audio_extensions) || $result[$n]["file_extension"]=="mp3") && $mp3_player));
						if ($use_mp3_player)
							{
							$mp3realpath=get_resource_path($ref,true,"",false,"mp3");
							if (file_exists($mp3realpath))
								{$mp3path=get_resource_path($ref,false,"",false,"mp3");}
							}
						}
					if (isset($flvfile) && hook("replacevideoplayerlogicxl","",array($flvfile,$result,$n)))
						{ }
    				else if ($show_flv)
				        {
				        # Include the Flash player if an FLV file exists for this resource.
				        if(!hook("customflvplay"))
				            {
				            include "video_player.php";
				            }
				        }
				    elseif ($use_mp3_player && file_exists($mp3realpath) && !hook("custommp3player"))
						{
						$thumb_path=get_resource_path($ref,true,"pre",false,"jpg");
						if(file_exists($thumb_path))
							{$thumb_url=get_resource_path($ref,false,"pre",false,"jpg"); }
						else
							{$thumb_url=$baseurl_short . "gfx/" . get_nopreview_icon($result[$n]["resource_type"],$result[$n]["file_extension"],false);}
						include "mp3_play.php";
						}    
    				elseif ($result[$n]['file_extension']=="swf" && $display_swf && $display_swf_xlarge_view)
    					{
        				$swffile=get_resource_path($ref,true,"",false,"swf");
        				if (file_exists($swffile)) { include "swf_play.php";}	
						}
					else 
						{
						$pre_url=get_resource_path($ref,false,($retina_mode?"scr":"pre"),false,$result[$n]["preview_extension"],-1,1,$use_watermark,$result[$n]["file_modified"]);
						if (isset($result[$n]["pre_url"])) {$pre_url=$result[$n]["pre_url"];}
						?>
						<a 
							style="position:relative;" 
							href="<?php echo $url?>"  
							onClick="return <?php echo ($resource_view_modal?"Modal":"CentralSpace") ?>Load(this,true);" 
							title="<?php echo str_replace(array("\"","'"),"",htmlspecialchars(i18n_get_translated(strip_tags(strip_tags_and_attributes($result[$n]["field".$view_title_field])))))?>"
						>
							<?php 
							if ($result[$n]["has_image"]==1) 
								{ ?>
								<img 
									<?php 
									if ($result[$n]["thumb_width"]!="" && $result[$n]["thumb_width"]!=0 && $result[$n]["thumb_height"]!="") 
										{ 
										$ratio=$result[$n]["thumb_width"]/$result[$n]["thumb_height"];
										if ($result[$n]["thumb_width"]>$result[$n]['thumb_height'])
											{
											$xlwidth=350;
											$xlheight=round(350/$ratio);
											} 
										else 
											{
											$xlheight=350;
											$xlwidth=round(350*$ratio);
											}
										?> 
										width="<?php echo $xlwidth?>" 
										height="<?php echo $xlheight?>" 
										<?php 
										} ?>
										src="<?php echo $pre_url ?>" 
										class="ImageBorder"
										alt="<?php echo str_replace(array("\"","'"),"",htmlspecialchars(i18n_get_translated(strip_tags(strip_tags_and_attributes($result[$n]["field".$view_title_field]))))); ?>"
								/>
								<?php
                                // For videos ($ffmpeg_supported_extensions), if we have snapshots set, add code to fetch them from the server
                                // when user hovers over the preview thumbnail
                                if(1 < $ffmpeg_snapshot_frames && in_array($result[$n]['file_extension'], $ffmpeg_supported_extensions) && 0 < get_video_snapshots($ref, false, true))
                                    {
                                    ?>
                                    <script>
                                    jQuery('#ResourceShell<?php echo $ref; ?> .ResourcePanelLarge table tbody tr td a img').mousemove(function(event)
                                        {
                                        var x_coord             = event.pageX - jQuery(this).offset().left;
                                        var video_snapshots     = <?php echo json_encode(get_video_snapshots($ref)); ?>;
                                        var snapshot_segment_px = Math.ceil(jQuery(this).width() / Object.keys(video_snapshots).length);
                                        var snapshot_number     = Math.ceil(x_coord / snapshot_segment_px);

                                        jQuery(this).attr('src', video_snapshots[snapshot_number]);
                                        }
                                    ).mouseout(function(event)
		                                {
		                                jQuery(this).attr('src', "<?php echo $pre_url; ?>");
		                                }
		                            );
                                    </script>
                                    <?php
                                    }
								} 
							else 
								{ ?>
								<img 
									border=0 
									src="<?php echo $baseurl_short?>gfx/<?php echo get_nopreview_icon($result[$n]["resource_type"],$result[$n]["file_extension"],false) ?>" 
								/>
								<?php 
								}
							hook("aftersearchimg","",array($result[$n])); 
							?>
						</a>
					    <?php 
						} ?>
    			</td>
    			</tr>
    			</table>
    			<?php 
				} ?> <!-- END HOOK Renderimagelargethumb-->
			<?php 
			hook("beforesearchstars");
			if($display_user_rating_stars && $k=="")
				{ 
				if(!hook("replacesearchstars"))
					{
					if($result[$n]['user_rating']=="") 
						{
						$result[$n]['user_rating']=0;
						}
					$modified_user_rating=hook("modifyuserrating");
					if ($modified_user_rating!='')
						{$result[$n]['user_rating']=$modified_user_rating;}
					?>
					<div  
						class="RatingStars" 
						onMouseOut="UserRatingDisplay(<?php echo $result[$n]['ref']?>,<?php echo $result[$n]['user_rating']?>,'StarCurrent');"
					>
						&nbsp;
						<?php
						for ($z=1;$z<=5;$z++)
							{
							?><a href="#" onMouseOver="UserRatingDisplay(<?php echo $result[$n]['ref']?>,<?php echo $z?>,'StarSelect');" onClick="UserRatingSet(<?php echo $userref?>,<?php echo $result[$n]['ref']?>,<?php echo $z?>);return false;" id="RatingStarLink<?php echo $result[$n]['ref'].'-'.$z?>"><span id="RatingStar<?php echo $result[$n]['ref'].'-'.$z?>" class="Star<?php echo ($z<=$result[$n]['user_rating']?"Current":"Empty")?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></a><?php
							}
						?>
					</div>
					<?php 
					} // end hook replacesearchstars
				}
			if (!hook("replaceicons")) 
				{
				hook("icons");
				} //end hook replaceicons
			if (!hook("rendertitlelargethumb")) 
				{} ?> 
			<!-- END HOOK Rendertitlelargethumb -->			
			<?php
            if($annotate_enabled)
                {
                $annotations_count = getResourceAnnotationsCount($ref);
                $message           = '';

                if(1 < $annotations_count)
                    {
                    $message = $annotations_count . ' ' . mb_strtolower($lang['annotate_annotations_label']);
                    }
                else if(1 == $annotations_count)
                    {
                    $message = $annotations_count . ' ' . mb_strtolower($lang['annotate_annotation_label']);
                    }
                ?>
                <div class="ResourcePanelInfo AnnotationInfo">
                <?php
                if(0 < $annotations_count)
                    {
                    ?>
                    <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
                    <span><?php echo $message; ?></span>
                    <?php
                    }
                    ?>
                &nbsp;
                </div>
                <?php
                }

			$df_alt=hook("displayfieldsalt");
			$df_normal=$df;
			if ($df_alt)
				{$df=$df_alt;}

			# xlthumbs_display_fields
			for ($x=0;$x<count($df);$x++)
				{
				//if(!in_array($df[$x]['ref'],$xl_thumbs_display_fields)){continue;}
				#value filter plugin -tbd	
				$value=@$result[$n]['field'.$df[$x]['ref']];
				$plugin="../plugins/value_filter_" . $df[$x]['name'] . ".php";
				if ($df[$x]['value_filter']!="")
					{eval($df[$x]['value_filter']);}
				else if (file_exists($plugin)) 
					{include $plugin;}
				# swap title fields if necessary
				if (isset($metadata_template_resource_type) && isset ($metadata_template_title_field))
					{
					if (($df[$x]['ref']==$view_title_field) && ($result[$n]['resource_type']==$metadata_template_resource_type))
						{
						$value=$result[$n]['field'.$metadata_template_title_field];
						}
					}
				// extended css behavior 
				if ( in_array($df[$x]['ref'],$xl_thumbs_display_extended_fields) &&
					((isset($metadata_template_title_field) && $df[$x]['ref']!=$metadata_template_title_field) || !isset($metadata_template_title_field)))
					{
					if (!hook("replaceresourcepanelinfolarge"))
						{ ?>
						<div class="ResourcePanelInfo ResourceTypeField<?php echo $df[$x]['ref']?>">
							<div class="extended">
								<?php 
								if ($x==0)
									{ // add link if necessary ?>
									<a 
										href="<?php echo $url?>"  
										onClick="return <?php echo ($resource_view_modal?"Modal":"CentralSpace") ?>Load(this,true);"
										title="<?php echo str_replace(array("\"","'"),"",htmlspecialchars(i18n_get_translated(strip_tags(strip_tags_and_attributes($value)))))?>"
										>
									<?php 
									} //end link
								echo format_display_field($value);			
								if ($show_extension_in_search)
									{
									echo " " . str_replace_formatted_placeholder("%extension", $result[$n]["file_extension"], $lang["fileextension-inside-brackets"]);
									}
								if ($x==0)
									{ // add link if necessary ?>
									</a>
									<?php 
									} //end link?>
								&nbsp;
							</div>
						</div>
						<?php 
						} /* end hook replaceresourcepanelinfolarge */ 
					// normal behavior
					} 
				else if  ((isset($metadata_template_title_field)&&$df[$x]['ref']!=$metadata_template_title_field) || !isset($metadata_template_title_field) )
					{ 
					if (!hook("replaceresourcepanelinfolargenormal"))
						{ ?>
						<div class="ResourcePanelInfo ResourceTypeField<?php echo $df[$x]['ref']?>">
							<?php 
							if ($x==0)
								{ // add link if necessary ?>
								<a 
									href="<?php echo $url?>"  
									onClick="return <?php echo ($resource_view_modal?"Modal":"CentralSpace") ?>Load(this,true);" title="<?php echo str_replace(array("\"","'"),"",htmlspecialchars(i18n_get_translated(strip_tags(strip_tags_and_attributes($value)))))?>"
									
								>
								<?php 
								} //end link
							echo highlightkeywords(tidy_trim(TidyList(i18n_get_translated(strip_tags(strip_tags_and_attributes($value)))),$xl_search_results_title_trim),$search,$df[$x]['partial_index'],$df[$x]['name'],$df[$x]['indexed']);
							if ($x==0)
								{ // add link if necessary ?>
								</a>
								<?php 
								} //end link?>
							&nbsp;
						</div>
						<div class="clearer"></div>
						<?php 
						}
					} /* end hook replaceresourcepanelinfolargenormal */
				hook("processthumbsfields");
				}
			hook("afterxlthumbfields");
			$df=$df_normal;
			?>
			<div class="ResourcePanelIcons">
				<?php 
				if(!hook("xlthumbscheckboxes"))
					{
					if ($use_checkboxes_for_selection)
						{
						if(!in_array($result[$n]['resource_type'],$collection_block_restypes))	
							{?>
							<input 
								type="checkbox" 
								id="check<?php echo htmlspecialchars($ref)?>" 
								class="checkselect" 
								<?php 
								if (in_array($ref,$collectionresources))
									{ ?>
									checked
									<?php 
									} ?> 
								onclick="if (jQuery('#check<?php echo htmlspecialchars($ref)?>').prop('checked')){ AddResourceToCollection(event,<?php echo htmlspecialchars($ref)?>); } else if (jQuery('#check<?php echo htmlspecialchars($ref)?>').prop('checked')==false){ RemoveResourceFromCollection(event,<?php echo htmlspecialchars($ref)?>); }"
							>
							&nbsp;
							<?php 
							}
						else
							{
							?>
							<input type="checkbox" style="opacity: 0;">
							<?php
							}
						}
					} # end hook xlthumbscheckboxes
				if ($display_resource_id_in_thumbnail && $ref>0) 
					{ echo htmlspecialchars($ref); } 
				else 
					{ ?>&nbsp;<?php }	
	    		if (!hook("replaceresourcetoolsxl"))
	    			{
				include "resource_tools.php";
				} // end hook replaceresourcetoolsxl
				?>
			</div>
		</div>
		
	</div>
 	<?php 
 	} # end hook renderresultlargethumb
