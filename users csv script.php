<?php
namespace Eldad;
require __DIR__ . '/vendor/autoload.php';

use Kaltura\Client\Client as KalturaClient;
use Kaltura\Client\Configuration as KalturaConfiguration;
use Kaltura\Client\Enum\SearchOperatorType;
use Kaltura\Client\Enum\SessionType as KalturaSessionType;

use Kaltura\Client\ILogger;
use \Kaltura\Client\ApiException as KalturaApiException;

use Kaltura\Client\Plugin\Metadata\Type\Metadata;
use Kaltura\Client\Plugin\Metadata\Type\MetadataSearchItem;
use Kaltura\Client\Type\SearchCondition;
use Kaltura\Client\Type\UserFilter as KalturaUserFilter;
use Kaltura\Client\Enum\UserOrderBy as KalturaUserOrderBy;
use Kaltura\Client\Type\FilterPager as KalturaFilterPager;

use Kaltura\Client\Plugin\Metadata\Type\MetadataFilter as KalturaMetadataFilter;
use Kaltura\Client\Plugin\Metadata\Enum\MetadataObjectType as KalturaMetadataObjectType;
use Kaltura\Client\Plugin\Metadata\MetadataPlugin as KalturaMetadataPlugin;

class KalturaLogger implements ILogger {
	public function log($msg) {
		return;
	}
}

// params
define('SERVICE_URL', "https://www.kaltura.com");
define('ADMIN_SECRET', "2938a5995819fb83d076a9a2b615b73f");
define('USER_ID', "Roy");
define('PARTNER_ID', 331501);
define('EXPIRY', 7*86400);
define('PRIVILEGES', 'disableentitlement');

define('PAGE_SIZE', 500);

define('PROFILE_ID', 574132);

// init kaltura configuration
$config = new KalturaConfiguration();
$config->setServiceUrl(SERVICE_URL);
$config->setVerifySSL(false);
$config->setLogger(new KalturaLogger());

// init kaltura client
$client = new KalturaClient($config);

// generate session
$ks = $client->generateSession(ADMIN_SECRET, USER_ID, KalturaSessionType::ADMIN, PARTNER_ID, EXPIRY, PRIVILEGES);

$config->getLogger()->log('Kaltura session (ks) was generated successfully: ' . $ks);
echo "Kaltura session (ks) was generated successfully: " . $ks . "\n";

$client->setKs($ks);

// print the headers
echo "i\tUserID\tFirstName\tLastName\tE-mail\tRole\n\n";

// initialize state
$userList           = NULL;
$usersMetadatas     = NULL;
$metadataObjectIdIn = "";

$usersInMediaSpace = array();
$userRole = NULL;
$role = NULL;

// build the filter
$userFilter          = new KalturaUserFilter();
$userFilter->orderBy = KalturaUserOrderBy::CREATED_AT_ASC;

// user filter - search only users with existing metadata
$userFilter->advancedSearch = new MetadataSearchItem();
$search = $userFilter->advancedSearch;
$search->type = SearchOperatorType::SEARCH_AND;
$search->metadataProfileId = PROFILE_ID;

$searchCondition = new SearchCondition();
$searchCondition->field = "/*[local-name()='metadata']/*[local-name()='role']";
$searchCondition->value = "*";
$search->items = [$searchCondition];

$pager = new KalturaFilterPager();
$pager->pageSize = PAGE_SIZE;
$pager->pageIndex = 1;                  //TODO: Always getting the first page

$metadataFilter                          = new KalturaMetadataFilter();
$metadataFilter->metadataProfileIdEqual  = PROFILE_ID;
$metadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::USER;

// get the users
echo "Getting the users...\n";

$f = fopen('users.csv', 'w');

echo "Starting from page 1\n\n";

// first time:
// Get Number of users
try {
	// user . list
	$userList = $client->getUserService()->listAction($userFilter, $pager);
	$numberOfUsers = $userList->totalCount;
} catch(KalturaApiException $apiException) {
	echo "This is API exception: \n";
	echo "Users list of page 1 failed- \n";
	echo $apiException->getMessage() . "\n";
	echo "At line: " . $apiException->getLine() . "\n";
	echo "Arguments: \n";
	$comma_separated = implode(",", $apiException->getArguments());
	echo $comma_separated;
	echo "This is stack trace: \n";
	echo $apiException->getTraceAsString();
	die;
}

