# Changelog

### Salesfire v1.4.17
Released on 2024-10-03
Released notes:

- Add support for Magento MSI and stop disabled products from being included in the feed.

### Salesfire v1.4.16
Released on 2024-09-12
Released notes:

- Fix configurable products stock level calculation.

### Salesfire v1.4.15
Released on 2024-09-09
Released notes:

- Fix php 8.3 compatibility.

### Salesfire v1.4.14
Released on 2024-08-15
Released notes:

- No changes from v1.4.13.

### Salesfire v1.4.13
Released on 2024-08-14
Released notes:

- Added ability to strip invalid UTF-8 characters.

### Salesfire v1.4.12
Released on 2024-06-11
Released notes:

- Added additional logging to improve debugging.

### Salesfire v1.4.11
Released on 2024-05-20
Released notes:

- Inline script CSP support.

### Salesfire v1.4.10
Released on 2024-04-24
Released notes:

- Fix issue where the generated feed file was being overwritten.

### Salesfire v1.4.9
Released on 2024-04-02
Released notes:

- Add CSP whitelist.

### Salesfire v1.4.8
Released on 2024-04-02
Released notes:

- Fix feed generator stock level calculation.

### Salesfire v1.4.7
Released on 2024-01-31
Released notes:

- Fix bug where configurable product prices don't include tax.

### Salesfire v1.4.6
Released on 2024-01-18
Released notes:

- Fix bug where sale price isn't always included in feed.

### Salesfire v1.4.5
Released on 2023-12-14
Released notes:

- Fix bug where colour isn't always included in feed.

### Salesfire v1.4.4
Released on 2023-12-13
Released notes:

- Added setting to include or exclude tax from Salesfire feed product prices.

### Salesfire v1.4.3
Released on 2023-12-13
Released notes:

- Update feed generator to use tax settings when calculating price.

### Salesfire v1.4.2
Released on 2023-12-11
Released notes:

- Fix PHP 7 compatibility.

### Salesfire v1.4.1
Released on 2023-09-01
Released notes:

- Prevent infinite loop than can occasionally occur during feed generation.
- Fix bug where colour code isn't always included in feed.

### Salesfire v1.4.0
Released on 2023-08-24
Released notes:

- Added log truncating with max size setting.
- Show last 100 log entries in admin.

### Salesfire v1.3.10
Released on 2023-08-04
Released notes:

- Fixed additional attributes not being included in feed.
- Added additional information to feed including generator type and product type.

### Salesfire v1.3.9
Released on 2023-07-26
Released notes:

- Fixed being unable to save Enable option in Single Store mode.

### Salesfire v1.3.8
Released on 2023-07-25
Released notes:

- Added PHP 7.3 support fix.

### Salesfire v1.3.7
Released on 2023-07-07
Released notes:

- Updated the feed generator to use the minimum price rather than the sum price of its associated products.

### Salesfire v1.3.6
Released on 2023-06-28
Released notes:

- Fix bug where the feed generation cron wasn't running.
- Improved product links stability when URL rewrites are missing or have been corrupted.

### Salesfire v1.3.5
Released on 2023-06-20
Released notes:

- Removed trailing comma's in the function calls that were causing feed errors.

### Salesfire v1.3.4
Released on 2023-05-17
Released notes:

- Fix potential bug with single/non-single store retrieving of setting values.

### Salesfire v1.3.3
Released on 2023-04-25
Release notes:

- Added support for PHP 7.1, 7.2, 7.3 and 7.4.
- Formatting.

### Salesfire v1.3.2
Released on 2023-04-14
Release notes:

- Remove UTF8 character stripping

### Salesfire v1.3.1
Released on 2023-03-20
Release notes:

- Fix issues running on Magento 2.4.5-p2 by @ssx in #26

### Salesfire v1.3.0
Released on 2023-03-14
Release notes:

- Add Magento 2 Single Store support.
- Added button to manually run feed.
- Added customisable cron schedule for feed generation.

### Salesfire v1.2.17
Released on 2023-03-14
Release notes:

- Updated strip_tags deprecated argument

### Salesfire v1.2.16
Released on 2022-10-18
Release notes:

- Add parent images to product feed
- Fix php 8 issues

### Salesfire v1.2.15
Released on 2022-08-18
Release notes:

- Fix tracking issues
- Fix php 8 issues

### Salesfire v1.2.14
Released on 2022-01-07
Release notes:

- Replaced Zend module with Magento's native logging module.

### Salesfire v1.2.13
Released on 2021-09-16
Release notes:

- Fix an issue with incorrect product ids being stored on purchases

### Salesfire v1.2.12
Released on 2021-07-23
Release notes:

- Added support for Magento 2.4

### Salesfire v1.2.11
Released on 2021-02-15
Release notes:

- Fix feed product prices

### Salesfire v1.2.10
Released on 2021-01-06
Release notes:

- Fix exception handling
- Fix issue when retrieving brand attribute

### Salesfire v1.2.9
Released on 2020-08-26
Release notes:

- Added CDATA to link attributes

### Salesfire v1.2.8
Released on 2020-07-23
Release notes:

- Fix issue paging products

### Salesfire v1.2.7
Released on 2020-07-15
Release notes:

- Fix issue getting product attributes

### Salesfire v1.2.6
Released on 2020-07-08
Release notes:

- Fix issue getting product images

### Salesfire v1.2.4
Released on 2020-02-10
Release notes:

- Add product image fallback
- Add version to feed
- Fixed stock level


### Salesfire v1.2.3
Released on 2020-02-07
Release notes:

- Fix issue with missing images
- Fix feed generation process issue


### Salesfire v1.2.2
Released on 2020-02-06
Release notes:

- Fix feed attributes issue


### Salesfire v1.2.1
Released on 2019-09-23
Release notes:

- Added salesfire feed


### Salesfire v1.1.2
Released on 2019-06-04
Release notes:

- Resolve issue with product attribute mapping


### Salesfire v1.1.1
Released on 2019-04-11
Release notes:

- Updated Salesfire SDK
- Resolve Magento recommendations


### Salesfire v1.1.0
Released on 2019-04-04
Release notes:

- Added tracking


### Salesfire v1.0.0
Released on 2018-02-07
Release notes:

- Release first version
