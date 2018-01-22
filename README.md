# DADS

Digital Archive Delivery Service - A proof of concept project for the delivery of archive items via [Filesender](http://filesender.org/).

## Getting Started

It's recommended that DADS be installed in a directory under your web root, separate to the OHRM/HDMS archive of interest, for code reuse and reducing redundancy.

### Prerequisites

PHP 5 and upwards (developed with PHP 5.4)

### Installing

Rename `config.php.dist` to `config.php`, and edit, as required.

config.php constants:
* FILESENDER_URL - The REST URL for your selected Filesender instance.
* FILESENDER_USERID - Username for your selected Filesender instance.
* FILESENDER_APIKEY - API key for your selected Filesender instance.
* ACCESS_CONDITIONS - Mandated access conditions for the archive.
* ASSET_BASE - Location of the archive, relative to the root of your web server.
* ASSET_BASESUFFIX - Directory under the selected archive item to deliver. For example, 'large' will only deliver large images, and no other assets.
* OHRMLIST - An array of allowed ASSET directories

## Authors

* **Peter Tonoli** - [The University of Melbourne, eScholarship Research Centre](https://esrc.unimelb.edu.au) *Initial work*

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* Michael D'Silva (@madisim), Aarnet, for assisting with the Filesender API.
* The  [Filesender Project](http://filesender.org/) for the API code, and the underlying Filesender platform.
