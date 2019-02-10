# restfulapi-for-erp-elastomers

## inspectionentry

#### initial fetch (GET)

http://13.127.28.250/api/inspectionentry/fetch.php?id=E2923_18-x

#### initial fetch (POST)

http://13.127.28.250/api/inspectionentry/fetch.php

post request json body structure:-

```
{
    "cmpdid": "CNT0305",
    "mdlrref": "61337",
    "isexternal": "0",
    "inspdate": "2018-09-06",
    "recqty": 700,
    "appqty": 649,
    "inspector": "A. Julie",
    "planid": "E2923_18-x",
    "rejection": [
        {
            "rej_short_name":  "FL",
            "value": 23
        },{
            "rej_short_name":  "TR",
            "value": 12
        }
    ]
}

```

## securityentry

### dispatch entry

#### initial fetch (GET)

http://13.127.28.250/api/securityentry/dispatchentry/fetch.php?invid=inv~18-6268
http://13.127.28.250/api/securityentry/dispatchentry/fetch.php?invid=inv~18-6269
http://13.127.28.250/api/securityentry/dispatchentry/fetch.php?invid=inv~18-6270

#### initial fetch (POST)

http://13.127.28.250/api/securityentry/dispatchentry/fetch.php

post request json body structure:-

```
{
    "planList": [
        {
            "docId": "id",
            "docType": "type",
            "totalQty": 234,
            "numPacks": 10
        },{
            "docId": "id",
            "docType": "type",
            "totalQty": 234,
            "numPacks": 10
        }
    ],
    "pickUp": {
        "by": "name",
        "vehicle": "vehicleno"
    }
}

```
