<?php
/**
 * Copyright (c) 2013 Khang Minh <betterwp.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Template function to process links for additional contents
 */
function bwp_external_links($content = '')
{
	global $bwp_ext;

	return $bwp_ext->parse_links($content);
}

if (!class_exists('BWP_FRAMEWORK'))
	require_once(dirname(__FILE__) . '/class-bwp-framework.php');
	
class BWP_EXTERNAL_LINKS extends BWP_FRAMEWORK {

	var $ext_relation = '';
	var $anonymous_prefix = '';

	/**
	 * Constructor
	 */	
	function __construct($version = '1.1.2')
	{
		// Plugin's title
		$this->plugin_title = 'BetterWP External Links';
		// Plugin's version
		$this->set_version($version);
		// Basic version checking
		if (!$this->check_required_versions())
			return;
		
		// Default options
		$options = array(
			'enable_page_process' => '',
			'enable_post_contents' => 'yes',
			'enable_comment_text' => 'yes',
			'enable_widget_text' => 'yes',
			'enable_css' => 'yes',
			'enable_rel_external' => 'yes',
			'enable_rel_nofollow' => 'yes',
			'input_external_rel' => '',
			'input_local_rel' => '',
			'input_external_class' => 'ext-link',
			'input_local_class' => 'local-link',
			'input_local_sub' => '',
			'input_forced_local'  => '',
			'input_forbidden' => '',
			'input_forbidden_replace' => '#',
			'input_top_level_domain' => '',
			'input_custom_prefix' => '',
			'select_sub_method' => 'all',
			'select_anonymous_prefix' => 'none',
			'select_target' => '_blank',
			'select_exe_type' => 'onclick'
		);
		
		$this->build_properties('BWP_EXT', 'bwp-ext', $options, 'BetterWP External Links', dirname(dirname(__FILE__)) . '/bwp-external-links.php', 'http://betterwp.net/wordpress-plugins/bwp-external-links/', false);
		
		$this->add_option_key('BWP_EXT_OPTION_GENERAL', 'bwp_ext_general', __('Better WordPress External Links Settings', 'bwp-ext'));

		$this->init();

	}

	function print_scripts()
	{
?>
<script type="text/javascript">
	jQuery(document).ready(function(){	
		jQuery('a[rel~="external"]').attr('target','<?php echo $this->options['select_target']; ?>');
	});
</script>
<?php	
	}

	function add_hooks()
	{
		// No links processing for admin page - @since 1.1.0
		if (is_admin())
			return;
		if ('yes' != $this->options['enable_page_process'])
		{
			if ('yes' == $this->options['enable_post_contents'])
				add_filter('the_content', array($this, 'parse_links'));
			if ('yes' == $this->options['enable_comment_text'])
				add_filter('comment_text', array($this, 'parse_links'));
			// @since 1.1.0
			if ('yes' == $this->options['enable_widget_text'])
				add_filter('widget_text', array($this, 'parse_links'));
		}
		else
		{
			require_once(dirname(__FILE__) . '/class-anchor-utils.php');
			$bwp_ext_ob = new BWP_EXTERNAL_LINKS_OB;
			add_action('wp_head', array($bwp_ext_ob, 'ob_start'), 10000);
		}
		if ('jquery' == $this->options['select_exe_type'])
		{
			wp_enqueue_style('jquery');
			add_action('wp_head', array($this, 'print_scripts'), 9);
		}
	}
	
