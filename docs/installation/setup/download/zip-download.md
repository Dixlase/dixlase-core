# ZIP Download

Download Dixlase as a ZIP archive from the official website or GitHub.

## From GitHub

1. Go to the [Dixlase releases page](https://github.com/Dixlase/dixlase-core/releases)
2. Download `dixlase-core.zip` from the latest release (the same file is also attached as `dixlase-vX.Y.Z.zip`, with `checksums.sha256` to check it against)
3. Extract the archive to your desired location

## From the Official Website

1. Visit the official Dixlase website
2. Navigate to the download page
3. Download the latest version

## After Download

```bash
# Extract and enter the directory (the archive holds one folder, dixlase-vX.Y.Z)
unzip dixlase-core.zip
cd dixlase-v*
```

The release ZIP already contains `vendor/` and the built front-end assets, so neither `composer install` nor `npm run build` is needed.

Then proceed to set up your [development environment](../development/index.md) or [production environment](../production/index.md).
