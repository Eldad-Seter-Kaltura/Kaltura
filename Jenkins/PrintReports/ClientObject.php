<?php
require_once('KalturaGeneratedAPIClientsPHP/KalturaClient.php');


class ClientObject
{
	/* @var $client KalturaClient */
	private $client;

	private $hostname;
	private $partnerId;
	private $partnerAdminSecret;

	public function __construct($m_hostname, $m_partnerID, $m_partnerAdminSecret) {
		$this->hostname           = $m_hostname;
		$this->partnerId          = $m_partnerID;
		$this->partnerAdminSecret = $m_partnerAdminSecret;
	}

	public function startClientOrRefreshKsIfNeeded($action) {
		$config             = new KalturaConfiguration();
		$config->serviceUrl = $this->hostname;

		$newClient = new KalturaClient($config);
		$newClient->setClientTag("kmcng");
		$ks = $newClient->generateSession($this->partnerAdminSecret, NULL, KalturaSessionType::ADMIN, $this->partnerId, 86400, 'disableentitlements');
		$newClient->setKs($ks);

		if($action == "start") {
			echo "Kaltura session (ks) for partner id " . $this->partnerId . " was created successfully: \n" . $newClient->getKs() . "\n\n";
		} else {
			if($action == "refresh") {
				echo "Kaltura session (ks) for partner id " . $this->partnerId . " was refreshed: \n" . $newClient->getKs() . "\n\n";
			}
		}

		$this->client = $newClient;
	}

	public function doMediaList($mediaEntryFilter, $pager, $message, $trialsExceededMessage, $numberOfTrials) {
		/* @var $mediaList KalturaMediaListResponse */

		if($numberOfTrials > 3) {
			echo $trialsExceededMessage;
			return $mediaList;
		}

		try {
			$mediaList = $this->client->media->listAction($mediaEntryFilter, $pager);
			if($message) {
				echo $message . $mediaList->totalCount . "\n\n";
			}

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $mediaList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$this->startClientOrRefreshKsIfNeeded("refresh");
			sleep(3);

			//retry
			$mediaList = $this->doMediaList($mediaEntryFilter, $pager, $message, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $mediaList;
	}

	public function doBaseEntryList($baseEntryFilter, $pager, $message, $trialsExceededMessage, $numberOfTrials) {
		/* @var $baseEntryList KalturaBaseEntryListResponse */

		if($numberOfTrials > 3) {
			echo $trialsExceededMessage;
			return $baseEntryList;
		}

		try {
			$baseEntryList = $this->client->baseEntry->listAction($baseEntryFilter, $pager);
			if($message) {
				echo $message . $baseEntryList->totalCount . "\n\n";
			}

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $baseEntryList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$this->startClientOrRefreshKsIfNeeded("refresh");
			sleep(3);

			//retry
			$baseEntryList = $this->doBaseEntryList($baseEntryFilter, $pager, $message, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $baseEntryList;
	}

	public function doCategoryEntryList($categoryEntryFilter, $pager, $message, $trialsExceededMessage, $numberOfTrials) {
		/* @var $categoryEntryList KalturaCategoryEntryListResponse */

		if($numberOfTrials > 3) {
			echo $trialsExceededMessage;
			return $categoryEntryList;
		}

		try {
			$categoryEntryList = $this->client->categoryEntry->listAction($categoryEntryFilter, $pager);
			if($message) {
				echo $message . $categoryEntryList->totalCount . "\n\n";
			}

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $categoryEntryList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$this->startClientOrRefreshKsIfNeeded("refresh");
			sleep(3);

			//retry
			$categoryEntryList = $this->doCategoryEntryList($categoryEntryFilter, $pager, $message, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $categoryEntryList;
	}

	public function doCategoryUserList($categoryUserFilter, $pager, $message, $trialsExceededMessage, $numberOfTrials) {
		/* @var $categoryUserList KalturaCategoryUserListResponse */

		if($numberOfTrials > 3) {
			echo $trialsExceededMessage;
			return $categoryUserList;
		}

		try {
			$categoryUserList = $this->client->categoryUser->listAction($categoryUserFilter, $pager);
			if($message) {
				echo $message . $categoryUserList->totalCount . "\n\n";
			}

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $categoryUserList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$this->startClientOrRefreshKsIfNeeded("refresh");
			sleep(3);

			//retry
			$categoryUserList = $this->doCategoryUserList($categoryUserFilter, $pager, $message, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $categoryUserList;
	}

	public function doCategoryList($categoryFilter, $pager, $message, $trialsExceededMessage, $numberOfTrials) {
		/* @var $categoryList KalturaMediaListResponse */

		if($numberOfTrials > 3) {
			echo $trialsExceededMessage;
			return $categoryList;
		}

		try {
			$categoryList = $this->client->category->listAction($categoryFilter, $pager);
			if($message) {
				echo $message . $categoryList->totalCount . "\n\n";
			}

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $categoryList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$this->startClientOrRefreshKsIfNeeded("refresh");
			sleep(3);

			//retry
			$categoryList = $this->doCategoryList($categoryFilter, $pager, $message, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $categoryList;
	}

	public function doCategoryGet($categoryId, $trialsExceededMessage, $numberOfTrials) {
		/* @var $category KalturaCategory */

		if($numberOfTrials > 2) {
			echo $trialsExceededMessage;
			return null;
		}

		try {
			$category = $this->client->category->get($categoryId);
		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return null;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$this->startClientOrRefreshKsIfNeeded("refresh");
			sleep(3);

			$this->doCategoryGet($categoryId, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $category;
	}

}
