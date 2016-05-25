<?php
/**
* Returns the path to a pdf template
*
* @param  string  $resource_type  ID of the resource type
* @param  string  $template_name  Known template name already found in the array
*
* @return string
*/
function get_pdf_template_path($resource_type, $template_name = '')
    {
    global $storagedir, $pdf_resource_type_templates;

    $templates     = $pdf_resource_type_templates[$resource_type];
    $template      = '';

    if(
        !array_key_exists($resource_type, $pdf_resource_type_templates) ||
        (array_key_exists($resource_type, $pdf_resource_type_templates) && empty($templates))
    )
        {
        trigger_error('There are no PDF templates set for resource type "' . $resource_type . '"');
        }

    // Client code wants a specific template name but there isn't one
    if('' !== $template_name && !in_array($template_name, $templates))
        {
        trigger_error('PDF template "' . $template_name . '" could not be found in $pdf_resource_type_templates');
        }

    // Client code wants a specific template name
    if('' !== $template_name && in_array($template_name, $templates))
        {
        $template_array_key = array_search($template_name, $templates);
        if(false !== $template_array_key)
            {
            $template = $templates[$template_array_key];
            }
        }

    // Provide a default one if template name is empty
    if('' === $template && '' === $template_name)
        {
        $template = $templates[0];
        }

    return $storagedir . '/system/pdf_templates/' . $template . '.html';
    }


/**
* Takes an HTML template suitable for HTML2PDF library and generates a PDF file if successfull
*
* @param  string   $html_template_path  HTML template path
* @param  string   $filename            The file name of the generated PDF file. If this is an actual path,
*                                       and $save_on_server = true, it will be save on the server
* @param  array    $bind_placeholders   A map of all the values that are meant to replace any 
*                                       placeholders found in the HTML template
* @param  boolean  $save_on_server      If true, PDF file will be saved to the filename path
* @param  array    $pdf_properties      Properties of the PDF file (e.g. author, title, font, margins)
*
* @return boolean
*/
function generate_pdf($html_template_path, $filename, array $bind_placeholders = array(), $save_on_server = false, array $pdf_properties = array())
    {
    global $applicationname, $baseurl, $baseurl_short, $storagedir, $date_d_m_y, $linkedheaderimgsrc;

    $html2pdf_path = dirname(__FILE__) . '/../lib/html2pdf/html2pdf.class.php';
    if(!file_exists($html2pdf_path))
        {
        trigger_error('html2pdf class file is missing. Please make sure you have it under lib folder!');
        }
    require_once($html2pdf_path);

    // Do we have a physical HTML template
    if(!file_exists($html_template_path))
        {
        trigger_error('File "' . $html_template_path . '" does not exist!');
        }

    $html = file_get_contents($html_template_path);
    if(false === $html)
        {
        return false;
        }

    // General placeholders available to HTML templates
    $general_params = array(
        'applicationname' => $applicationname,
        'baseurl'         => $baseurl,
        'baseurl_short'   => $baseurl_short,
        'filestore'       => $storagedir,
        'filename'        => (!$save_on_server ? $filename : basename($filename)),
        'date'            => ($date_d_m_y ? date('d/m/Y') : date('m/d/Y')),
    );

    if('' != $linkedheaderimgsrc)
        {
        $general_params['linkedheaderimgsrc'] = $linkedheaderimgsrc;
        }

    $bind_params = array_merge($general_params, $bind_placeholders);

    foreach($bind_params as $param => $param_value)
        {
        // Bind [%param%] placeholders to their values
        $html = str_replace('[%' . $param . '%]', $param_value, $html);
        
        // replace \r\n with <br>. This is how they do it at the moment at html2pdf.fr
        $html = str_replace("\r\n", '<br>', $html);
        }

    $html = process_if_statements($html, $bind_params);
    
    // Last resort to clean up PDF templates by searching for all remaining placeholders
    $html = preg_replace('/\[%.*%\]/', '', $html);

    // Setup PDF
    $pdf_orientation = 'P';
    $pdf_format      = 'A4';
    $pdf_language    = 'en';
    $pdf_unicode     = true;
    $pdf_encoding    = 'UTF-8';
    $pdf_margins     = array(5, 5, 5, 8);

    if(array_key_exists('orientation', $pdf_properties) && '' != trim($pdf_properties['orientation']))
        {
        $pdf_orientation = $pdf_properties['orientation'];
        }

    if(array_key_exists('format', $pdf_properties) && '' != trim($pdf_properties['format']))
        {
        $pdf_format = $pdf_properties['format'];
        }

    if(array_key_exists('language', $pdf_properties) && '' != trim($pdf_properties['language']))
        {
        $pdf_language = $pdf_properties['language'];
        }

    if(array_key_exists('margins', $pdf_properties) && is_array($pdf_properties['margins']) && 0 !== count($pdf_properties['margins']))
        {
        $pdf_margins = $pdf_properties['margins'];
        }

    $html2pdf = new HTML2PDF($pdf_orientation, $pdf_format, $pdf_language, $pdf_unicode, $pdf_encoding, $pdf_margins);

    // Set PDF title
    if(array_key_exists('title', $pdf_properties) && '' != trim($pdf_properties['title']))
        {
        $html2pdf->pdf->SetTitle($pdf_properties['title']);
        }

    // Set PDF author
    if(array_key_exists('author', $pdf_properties) && '' != trim($pdf_properties['author']))
        {
        $html2pdf->pdf->SetAuthor($pdf_properties['author']);
        }

    // Set PDF subject
    if(array_key_exists('subject', $pdf_properties) && '' != trim($pdf_properties['subject']))
        {
        $html2pdf->pdf->SetSubject($pdf_properties['subject']);
        }

    // Set PDF font family
    if(array_key_exists('font', $pdf_properties) && '' != trim($pdf_properties['font']))
        {
        $html2pdf->setDefaultFont($pdf_properties['font']);
        }

    $html2pdf->WriteHTML($html);

    if($save_on_server)
        {
        $html2pdf->Output($filename, 'F');
        }
    else
        {
        $html2pdf->Output($filename);
        }

    return true;
    }


