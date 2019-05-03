Lesterius FileMaker 17 Data API wrapper - myFMApiLibrary for PHP
=======================

# Presentation

## Team
[Lesterius](https://www.lesterius.com "Lesterius") is first and foremost a collective of FileMaker and Web developers who are experts and passionated.\
Sharing knowledge takes part of our DNA, that's why we developed this library to make the FileMaker Data API easy-to-use with PHP.\
Break the limits of your application!\
![Lesterius logo](http://i1.createsend1.com/ei/r/29/D33/DFF/183501/csfinal/Mailing_Lesterius-logo.png "Lesterius")

## Description
This library is a PHP wrapper of the FileMaker Data API.<br/>
You will be able to use every functions like it's documented in your FileMaker server Data Api documentation (accessible via https://[your server domain]/fmi/data/apidoc).
General FileMaker document on the Data API is available [here](https://fmhelp.filemaker.com/docs/17/en/dataapi/)

## Requirements

- PHP >= 5.5
- PHP cURL extension

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

## Prepare your FileMaker solution

1. Enable the FileMaker Data API option on your FileMaker server admin console.
2. Create a specific user in your FileMaker database with the 'fmrest' privilege
3. Define records & layouts access for this user

## Use the library

### Login

Login with credentials:
```php
$dataApi = new \Lesterius\FileMakerApi\DataApi('https://test.fmconnection.com/fmi/data', 'MyDatabase');
$dataApi->login('filemaker api user', 'filemaker api password', 'layout name');
```

Login with oauth:
```php
$dataApi = new \Lesterius\FileMakerApi\DataApi('https://test.fmconnection.com/fmi/data', 'MyDatabase');
$dataApi->loginOauth('oAuthRequestId', 'oAuthIdentifier', 'layout name');
```

### Logout

```php
// Call login method first

$dataApi->logout();
```

### Create record

```php
// Call login method first

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
    'lunchDate' => '17/04/2013',
    'lunchPlace' => 'Acme Inc.'
];

try {
    $recordId = $dataApi->createRecord('layout name', $data, $scripts, $portalData);
} catch(\Exception $e) {
  // handle exception
}
```

### Delete record

```php
// Call login method first

try {
  $dataApi->deleteRecord('layout name', $recordId, $script);
} catch(\Exception $e) {
  // handle exception
}
```

### Edit record

```php
// Call login method first

try {
  $recordId = $dataApi->editRecord('layout name', $recordId, $data, $lastModificationId, $$portalData, $scripts);
} catch(\Exception $e) {
  // handle exception
}
```

### Get record

```php
// Call login method first

$portals = [
    [
        'portal' => 'Portal1',
        'limit'  => 10
    ],
    [ 
        'portal' => 'Portal2',
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
// Call login method first

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
// Call login method first
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
// Call login method first

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

### Upload file to container

```php
// Call login method first

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
