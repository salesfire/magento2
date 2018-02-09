# Magento 2.x Salesfire Module
Salesfire is a service that provides a number of tools that help to increase sales using various on site methods.

https://www.salesfire.co.uk/


## FAQs

#### Q: Is there any additional costs?
A: Yes, we provide the software which helps increase sales for a fee which is tailored to your business. This is to provide you with the best ROI as possible.

You can find out more information and even get a free trial at https://www.salesfire.co.uk/


## How to install

### Method 1: Install via composer

```
composer require salesfire/magento2
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
```

### Method 2: Manually install via composer

1. Access to your server via SSH
2. Create a folder (Not Magento root directory) in called: `salesfire`, then upload the zip package to salesfire folder.
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
