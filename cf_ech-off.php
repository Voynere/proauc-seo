<?php

$curl = curl_init();

/*
curl -X PATCH "https://api.cloudflare.com/client/v4/zones/{ID_ZONE}/settings/ech" \
     -H "Authorization: Bearer {API_KEY}" \
     -H "Content-Type:application/json" --data '{"id":"ech","value":"off"}'
*/	 
	 
	 
curl_setopt_array($curl, [
  CURLOPT_URL => "https://api.cloudflare.com/client/v4/zones/9d60b7b29fb023482f926bdc22c96dc5/settings/ech",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "PATCH",
  CURLOPT_POSTFIELDS => "{\n \"id\": \"ech\", \n  \"value\": \"off\"\n}",
  CURLOPT_HTTPHEADER => [
    "Content-Type: application/json",
    "X-Auth-Email: greddy2404qaz@gmail.com",
	"Authorization: Bearer fYdkSqS_vVKOat33MMqe8GV3eP0qQNcfXnGhBQ-y"
  ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo $response;
}