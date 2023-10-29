<?php
// BOOTSTRAP FreshRSS
require(__DIR__ . '/../../constants.php');
require(LIB_PATH . '/lib_rss.php');     //Includes class autoloader
FreshRSS_Context::initSystem();

// Load list of extensions and enable the "system" ones.                                              
Minz_ExtensionManager::init();                                
                                                              
//Returns request for website protected by Cloudflare                     
//Requires  ghcr.io/flaresolverr/flaresolverr docker container
$feed = $_GET['feed'];
$loadViaHTML = $_GET['viahtml'] == '1';

$ch = curl_init();                                            
$headers  = [                       
    'Content-Type: application/json'
];                                  
$postData = [                       
    'cmd' => 'request.get',         
    'url' => $feed,        
    "maxTimeout"=> 60000,  
    //'session' => $session                                                                    
];                                                                                             
curl_setopt($ch, CURLOPT_URL, FreshRSS_Context::$system_conf->flaresolver_url."/v1"); //This is my flaresolverr address 
curl_setopt($ch, CURLOPT_POST, 1);                                                                                                                              
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                                                                 
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);                                                                                                                 
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));                                                                                                   
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);                                                                                                                 
                                                                                                                                                                
$array = json_decode(curl_exec($ch), true);                  
                                               
header("Content-type: application/xml");   
                                           
//echo curl_exec($ch);                  

// Some feeds returned from FlareSolverr have the RSS embedded as HTML entities.
// Loading via HTML and extracting the body seems to fix this.
$doc = new DOMDocument();

if ($loadViaHTML) {
    $doc->loadHTML($array['solution']['response']);
    $upstreamBody = $doc->getElementsByTagName('body')[0];
    $doc = new DOMDocument();
    $doc->loadXml($upstreamBody->textContent);
} else {
    $doc->loadXML($array['solution']['response']);
}

                                              
$feed = $doc->getElementsbyTagName('rss')[0]; 
                                              
echo $doc->saveXML($feed);                   
                                             
curl_close($ch); 