	function init_properties()
	{
		$this->options['input_top_level_domain'] = !empty($this->options['input_top_level_domain']) ? str_replace('.', '\\.', $this->options['input_top_level_domain']) : '[a-z0-9-]+\.(?:aero|biz|com|coop|info|jobs|museum|name|net|org|pro|travel|gov|edu|mil|int)';			
		$rel = '';
		$rel .= ('yes' == $this->options['enable_rel_external']) ? 'external ' : '';
		$rel .= ('yes' == $this->options['enable_rel_nofollow']) ? 'nofollow ' : '';
		$rel = trim($rel);
		$rel = (!empty($rel)) ? $rel . $this->options['input_external_rel'] : $this->options['input_external_rel'];
		$this->ext_relation = $rel;
		
		if ('custom' == $this->options['select_anonymous_prefix'])
			$this->anonymous_prefix = $this->options['input_custom_prefix'];
		else if ('none' != $this->options['select_anonymous_prefix'])
			$this->anonymous_prefix = $this->options['select_anonymous_prefix'];
		
		if (in_array($this->options['select_sub_method'], array('all', 'none')))
			$this->options['input_local_sub'] = $this->options['select_sub_method'];
	}

	function enqueue_media()
	{
		if ('yes' == $this->options['enable_css'])
			wp_enqueue_style('bwp-ext', BWP_EXT_CSS . '/bwp-external-links.css', false, $this->get_version());
	}

	/**
	 * Build the Menus
	 */
	function build_menus()
	{
		add_options_page(__('Better WordPress External Links', 'bwp-ext'), 'BWP External Links', BWP_EXT_CAPABILITY, BWP_EXT_OPTION_GENERAL, array($this, 'build_option_pages'));		
	}

