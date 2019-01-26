# restfulapi-for-erp-elastomers

## inspectionentry

#### initial fetch (GET)

http://thayalangr-pc:50/api/inspectionentry/fetch.php?id=E2923_18-x

## securityentry

### dispatch entry

#### initial fetch (GET)

http://thayalangr-pc:50/api/securityentry/dispatchentry/fetch.php?invid=inv~18-6269

#### initial fetch (POST)

http://thayalangr-pc:50/api/securityentry/dispatchentry/fetch.php

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