$i = 0;
$j = 0;
while($i <= $numberOfUsers) {

	$users500 = array();
	$users500String = "";
	$lastCreate = 0;
	$arr = array();
	// FOR EACH USER IN PAGE:
	foreach ($userList->objects as $currentUser) {

		// users of next timestamp- are not relevant
		if($lastCreate != $currentUser->createdAt) {
			$arr = array();
		}

		$i++;

		// Add to IDs that we're going to filter by
		$metadataObjectIdIn .=  $currentUser->id . ",";

		$userInfoArray = array($currentUser->id, $currentUser->firstName, $currentUser->lastName, $currentUser->email);
		$userInfo = $i . " " . $currentUser->id. " " . $currentUser->createdAt . " " . "\n";

		$users500[$currentUser->id] = $userInfoArray;
		// TODO
		$users500String .= $userInfo;

		// only users of Current timestamp
		// push to back of array
		$arr[] = $currentUser->id;
		$lastCreate = $currentUser->createdAt;
	}

	// Now we have users of last timestamp!
	// If there is duplicates- will not print their metadata

	// Every page - call metadata . list
	$idIn = substr($metadataObjectIdIn, 0, -1);

	// TODO Debug
	$idInReplace = str_replace(",", "\n", $idIn);

	// filter by several IDs

	$metadataFilter->objectIdIn = $idIn;

	// metadata . list
	$metadataPlugin = KalturaMetadataPlugin::get($client);
	try {
		/** @var  Metadata MetadataListResponse */
		$usersMetadatas = $metadataPlugin->getMetadataService()->listAction($metadataFilter, $pager);
	}
	catch(KalturaApiException $apiException) {
//		echo $userInfo . "\n";
		echo "This is API exception: \n";
		echo "Metadata list of page $j+1 failed- \n";
		echo $apiException->getMessage();
		echo "At line: " . $apiException->getLine() . "\n";
		echo "Arguments: \n";
		$comma_separated = implode(",", $apiException->getArguments());
		echo $comma_separated;
		echo "This is stack trace: \n";
		echo $apiException->getTraceAsString();
		die;
	}
	// EXISTS IN MEDIASPACE:
	if($usersMetadatas->objects != array()) {

		$stringIds = "";
		/** @var  Metadata $metaDataObject */
		foreach($usersMetadatas->objects as $metaDataObject) {
			$userId = $metaDataObject->objectId;
			$stringIds .= $userId . ",";

			// if already retrieved this user-
			if(array_key_exists($userId, $arr)) {
				// don't write to file!
				continue;
			}

			$userRole = $metaDataObject->xml;
			if($userRole != null) {
				$xml = new \SimpleXMLElement($userRole);
				$role = $xml->children();
				$usersInMediaSpace[] = array_push($users500[$userId], $role);
				fputcsv($f, $users500[$userId]);
			}
		}
		// TODO Debug
		$stringIdsConcat = substr($stringIds, 0, -1);
		$stringConcatReplace = str_replace(",", "\n", $stringIdsConcat);
	}
	$metadataFilter->objectIdIn = "";
	$metadataObjectIdIn = "";
	$idIn = "";

	$j++;
	echo "End of page ". $j . "\n" . "=================" . "\n";

	//after each page:
	// next batch will ascend according to createdAt after last batch

	// Important- May have duplicates! as this is >= and not >
	$userFilter->createdAtGreaterThanOrEqual = $currentUser->createdAt;

	// user . list
	try {
		$userList = $client->getUserService()->listAction($userFilter, $pager);
	} catch(KalturaApiException $apiException) {
		echo "This is API exception: \n";
		echo "Users list of page $j+1 failed- \n";
		echo $apiException->getMessage() . "\n";
		echo "At line: " . $apiException->getLine() . "\n";
		echo "Arguments: \n";
		$comma_separated = implode(",", $apiException->getArguments());
		echo $comma_separated;
		echo "This is stack trace: \n";
		echo $apiException->getTraceAsString();
		die;
	}
}

fclose($f);

