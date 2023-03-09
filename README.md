# DataFeedWatch Connector for WooCommerce 2.8.1 or newer

### Releasing new version
- Create new tag using following pattern for name: `release-YYYYMMDD{version}`.

  Version should be a double digit number, starting from `01` every day.

  Example:

  `release-2023030901`, `release-2023030902`, `release-2023031001`

- Tag command
  ```git tag -a release-2023030902```

  As comment use: Release 2023030902
- Add new release using previously created tag (you can use GitHub UI)
- After creating new release:
  - download "Source code" zip archive
  - change the archive name to `dfwconnector-woocommerce.zip`
  - change the top level directory name in the archive to `dfwconnector-woocommerce`
  - upload the `dfwconnector-woocommerce.zip` archive to the release