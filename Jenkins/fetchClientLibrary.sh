#!/usr/bin/env bash
 
CLIENT_REPO_URL=https://github.com/kaltura/KalturaGeneratedAPIClientsPHP
echo "Fetching up-to-date client from $CLIENT_REPO_URL"

if [ -d "./KalturaGeneratedAPIClientsPHP" ]
then
	echo "Client directory already exists. Updating"
	cd KalturaGeneratedAPIClientsPHP
	git pull origin master
	cd ..
else
	git clone $CLIENT_REPO_URL
fi