	/**
	 * Build the option pages
	 *
	 * Utilizes BWP Option Page Builder (@see BWP_OPTION_PAGE)
	 */	
	function build_option_pages()
	{
		if (!current_user_can(BWP_EXT_CAPABILITY))
			wp_die(__('You do not have sufficient permissions to access this page.'));

		// Init the class
		$page = $_GET['page'];		
		$bwp_option_page = new BWP_OPTION_PAGE($page);
		
		$options = array();
		
if (!empty($page))
{	
	if ($page == BWP_EXT_OPTION_GENERAL)
	{
		$form = array(
			'items'	=> array('heading', 'section', 'checkbox', 'checkbox', 'heading', 'checkbox', 'checkbox', 'input', 'select', 'textarea', 'textarea', 'textarea', 'input', 'heading', 'input', 'input', 'select', 'select'),
			'item_labels' => array(
				__('General Options', 'bwp-ext'),
				__('Process links inside', 'bwp-ext'),
				__('Process page-wide links?', 'bwp-ext'),
				__('Use CSS provided by this plugin?', 'bwp-ext'),
				__('Processing Options', 'bwp-ext'),
				__('Add the attribute', 'bwp-ext'),				
				__('Add the attribute', 'bwp-ext'),
				__('A custom rel attribute', 'bwp-ext'),
				__('Ignore links pointing to', 'bwp-ext'),
				__('If you choose to ignore some subdomains, input them here (one per line)', 'bwp-ext'),
				/*__('Ignore links pointing to URL with', 'bwp-ext'),*/
				__('Forced local domains (one per line)', 'bwp-ext'),
				__('Forbidden domains (one per line) &mdash; useful for comment text', 'bwp-ext'),
				__('URLs pointing to forbidden domains will be replaced with', 'bwp-ext'),
				__('Displaying &amp; Interacting Options', 'bwp-ext'),
				__('External links&#8217; CSS class', 'bwp-ext'),
				__('Local links&#8217; CSS class', 'bwp-ext'),
				__('Open', 'bwp-ext'),
				__('Prefix external links with', 'bwp-ext')
			),
			'item_names'	=> array('h1', 'sec1', 'cb7', 'cb3', 'h2', 'cb4', 'cb5', 'input_external_rel', 'select_sub_method', 'input_local_sub', 'input_forced_local', 'input_forbidden', 'input_forbidden_replace', 'h3', 'input_external_class', 'input_local_class', 'select_target', 'select_anonymous_prefix'),
			'heading' => array(
				'h1' => '',
				'h2' => sprintf(__('<em>All input fields are optional. To say the truth, configuring this plugin can be quite confusing (I\'m terribly sorry!) I have written a rather detailed guide that might help you out, please take a <a href="%s#usage">look at it</a> if you are interested.</em>', 'bwp-ext'), $this->plugin_url),
				'h3' => __('<em>Customize how your links are shown.</em>', 'bwp-ext')
			),
			'sec1' => array(
					array('checkbox', 'name' => 'cb1'),
					array('checkbox', 'name' => 'cb2'),
					array('checkbox', 'name' => 'cb6')
			),
			'select' => array(
				'select_sub_method' => array(
					__('all subdomains', 'bwp-ext') => 'all',
					__('no subdomains', 'bwp-ext') => 'none',
					__('some subdomains', 'bwp-ext') => 'some'
				),
				'select_exe_type' => array(
					__('using the "onclick" attribute', 'bwp-ext') => 'onclick',
					__('using the "target" attribute', 'bwp-ext') => 'target',
					__('using jQuery', 'bwp-ext') => 'jquery'
				),
				'select_target' => array(
					__('one new window for each external link (_blank)', 'bwp-ext') => '_blank',
					__('just one new window for all external links (_new)', 'bwp-ext') => '_new',
					__('no new window at all for external links', 'bwp-ext') => '_none'
				),
				'select_anonymous_prefix' => array(
					__('nothing', 'bwp-ext') => 'none',
					'http://anonym.to?' => 'http://anonym.to?',
					__('a custom URL', 'bwp-ext') => 'custom'
				)
			),
			'checkbox'	=> array(
				'cb1' => array(__('post contents, i.e. hook to <code>the_content()</code>', 'bwp-ext') => 'enable_post_contents'),
				'cb2' => array(__('comment text, i.e. hook to <code>comment_text()</code>', 'bwp-ext') => 'enable_comment_text'),
				'cb6' => array(__('text widgets, i.e. hook to <code>widget_text()</code>', 'bwp-ext') => 'enable_widget_text'),
				'cb7' => array(__('This plugin will find and process all links on any given page. This option will override what you check above. This is an advanced feature and should be used only for sites that do not have too much contents.', 'bwp-ext') => 'enable_page_process'),
				'cb3' => array(__('a very simple CSS provided so that you can see the "external" effect :). It is recommended that you disable this and use your own rules.', 'bwp-ext') => 'enable_css'),
				'cb4' => array(__('<code>rel="external"</code> to external links?', 'bwp-ext') => 'enable_rel_external'),
				'cb5' => array(__('<code>rel="nofollow"</code> to external links?', 'bwp-ext') => 'enable_rel_nofollow')
			),
			'input'	=> array(
				'input_external_rel' => array('size' => 50, 'label' => __('a simple string describing the relation.', 'bwp-ext')),
				/*'input_top_level_domain' => array('size' => 50, 'label' => __('as its top domain. This means you can make links pointing from <code>http://yourdomain.com</code> to <code>http://yourdomain.co.jp</code> become local. In such case, just input <code>yourdomain.com</code>.', 'bwp-ext')),*/
				'input_forbidden_replace' => array('size' => 50, 'label' => __('This can be <code>#top</code>, <code>http://www.google.com</code> or anything else suitable for the <code>href</code> attribute.', 'bwp-ext')),
				'input_external_class' => array('size' => 50),
				'input_local_class' => array('size' => 50),
				'input_custom_prefix' => array('pre' => __(' <em>and the custom URL (if chosen) is</em> ', 'bwp-ext'), 'size' => 30, 'label' => __('(for example, prepend external links with something like <code>http://yourdomain.com/out.php?</code>)', 'bwp-ext'))
			),
			'textarea' => array(
				'input_local_sub' => array('cols' => 40, 'rows' => 3),
				'input_forced_local' => array('cols' => 40, 'rows' => 3),
				'input_forbidden' => array('cols' => 40, 'rows' => 3)
			),
			'inline_fields' => array(
				'select_target' => array('select_exe_type' => 'select'),
				'select_anonymous_prefix' => array('input_custom_prefix' => 'input')
			)
		);
		
		// Get the default options
		$options = $bwp_option_page->get_options(array('enable_page_process', 'enable_post_contents', 'enable_comment_text', 'enable_widget_text', 'enable_css', 'enable_rel_external', 'enable_rel_nofollow', 'input_external_rel', 'input_external_class', 'input_local_class', 'input_local_sub', 'input_forced_local', 'input_forbidden', 'input_forbidden_replace', 'input_custom_prefix', 'select_sub_method', 'select_anonymous_prefix', 'select_target', 'select_exe_type'), $this->options_default);

		// Get option from the database
		$options = $bwp_option_page->get_db_options($page, $options);
		$option_formats = array();
	}
}

		// Get option from user input
		if (isset($_POST['submit_' . $bwp_option_page->get_form_name()]) && isset($options) && is_array($options))
		{
			check_admin_referer($page);
			foreach ($options as $key => &$option)
			{
				if (isset($_POST[$key]))
					$bwp_option_page->format_field($key, $option_formats);
				if (isset($option_ignore) && in_array($key, $option_ignore)) {}
				else if (!isset($_POST[$key]))
					$option = '';
				else if (isset($option_formats[$key]) && 0 == $_POST[$key] && 'int' == $option_formats[$key])
					$option = 0;
				else if (isset($option_formats[$key]) && empty($_POST[$key]) && 'int' == $option_formats[$key])
					$option = $this->options_default[$key];
				else if (!empty($_POST[$key])) // should add more validation here though
					$option = trim(stripslashes($_POST[$key]));
				else
					$option = '';
			}
			update_option($page, $options);
		}

		// Assign the form and option array		
		$bwp_option_page->init($form, $options, $this->form_tabs);

		// Build the option page	
		echo $bwp_option_page->generate_html_form();

	}

