# MageWorx Option Health Checker Extension for Magento 2

The extension is designed for analysis and clearing the tables of Advanced Product Options from the garbage data 
that could remain in the database due to some third-party factors. 
At the first installation, the extension will clean data from the APO tables that are not relevant any more 
and not used anywhere. Also, there are 2 commands that can be run in CLI:

- mageworx:apo:analyze-data – analyzes APO tables and displays information about unnecessary (junk) data, if any
- mageworx:apo:clean-data – clears the APO tables from unnecessary (junk) data, if any

## Upload the extension

### Upload via Composer

See the corresponding section in the README file for the extension meta package

### Upload by copying code

1. Log into Magento server (or switch to) as a user who has permissions to write to the Magento file system.
2. Download the "Ready to paste" package from your customer's area, unzip it and upload the 'app' folder to your Magento install dir.


## Enable the extension

1. Log in to the Magento server as, or switch to, a user who has permissions to write to the Magento file system.
2. Go to your Magento install dir:
```
cd <your Magento install dir> 
```

3. And finally, update the database:
```
php bin/magento setup:upgrade
php bin/magento cache:flush
php bin/magento setup:static-content:deploy
```
