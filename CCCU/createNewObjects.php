<?php 
require_once('php5/KalturaClient.php');
class ConstructNewObjects
{
	public $hostname;
	
	public $partnerId;
	
	public $partnerAdminSecret;
	
	function __construct($m_hostname, $m_partnerID, $m_partnerAdminSecret)
	{
		$this->hostname = $m_hostname;
		$this->partnerId = $m_partnerID;
		$this->partnerAdminSecret = $m_partnerAdminSecret;
	}
	
	function constructObjects ()
	{
		//Create new metadata profile
		$config = new KalturaConfiguration();
		$config->serviceUrl = $this->hostname;
		$client = new KalturaClient($config);
		
		$session = $client->generateSessionV2($this->partnerAdminSecret, null, 2, $this->partnerId, 86400, null);
		$client->setKs($session);
		
		$metadataPlugin = KalturaMetadataClientPlugin::get($client);
		//Try to list all MP with the required system name. If exists - just use is and exit.
		$filter = new KalturaMetadataProfileFilter();
		$filter->systemNameEqual = 'media_retention_profile';
		$result = $metadataPlugin->metadataProfile->listAction($filter);
		
		if (count ($result->objects))
		{
			return $result->objects[0]->id;
		}
		
		$profile = new KalturaMetadataProfile();
		$profile->name = "Media Retention Profile";
		$profile->systemName = "media_retention_profile";
		$profile->metadataObjectType = KalturaMetadataObjectType::ENTRY;
		
		//Create a new metadata profile
		try {
			$profile = $metadataPlugin->metadataProfile->add($profile, file_get_contents('retentionMP.xsd'));
		}
		catch (Exception $e)
		{
			die ('Failed to create data retention MP. Problem occurred: [' . $e->getMessage() . ']');
		}
		
		//Create a new template entry
		$templateEntry = new KalturaBaseEntry();
		$templateEntry->name = "Template entry";
		$templateEntry->description = "Template entry for new OTube entries.";
		try {
			$addedEntry = $client->baseEntry->add($templateEntry);
		}
		catch (Exception $e)
		{
			die ('Failed to create data retention template entry. Problem occurred: [' . $e->getMessage() . ']');
		}
		
		//Add metadata object to the template entry
		$metadataObj = new KalturaMetadata();
		$metadataObj->metadataProfileId = $profile->id;
		$metadataObj->objectId = $addedEntry->id;
		
		try {
			$metadataObj = $metadataPlugin->metadata->add($profile->id, KalturaMetadataObjectType::ENTRY, $addedEntry->id, file_get_contents("template_data_retention_metadata.xml"));
		}
		catch (Exception $e) {
			die ('Failed to create data retention template entry metadata. Problem occurred: [' . $e->getMessage() . ']');
		}
		
		try {
			$defaultConversionProfiles = $client->conversionProfile->getDefault();
		}
		catch (Exception $e) {
			die ('Failed to retrieve partner default conversion profile. Problem occurred: [' . $e->getMessage() . ']');
		}
		
		$updateConversionProfile = new KalturaConversionProfile();
		$updateConversionProfile->defaultEntryId = $addedEntry->id;
		
		try {
			$client->conversionProfile->update($defaultConversionProfiles->id, $updateConversionProfile);
		}
		catch (Exception $e)
		{
			die ('Failed to update default conversion profile. Problem occurred: [' . $e->getMessage() . ']');
		}
		
		return $profile->id;
	}
}

