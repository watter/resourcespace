<?php

function falcon_link_publish($resources,$template_text,$template_tags)
    {
    global $lang, $userref, $username, $baseurl_short, $baseurl, $hide_real_filepath;
    global $falcon_base_url, $falcon_link_api_key, $falcon_link_text_field, $falcon_link_tag_fields, $falcon_link_url_field; 
    $result = array("success"=>false,"errors"=>array());
    debug("falcon_link: falcon_link_publish (resources=" . implode(",",array_column($resources, "ref")) . ", template_text='" . $template_text . "', template_tags='" . $template_tags . "')");
    if(!is_array($resources) || count($resources) < 1)
        {
        $result["success"] = false;
        $result["errors"][] = $lang["falcon_link_error_no_resources"];
        return $result;
        }
    
    foreach($resources as $resource)
        {
        $resourcedata = get_resource_data($resource["ref"]);
        
        // Check that files actually exists
        $check = get_resource_path($resource["ref"],true,'',false,$resourcedata['file_extension']);
        if(!file_exists($check))
            {
            debug("falcon_link: falcon_link_publish - resource file not found . Resource:" . $resource["ref"]);
            // Error - file does not exist
            $result["success"] = false;
            $result["errors"][] = $lang["falcon_link_error_no_resources"];
            return $result;
            }
        }
    $falcon_errors = array();
    
    $hide_real_filepath = true; // Set so that Falcon doesn't use the real filestore path. This allows access to be revoked from ResourceSpace if necessary
    
    foreach($resources as $resource)
        {
        $key                = generate_resource_access_key($resource["ref"],$userref,0,0,$username . 'user@falcon.io');
        $resource_url       = get_resource_path($resource["ref"],false,'',false,$resourcedata['file_extension']) . "&k=" . $key;
        $filename           = get_download_filename($resource["ref"], '', -1, $resourcedata['file_extension']);
        $upload_text        = ($template_text == "") ? get_data_by_field($resource["ref"],$falcon_link_text_field) : $template_text;
        if($template_tags == "")
            {
            $upload_tags = "";
            foreach ($falcon_link_tag_fields as $falcon_link_tag_field)
                {
                $resource_keywords  =  get_data_by_field($resource["ref"],$falcon_link_tag_field);
                $upload_tags     .=  ($upload_tags != "" ? "," : "") . $resource_keywords;
                }
            }
        else
            {
            $upload_tags = $template_tags;  
            }
        $falcon_query_params = array(
        'apikey'    => $falcon_link_api_key
        );
        
        $falcon_post_params = json_encode(array(
            'tags'      => explode(",",$upload_tags),
            'content'   => array(
                'picture' => array(
                    'message'           => $upload_text,
                    'url'               => $resource_url,
                    'originalPicture'   => $resource_url,
                    'fileName'          => $filename
                    )
                )
            ));
        
        //exit(get_resource_path($resource["ref"],false,'',false,$resourcedata['file_extension']) . "&k=" . $key);
        //print_r($falcon_post_params);
        $create_url = generateURL($falcon_base_url . "/publish/publishing/template", $falcon_query_params);
        
        $curl = curl_init($create_url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json;charset=utf-8"));
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS,$falcon_post_params);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2 );
        
        debug("falcon_link: falcon_link_publish. Resource:" . $resource["ref"] . " - Sending request");
        $curl_response  = curl_exec($curl);
        $curl_info      = curl_getinfo($curl);
       
        if ($curl_info['http_code'] != 201)
            {            
            debug("falcon_link: falcon_link_publish. Resource:" . $resource["ref"] . " - Publish failed. Info: " . print_r($curl_info, true));
            $falcon_errors[] = $lang["falcon_link_error_falcon_api"] . $curl_response . print_r($curl_info);
            continue;
            }
        
        $response = json_decode($curl_response, true );
        $falconid = $response['id'];
        debug("falcon_link: falcon_link_publish. Resource:" . $resource["ref"] . " - Successfuly published. Falcon id# " . $falconid);
        $falcon_template_path = "https://app.falcon.io/#/publish/content-pool/card/preview/stock/" . $falconid;
        update_field($resource["ref"],$falcon_link_url_field,$falcon_template_path);
            
        //exit("COMPLETE: -<br />" . print_r($response));
        return $result;    
        }
    if(count($falcon_errors) > 0 )
        {
        $result["success"] = false;
        $result["errors"] = array_merge( $result["errors"],$falcon_errors );
        }
    $result["success"] = true;
    return $result;
    }
    
function falcon_link_archive($resource)
    {
        
    }
   

