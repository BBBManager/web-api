#!/bin/bash
date
echo "Synchronizing LDAP"
curl -X GET 'http://127.0.0.1:82/recording_sync.php' --header "Accept-Language: en"
echo ;