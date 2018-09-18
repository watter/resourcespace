<?php
function HookRse_workflowAllInitialise()
     {
	 include_once dirname(__FILE__)."/../include/rse_workflow_functions.php";
	 include_once dirname(__FILE__)."/../../../include/language_functions.php";
     # Deny access to specific pages if RSE_KEY is not enabled and a valid key is not found.
     global $pagename, $additional_archive_states, $fixed_archive_states, $wfstates;
    
    # Update $archive_states and associated $lang variables with entries from database
	$wfstates=rse_workflow_get_archive_states();
	global $lang;
	foreach($wfstates as $wfstateref=>$wfstate)
		{
		if (!$wfstate['fixed'])
			{
			$additional_archive_states[]=$wfstateref;
            }
        else
            {
            // Save for later so we know which are editable
            $fixed_archive_states[] = $wfstateref;
            }
        $lang["status" . $wfstateref] =  i18n_get_translated($wfstate["name"]);
		}
    natsort($additional_archive_states);		 
    }
    
function HookRse_workflowAllAfter_update_archive_status($resource, $archive, $existingstates)
    {
    global  $baseurl, $lang, $userref, $wfstates, $applicationname, $use_phpmailer;
    
    $rse_workflow_from="";
    if (isset($wfstates[$archive]["email_from"]) && $wfstates[$archive]["email_from"]!="")
        {
        $rse_workflow_from=$wfstates[$archive]["email_from"];
        }
    
    // Set message text and URL to link to resources
    $message = $lang["rse_workflow_state_notify_message"] . $lang["status" . $archive];
    if(getval('more_workflow_action_' . $archive,'') != '')
        {
        $message .= "\n\n" . getval('more_workflow_action_' . $archive, '');
        }
        
    if(count($resource) > 200)
        {
        // Too many resources to link to directly
        $linkurl = $baseurl . "/pages/search.php?search=archive" . $archive;
        }
     else
        {
        $linkurl = $baseurl . "/pages/search.php?search=!list" . implode(":",$resource);;
        }
    
    $maillinkurl = (($use_phpmailer) ? "<a href=\"$linkurl\">$linkurl</a>" : $linkurl); // Convert to anchor link if using html mails
      
    /***** NOTIFY GROUP SUPPORT *****/
    if(isset($wfstates[$archive]['notify_group']) && $wfstates[$archive]['notify_group'] != '')
        {   
        $archive_notify = sql_query("
            SELECT ref, email
              FROM user
             WHERE approved = 1
               AND usergroup = '" . escape_check($wfstates[$archive]['notify_group']) . "'
        ");

        // Send notifications to members of usergroup
        foreach($archive_notify as $archive_notify_user)
            {
            debug("processing notification for contributing user " . $archive_notify_user['ref']);
            get_config_option($archive_notify_user['ref'],'user_pref_resource_notifications', $send_message);          
            if($send_message==false)
                {
                continue;
                }
                
            // Does this user want an email or notification?
            get_config_option($archive_notify_user['ref'],'email_user_notifications', $send_email); 
            if($send_email && filter_var($archive_notify_user["email"], FILTER_VALIDATE_EMAIL))
                {
                debug("sending email notification to user " . $archive_notify_user['ref']);
                send_mail($archive_notify_user["email"],$applicationname . ": " . $lang["status" . $archive],$message . "\n\n" . $maillinkurl);
                }
            else
                {
                global $userref;
                debug("sending system notification to user " . $archive_notify_user['ref']);
                message_add($archive_notify_user['ref'],$message,$linkurl);
                }
            }
        }
    /***** END OF NOTIFY GROUP SUPPORT *****/

    /*****NOTIFY CONTRIBUTOR*****/
    if($wfstates[$archive]['notify_user_flag'] == 1)
        {
        $cntrb_arr = array();
        foreach($resource as $resourceref)
            { 
            $resdata = get_resource_data($resourceref);
            if(isset($resdata['created_by']) && is_numeric($resdata['created_by']))
                {
                $contuser = sql_query('SELECT ref, email FROM user WHERE ref = ' . $resdata['created_by'] . ';', '');
                if(count($contuser) == 0)
                    {
                    // No contributor listed
                    debug("No contributor listed for resource " . $resourceref);
                    continue;
                    }
                    
                if(!isset($cntrb_arr[$contuser[0]["ref"]]))
                    {
                    // This contributor needs to be added to the array of users to notify
                    $cntrb_arr[$contuser[0]["ref"]] = array();
                    $cntrb_arr[$contuser[0]["ref"]]["resources"] = array();
                    $cntrb_arr[$contuser[0]["ref"]]["email"] = $contuser[0]["email"];
                    }
                $cntrb_arr[$contuser[0]["ref"]]["resources"][] = $resourceref;
                }
            }
        
        // Construct messages for each user    
        foreach($cntrb_arr as $cntrb_user => $cntrb_resources)
            {
            debug("processing notification for contributing user " . $cntrb_user);
            // Does this user want to receive any notifications?
            get_config_option($cntrb_user,'user_pref_resource_notifications', $send_message);          
            if($send_message==false)
                {
                continue;
                }
            
            // Does this user want an email or system message?
            get_config_option($cntrb_user,'email_user_notifications', $send_email);
            if($send_email && filter_var($notifyuser["email"], FILTER_VALIDATE_EMAIL))
                {
                debug("sending email notification to contributing user " . $cntrb_user);
                send_mail($notifyuser["email"],$applicationname . ": " . $lang["status" . $archive],$message . "\n\n" . $maillinkurl, $rse_workflow_from,$rse_workflow_from);
                if($wfstates[$archive]["bcc_admin"]==1)
                    {
                    $bccadmin_users = get_notification_users("SYSTEM_ADMIN");
                    foreach($bccadmin_users as $bccadmin_user)
                        {
                        debug("processing bcc notification for contributing user " . $bccadmin_user);
                        // Does this admin user want to receive any notifications?
                        get_config_option($bccadmin_user,'user_pref_resource_notifications', $send_message);          
                        if($send_message==false)
                            {
                            continue;
                            }
                        
                        // Does this admin user want an email or system message?
                        get_config_option($bccadmin_user,'email_user_notifications', $send_email); 
                        if($send_email && filter_var($bccadmin_user["email"], FILTER_VALIDATE_EMAIL)) 
                            {
                            send_mail($bccadmin_user["email"], $applicationname . ': ' . $lang['status' . $archive], $message, $rse_workflow_from,$rse_workflow_from);
                            }
                        else
                            {
                            message_add($bccadmin_user["email"]['ref'],$message,$linkurl);
                            }
                        }
                    }					
                }
            else
                {
                debug("sending system notification to contributing user " . $cntrb_user);
                message_add($archive_notify_user['ref'],$message,$linkurl);
                }
            }
        }
    /*****END OF NOTIFY CONTRIBUTOR*****/    
    }
    
