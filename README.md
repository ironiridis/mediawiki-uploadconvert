mediawiki-uploadconvert
=======================

Converts a file at upload time to another media format

Example usage (LocalSettings.php):

	require_once("$IP/extensions/UploadConvert/UploadConvert.php");
	extUploadConvert::filterByExtention('bmp','png','/usr/bin/convert bmp:%from% png:%to%','mandatory');
	extUploadConvert::filterByExtention('tiff','png','/usr/bin/convert tiff:%from% png:%to%');


Notes for the intrepid user
===========================

* Even if you are converting file types, MediaWiki will still refuse an upload if it doesn't have an acceptable extension. That means that if you plan to allow your users to upload bmp files, you must add 'bmp' to $wgFileExtensions in LocalSettings.php.
* ImageMagick will convert multi-layer PSDs and animated GIFs (and probably other files with more than one "image") into several output files by default. If you want your users to be able to upload PSD files, you will need to run `convert` with additional options to only output **one file**. You will get a weird internal error otherwise. Patches welcome!

-------

**This extension is incomplete, poorly tested, and wildly insecure. You've been warned.**

-------

