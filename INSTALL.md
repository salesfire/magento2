
## How to install

### Method 1: Extension Manager

1. Add Salesfire to your magento account (https://marketplace.magento.com/salesfire-magento2.html)
2. Navigate to the Extension Manager within your store admin (System > Web Setup Wizard > Extension Manager)
3. Refresh then navigate to Review and Install
4. Install salesfire/magento2 (Version 1.1.0)

### Method 2: Install via composer

```
composer require salesfire/magento2
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
```

### Method 3: Manually install via composer

1. Access to your server via SSH
2. Create a folder (Not Magento root directory) called: `salesfire`, then upload the zip package to salesfire folder.
Download the zip package at https://github.com/salesfire/magento2/archive/master.zip

3. Add the following snippet to `composer.json`

```
    {
        "repositories": [
            {
                "type": "artifact",
                "url": "path/to/root/directory/salesfire/"
            }
        ]
    }
```

4. Run composer command line

```
composer require salesfire/magento2
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
```


## How to setup

After installing, you will need to enter your Salesfire details by following the steps below:

1. Navigate to the store configuration (Stores > Configuration)
2. Navigate to the Salesfire settings (Salesfire > General)
4. Populate the Site ID (This can be found within your Salesfire admin)
5. Mark the extension as enabled
6. Done
