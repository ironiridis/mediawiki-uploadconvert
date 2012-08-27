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
 * 'strict': Whatever matching logic is being applied will be done in a case-sensitive
 *           manner. If not specified, all string comparisons where a filter is being
 *           evaluated are done in a case-insensitive way.
 *
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
        'version' => '0.1.1-beta'
);

class extUploadConvert {
	static protected $filters = array();
	
	static public function getFilter($idx)
	{
		if (!isset(self::$filters[$idx])) return false;
		return(self::$filters[$idx]);
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
		
		self::$filters[] = array(
			'matchType'=>'extension',
			'extension'=>$matchext,
			'newextension'=>$newext,
			'command'=>$cmd,
			'options'=>$opt
		);
	}
	
	static public function convertUsingFilter($file, $idx)
	{
		$cmd = self::$filters[$idx]['command'];
		$opt = self::$filters[$idx]['options'];
		$pi = @pathinfo($file);
		$newfn = tempnam($pi['dirname'], 'eUC');
		unlink($newfn); // tempnam generates the file, which may make some commands fail
		
		$cmd = str_replace('%from%', escapeshellarg($file), $cmd);
		$cmd = str_replace('%dir%', escapeshellarg($pi['dirname']), $cmd);
		$cmd = str_replace('%to%', escapeshellarg($newfn), $cmd);
		
		if (in_array('allow_raw_args', $opt))
		{
			$cmd = str_replace('%raw-from%', $file, $cmd);
			$cmd = str_replace('%raw-dir%', $pi['dirname'], $cmd);
			$cmd = str_replace('%raw-to%', $newfn, $cmd);
		}
		
		$r = 255; // return value from the command
		$discard = array();
		exec($cmd, $discard, $r);
		file_put_contents('/tmp/convertcmdresult', serialize($discard));
		unset($discard);
		/* yeah, you can't just replace $discard with null. */
		if (in_array('ignore_return_value', $opt))
			$r = 0; // fake success
		
		if (!file_exists($newfn)) return false;
		if ($r != 0) // command failed
		{
			@unlink($newfn); // remove new file
			return false;
		}
		
		rename($newfn, $file);
		return true;
	}
	
	static protected function matchExtensionFilter($filename, $idx)
	{
		$pi = pathinfo($filename);
		if (!isset($pi['extension'])) return false;
		
		if (in_array('strict', self::$filters[$idx]['options']))
		{
			return(strcmp($pi['extension'],
				self::$filters[$idx]['extension'])==0);
		}
		else
		{
			return(strcasecmp($pi['extension'],
				self::$filters[$idx]['extension'])==0);
		}
	}

	static protected function matchMimeTypeFilter($mimetype, $idx)
	{
		if (function_exists('mime_content_type') === false and
			function_exists('finfo_file') === false)
			throw new Exception('Neither mime_content_type() nor finfo_file() are available');
		
		if (in_array('strict', self::$filters[$idx]['options']))
		{
			return(strcmp($mimetype,self::$filters[$idx]['mime'])==0);
		}
		else
		{
			// FUTURE: Might want to also permit wildcard matching
			return(strcasecmp($mimetype,self::$filters[$idx]['mime'])==0);
		}
	}
	
	static public function evaluateFile($file='', $originalName='', $mime='', $size=0)
	{
		foreach(self::$filters as $idx=>$filter)
			switch($filter['matchType'])
			{
				case 'extension':
					if(self::matchExtensionFilter($originalName, $idx)) return($idx);
					else break;
				case 'mimetype':
					if(self::matchMimeTypeFilter($mime, $idx)) return($idx);
					else break;
			}
		
		return false; // failed to match against any filters
	}
	
	static public function hook($type, &$className)
	{
		$n = 'extUploadConvert'.$type;
		if (class_exists($n)) $className = $n;
	
		return true;
	}
	
	static public function getMimeType($file)
	{
		if (function_exists('mime_content_type'))
			return(mime_content_type($file));
		else if (function_exists('finfo_file'))
		{
			$fi = finfo_open(FILEINFO_MIME);
			if ($fi === false) return false;
			$mime = finfo_file($fi, $file, FILEINFO_MIMETYPE);
			finfo_close($fi);
			return($mime);
		}
		else return false;
	}
}

class extUploadConvertFile extends UploadFromFile {
	public function initializeFromRequest(&$request)
	{
		$upload = $request->getUpload('wpUploadFile');
		if ($upload->exists())
		{
			// get info we know and find a matching filter
			$f = extUploadConvert::evaluateFile(
				$upload->getTempName(),
				$upload->getName(),
				extUploadConvert::getMimeType($upload->getTempName()),
				$upload->getSize());
			
			// no matching filter? handle it normally.
			if ($f === false)
				return(parent::initializeFromRequest($request));
			
			$filter = extUploadConvert::getFilter($f);
			
			// execute filter $f
			$r = extUploadConvert::convertUsingFilter($upload->getTempName(), $f);
			
			// if filter failed, and it was mandatory, abort upload
			if ($r === false and in_array('mandatory', $filter['options']))
				return false;
			
			if ($r === true) // filter succeeded
			{
				if ($filter['newextension'] != '')
					$newfn = $upload->getName().'.'.$filter['newextension'];
				else
					$newfn = $upload->getName();
				
				$this->initializePathInfo(
					$newfn,
					$upload->getTempName(),
					filesize($upload->getTempName()),
					false);
				
				$request->setVal('wpDestFile', $newfn);
			}
		}
		
		return(parent::initializeFromRequest($request));
	}
}

class extUploadConvertStash extends UploadFromStash {
	/* ** TODO ** */
}

class extUploadConvertUrl extends UploadFromUrl {
	/* ** TODO ** */
}

$wgHooks['UploadCreateFromRequest'][]='extUploadConvert::hook';
