<?php
header('Content-Type: text/html; charset=UTF-8');

// Include the CHAOS client.
$chaos_path = realpath('./chaos/src/');
set_include_path(get_include_path() . PATH_SEPARATOR . $chaos_path);
require("chaos/src/CaseSensitiveAutoload.php");
spl_autoload_extensions(".php");
spl_autoload_register("CaseSensitiveAutoload");

// Enable error reporting.
error_reporting(E_ALL);
ini_set('display_errors', '1');

// The URL of the service.
$CHAOS_URL = "http://api.chaos-systems.com/v5";
// Some random guid.
$CLIENT_GUID = "307698db-0704-49d7-a3f1-d2afebb9d4b0";
// The accesspoint on which everything is published.
$ACCESSPOINT_GUID = "C4C2B8DA-A980-11E1-814B-02CEA2621172";
// The number of objects visible pr. page.
$PAGE_SIZE = 500;
$SITEMAP_SIZE = 5000;
// A priority between metadata schemas.
$METADATA_SCHEMA_PRIORITY = array('5906a41b-feae-48db-bfb7-714b3e105396', '00000000-0000-0000-0000-000063c30000');

// Initialization of the CHAOS client.
$chaos = new \CHAOS\Portal\Client\PortalClient($CHAOS_URL, $CLIENT_GUID);

$sitemapIndex = array_key_exists('sitemapIndex', $_GET) ? intval($_GET['sitemapIndex']) : 0;

// Page index from the URL
$pageIndex = floor($sitemapIndex * $SITEMAP_SIZE / $PAGE_SIZE);
	
function getObjectURL($object) {
	return sprintf("http://indexing.danskkulturarv.dk/object.html?sitemapped#%s", $object->GUID);
}

$number_of_printed_objects = 0;
do {
	// Fetch objects from the service.
	$response = $chaos->Object()->Get("", null, $ACCESSPOINT_GUID, $pageIndex, $PAGE_SIZE, true, true, true, true);
	
	$pageCount = ceil($response->MCM()->TotalCount() / $PAGE_SIZE);
	
	foreach($response->MCM()->Results() as $object) {
		echo getObjectURL($object) . "\n";
		$number_of_printed_objects++;
		if($number_of_printed_objects >= $SITEMAP_SIZE) {
			break;
		}
	}
	$pageIndex++;
} while($number_of_printed_objects < $SITEMAP_SIZE);

?>
