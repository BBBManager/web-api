#!/bin/bash
date
echo "Synchronizing LDAP"
curl -X GET 'http://127.0.0.1:82/api/ldap-sync/' --header "Accept-Language: en"
echo ;