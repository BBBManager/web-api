#!/bin/bash
date
echo "Synchronizing LDAP"
curl -X GET 'http://localhost:82/api/ldap-sync/' --header "Accept-Language: en"
echo ;