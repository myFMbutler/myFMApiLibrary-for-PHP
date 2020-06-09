Lesterius (Claris) FileMaker 19 Data API wrapper - myFMApiLibrary for PHP
=======================

# Presentation

## Team
[Lesterius](https://www.lesterius.com "Lesterius") is a European Claris (FileMaker) Business Alliance Platinum member that operates in Belgium, France, the Netherlands, Portugal and Spain. We are creative business consultants who co-create FileMaker Platform based solutions with our customers.\
Sharing knowledge takes part of our DNA, that's why we developed this library to make the FileMaker Data API easy-to-use with PHP.\
Break the limits of your application!\
![Lesterius logo](http://i1.createsend1.com/ei/r/29/D33/DFF/183501/csfinal/Mailing_Lesterius-logo.png "Lesterius")

## Description
This library is a PHP wrapper of the (Claris) FileMaker Data API 19.<br/>

You can find the PHP wrapper of the FileMaker Data API 17 on the releases <= v.1.* .<br/>
You can find the PHP wrapper of the FileMaker Data API 18 on the releases <= v.2.* .<br/>

You will be able to use every functions like it's documented in your FileMaker server Data Api documentation (accessible via https://[your server domain]/fmi/data/apidoc).
General Claris document on the Data API is available [here](https://help.claris.com/en/data-api-guide/)

## Requirements

- PHP >= 5.5
- PHP cURL extension
- PHP mb_string extension

## Installation

The recommended way to install it is through [Composer](http://getcomposer.org).

```bash
composer require myfmbutler/myfmapilibrary-for-php
```

After installing, you need to require Composer's autoloader:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

# Usage

## Prepare your Claris (Filmaker) solution

1. Enable the (Claris) FileMaker Data API option on your FileMaker server admin console.
2. Create a specific user in your (Claris) FileMaker database with the 'fmrest' privilege
3. Define records & layouts access for this user

## Use the library

### Login

Login with credentials:
```php
$dataApi = new \Lesterius\FileMakerApi\DataApi('https://test.fmconnection.com/fmi/data', 'MyDatabase', 'filemaker api user', 'filemaker api password');
```

Login with oauth:
```php
$dataApi = new \Lesterius\FileMakerApi\DataApi('https://test.fmconnection.com/fmi/data', 'MyDatabase', null, null, true, 'oAuthRequestId', 'oAuthIdentifier');
```

### Logout

```php

$dataApi->logout();
```

### Validate Session

```php

$dataApi->validateSession();
```

### Create record

```php

$data = [
    'FirstName'         => 'John',
    'LastName'          => 'Doe',
    'email'             => 'johndoe@acme.inc',
    'RepeatingField(1)' => 'Test'
];

$scripts = [
    [
        'name'  => 'ValidateUser',
        'param' => 'johndoe@acme.inc',
        'type'  => Lesterius\FileMakerApi\DataApi::SCRIPT_PREREQUEST
    ],
    [
        'name'  => 'SendEmail',
        'param' => 'johndoe@acme.inc',
        'type'  => Lesterius\FileMakerApi\DataApi::SCRIPT_POSTREQUEST
    ]
];

$portalData = [
  'portalName or OccurenceName' => [
      [
          "Occurence::PortalField 1" => "Value",
          "Occurence::PortalField 2" => "Value",
      ]
  ]
];

try {
    $recordId = $dataApi->createRecord('layout name', $data, $scripts, $portalData);
} catch(\Exception $e) {
  // handle exception
}
```

### Delete record

```php

try {
  $dataApi->deleteRecord('layout name', $recordId, $script);
} catch(\Exception $e) {
  // handle exception
}
```

### Edit record

```php

try {
  $recordId = $dataApi->editRecord('layout name', $recordId, $data, $lastModificationId, $portalData, $scripts);
} catch(\Exception $e) {
  // handle exception
}
```

### Duplicate record

```php

try {
  $recordId = $dataApi->editRecord('layout name', $recordId, $scripts);
} catch(\Exception $e) {
  // handle exception
}
```

### Get record

```php

$portals = [
    [
        'name'  => 'Portal1',
        'limit' => 10
    ],
    [ 
        'name'   => 'Portal2',
        'offset' => 3
    ]
];

try {
  $record = $dataApi->getRecord('layout name', $recordId, $portals, $scripts);
} catch(\Exception $e) {
  // handle exception
}
```

### Get records

```php

$sort = [
    [
        'fieldName' => 'FirstName',
        'sortOrder' => 'ascend'
    ],
    [
        'fieldName' => 'City',
        'sortOrder' => 'descend'
    ]
];

try {
  $record = $dataApi->getRecords('layout name', $sort, $offset, $limit, $portals, $scripts);
} catch(\Exception $e) {
  // handle exception
}
```

### Find records

```php

$query = [
    [
        'fields'  => [
            ['fieldname' => 'FirstName', 'fieldvalue' => '==Test'],
            ['fieldname' => 'LastName', 'fieldvalue' => '==Test'],
        ],
        'options' => [
            'omit' => false
        ]
    ]
];

try {
  $results = $dataApi->findRecords('layout name', $query, $sort, $offset, $limit, $portals, $scripts, $responseLayout);
} catch(\Exception $e) {
  // handle exception
}
```

### Set global fields

```php

$data = [
  'FieldName1'	=> 'value',
  'FieldName2'	=> 'value'
];

try {
  $dataApi->setGlobalFields('layout name', $data);
} catch(\Exception $e) {
  // handle exception
}
```

### Execute script

```php


try {
  $dataApi->executeScript('script name', $scriptsParams);
} catch(\Exception $e) {
  // handle exception
}
```

### Upload file to container

#### Renaming file
```php

$containerFieldName       = 'Picture';
$containerFieldRepetition = 1;
// replace 'upload' below with the name="value" of the file input element of your web form
$filepath                 = $_FILES['upload']['tmp_name'];
$filename                 = $_FILES['upload']['name'];

try {
  $dataApi->uploadToContainer('layout name', $recordId, $containerFieldName, $containerFieldRepetition, $filepath, $filename);
} catch(\Exception $e) {
  // handle exception
}
```

#### Without checking filename
```php

$containerFieldName       = 'Picture';
$containerFieldRepetition = 1;
$filepath                 = '/usr/home/acme/pictures/photo.jpg';

try {
  $dataApi->uploadToContainer('layout name', $recordId, $containerFieldName, $containerFieldRepetition, $filepath);
} catch(\Exception $e) {
  // handle exception
}
```

### Metadata Info

#### Product Info
```php

try {
  $dataApi->getProductInfo();
} catch(\Exception $e) {
  // handle exception
}
```

#### Database Names
```php

try {
  $dataApi->getDatabaseNames();
} catch(\Exception $e) {
  // handle exception
}
```

#### Layout Names
```php

try {
  $dataApi->getLayoutNames();
} catch(\Exception $e) {
  // handle exception
}
```

#### Script Names
```php

try {
  $dataApi->getScriptNames();
} catch(\Exception $e) {
  // handle exception
}
```

#### Layout Metadata
```php

try {
  $dataApi->getLayoutMetadata('layout name', $recordId);
} catch(\Exception $e) {
  // handle exception
}
```