	/**
	 * Decodes all HTML entities. 
	 *
	 * The html_entity_decode() function doesn't decode numerical entities,
	 * and the htmlspecialchars_decode() function only decodes the most common form for entities.
	 *
	 * @copyright (c) 2007-2010 Ken F. Innes IV
	 */
	function decode_entities($text)
	{
		$text = html_entity_decode($text, ENT_QUOTES, 'ISO-8859-1'); 		// UTF-8 does not work!
		$text = preg_replace('/&#(\d+);/me', 'chr($1)', $text); 			// Decimal notation
		$text = preg_replace('/&#x([a-f0-9]+);/mei', 'chr(0x$1)', $text);	// HEX notation
		return($text);
	}

	function parse_options($option = '', $preg = true)
	{
		if (empty($this->options[$option]))
			return '';
		if ('all' == $option || 'none' == $option)
			return $option;

		$items = explode("\n", $this->options[$option]);
		$items = array_map('trim', $items);
		if (false == $preg)
			return $items;
		$preg_string = '';
		foreach ($items as $item)
		{
			$item = str_replace('.', '\\.', $item);
			$preg_string .= $item . '|';
		}

		return rtrim($preg_string, '|');
	}

	/**
	 * Removes subdomains from a URL. 
	 *
	 * If no subdomains are provided as an input parameter, all subdomains will be removed.
	 *
	 * @copyright (c) 2007-2010 Ken F. Innes IV (with some modifications)
	 */
	function remove_subdomains($url, $remove = 'all')
	{
		$stripped_url = $url;
		if ('none' != $remove && strpos($url, '//') !== false)
		{
			$url_parts = @parse_url($url);
			if ('all' == $remove)
			{
				// Simple check for TLDs - @since 1.1.0
				$slashed_url = (empty($url_parts['path']) && empty($url_parts['query'])) ? trailingslashit($url) : $url;
				$stripped_url = $slashed_url;
				$country_tld = 'ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bl|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mf|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw';
				if (!preg_match('#^(http|https)://([^\.^/]+)\.([^\.^/]+)\.(' . $country_tld . ')/#i', $slashed_url))
				{
					$stripped_url = preg_replace('#^(http|https)://(?:.*)\.([^\.^/]+)\.([^\.^/]+)\.(' . $country_tld . ')/#i', '$1://$2.$3.$4/', $slashed_url);
					// Last try
					if ($slashed_url == $stripped_url)
						$stripped_url = preg_replace('#^(http|https)://(?:.*)\.([^\.^/]+)\.([^\.^/]+)#i', '$1://$2.$3', $url);
				}
			}
			else if ('all' != $remove)
			// Always include 'www' sub-domain - @since 1.1.0
				$stripped_url = preg_replace('#^(http|https)://(?:' . $remove . '|www)\.([^/]+)#i', '$1://$2', $url);
			else if (!empty($url_parts['host']) && 'localhost' == substr($url_parts['host'], -9)) 
			// Domain could have a port number, but it's too rare a case with localhost
				$stripped_url = preg_replace('#^(http|https)://[^/]+\.localhost#i', '$1://localhost', $url);
		}
		return $stripped_url;
	}

