<?php

function parse_content($content)
{

	$dom = new DOMDocument('1.0', 'UTF-8');
	$dom->encoding = 'utf-8';

	// Disable error reporting
	libxml_use_internal_errors(true);

	// Try to load as XML first
	$isXml = @$dom->loadXML($content);

	if ($isXml) {
		// Content is XML
		libxml_clear_errors();
		return $dom;
	} else {
		// Content might be HTML, try loading as HTML
		@$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

		// Get the body content
		$body = $dom->getElementsByTagName('body')->item(0);

		if ($body) {
			// Check if there's a pre tag directly inside the body
			$pre = $body->getElementsByTagName('pre')->item(0);

			if ($pre && $pre->parentNode === $body) {
				// If pre tag exists directly under body, use its content
				$bodyContent = $pre->textContent;
			} else {
				// Otherwise, use the entire body content
				$bodyContent = $body->textContent;
			}

			// Remove the XML declaration using a regular expression
			$bodyContent = preg_replace('/<\?xml[^>]+\?>/', '', $bodyContent);

			// Decode HTML entities
			$decodedContent = html_entity_decode($bodyContent, ENT_QUOTES | ENT_XML1, 'UTF-8');


			// Create a new DOM document with the decoded content
			$newDom = new DOMDocument('1.0', 'utf-8');
			$newDom->recover = TRUE;

			libxml_clear_errors();
			libxml_use_internal_errors(true);

			// Suppress warnings for loadXML as the content might not be perfect XML
			$newDom->loadXML($decodedContent,);

			//print_r(libxml_get_errors());

			return $newDom;
		}

		// If no body found, return the original DOM (shouldn't happen in normal cases)
		return $dom;
	}
}

class InvalidResponseException extends Exception {
	public function __construct($message, ?int $code = null, ?Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}

class FlaresolverrExtensionNotConfiguredException extends Exception {
	public function __construct($message, ?int $code = null, ?Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}

function fetch_feed($feed) {

	if(!FreshRSS_Context::systemConf()->hasParam('flaresolver_url')){
		throw new FlaresolverrExtensionNotConfiguredException('FlareSolverr extension not configured. You must set your flaresolver url. See https://github.com/ravenscroftj/freshrss-flaresolverr-extension');
	}

	$ch = curl_init();
	$headers = [
		'Content-Type: application/json'
	];

	// set global timeout based on config or default to 60 seconds
	$globalTimeout = FreshRSS_Context::systemConf()->hasParam('flaresolver_maxTimeout') ? intval(FreshRSS_Context::systemConf()->flaresolver_maxTimeout) : 60000;

	// if maxTimeout param is set then use it instead of global one as long as it is less than global one.
	$maxTimeout = isset($_GET['maxTimeout']) ? min(array(intval($_GET['maxTimeout']), $globalTimeout)) : $globalTimeout;

	$postData = [
		'cmd' => 'request.get',
		'url' => $feed,
		"maxTimeout" => $maxTimeout,
		//'session' => $session
	];
	curl_setopt( $ch, CURLOPT_URL, FreshRSS_Context::systemConf()->flaresolver_url."/v1"); //This is my flaresolverr address
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36');
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$array = json_decode(curl_exec($ch), true);



	if(curl_errno($ch)){
		throw new InvalidResponseException("Error fetching response from FlareSolverr: ".curl_error($ch));
	}

	if (json_last_error() !== JSON_ERROR_NONE) {
		throw new InvalidResponseException("Invalid JSON Response from FlareSolverr");

	}

	curl_close($ch);

	return $array;
}

function run_flaresolverr_extension(){

	try{
		$array = fetch_feed(urldecode($_GET['feed']));
		$doc = parse_content($array['solution']['response']);

		if(isset($_GET['debug']) && $_GET['debug'] == '1'){
			print_r($array);
		}else{
			// Check for RSS element
			$feed = $doc->getElementsByTagName('rss')->item(0);

			if ($feed) {
				// If RSS element found, output the content
				header("Content-type: application/xml");
				echo $doc->saveXML($feed);
			} else {
				// If no RSS element found, output the entire document
				// This allows for cases where the content might be an Atom feed or other XML format
				echo $doc->saveXML();
			}
		}


	} catch (Exception $e) {
		header("HTTP/1.1 500 Internal Server Error");
		echo "Error: " . $e->getMessage();
	}

}