/**
* Returns the path to any HTML template in the system.
* 
* Returns the path to any HTML templates in the system as long as they are saved in the correct place:
* - /templates (default - base templates)
* - /filestore/system/templates (custom templates)
* Templates will be structured in folders based on features (e.g templates/contact_sheet/ 
* will be used for any templates used for contact sheets)
* 
* @param string  $template_name       Template names should be the actual template filename (e.g. test_tpl for test_tpl.html)
* @param string  $template_namespace  The name by which multiple templates are grouped together
* 
* @return string
*/
function get_template_path($template_name, $template_namespace)
    {
    global $storagedir;

    $template_path = '';
    $template_name .= '.html';

    $remove_directory_listings = array('.', '..');

    // Directories that may contain these files
    $default_tpl_dir   = dirname(__FILE__) . "/../templates/{$template_namespace}";
    $filestore_tpl_dir = "{$storagedir}/system/templates/{$template_namespace}";

    if(!file_exists($default_tpl_dir))
        {
        trigger_error("ResourceSpace could not find templates folder '{$template_namespace}'");
        }

    // Get default path
    $default_tpl_files = array_diff(scandir($default_tpl_dir), $remove_directory_listings);
    if(in_array($template_name, $default_tpl_files))
        {
        $template_path = "$default_tpl_dir/$template_name";
        }

    // Get custom template (if any)
    if(file_exists($filestore_tpl_dir))
        {
        $filestore_tpl_files = array_diff(scandir($filestore_tpl_dir), $remove_directory_listings);
        if(in_array($template_name, $filestore_tpl_files))
            {
            $template_path = "$filestore_tpl_dir/$template_name";
            }
        }

    if('' == $template_path)
        {
        trigger_error("ResourceSpace could not find template '{$template_name}'");
        }

    return $template_path;
    }


/**
* Process a string (mainly HTML) which contains if statement placeholders and return the processed string
* 
* Handles [%if var is set%] [%endif%] type of placeholders
* 
* @param string $original_string  Full string containing placeholders
* @param array  $bind_params      A map of all the values that are meant to replace any 
*                                 placeholders found in the HTML template
* 
* @return string
*/
function process_if_statements($original_string, array $bind_params)
    {
    $remove_placeholder_elements = array('[%if ', ' is set%]');

    preg_match_all('/\[%if (.*?) is set%\]/', $original_string, $if_isset_matches);
    foreach($if_isset_matches[0] as $if_isset_match)
        {
        $var_name = str_replace($remove_placeholder_elements, '', $if_isset_match);

        $if_isset_match_position = strpos($original_string, $if_isset_match);
        $endif_position          = strpos($original_string, '[%endif%]', $if_isset_match_position);
        $substr_lenght           = $endif_position - $if_isset_match_position;

        $substr_html_one   = substr($original_string, 0, $if_isset_match_position);
        $substr_html_two   = substr($original_string, $if_isset_match_position, $substr_lenght + 9);
        $substr_html_three = substr($original_string, $endif_position + 9);

        /*
        Make sure we have the correct subset (html2) for our if statement. This means we don't
        stop at the first endif we found unless there are no if statements inside the subset.
        If there are, move passed it and continue looking for other endifs until we reach the
        same number of endifs as we had ifs
        */
        $endif_count = 0;
        do
            {
            $if_count = preg_match_all('/\[%if (.*?) is set%\]/', $substr_html_two, $if_isset_matches_subset_two) - 1;
            if(0 < $if_count)
                {
                // Move the end of the second subset of HTML to the next endif and then increase endif counter
                $next_endif_position = strpos($substr_html_three, '[%endif%]') + 9;
                $substr_html_two    .= substr($substr_html_three, 0, $next_endif_position);
                $substr_html_three   = substr($substr_html_three, $next_endif_position);

                $endif_count++;
                }
            }
        while($if_count !== $endif_count);

        // Variable is not set, remove that section from the template
        if(!array_key_exists($var_name, $bind_params))
            {
            $original_string = $substr_html_one . $substr_html_three;

            continue;
            }

        // The section stays in, clean it up of the top level if - endif placeholders
        $substr_html_two = substr($substr_html_two, strlen($if_isset_match));
        $substr_html_two = substr($substr_html_two, 0, -9);

        $original_string = $substr_html_one . $substr_html_two . $substr_html_three;
        }

    return $original_string;
    }