	/**
	 * Determine if the URL contains a domain.
	 *
	 * @copyright (c) 2007-2010 Ken F. Innes IV
	 */
	function match_domain($url, $domains, $remove = 'all')
	{
		$domains = (array) $domains;
		foreach ($domains as $domain)
		{
			$domain = (strpos($domain, 'http') === 0) ? $domain : 'http://' . $domain;
			$domain = $this->remove_subdomains($domain, $remove);
			if (strpos($url, $domain) === 0)
				return true;
		}
		return false;
	}

	/**
	 * Insert an attribute into an HTML tag.
	 *
	 * @copyright (c) 2007-2010 Ken F. Innes IV
	 */
	function insert_attribute($attr_name, $new_attr, $html_tag, $overwrite = false)
	{
		$javascript	= (strpos($attr_name, 'on') === 0);	// onclick, onmouseup, onload, etc.
		$old_attr	= preg_replace('/^.*' . $attr_name . '="([^"]*)".*$/iu', '$1', $html_tag);
		$is_attr	= !($old_attr == $html_tag); // Does the attribute already exist?
		$old_attr	= ($is_attr) ? $old_attr : '';

		if ($javascript)
		{
			if ($is_attr && !$overwrite)
			{
				$old_attr = ($old_attr && ($last_char = substr(trim($old_attr), -1)) && $last_char != '}' && $last_char != ';') ? $old_attr . ';' : $old_attr; // Ensure we can add code after any existing code
				$new_attr = $old_attr . $new_attr;
			}
			$overwrite = true;
		}

		if ($overwrite && is_string($overwrite))
		{
			if (strpos(' ' . $overwrite . ' ', ' ' . $old_attr . ' ') !== false) 
				// Overwrite the specified value if it exists, otherwise just append the value.
				$new_attr = trim(str_replace(' '  . $overwrite . ' ', ' ' . $new_attr . ' ', ' '  . $old_attr . ' '));
			else
				$overwrite = false;
		}
		
		if (!$overwrite) // Append the new one if it's not already there.
			$new_attr = strpos(' ' . $old_attr . ' ', ' ' . $new_attr . ' ') === false ? trim($old_attr . ' ' . $new_attr) : $old_attr;

		$html_tag = $is_attr ? str_ireplace("$attr_name=\"$old_attr\"", "$attr_name=\"$new_attr\"", $html_tag) : str_ireplace('>', " $attr_name=\"$new_attr\">", $html_tag);
		
		return $html_tag;
	}

	/**
	 * Determines if a URL is local or external. 
	 *
	 * @copyright (c) 2007-2010 Ken F. Innes IV
	 */
	function is_local($url, $home)
	{
		$url = strtolower($url);
		// Treat http and https as the same scheme
		$home		= (strpos($home, 'https://') === 0) ? ('http' . substr($home, 5)) : $home;
		$url 		= (strpos($url, 'https://') === 0) ? ('http' . substr($url, 5)) : $url;
		// Compare the URLs
		if (!($is_local = (strpos($url, $home) === 0)))
		{
			// If there is no scheme, then it's probably a relative, local link
			$scheme = substr($url, 0, strpos($url, ':'));
			$is_local = !$scheme || ($scheme && !preg_match('/^[a-z0-9.]{2,16}$/iu', $scheme));
		}
		// Not local, now check forced local domains
		if (!$is_local && $this->options['input_forced_local'])
			$is_local = $this->match_domain($url, $this->parse_options('input_forced_local', false));

		return $is_local;
	}

