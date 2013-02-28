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
$PAGE_SIZE = 25;
// A priority between metadata schemas.
$METADATA_SCHEMA_PRIORITY = array('5906a41b-feae-48db-bfb7-714b3e105396', '00000000-0000-0000-0000-000063c30000');

// Initialization of the CHAOS client.
$chaos = new \CHAOS\Portal\Client\PortalClient($CHAOS_URL, $CLIENT_GUID);

// Page index from the URL
$pageIndex = array_key_exists('pageIndex', $_GET) ? intval($_GET['pageIndex']) : 0;

// Fetch objects from the service.
$response = $chaos->Object()->Get("", null, $ACCESSPOINT_GUID, $pageIndex, $PAGE_SIZE, true, true, true, true);

$pageCount = ceil($response->MCM()->TotalCount() / $PAGE_SIZE);

/**
 * Gets a field of metadata from a CHAOS object.
 * @param unknown_type $object CHAOS object.
 * @param string $xpath,... One or more xpath expressions to query for, the first one having a non empty result will return.
 * @return unknown|NULL
 */
function get_metadata_field(&$object) {
	global $METADATA_SCHEMA_PRIORITY;
	$metadatas = array();
	$xpaths = array_slice(func_get_args(), 1);
	foreach($METADATA_SCHEMA_PRIORITY as $metadataSchemaGUID) {
		foreach($object->Metadatas as $metadata) {
			if($metadata->MetadataSchemaGUID == $metadataSchemaGUID) {
				$metadatas[] = $metadata;
			}
		}
	}
	foreach($metadatas as $metadata) {
		if(is_string($metadata->MetadataXML)) {
			// Convert this to simple xml.
			$metadata->MetadataXML = simplexml_load_string($metadata->MetadataXML);
			$metadata->MetadataXML->registerXPathNamespace('dka', 'http://www.danskkulturarv.dk/DKA.xsd');
			$metadata->MetadataXML->registerXPathNamespace('dka2', 'http://www.danskkulturarv.dk/DKA2.xsd');
		}
		/* @var $metadata->MetadataXML \SimpleXMLElement */
		foreach($xpaths as $xpath) {
			$result = $metadata->MetadataXML->xpath($xpath);
			if(count($result) > 0) {
				return $result;
			}
		}
	}
	return null;
}

function get_first_metadata_field($object) {
	$result = call_user_func_array('get_metadata_field', func_get_args());
	if($result !== null && count($result) > 0) {
		return strval($result[0]);
	} else {
		return null;
	}
}

?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>www.danskkulturarv.dk</title>
	</head>
	<body>
		<h1>www.danskkulturarv.dk sitemap</h1>
		<h2>This is a simplified paginated version of www.danskkulturarv.dk, publishing <?=$response->MCM()->TotalCount()?> objects.</h2>
		<section>
			<h3>Page <?=$pageIndex?> of <?=$pageCount?>.</h3>
			<p>
				<?if($pageIndex > 0):?>
					<a href="?pageIndex=<?=$pageIndex-1?>">Previous page</a>
				<?endif;?>
				<?if($pageIndex < $pageCount-1):?>
					<a href="?pageIndex=<?=$pageIndex+1?>">Next page</a>
				<?endif;?>
			</p>
				
		</section>
		<section>
			<?foreach($response->MCM()->Results() as $object):?>
				<article>
					<header>
						<h3><a href="object.html#<?=$object->GUID?>"><?=get_first_metadata_field($object, 'Title', 'dka:Title', 'dka2:Title')?></a></h3>
					</header>
				<article>
			<?endforeach;?>
		</section>
	</body>
</html>
