<?php
const token = "Your account token";
// Open the file using fopen() in append
$fp = fopen("log.txt", 'a');

// Get the real IP, as CloudFlare breaks REMOTE_ADDR if you just use that.
// Source: https://stackoverflow.com/questions/13646690/how-to-get-real-ip-from-visitor/13646848
function GetUserIP()
{
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
              $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
              $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];

    if(filter_var($client, FILTER_VALIDATE_IP))
    {
        $ip = $client;
    }
    elseif(filter_var($forward, FILTER_VALIDATE_IP))
    {
        $ip = $forward;
    }
    else
    {
        $ip = $remote;
    }

    return $ip;
}

// Get content from an external URL using cURL
function ReadURL($URL)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $URL);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// Verify that the user isn't on a VPN
function Verify()
{
    global $ip;
    if (IsVPN($ip)) die("Sorry, but VPNs and/or Proxies aren't allowed!");
}

// Check if the specified IP is a VPN using IPHub's Guest-API
function IsVPN($ip)
{
    $iphub = json_decode(ReadURL("https://v2.api.iphub.info/guest/ip/" . GetUserIP()));
    return $iphub->block;
}

// Get a new invite.
function GetInvite()
{
    Verify();
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://discord.com/api/v7/channels/channel_id/invites",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "{\r\n    \"max_age\": 1800,\r\n    \"max_uses\": 1,\r\n    \"temporary\": false\r\n}",
        CURLOPT_HTTPHEADER => array(
            "Authorization: " . token,
            "Content-Type: application/json"
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

$invite = json_decode(GetInvite())->code;

// Log the client's information in this format:
// [Date-Time] IP
fwrite($fp, "[" . date('Y-m-d @ H:i:s') . "] " . GetUserIP() . "\n");
fclose($fp);
echo "Please wait...";
header("refresh:2;url=https://discord.gg/$invite");
