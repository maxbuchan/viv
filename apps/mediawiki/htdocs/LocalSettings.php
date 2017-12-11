<?php
# This file was automatically generated by the MediaWiki 1.17.0
# installer. If you make manual changes, please keep track in case you
# need to recreate them later.
#
# See includes/DefaultSettings.php for all configurable settings
# and their default values, but don't forget to make changes in _this_
# file, not there.
#
# Further documentation for configuration settings may be found at:
# http://www.mediawiki.org/wiki/Manual:Configuration_settings

# Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

## Uncomment this to disable output compression
# $wgDisableOutputCompression = true;

$wgSitename      = "BerkeleyText";
$wgMetaNamespace = "BerkeleyText";

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
## For more information on customizing the URLs please see:
## http://www.mediawiki.org/wiki/Manual:Short_URL
$wgScriptPath = "";
$wgScriptExtension  = ".php";

## The relative URL path to the skins directory
$wgStylePath        = "$wgScriptPath/skins";

## The relative URL path to the logo.  Make sure you change this from the default,
## or else you'll overwrite your logo when you upgrade!
$wgLogo             = "https://78.media.tumblr.com/avatar_5af5054f0107_128.png";

## UPO means: this is also a user preference option

$wgEnableEmail      = true;
$wgEnableUserEmail  = true; # UPO

$wgEmergencyContact = "buchan.maxwell@gmail.com";
$wgPasswordSender   = "buchan.maxwell@gmail.com";

$wgEnotifUserTalk      = false; # UPO
$wgEnotifWatchlist     = false; # UPO
$wgEmailAuthentication = true;

## Database settings
$wgDBtype           = "mysql";
$wgDBserver         = "localhost:3306";
$wgDBname           = "bitnami_mediawiki";
$wgDBuser           = "bn_mediawiki";
$wgDBpassword       = "294d66de2f";

# MySQL specific settings
$wgDBprefix         = "";

# MySQL table options to use during installation or update
$wgDBTableOptions   = "ENGINE=InnoDB, DEFAULT CHARSET=utf8";

# Experimental charset support for MySQL 4.1/5.0.
$wgDBmysql5 = false;

## Shared memory settings
$wgMainCacheType    = CACHE_NONE;
$wgMemCachedServers = array();

# Image Converter
$wgSVGConverter = 'ImageMagick';

# Image converter path
$wgSVGConverterPath = '/opt/bitnami/common/bin';

## To enable image uploads, make sure the 'images' directory
## is writable, then set this to true:
$wgEnableUploads  = true;
$wgUseImageMagick = true;
$wgImageMagickConvertCommand = "/opt/bitnami/common/bin/convert";

# Path to jpegtran utility
$wgJpegTran = '/opt/bitnami/common/bin/';

# Path to tidy utility binary
$wgTidyBin = '/opt/bitnami/common/bin/';

# InstantCommons allows wiki to use images from http://commons.wikimedia.org
$wgUseInstantCommons  = false;

## If you use ImageMagick (or any other shell command) on a
## Linux server, this will need to be set to the name of an
## available UTF-8 locale
$wgShellLocale = "en_US.utf8";

## If you want to use image uploads under safe mode,
## create the directories images/archive, images/thumb and
## images/temp, and make them all writable. Then uncomment
## this, if it's not already uncommented:
#$wgHashedUploadDirectory = false;

## If you have the appropriate support software installed
## you can enable inline LaTeX equations:
$wgUseTeX           = true;

## Set $wgCacheDirectory to a writable directory on the web server
## to make your wiki go slightly faster. The directory should not
## be publically accessible from the web.
#$wgCacheDirectory = "$IP/cache";

# Site language code, should be one of ./languages/Language(.*).php
$wgLanguageCode = "en";

$wgSecretKey = "6dd75501a912aea8049742db66675c708e3557537a66dff30fb972da114802fd";

# Site upgrade key. Must be set to a string (default provided) to turn on the
# web installer while LocalSettings.php is in place
$wgUpgradeKey = "cbf56817e48b624f";

## Default skin: you can change the default skin. Use the internal symbolic
## names, ie 'standard', 'nostalgia', 'cologneblue', 'monobook', 'vector':
$wgDefaultSkin = "athena";

## For attaching licensing metadata to pages, and displaying an
## appropriate copyright notice / icon. GNU Free Documentation
## License and Creative Commons licenses are supported so far.
#$wgEnableCreativeCommonsRdf = true;
$wgRightsPage = ""; # Set to the title of a wiki page that describes your license/copyright
$wgRightsUrl  = "";
$wgRightsText = "";
$wgRightsIcon = "";
# $wgRightsCode = ""; # Not yet used

# Path to the GNU diff3 utility. Used for conflict resolution.
$wgDiff3 = "/usr/bin/diff3";



# Query string length limit for ResourceLoader. You should only set this if
# your web server has a query string length limit (then set it to that limit),
# or if you have suhosin.get.max_value_length set in php.ini (then set it to
# that value)
$wgResourceLoaderMaxQueryLength = -1;


# End of automatically generated settings.
# Add more configuration options below.

$wgArticlePath = "/$1";
$wgUsePathInfo = true;
$wgPhpCli = "/opt/bitnami/php/bin/php";

require_once "$IP/extensions/Scribunto/Scribunto.php";
$wgScribuntoDefaultEngine = 'luastandalone';

wfLoadSkin( 'Athena' );
wfLoadSkin( 'Timeless' );

wfLoadExtension( 'PDFEmbed' );
// Default width for the PDF object container.
$wgPdfEmbed['width'] = 800;

// Default height for the PDF object container.
$wgPdfEmbed['height'] = 1090;

wfLoadExtension( 'EmbedVideo' );

wfLoadExtension( 'Math' );

wfLoadExtension( 'SyntaxHighlight_GeSHi' );

wfLoadExtension( 'ParserFunctions' );

wfLoadExtension( 'AntiSpoof' );

wfLoadExtension( 'AbuseFilter' );

wfLoadExtension( 'VisualEditor' );
$wgDefaultUserOptions['visualeditor-enable'] = 1;
$wgHiddenPrefs[] = 'visualeditor-enable';
$wgVirtualRestConfig['modules']['parsoid'] = array(
'url' => 'http://berkeleytext.org',
'domain' => 'berkeleytext.org'
);

require_once "$IP/extensions/FancyBoxThumbs/FancyBoxThumbs.php";

//wfLoadExtension( 'MobileFrontend' );
//$wgMFAutodetectMobileView = false;
