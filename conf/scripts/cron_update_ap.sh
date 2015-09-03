#!/bin/bash
curl 'http://localhost:81/api/access-profiles-update' --header "Accept-Language: en" -XPUT --data "{\"r\": \"`uuidgen`\"}"
