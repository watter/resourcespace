<?php
include "../../include/db.php";

if(!$iiif_enabled || !isset($iiif_identifier_field) || !is_numeric($iiif_identifier_field) || !isset($iiif_userid) || !is_numeric($iiif_userid)){exit($lang["iiif_disabled"]);}

include_once "../../include/general.php";
include_once "../../include/resource_functions.php";
include_once "../../include/search_functions.php";
include_once "../../include/api_functions.php";
$debug = getval("debug","")!="";
   
$iiif_user = get_user($iiif_userid);
setup_user($iiif_user);

$rootlevel = $baseurl_short . "iiif/";
$rooturl = $baseurl . "/iiif/";
$rootimageurl = $baseurl . "/iiif/image/";
$request_url=strtok($_SERVER["REQUEST_URI"],'?');
$path=substr($request_url,strpos($request_url,$rootlevel) + strlen($rootlevel));
$xpath = explode("/",$path);

$validrequest = false;
$iiif_headers = array();
$errors=array();

if (count($xpath) == 1)
	{
	# Root level request - send information file only		   	
	$response["@context"] = "http://iiif.io/api/presentation/2/context.json";
  	$response["@id"] = $rooturl;
  	$response["@type"] = "sc:Manifest";
  	$response["@label"] = "";
	$response["width"] = 6000;
	$response["height"] = 4000;
			  
	$response["sizes"] = array();
	$response["sizes"][] = array("width" => 150, "height" => 100);
	$response["sizes"][] = array("width" => 600, "height" => 400);
	$response["sizes"][] = array("width" => 3000, "height" => 2000);
  	$response["tiles"] = array("width" => 512, "scaleFactors" => array(1,2,4,8,16));
	$response["profile"] = array("http://iiif.io/api/image/2/level2.json");
	
	$validrequest = true;	
	}
