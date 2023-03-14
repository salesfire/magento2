# Submitting to Magento 2

## Ensure version number has been bumped in the following:

- Ensure composer.json is bumped.
- Ensure the Helper/Data@getVersion method is bumped.
- Any changed file should have version comment bumped.

## Update CHANGELOG

- Update the CHANGELOG.md with release notes.

These will be reused within submission. Be conscious these will be public.

## Push to Github, merge and tag

- Push changes to Github.
- Open PR, review and merge.
- Add a tag for merge commit on master in the format vX.X.X.

## Create a zip

Run the following command in the folder above the module:

`zip -r salesfire_magento2-1.2.17.zip magento2/ -x 'magento2/.git/*' -x 'magento2/.DS_Store'`


## Login to Magento

- Go to developer.magento.com.
- Login with 1Password details. User: platforms@salesfire.com.
- Go to Extensions.
- Go to Salesfire.
- Click Submit a New Version.
- Enter version name.
- Upload the zip folder created in previous section.
- Copy release notes.
- Submit.
