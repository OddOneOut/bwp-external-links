<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

if (@ini_get('pcre.backtrack_limit') <= 750000)
	@ini_set('pcre.backtrack_limit', 750000);
if (@ini_get('pcre.recursion_limit') <= 250000)
	@ini_set('pcre.recursion_limit', 250000);

class BWP_EXTERNAL_LINKS_OB {

	function ob_start()
	{
		static $done = false;

		if ($done)
			return;

		ob_start(array($this, 'ob_filter'));
		add_action('wp_footer', array($this, 'ob_flush'), 10000);

		$done = true;
	}
	
	function ob_filter($text)
	{
		$text = bwp_external_links($text);
		return $text;
	}
	
	function ob_flush()
	{
		static $done = true;
		
		if ($done)
			return;

		ob_end_flush();

		$done = true;
	}
	
}
?>