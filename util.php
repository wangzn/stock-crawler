<?php
function plist($l)
{
    foreach ($l as $k => $v) {
        echo "$k: $v\n";
    }
}

function http_request($url, $data = false)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    if ($data !== false) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $body = curl_exec($ch);
    $info = curl_getinfo($ch);
    $info["body"] = $body;
    return $info;
}
