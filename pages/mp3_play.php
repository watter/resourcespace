<?php
if($videojs || $pagename=="search") 
	{
	# VideoJS audio player - plays the mp3 preview file created to preview audio resources.

	global $alternative,$css_reload_key,$display,$keyboard_navigation_video_search,$keyboard_navigation_video_view,$keyboard_navigation_video_preview;

	if(!isset($mp3path)){$mp3path=get_resource_path($ref,false,"",false,"mp3",-1,1,false,"",$alternative);}
	if(!isset($thumb_url))
		{
		if(isset($thm_url)){$thumb_url=$thm_url;}
		else
			{
			$thumb=get_resource_path($ref,false,"pre",false,"jpg",-1,1,false,"",$alternative); 
			$thumb_url=$thumb;
			}
		}
	# Choose a colour based on the theme.
	$theme=(isset($userfixedtheme) && $userfixedtheme!="")?$userfixedtheme:getval("colourcss","greyblu");
	$color="505050";$bgcolor1="666666";$bgcolor2="111111";$buttoncolor="999999";
	if ($theme=="greyblu") {$color="446693";$bgcolor1="6883a8";$bgcolor2="203b5e";$buttoncolor="adb4bb";}	
	if ($theme=="whitegry") {$color="ffffff";$bgcolor1="ffffff";$bgcolor2="dadada";$buttoncolor="666666";}	
	if ($theme=="black") {$bgcolor1="666666";$bgcolor2="111111";$buttoncolor="999999";}	

	$preload='auto';
	// preview size adjustments for search
	if ($pagename=="search")
		{
		switch($display){
			case "xlthumbs":
				$width="350";
				$height=350/$ffmpeg_preview_max_width*$ffmpeg_preview_max_height;
				break;
			case "thumbs":
				$width="150";
				$height=150/$ffmpeg_preview_max_width*$ffmpeg_preview_max_height;
				break;
			case "smallthumbs":
				$width="75";
				$height=75/$ffmpeg_preview_max_width*$ffmpeg_preview_max_height;
				break;
		}
		}
	else // Not search, standard page
		{
		$width=$ffmpeg_preview_max_width;
		$height=$ffmpeg_preview_max_height;
		}

	// play video on hover?
	$play_on_hover=false;

	if(($pagename=='search' && $video_search_play_hover) || ($pagename=='view' && $video_view_play_hover) || (($pagename=='preview' || $pagename=='preview_all') && $video_preview_play_hover)){
		$play_on_hover=true;
	}
	// using keyboard hotkeys?
	$playback_hotkeys=false;
	if(($pagename=='search' && $keyboard_navigation_video_search) || ($pagename=='view' && $keyboard_navigation_video_view) || (($pagename=='preview' || $pagename=='preview_all') && $keyboard_navigation_video_preview)){
		$playback_hotkeys=true;
	}
	 
	global $ffmpeg_preview_extension,$css_reload_key,$context;
	?>
	<link href="<?php echo $baseurl_short?>lib/videojs/video-js.min.css?r=<?=$css_reload_key?>" rel="stylesheet">
	<script src="<?php echo $baseurl_short?>lib/videojs/video.min.js?r=<?=$css_reload_key?>"></script>
	<script src="<?php echo $baseurl_short?>lib/js/videojs-extras.js?r=<?=$css_reload_key?>"></script>
	<div class="videojscontent">
	<audio 
		id="<?php echo $context ?>_<?php echo $display ?>_introaudio<?php echo $ref?>"
		controls
		data-setup='{ 
				<?php if($play_on_hover){
				// Unlike video we are leaving the controls showing for audio, otherwise we don't get to see the poster image
				?>
				"loadingSpinner" : false,
				"children": { 
					"bigPlayButton":false
						}
					}
			<?php } ?> 
		}'
		preload="<?php echo $preload?>"
		width="<?php echo $width?>" 
		height="<?php echo $height?>" 
		class="video-js vjs-default-skin vjs-big-play-centered <?php if($pagename=='search'){echo "video-$display";}?>" 
		poster="<?php echo $thumb_url?>"
		<?php if($play_on_hover){ ?>
		onmouseout="videojs_<?php echo $context ?>_<?php echo $display ?>_introaudio<?php echo $ref ?>[0].pause();"
		onmouseover="videojs_<?php echo $context ?>_<?php echo $display ?>_introaudio<?php echo $ref ?>[0].play();"
		<?php } ?>
	>
		<source src="<?php echo $mp3path?>" type="audio/mp3" >
		<p class="vjs-no-js">To hear this audio please enable JavaScript, and consider upgrading to a web browser that <a href="http://videojs.com/html5-video-support/" target="_blank">supports HTML5 audio</a></p>
	</audio>
	</div>

	<?php if($play_on_hover){ ?>	
			<script>
			var videojs_<?php echo $context ?>_<?php echo $display ?>_introaudio<?php echo $ref ?> = jQuery('#<?php echo $context ?>_<?php echo $display ?>_introaudio<?php echo $ref ?>');
			</script>
	<?php } ?>

	<!-- START DISABLE VIDEOJS RIGHT CONTEXT MENU -->
	<script>
		jQuery('.video-js').bind('contextmenu',function() { return false; });
	</script>
	<!-- END DISABLE VIDEOJS RIGHT CONTEXT MENU -->

	<!-- END VIDEOJS -->
	<?php
	}
else
	{
	if(!hook("mp3player"))
		{
		// for removal of the default mp3 player bar
		# Choose a colour based on the theme.
		$theme=(isset($userfixedtheme) && $userfixedtheme!="")?$userfixedtheme:getval("colourcss","greyblu");
		$color="505050";$bgcolor1="666666";$bgcolor2="111111";$buttoncolor="999999";
		if ($theme=="greyblu") {$color="446693";$bgcolor1="6883a8";$bgcolor2="203b5e";$buttoncolor="adb4bb";}	
		if ($theme=="whitegry") {$color="ffffff";$bgcolor1="ffffff";$bgcolor2="dadada";$buttoncolor="666666";}	
		if ($theme=="black") {$bgcolor1="666666";$bgcolor2="111111";$buttoncolor="999999";}	
		# Flash MP3 Player was found at http://flash-mp3-player.net/players/maxi/

		hook("beforemp3tablecells");?>
		<tr class="DownloadDBlend">
		<td><h2><?php echo $lang["preview"] ?></h2></td>
		<td align="center" colspan="2"><center>
		<object type="application/x-shockwave-flash" data="<?php echo $baseurl_short?>lib/flashplayer/player_mp3_maxi.swf" width="200" height="20" <?php if ($pagename=="search"){?>style="margin:0px;margin-top:-20px;margin-left:auto;margin-right:auto;"<?php }?>>
		<param name="movie" value="<?php echo $baseurl_short?>lib/flashplayer/player_mp3_maxi.swf" />
		<param name="FlashVars" value="mp3=<?php echo $mp3path?>&width=200&buttoncolor=<?php echo $buttoncolor?>&playercolor=<?php echo $color?>&bgcolor=<?php echo $color?>&bgcolor1=<?php echo $bgcolor1?>&bgcolor2=<?php echo $bgcolor2?>&volume=100&showvolume=1" /></object>
		</center><?php if ($pagename!="search"){?>
		</td>
		</tr><?php } ?>
		<?php hook("aftermp3tablecells");
		
		}
	}
