<?php
if (!defined('MEDIAWIKI')) exit(1);

$wgExtensionCredits['UploadConvert'][] = array(
        'path' => __FILE__,
        'name' => 'UploadConvert',
        'author' => 'Chris Harrington (ironiridis)',
        'url' => 'https://github.com/ironiridis/mediawiki-uploadconvert',
        'descriptionmsg' => 'This extension (based in part on UploadPDF) will convert an
uploaded object using an external utility.',
        'version' => '0.0.0'
);

function extUploadConvertIntercept()
{
	
}

function extUploadConvertByExtention($matchext, $newext, $cmd)
{
	if (!(is_string($matchext) and is_string($newext) and is_string($cmd)))
		throw new Exception('All arguments for filter must be strings');
	
	while(strlen($matchext) > 0 and $matchext[0] == '.')
		$matchext = substr($matchext,1); // strip leading dots
	if ($matchext == '') throw new Exception('Invalid matching file extention');
	
	if ($newext == '') // keep file extension despite being converted
		$newext = $matchext;
	else
	{
		while(strlen($newext) > 0 and $newext[0] == '.')
			$newext = substr($newext,1); // strip leading dots
		if ($newext == '') throw new Exception('Invalid new file extention');
	}	
	
	return(array(
		'matchType'=>'extension',
		'extension'=>$matchext,
		'newextension'=>$newext,
		'command'=>$cmd
	));
}

function extUploadConvertExecute($file, $newext, $cmd)
{
	
}

$extUploadConvertSettings = array();

$wgHooks['UploadCreateFromRequest'][]=array(
	'extUploadConvertIntercept',
	$extUploadConvertSettings
);