	/**
	 * Main function to parse links.
	 *
	 * @copyright (c) 2007-2010 Ken F. Innes IV (with some modifications)
	 */
	function parse_links($content)
	{
		// Don't do a thing if there's no anchor at all
		if (false === stripos($content, '<a '))
			return($content);

		$home = strtolower(get_option('home'));
		$remove = ('some' == $this->options['select_sub_method']) ? $this->parse_options('input_local_sub') : $this->options['select_sub_method'];
		$original_home = $home;
		$home = $this->remove_subdomains($home, 'all');
		preg_match_all('#(<a\s[^>]+?>)(.*?</a>)#iu', $content, $matches, PREG_SET_ORDER);

		foreach ($matches as $links)
		{
			$link = $new_link = $links[1];
			// Remove all rel='nofollow' and rel="nofollow" - @since 1.1.1
			$new_link = str_replace(array(' rel="nofollow"', " rel='nofollow'"), '', $link);
			// Take the href attribute out of the anchor
			$href = preg_replace('/^.*href="([^"]*)".*$/iu', '$1', $link);
			if ($href == $link) // No link was found, try with single quotes
				$href = preg_replace("/^.*href='([^']*)'.*$/iu", '$1', $link);
			if ($href == $link) // No link was found, give up
				continue;
			$href	= $this->decode_entities($href);
			$scheme	= substr($href, 0, strpos($href, ':'));
			if ($scheme)
			{
				$scheme = strtolower($scheme);
				if ($scheme != 'http' && $scheme != 'https') // Only classify links for these schemes (or no scheme)
					continue;
			}
			
			$is_local = $this->is_local($href, $home);

			if (!$is_local)
			{
				$temp_href = $this->remove_subdomains($href);
				if (!empty($this->options['input_forbidden']) && $this->match_domain($temp_href, $this->parse_options('input_forbidden', false)))
				{
					$searches[]		= $link;
					$replacements[]	= $this->insert_attribute('href', $this->options['input_forbidden_replace'], $new_link, true);
					continue;
				}
			}

			// If $href and $home are simply the same domain, no need to try removing anything - @since 1.1.0
			if (strpos($href, $original_home) === 0)
				$is_local = true;
			else
			{
				$href = $this->remove_subdomains($href, $remove);
				// We need to recheck this
				$is_local = $this->is_local($href, $home);
			}

			$new_class	= $is_local ? $this->options['input_local_class']  : $this->options['input_external_class'];
			$new_target	= $is_local ? '' : $this->options['select_target'];
			$new_rel	= $is_local ? ''  : $this->ext_relation;

			// Check if this link needs a special class based on the type of file to which it points. -> 1.2.0

			if (!empty($new_class))
				$new_link = $this->insert_attribute('class', $new_class, $new_link, $this->options['input_external_class']);

			if (!empty($new_rel))
				$new_link = $this->insert_attribute('rel', $new_rel, $new_link);

			if (!empty($new_target) && '_none' != $new_target)
			{
				if ('target' == $this->options['select_exe_type'])
					$new_link = $this->insert_attribute('target', $new_target, $new_link, true);
				else if ('onclick' == $this->options['select_exe_type'])
					$new_link = $this->insert_attribute('onclick', "this.target='$new_target';", $new_link);
			}

			$external_prefix = $this->anonymous_prefix;
			if (!$is_local && $external_prefix)
				$new_link = preg_replace('/href=(?:"|\')([^"\']*)(?:"|\')/iu', 'href="' . $external_prefix . '$1"', $new_link);

			$searches[]		= $link;
			$replacements[]	= $new_link;
		}

		if (isset($searches) && isset($replacements))
			$content = str_replace($searches, $replacements, $content);

		return $content;
	}
}
?>