<?php
/**
* Encrypts data
* 
* @uses generateSecureKey()
* 
* @param  string  $data  Data to be encypted
* @param  string  $key
* @todo Add a third parameter to use with custom metadata (NOT ResourceSpace metadata) for generating MAC. this should
* add extra security by making MAC harder to be forged
* 
* @return  string  Encrypted data, base64 encoded
*/
function rsEncrypt($data, $key)
    {
    global $scramble_key;

    /*
    Encrypt-then-MAC (EtM)
    ======================
    PlainText
        |
    Encryption <-- Key
        |_________   |
        |         |  |
        |      HashFunction
        |           |
    --------------------
    | Ciphertext | MAC |
    --------------------
    The plaintext is first encrypted, then a MAC is produced based on the resulting ciphertext.  The ciphertext and its 
    MAC are sent together.
    */
    $method  = "AES-128-CTR";
    $options = OPENSSL_RAW_DATA;
    $nonce   = generateSecureKey(128);
    
    // Get 2 derived subkeys, one for message authentication code (MAC) and the other one for encryption/ decryption.
    $mac_key = hash_hmac("sha256", "mac_key", $scramble_key, true);
    $enc_key = hash_hmac("sha256", "enc_key", $scramble_key, true);

    // Synthetic Initialization Vector (SIV)
    $siv = substr(hash_hmac("sha256", "{$nonce}{$scramble_key}{$key}", $mac_key, true), 0, 16);

    $cyphertext = openssl_encrypt($data, $method, $enc_key, $options, $siv);

    $mac = hash_hmac("sha256", "{$cyphertext}{$nonce}{$scramble_key}", $mac_key, true);

    return base64_encode("{$nonce}@@{$cyphertext}@@{$mac}");
    }


/**
* Decrypts data
* 
* @param  string  $data  Data to be decypted
* @param  string  $key
* @todo Add a third parameter to use with custom metadata (NOT ResourceSpace metadata) for generating MAC. this should
* add extra security by making MAC harder to be forged
* 
* @return  false|string  Returns FALSE if MAC check failed, plaintext otherwise
*/
function rsDecrypt($data, $key)
    {
    global $scramble_key;

    $data    = base64_decode($data);
    $method  = "AES-128-CTR";
    $options = OPENSSL_RAW_DATA;

    // Get 2 derived subkeys, one for message authentication code (MAC) and the other one for encryption/ decryption.
    $mac_key = hash_hmac("sha256", "mac_key", $scramble_key, true);
    $enc_key = hash_hmac("sha256", "enc_key", $scramble_key, true);

    list($nonce, $cyphertext, $mac) = explode("@@", $data);

    // Check MAC
    if($mac !== hash_hmac("sha256", "{$cyphertext}{$nonce}{$scramble_key}", $mac_key, true))
        {
        return false;
        }

    // Synthetic Initialization Vector (SIV)
    $siv = substr(hash_hmac("sha256", "{$nonce}{$scramble_key}{$key}", $mac_key, true), 0, 16);

    $plaintext = openssl_decrypt($cyphertext, $method, $enc_key, $options, $siv);

    return $plaintext;
    }