else
	{
	if(strtolower($xpath[0]) == "image")
        {
		// IMAGE REQUEST (http://iiif.io/api/image/2.1/)
        $resourceid = $xpath[1];		
		//$iiif_search = $iiif_field["name"] . ":" . $identifier;
		//$iiif_results = do_search($iiif_search);
		$resource_access=get_resource_access($resourceid);
		if($resource_access==0)
            {	
            if(!isset($xpath[2]) || $xpath[2] == "info.json")
                {
                // Image information request. Only fullsize available in this initial version
                $response["@context"] = "http://iiif.io/api/image/2/context.json";
                $response["@id"] = $rootimageurl . $resourceid . "/info.json";
                $response["protocol"] = "http://iiif.io/api/image";			
                
                $img_path = get_resource_path($resourceid,true,'',false);
                $image_size = get_original_imagesize($resourceid,$img_path);
                //print_r($image_size);
                $response["width"] = $image_size[1];
                $response["height"] = $image_size[2];
                
                $response["sizes"] = array();
                $response["sizes"][0]["width"] = $image_size[1];
                $response["sizes"][0]["height"] = $image_size[2];
                
                $response["profile"] = array();
                $response["profile"][] = "http://iiif.io/api/image/2/level0.json";
                $response["profile"][] = array(
                    "formats" => array("jpg"),
                    "qualities" => array("color"),
                    "supports" => array("baseUriRedirect")				
                    );
                
                $iiif_headers[] = 'Link: <http://iiif.io/api/image/2/level0.json>;rel="profile"';
                $validrequest = true;
                }
                
            // The IIIF Image API URI for requesting an image must conform to the following URI Template:
            // {scheme}://{server}{/prefix}/{identifier}/{region}/{size}/{rotation}/{quality}.{format}
            // Initial version only supports full image
            elseif(!isset($xpath[3]) || !isset($xpath[4]) || !isset($xpath[5]) || !isset($xpath[5]) || strpos(".",$xpath[5] === false))
                {
                $errorcode= 400;
                $errors[] = "Invalid image request format.";
                }
            else
                {
                // Request is ok so far, check the request parameters
                $region = $xpath[2];
                $size = $xpath[3];
                $rotation = $xpath[4];
                $formatparts =explode(".",$xpath[5]);
                if(count($formatparts) != 2)
                    {
                    // Format. As we only support IIIF Image level 0 only a value of 'jpg' is accepted 
                    $errorcode=501;
                    $errors[] = "Invalid quality or format requested. Try using 'default.jpg'";   
                    }
                else
                    {
                    $quality = $formatparts[0];
                    $format = $formatparts[1];
                    }
                
                if($region != "full")
                    {
                    // Invalid region, only 'full' specified at present
                    $errorcode=501;
                    $errors[] = "Invalid region requested. Only 'full' is permitted";   
                    }
                if($size != "full"  && $size != "max" && $size != "thm")
                    {
                    // Need full size image, only max resolution is available
                    $errorcode=501;
                    $errors[] = "Invalid size requested. Only 'max', 'full' or 'thm' is permitted";   
                    }
                if($rotation!=0)
                    {
                    // Rotation. As we only support IIIF Image level 0 only a rotation value of 0 is accepted 
                    $errorcode=501;
                    $errors[] = "Invalid rotation requested. Only '0' is permitted";   
                    }
                 if(isset($quality) && $quality != "default" && $quality != "color")
                    {
                    // Quality. As we only support IIIF Image level 0 only a quality value of 'default' or 'color' is accepted 
                    $errorcode=501;
                    $errors[] = "Invalid quality requested. Only 'default' is permitted";   
                    }
                 if(isset($format) && strtolower($format) != "jpg")
                    {
                    // Format. As we only support IIIF Image level 0 only a value of 'jpg' is accepted 
                    $errorcode=501;
                    $errors[] = "Invalid format requested. Only 'jpg' is permitted";   
                    }
                    
                if(!isset($errorcode))
                    {                                           
                    // Request is supported, send the image
                    $validrequest = true;
                    $imgfound = false;
                    $imgpath = get_resource_path($resourceid,true,($size == "thm"?'thm':''),false,"jpg");
					if(file_exists($imgpath))
						{
						$response_image=$imgpath;
						$imgfound = true;
						}
                    if(!$imgfound)
                        {
                        $errorcode = "404";
                        $errors[] = "No image available for this identifier";
                        }
                    }
                else
                    {
                    // Invalid request format
                    $errorcode=400;
                    $errors[] = "Bad request. Please check the format of your request.";
                    $errors[] = "For the full image use " . $rootimageurl . $resourceid . "/full/max/0/default.jpg";
                    }
                }
            /* IMAGE REQUEST END */
            }
        else
            {
            $errorcode=404;
            $errors[] = "Missing or invalid identifier";    
            }
        } // End of image API
	else
        {
		// Presentation API
		$identifier = $xpath[0];
		$iiif_field = get_resource_type_field($iiif_identifier_field);
		$iiif_search = $iiif_field["name"] . ":" . $identifier;
		$iiif_results = do_search($iiif_search);
        //print_r($iiif_results);
        if(is_array($iiif_results) && count($iiif_results)>0)
			{	
			if(!isset($xpath[1]))
				{
				$errorcode=404;
				$errors[] = "Bad request. Valid options are 'manifest', 'sequence' or 'canvas' e.g. ";
				$errors[] = "For the manifest: " . $rooturl . $xpath[0] . "/manifest";
				$errors[] = "For a sequence : " . $rooturl . $xpath[0] . "/sequence";
				$errors[] = "For a canvas : " . $rooturl . $xpath[0] . "/canvas/<identifier>";
				}
			else
				{
				if(!is_array($iiif_results) || count($iiif_results) == 0)
					{
					$errorcode=404;
					$errors[] = "Invalid identifier: " . $identifier;
					}
				else
					{
					if($xpath[1] == "manifest")
						{
						/* MANIFEST REQUEST - see http://iiif.io/api/presentation/2.1/#manifest */
						
						$response["@context"] = "http://iiif.io/api/presentation/2/context.json";
						$response["@id"] = $rooturl . $identifier . "/manifest";
						$response["@type"] = "sc:Manifest";		
						
						// Descriptive metadata about the object/work
						// The manifest data should be the same for all resources that are returned.
						// This is the default when using the tms_link plugin for TMS integration. 
						// Therefore we use the data from the first returned result.
						$iiif_data = get_resource_field_data($iiif_results[0]["ref"]);
						
						$response["label"] = get_data_by_field($iiif_results[0]["ref"], $view_title_field);
						
						$response["description"] = get_data_by_field($iiif_results[0]["ref"], $iiif_description_field);
						
						$response["metadata"] = array();
						$n=0;
						foreach($iiif_data as $iiif_data_row)
							{
							$response["metadata"][$n] = array();		
							$response["metadata"][$n]["label"] = $iiif_data[$n]["title"];
							if(in_array($iiif_data[$n]["type"],$FIXED_LIST_FIELD_TYPES))
								{
								// Don't use the data as this has already concatentated the translations, add an entry for each node translation by building up a new array
								$resnodes = get_resource_nodes($iiif_results[0]["ref"],$iiif_data[$n]["resource_type_field"],true);
								$langentries = array();
								$nodecount = 0;
								unset($def_lang);
								foreach($resnodes as $resnode)
									{
									debug("iiif: translating " . $resnode["name"] . " from field '" . $iiif_data[$n]["title"] . "'");
									$node_langs = i18n_get_translations($resnode["name"]);
									$transcount=0;
									$defaulttrans = "";
									foreach($node_langs as $nlang => $nltext)
										{
										if(!isset($langentries[$nlang]))
											{
											// This is the first translated node entry for this language. If we already have translations copy the default language array to make sure no nodes with missing translations are lost
											debug("iiif: Adding a new translation entry for language '" . $nlang . "', field '" . $iiif_data[$n]["title"] . "'");
											$langentries[$nlang] = isset($def_lang)?$def_lang:array();
											}
										// Add the node text to the array for this language;
										debug("iiif: Adding node translation for language '" . $nlang . "', field '" . $iiif_data[$n]["title"] . "': " . $nltext);
										$langentries[$nlang][] = $nltext;
										
										// Set default text for any translations
										if($nlang == $defaultlanguage || $defaulttrans == ""){$defaulttrans = $nltext;}
										$transcount++;						
										}
									
									$nodecount++;								
									
									// There may not be translations for all nodes, fill any arrays that don't have an entry with the untranslated versions
									foreach($langentries as $mdlang => $mdtrans)
										{
										debug("iiif: enry count for " . $mdlang . ":" . count($mdtrans));
										debug("iiif: node count: " . $nodecount);
										if(count($mdtrans) != $nodecount)
											{
											debug("iiif: No translation found for " . $mdlang . ". Adding default translation to language array for field '" . $iiif_data[$n]["title"] . "': " . $mdlang . ": " . $defaulttrans);
											$langentries[$mdlang][] =  $defaulttrans;
											}
										}				
										
									// To ensure that no nodes are lost due to missing translations,  
									// Save the default language array to make sure we include any untranslated nodes that may be missing when/if we find new languages for the next node
								   
									debug("iiif: Saving default language array for field '" . $iiif_data[$n]["title"] . "': " . implode(",",$langentries[$defaultlanguage]));
									// Default language is the ideal, but if no default language entries for this node have been found copy the first language we have
									reset($langentries);
									$def_lang = isset($langentries[$defaultlanguage])?$langentries[$defaultlanguage]:$langentries[key($langentries)];
									}		
												
								
								$response["metadata"][$n]["value"] = array();
								$o=0;
								foreach($langentries as $mdlang => $mdtrans)
									{
									debug("iiif: adding to metadata language array: " . $mdlang . ": " . implode(",",$mdtrans));
									//$response["metadata"][$n]["value"][$o]["@value"] = array();
									//$response["metadata"][$n]["value"][$o]["@value"][] = $mdtrans;
									//$response["metadata"][$n]["value"][$o]["@language"] = $mdlang;
									$response["metadata"][$n]["value"][$o]["@value"] = implode(",",array_values($mdtrans));
									$response["metadata"][$n]["value"][$o]["@language"] = $mdlang;
									$o++;
									}
								}
							else
								{
								$response["metadata"][$n]["value"] = $iiif_data[$n]["value"];
								}
							$n++;
							}
							
						$response["description"] = get_data_by_field($iiif_results[0]["ref"], $iiif_description_field);
						if(isset($iiif_license_field))
							{
							$response["license"] = get_data_by_field($iiif_results[0]["ref"], $iiif_license_field);
							}
											
						// Thumbnail property
						$response["thumbnail"] = iiif_get_thumbnail($iiif_results[0]["ref"], $iiif_results);
						
						// Sequences
						$response["sequences"] = array();                    
						$response["sequences"][0]["@id"] = $rooturl . $identifier . "/sequence/normal";
						$response["sequences"][0]["@type"] = "sc:Sequence";
						$response["sequences"][0]["label"] = "Default order";
						   
												
						$response["sequences"][0]["canvases"]  = iiif_get_canvases($identifier,$iiif_results,false);
						$validrequest = true;	
						/* MANIFEST REQUEST END */
						}
					elseif($xpath[1] == "canvas")
						{                        
						// This is essentially a resource
						// {scheme}://{host}/{prefix}/{identifier}/canvas/{name}
						$canvasid = $xpath[2];
						$allcanvases = iiif_get_canvases($identifier,$iiif_results,true);
						//$sequenceids = array_keys($allcanvases);
						//print_r($allcanvases);
						//print_r($allcanvases[$canvasid]);
						$response["@context"] =  "http://iiif.io/api/presentation/2/context.json";
						$response = array_merge($response,$allcanvases[$canvasid]);                    
						$validrequest = true;	
						}
					elseif($xpath[1] == "sequence")
						{
						if(isset($xpath[2]) && $xpath[2]=="normal")
							{
							$response["@context"] = "http://iiif.io/api/presentation/2/context.json";
							$response["@id"] = $rooturl . $identifier . "/sequence/normal";
							$response["@type"] = "sc:Sequence";
							$response["label"] = "Default order";
							$response["canvases"] = iiif_get_canvases($identifier,$iiif_results);
							$validrequest = true;
							}
						}
					}
				}
			} // End of valid $identifier check based on search results
		else
			{
			$errorcode=404;
			$errors[] = "Invalid identifier: " . $identifier;
			}
		}
    
    }
    
    // Send the data 
	if($validrequest)
		{
		http_response_code(200); # Send OK
        header("Access-Control-Allow-Origin: *");
		if(isset($response_image))
            {
            // Send the image
            $file_size   = filesize_unlimited($response_image);
            $file_handle = fopen($response_image, 'rb');
            header("Access-Control-Allow-Origin: *");
            header('Content-Disposition: inline;');
            header('Content-Transfer-Encoding: binary');
            $mime = get_mime_type($response_image);
            header("Content-Type: {$mime}");
            $sent = 0;
            while($sent < $file_size)
                {
                echo fread($file_handle, $download_chunk_size);
        
                ob_flush();
                flush();
        
                $sent += $download_chunk_size;
        
                if(0 != connection_status()) 
                    {
                    break;
                    }
                }
        
            fclose($file_handle);
            }
        else
            {
            header("Content-type: application/json");
            foreach($iiif_headers as $iiif_header)
                {
                header($iiif_header);
                }
            echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
		exit();
		}
	else
		{
		http_response_code($errorcode); # Send error status
        if($debug)
            {
            echo implode("<br />",$errors);	 
            }
		else
            {
            echo implode("\n",$errors);
            }
		}
	
