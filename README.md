mediawiki-uploadconvert
=======================

Converts a file at upload time to another media format

Example usage (LocalSettings.php):

	require_once("$IP/extensions/UploadConvert/UploadConvert.php");
	extUploadConvertBase::filterByExtention('bmp','png','/usr/bin/convert %from% %to%','mandatory');
	extUploadConvertBase::filterByExtention('tiff','png','/usr/bin/convert %from% %to%');
