#!/bin/bash
date
echo "Synchronizing LDAP"
curl 'http://localhost:81/apu/ldap-sync' --header "Accept-Language: en"
echo ;

date
echo "Updating access profiles"
curl 'http://localhost:81/api/access-profiles-update' --header "Accept-Language: en" -XPUT --data "{\"r\": \"`uuidgen`\"}"
echo ;

date
echo "Done"
echo ;
echo ;
