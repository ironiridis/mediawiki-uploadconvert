<?php
if (!defined('MEDIAWIKI')) exit(1);

/* ***
 * Filter options:
 * 
 * 'mandatory': If conversion fails, upload fails. Users cannot upload a file that
 *              matches this filter and fails conversion. If not specified, an upload
 *              that fails to convert will simply use the original file unmodified.
 * 
 * 'ignore_return_value': We don't care if the command "succeeded", just whether the
 *                        new file exists. If not specified, fails when command result
 *                        is non-zero.
 * 
 * 'allow_raw_args': Defeat a layer of paranoia by providing command elements (prefixed
 *                   with "raw-") that don't have shell escaping applied to them. This
 *                   forces you to quote your own command parameters. If not specified,
 *                   the only replacement values are escaped and quoted for the shell.
 * 
 *
 */

$wgExtensionCredits['UploadConvert'][] = array(
        'path' => __FILE__,
        'name' => 'UploadConvert',
        'author' => 'Chris Harrington (ironiridis)',
        'url' => 'https://github.com/ironiridis/mediawiki-uploadconvert',
        'descriptionmsg' => 'This extension (based in part on UploadPDF) will convert '.
			'an uploaded object using an external utility.',
        'version' => '0.0.0'
);

class extUploadConvertBase {
	static protected $opt = array();
	
	static protected function getOptions()
	{
		return(self::$opt);
	}
	
	static public function filterByExtention($matchext, $newext, $cmd, $opt=array())
	{
		if (!(is_string($matchext) and is_string($newext) and is_string($cmd)))
			throw new Exception('First 3 arguments for filter must be strings');
		
		if (!is_array($opt)) $opt = array($opt);
		
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
		
		// return values don't work consistently with windows apps
		if (strpos(php_uname('s'), 'Windows') !== false)
			$opt[] = 'ignore_return_value';
		
		self::$opt[] = array(
			'matchType'=>'extension',
			'extension'=>$matchext,
			'newextension'=>$newext,
			'command'=>$cmd,
			'options'=>$opt
		);
	}
	
	protected function extUploadConvertExecute($file, $newext, $cmd, &$opt)
	{
		$pi = @pathinfo($file);
		$newfn = $file.'.'.$newext;
		$cmd = str_replace('%from%', escapeshellarg($file), $cmd);
		$cmd = str_replace('%dir%', escapeshellarg($pi['dirname']), $cmd);
		$cmd = str_replace('%to%', escapeshellarg($newfn), $cmd);
		$cmd = str_replace('%toext%', escapeshellarg($newext), $cmd);
		
		if (in_array('allow_raw_args', $opt))
		{
			$cmd = str_replace('%raw-from%', $file, $cmd);
			$cmd = str_replace('%raw-dir%', $pi['dirname'], $cmd);
			$cmd = str_replace('%raw-to%', $newfn, $cmd);
			$cmd = str_replace('%raw-toext%', $newext, $cmd);
		}
		
		$r = 255; // return value from the command
		exec($cmd, $discard = array(), $r); unset($discard);
		if (in_array('ignore_return_value', $opt))
			$r = 0; // fake success
		
		if (!file_exists($newfn)) return false;
		if ($r != 0) // command failed
		{
			@unlink($newfn); // remove new file
			return false;
		}
		
		return true;
	}
}

class extUploadConvertFile extends UploadFromFile {
	/* ** TODO ** */
}

class extUploadConvertStash extends UploadFromStash {
	/* ** TODO ** */
}

class extUploadConvertUrl extends UploadFromUrl {
	/* ** TODO ** */
}

function extUploadConvertIntercept($type, $className)
{
	$n = 'extUploadConvert'.$type;
	if (class_exists($n)) $className = $n;

	return true;
}

$wgHooks['UploadCreateFromRequest'][]='extUploadConvertIntercept';
