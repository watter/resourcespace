<?php
include dirname(__DIR__) . '/../../../include/db.php';
include_once dirname(__DIR__) . '/../../../include/general.php';
include dirname(__DIR__) . '/../../../include/authenticate.php';


$accepted_cookies_use = getval('accepted_cookies_use', NULL, true);
$return               = array();

if(!is_null($accepted_cookies_use))
    {
    updateAcceptedCookiesUse($userref, $accepted_cookies_use);
    rs_setcookie('accepted_cookies_use', '', -1, '', '', substr($baseurl, 0, 5) == 'https', false);

    if((int) $accepted_cookies_use === 0)
        {
        $return['error'] = array(
            'status' => 307,
            'title'  => 'Temporary redirect',
            'detail' => "{$baseurl}/login.php?logout=true&cookies_use=true");
        }
    }

echo json_encode($return);
exit();