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

/* just kidding; this extension does jack shit at the moment. */