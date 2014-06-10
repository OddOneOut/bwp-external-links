<?php
/**
 * Copyright (c) 2014 Khang Minh <betterwp.net>
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

if (!class_exists('BWP_FRAMEWORK_IMPROVED'))
	require_once(dirname(__FILE__) . '/class-bwp-framework-improved.php');

class BWP_EXTERNAL_LINKS extends BWP_FRAMEWORK_IMPROVED {

	var $ext_relation     = '';
	var $anonymous_prefix = '';

	/**
	 * Constructor
	 */
	public function __construct($version = '1.1.3')
	{
		// Plugin's title
		$this->plugin_title = 'Better WordPress External Links';
		// Plugin's version
		$this->set_version($version);
		// Plugin's language domain
		$this->domain = 'bwp-ext';
		// Basic version checking
		if (!$this->check_required_versions())
			return;

		// Default options
		$options = array(
			'enable_page_process'        => '',
			'enable_post_contents'       => 'yes',
			'enable_comment_text'        => 'yes',
			'enable_widget_text'         => 'yes',
			'enable_css'                 => 'yes',
			'enable_rel_external'        => 'yes',
			'enable_rel_nofollow'        => 'yes',
			'input_external_rel'         => '',
			'input_local_rel'            => '',
			'input_external_class'       => 'ext-link',
			'input_external_image_class' => 'ext-image',
			'input_local_class'          => 'local-link',
			'input_local_sub'            => '',
			'input_forced_local'         => '',
			'input_forbidden'            => '',
			'input_forbidden_replace'    => '#',
			'input_top_level_domain'     => '',
			'input_custom_prefix'        => '',
			'select_sub_method'          => 'all',
			'select_anonymous_prefix'    => 'none',
			'select_target'              => '_blank',
			'select_exe_type'            => 'onclick'
		);

		$this->add_option_key('BWP_EXT_OPTION_GENERAL', 'bwp_ext_general',
			__('Better WordPress External Links Settings', $this->domain)
		);

		$this->build_properties('BWP_EXT', $this->domain, $options,
			'BetterWP External Links', dirname(dirname(__FILE__)) . '/bwp-external-links.php',
			'http://betterwp.net/wordpress-plugins/bwp-external-links/', false);
	}

	public function print_scripts()
	{
?>
<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('a[rel~="external"]').attr('target','<?php echo $this->options['select_target']; ?>');
	});
</script>
<?php
	}

	protected function pre_init_properties()
	{
		// define a few urls that are used throughout the plugin
		$this->add_url('bwp_tip_redirect',
			'http://betterwp.net/wordpress-tips/redirect-external-links/',
			false
		);
	}

	protected function init_hooks()
	{
		// no links processing for admin page - @since 1.1.0
		if (is_admin())
			return;

		if ('yes' != $this->options['enable_page_process'])
		{
			if ('yes' == $this->options['enable_post_contents'])
				add_filter('the_content', array($this, 'parse_links'), 99);

			if ('yes' == $this->options['enable_comment_text'])
				add_filter('comment_text', array($this, 'parse_links'), 99);

			// @since 1.1.0
			if ('yes' == $this->options['enable_widget_text'])
				add_filter('widget_text', array($this, 'parse_links'), 99);
		}
		else
		{
			require_once dirname(__FILE__) . '/class-anchor-utils.php';
			$bwp_ext_ob = new BWP_EXTERNAL_LINKS_OB;

			// The very late priority is to ensure we capture as much text
			// added after `wp_head` action as possible.
			add_action('wp_head', array($bwp_ext_ob, 'ob_start'), 10000);
		}

		if ('jquery' == $this->options['select_exe_type'])
		{
			wp_enqueue_style('jquery');
			add_action('wp_head', array($this, 'print_scripts'), 9);
		}
	}

	protected function init_properties()
	{
		$this->options['input_top_level_domain'] = !empty($this->options['input_top_level_domain'])
			? str_replace('.', '\\.', $this->options['input_top_level_domain'])
			: '[a-z0-9-]+\.(?:aero|biz|com|coop|info|jobs|museum|name|net|org|pro|travel|gov|edu|mil|int)';

		$rel  = '';
		$rel .= 'yes' == $this->options['enable_rel_external'] ? 'external ' : '';
		$rel .= 'yes' == $this->options['enable_rel_nofollow'] ? 'nofollow ' : '';

		$this->ext_relation = trim($rel) . $this->options['input_external_rel'];

		if ('custom' == $this->options['select_anonymous_prefix'])
			$this->anonymous_prefix = $this->options['input_custom_prefix'];
		else if ('none' != $this->options['select_anonymous_prefix'])
			$this->anonymous_prefix = $this->options['select_anonymous_prefix'];

		if (in_array($this->options['select_sub_method'], array('all', 'none')))
			$this->options['input_local_sub'] = $this->options['select_sub_method'];
	}

	protected function enqueue_media()
	{
		if ('yes' == $this->options['enable_css'])
		{
			wp_enqueue_style('bwp-ext', BWP_EXT_CSS . '/bwp-external-links.css',
				false, $this->get_version());
		}
	}

	/**
	 * Build the Menus
	 */
	protected function build_menus()
	{
		add_options_page(
			__('Better WordPress External Links', $this->domain),
			'BWP External Links',
			BWP_EXT_CAPABILITY,
			BWP_EXT_OPTION_GENERAL,
			array($this, 'build_option_pages')
		);
	}

	/**
	 * Build the option pages
	 *
	 * Utilizes BWP Option Page Builder (@see BWP_OPTION_PAGE)
	 */
	public function build_option_pages()
	{
		if (!current_user_can(BWP_EXT_CAPABILITY))
			wp_die(__('You do not have sufficient permissions to access this page.'));

		// Init the class
		$page            = $_GET['page'];
		$bwp_option_page = new BWP_OPTION_PAGE($page);

		$options = array();

		if (!empty($page))
		{
			if ($page == BWP_EXT_OPTION_GENERAL)
			{
				$form = array(
					'items' => array(
						'heading', // Plugin Functionality
						'section',
						'checkbox',
						'heading', // Link Settings
						'select',
						'textarea',
						'textarea',
						'textarea',
						'input',
						'select',
						'input',
						'heading', // Attribute Settings
						'checkbox',
						'checkbox',
						'input',
						'select',
						'heading', // Display Settings
						'input',
						'input',
						'input',
						'checkbox'
					),
					'item_labels' => array(
						__('Plugin Functionality', $this->domain),
						__('Process links inside', $this->domain),
						__('Process page-wide links?', $this->domain),
						__('Link Settings', $this->domain),
						__('Subdomains', $this->domain),
						__('Local subdomains &mdash; Links to these subdomains are local', $this->domain),
						/*__('Ignore links pointing to URL with', $this->domain),*/
						__('Forced local domains', $this->domain),
						__('Forbidden domains &mdash; useful for comment text', $this->domain),
						__('Links to forbidden domains will be replaced with', $this->domain),
						__('External link prefix', $this->domain),
						__('Custom URL prefix', $this->domain),
						__('Attribute Settings', $this->domain),
						__('Add the attribute', $this->domain),
						__('Add the attribute', $this->domain),
						__('Custom rel attribute values', $this->domain),
						__('Open external links', $this->domain),
						__('Display Settings', $this->domain),
						__('External links&#8217; CSS class', $this->domain),
						__('External image links&#8217; CSS class', $this->domain),
						__('Local links&#8217; CSS class', $this->domain),
						__('Use CSS provided by this plugin?', $this->domain)
					),
					'item_names' => array(
						'h1',
						'sec1',
						'cb7',
						'h2',
						'select_sub_method',
						'input_local_sub',
						'input_forced_local',
						'input_forbidden',
						'input_forbidden_replace',
						'select_anonymous_prefix',
						'input_custom_prefix',
						'h3',
						'cb4',
						'cb5',
						'input_external_rel',
						'select_target',
						'h4',
						'input_external_class',
						'input_external_image_class',
						'input_local_class',
						'cb3'
					),
					'heading' => array(
						'h1' => '',
						'h2' => '<em>' . sprintf(
							__('Control which kinds of links are considered external, '
							. 'and how they are processed. Check this '
							. '<a href="%s#usage">detailed guide</a> out for more information.</em>', $this->domain),
							$this->plugin_url
						) . '</em>',
						'h3' => '<em>' .
							__('Customize attributes added to external links.', $this->domain)
							. '</em>',
						'h4' => '<em>'
							. __('Customize the look and feel of external and local links.', $this->domain)
							. '</em>'
					),
					'sec1' => array(
						array('checkbox', 'name' => 'cb1'),
						array('checkbox', 'name' => 'cb2'),
						array('checkbox', 'name' => 'cb6')
					),
					'select' => array(
						'select_sub_method' => array(
							__('Links to all subdomains are considered local', $this->domain)  => 'all',
							__('Links to all subdomains are considered external', $this->domain)   => 'none',
							__('Links to some subdomains are considered local', $this->domain) => 'some'
						),
						'select_exe_type' => array(
							__('using the "onclick" attribute', $this->domain) => 'onclick',
							__('using the "target" attribute', $this->domain)  => 'target',
							__('using jQuery', $this->domain)                  => 'jquery'
						),
						'select_target' => array(
							__('in one new tab/window (_blank)', $this->domain) => '_blank',
							__('in just one new window (_new)', $this->domain)  => '_new',
							__('in the same tab/window', $this->domain)         => '_none'
						),
						'select_anonymous_prefix' => array(
							__('No prefix', $this->domain)      => 'none',
							'http://anonym.to?'               => 'http://anonym.to?',
							__('A custom URL', $this->domain) => 'custom'
						)
					),
					'checkbox'	=> array(
						'cb1' => array(__('post contents, i.e. hook to <code>the_content()</code>', $this->domain) => 'enable_post_contents'),
						'cb2' => array(__('comment text, i.e. hook to <code>comment_text()</code>', $this->domain) => 'enable_comment_text'),
						'cb6' => array(__('text widgets, i.e. hook to <code>widget_text()</code>', $this->domain) => 'enable_widget_text'),
						'cb7' => array(__('This plugin will find and process all links on any given page. This option will override what you check above. This is an advanced feature and should be used with caution.', $this->domain) => 'enable_page_process'),
						'cb3' => array(__('a very simple CSS provided so that you can see the "external" effect :). It is recommended that you disable this and use your own rules.', $this->domain) => 'enable_css'),
						'cb4' => array(__('<code>rel="external"</code> to external links?', $this->domain) => 'enable_rel_external'),
						'cb5' => array(__('<code>rel="nofollow"</code> to external links?', $this->domain) => 'enable_rel_nofollow')
					),
					'input'	=> array(
						'input_external_rel'      => array(
							'size'  => 50,
							'label' => __('separate each value by a space.', $this->domain)
						),
						/*'input_top_level_domain' => array(
							'size' => 50,
							'label' => __('as its top domain. This means you can make '
								. 'links pointing from <code>http://yourdomain.com</code> '
								. 'to <code>http://yourdomain.co.jp</code> become local. '
								. 'In such case, just input <code>yourdomain.com</code>.', $this->domain)
						),*/
						'input_forbidden_replace' => array(
							'size'  => 50,
							'label' => '<br />'
								. __('This can be <code>#top</code>, <code>http://www.google.com</code> '
								. 'or anything else suitable for the <code>href</code> attribute.', $this->domain)
						),
						'input_external_class'    => array(
							'size' => 50
						),
						'input_external_image_class' => array(
							'size' => 50
						),
						'input_local_class'       => array(
							'size' => 50
						),
						'input_custom_prefix'     => array(
							'size'  => 50,
							'label' => '<br >' .
								__('For example you can prepend external links with something like '
								. '<code>http://yourdomain.com/out.php?</code>.', $this->domain)
								. '<br />' . sprintf(
								__('Check <a href="%s" target="_blank">this tutorial</a> out for some tips on how to set up '
								. 'a redirection/disclaimer page for external links.', $this->domain),
								$this->get_url('bwp_tip_redirect'))
						)
					),
					'textarea' => array(
						'input_local_sub'    => array(
							'cols' => 40,
							'rows' => 5,
							'post' => '<br /><em>' .
								__('Please put one subdomain per line (WITHOUT scheme and main domain). '
								. 'Wildcard is supported.<br />Example: <code>sub2.sub1</code> or '
								. '<code>*.sub1</code> will make <code>sub2.sub1.example.com</code> local.<br />'
								. '<strong>Note</strong>: <code>www</code> and <code>non-www</code> versions '
								. 'of the same domain are always considered local.', $this->domain)
								. '</em>'
						),
						'input_forced_local' => array(
							'cols' => 40,
							'rows' => 5,
							'post' => '<br /><em>' .
								__('Please put one domain per line (WITHOUT scheme). '
								. 'Wildcard is supported.<br />Example: <code>external.com</code> or '
								. '<code>*.external.com</code>.', $this->domain)
								. '</em>'
						),
						'input_forbidden'    => array(
							'cols' => 40,
							'rows' => 5,
							'post' => '<br /><em>' .
								__('Please put one domain per line (WITHOUT scheme). '
								. 'Wildcard is supported.<br />Example: <code>forbidden.com</code> or '
								. '<code>*.forbidden.com</code>.', $this->domain)
								. '</em>'
						)
					),
					'inline_fields' => array(
						'select_target' => array(
							'select_exe_type' => 'select'
						)
					)
				);

				// Get the default options
				$options = $bwp_option_page->get_options(array(
					'enable_page_process',
					'enable_post_contents',
					'enable_comment_text',
					'enable_widget_text',
					'enable_css',
					'enable_rel_external',
					'enable_rel_nofollow',
					'input_external_rel',
					'input_external_class',
					'input_external_image_class',
					'input_local_class',
					'input_local_sub',
					'input_forced_local',
					'input_forbidden',
					'input_forbidden_replace',
					'input_custom_prefix',
					'select_sub_method',
					'select_anonymous_prefix',
					'select_target',
					'select_exe_type'
				), $this->options_default);

				// Get option from the database
				$options = $bwp_option_page->get_db_options($page, $options);

				$option_formats = array();
			}
		}

		// Get option from user input
		if (isset($_POST['submit_' . $bwp_option_page->get_form_name()])
			&& isset($options) && is_array($options)
		) {
			// basic security check
			check_admin_referer($page);

			foreach ($options as $key => &$option)
			{
				if (isset($_POST[$key]))
				{
					$bwp_option_page->format_field($key, $option_formats);
					$option = trim(stripslashes($_POST[$key]));
				}

				if (!isset($_POST[$key]))
				{
					// for checkboxes that are unchecked
					$option = '';
				}
				else if (isset($option_formats[$key])
					&& 'int' == $option_formats[$key]
					&& ('' === $_POST[$key] || 0 > $_POST[$key])
				) {
					// expect integer but received empty string or negative integer
					$option = $this->options_default[$key];
				}
			}

			// update per-blog options
			update_option($page, $options);

			// add an update success message
			$this->add_notice(__('All options have been saved.', $this->domain));
		}

		// Assign the form and option array
		$bwp_option_page->init($form, $options, $this->form_tabs);

		// Build the option page
		echo $bwp_option_page->generate_html_form();
	}

	/**
	 * Decodes all HTML entities.
	 *
	 * The html_entity_decode() function doesn't decode numerical entities, and
	 * the htmlspecialchars_decode() function only decodes the most common form
	 * for entities.
	 *
	 * @copyright (c) 2007-2010 Ken F. Innes IV
	 * @return string
	 */
	private static function _decode_entities($text)
	{
		$text = html_entity_decode($text, ENT_QUOTES, 'ISO-8859-1'); // UTF-8 does not work!
		$text = preg_replace('/&#(\d+);/me', 'chr($1)', $text); // Decimal notation
		$text = preg_replace('/&#x([a-f0-9]+);/mei', 'chr(0x$1)', $text); // HEX notation

		return $text;
	}

	private function _parse_options($option = '', $preg = true)
	{
		if ('all' == $option || 'none' == $option)
			return $option;

		if (!isset($this->options[$option]))
			return '';

		$items = explode("\n", $this->options[$option]); // todo: improve newline check
		$items = array_map('trim', $items);

		if (false == $preg)
			return $items;

		$preg_string = '';
		foreach ($items as $item)
		{
			// make each domain preg_replace ready
			// @since 1.1.3 basic support for wildcards
			$item = str_replace('.', '\\.', $item);
			$item = str_replace('*\\.', '.*?\\.', $item);

			$preg_string .= $item . '|';
		}

		return rtrim($preg_string, '|');
	}

	/**
	 * Removes subdomains from a URL.
	 *
	 * @deprecated 1.1.3
	 * @return string
	 */
	private function __remove_subdomains($url, $remove = 'all')
	{
		$stripped_url = $url;

		if ('none' != $remove && strpos($url, '//') !== false)
		{
			$url_parts = @parse_url($url);

			if ('all' == $remove)
			{
				// Simple check for TLDs - @since 1.1.0
				$slashed_url = empty($url_parts['path']) && empty($url_parts['query'])
					? trailingslashit($url)
					: $url;

				$stripped_url = $slashed_url;
				$country_tld  = 'ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|'
					. 'ba|bb|bd|be|bf|bg|bh|bi|bj|bl|bm|bn|bo|br|bs|bt|bv|bw|by|bz|'
					. 'ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cu|cv|cx|cy|cz|'
					. 'de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|'
					. 'ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|'
					. 'hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|'
					. 'ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|'
					. 'ma|mc|md|me|mf|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|'
					. 'na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|'
					. 'qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|'
					. 'tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|'
					. 'ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw';

				if (!preg_match('#^(http|https)://([^\.^/]+)\.([^\.^/]+)\.(' . $country_tld . ')/#i', $slashed_url))
				{
					$stripped_url = preg_replace(
						'#^(http|https)://(?:.*)\.([^\.^/]+)\.([^\.^/]+)\.(' . $country_tld . ')/#i',
						'$1://$2.$3.$4/', $slashed_url
					);

					// Last try
					if ($slashed_url == $stripped_url)
					{
						$stripped_url = preg_replace(
							'#^(http|https)://(?:.*)\.([^\.^/]+)\.([^\.^/]+)#i',
							'$1://$2.$3', $url
						);
					}
				}
			}
			else if ('all' != $remove)
			{
				// Always include 'www' sub-domain - @since 1.1.0
				$stripped_url = preg_replace(
					'#^(http|https)://(?:' . $remove . '|www)\.([^/]+)#i',
					'$1://$2', $url
				);
			}
			else if (!empty($url_parts['host']) && 'localhost' == substr($url_parts['host'], -9))
			{
				// Domain could have a port number, but it's too rare a case with localhost
				$stripped_url = preg_replace(
					'#^(http|https)://[^/]+\.localhost#i',
					'$1://localhost', $url
				);
			}
		}

		return $stripped_url;
	}

	/**
	 * Removes subdomains from a domain
	 *
	 * @return string
	 */
	private function _remove_subdomains($domain, $subdomains)
	{
		// always have a 'www' subdomain in the list to be removed
		$subdomains = false === strpos($subdomains, '|www')
			? $subdomains . '|www'
			: $subdomains;

		$domain = preg_replace(
			'#^(?:' . $subdomains . ')\.([^/]+)#iu',
			'$1', $domain
		);

		return $domain;
	}

	/**
	 * Insert an attribute into an HTML tag.
	 *
	 * This should take care of existing attributes. If attribute values exist
	 * we will not add duplicate ones.
	 *
	 * @return void
	 */
	private static function _insert_attribute($attr_name, $new_attr_value,
												$html_tag, $overwrite = false)
	{
		$attr_value     = false;
		$is_javascript  = strpos($attr_name, 'on') === 0; // onclick, onmouseup, onload, etc.
		$quote          = false; // quotes to wrap attribute values

		if (preg_match('/\s' . $attr_name . '="([^"]*)"/iu', $html_tag, $matches)
			|| preg_match('/\s' . $attr_name . "='([^']*)'/iu", $html_tag, $matches)
		) {
			// two possible ways to get existing attributes
			$attr_value = $matches[1];

			$quote = false !== stripos($html_tag, $attr_name . "='")
				? "'" : '"';
		}

		if (false === $attr_value)
		{
			// attribute does not currently exist, add it
			return str_ireplace('>', " $attr_name=\"" . esc_attr($new_attr_value) . '">', $html_tag);
		}

		if ($overwrite)
		{
			// simply overwrite current attribute if allowed
			return str_ireplace("$attr_name=" . $quote . "$attr_value" . $quote,
				$attr_name . '="' . esc_attr($new_attr_value) . '"', $html_tag);
		}

		if ($is_javascript)
		{
			// nea attribute value should use correct quotes
			$new_attr_value = $quote == "'"
				? str_replace($quote, '"', $new_attr_value)
				: $new_attr_value;

			// this is a javascript attribute ensure we must add code after any
			// existing codes
			$new_attr_value = ($last_char = substr(trim($attr_value), -1))
				&& $last_char != '}' && $last_char != ';'
				? $attr_value . ';' . $new_attr_value
				: $attr_value . $new_attr_value;
		}
		else
		{
			// regular attribute, simply append new attribute values if they're
			// not already there
			$values         = explode(' ', $new_attr_value);
			$new_attr_value = $attr_value;

			foreach ($values as $value)
			{
				$value = str_replace('/', '\/', $value);

				if (0 === stripos($attr_value, $value . ' ')
					|| false !== stripos($attr_value, ' ' . $value . ' ')
					|| preg_match('/\s' . $value . '$/ui', $attr_value)
				) {
					// attribute value already exists, no need to add anything
				}
				else
				{
					// add new attribute value
					$new_attr_value .= ' ' . $value;
				}

			}

			$new_attr_value = trim($new_attr_value);
		}

		return str_ireplace("$attr_name=" . $quote . "$attr_value" . $quote,
			$attr_name . '="' . esc_attr($new_attr_value) . '"', $html_tag);
	}

	/**
	 * Determine if a domain contains another domain
	 *
	 * @param $domain string domain being matched, with subdomains already
	 *        stripped if needed
	 * @param $domains mixed domain or domains to match against
	 * @return bool
	 */
	private static function _match_domain($domain, $domains)
	{
		$domains = (array) $domains;

		foreach ($domains as $base_domain)
		{
			// @since 1.1.3 basic support for domain wildcards
			if (0 === strpos($base_domain, '*.'))
			{
				$base_domain = substr($base_domain, 2);

				if (preg_match('#' . $base_domain . '$#iu', $domain))
					return true;

				continue;
			}

			if ($domain == $base_domain)
			{
				// not using wildcard, an exact match is required
				return true;
			}
		}

		return false;
	}

	/**
	 * Determines if a URL is external
	 *
	 * @since 1.1.3
	 * @return bool
	 */
	private function _is_external($url)
	{
		$url = strtolower($url);

		// add scheme for protocol relative urls and treat http and https as
		// same scheme
		$url = strpos($url, '//') === 0 ? 'http:' . $url : $url;
		$url = strpos($url, 'https://') === 0
			? 'http' . substr($url, 5)
			: $url;

		if (!preg_match('#https?://([^/]+)#iu', $url, $matches))
		{
			// try getting http host from url, if none match this url is
			// considered to be local
			return false;
		}

		$url_domain  = self::_extract_domain($url);

		// get the base domain of the current Site Address, without scheme
		$base_domain = strtolower(home_url());
		$base_domain = self::_extract_domain($base_domain);

		if ($url_domain == $base_domain)
		{
			// if current url's domain is the same as base domain, this url is local
			return false;
		}

		if (false !== strpos($base_domain, 'www.'))
		{
			// if base domain contains a `www` subdomain, remove it
			$base_domain = str_replace('www.', '', $base_domain);
		}

		$local_domains = $this->_parse_options('input_forced_local', false);
		if (count($local_domains) > 0)
		{
			// If there are forced local domains, we need to check if the
			// current url comes from one of them.
			if ($this->_match_domain($url_domain, $local_domains))
			{
				// consider this url to be local without further processing
				return false;
			}
		}

		if (!preg_match('#' . $base_domain . '$#iu', $url_domain))
		{
			// If current Site Address is not the end of current url, this url
			// is external, this means all urls pointing to same base domain
			// but has different country TLDs are also external.
			return true;
		}

		// this url should come from a subdomain of the base domain
		switch ($this->options['select_sub_method'])
		{
			case 'all':
				// all urls coming from a subdomain is considered local
				return false;
				break;

			case 'none':
				// all urls coming from a subdomain are considered external
				return true;
				break;

			case 'some':
				// urls coming from some subdomains are considered local
				$local_domains = $this->_parse_options('input_local_sub');

				if (!empty($local_domains))
				{
					$url_domain = $this->_remove_subdomains($url_domain, $local_domains);
					if (strpos($url_domain, $base_domain) === 0)
					{
						// Because current urls' domain has its subdomains
						// stripped, a match is found only when it starts with
						// the base domain.
						return false;
					}
				}

				break;
		}

		// all other urls are considered external
		return true;
	}

	/**
	 * Extracts domain (without trailing slash) from url
	 *
	 * @since 1.1.3
	 * @return string
	 */
	private static function _extract_domain($url, $with_port = false)
	{
		$colon = !$with_port ? ':' : '';

		return preg_replace('#https?://([^/' . $colon .']+).*$#iu', '$1', $url);
	}

	/**
	 * Main function to parse links.
	 *
	 * @return string
	 */
	public function parse_links($content)
	{
		// Don't do a thing if there's no anchor at all
		if (false === stripos($content, '<a '))
			return($content);

		// any urls pointing to these domains will be replaced
		$forbidden_domains = $this->_parse_options('input_forbidden', false);

		// find all occurrences of anchors and fill matches with links
		preg_match_all('#(<a\s[^>]+?>).*?</a>#iu', $content, $matches, PREG_SET_ORDER);

		foreach ($matches as $links)
		{
			// We take matched pattern (from `<a` to the first `>`) and look
			// for appropriate `href` attribute to act upon.
			$link = $new_link = $links[1];

			// assuming that the link being checked uses double quotes: href="url"
			$href = preg_replace('/^.*href="([^"]*)".*$/iu', '$1', $link);

			if ($href == $link)
			{
				// href attribute not found, try looking in single quotes
				$href = preg_replace("/^.*href='([^']*)'.*$/iu", '$1', $link);
			}

			if ($href == $link || empty($href) || 0 === strpos($href, '#'))
			{
				// no href attribute can be found, or href is empty, or href
				// starts with a location hash, give up this link
				continue;
			}

			if (false === strpos($href, 'http://')
				&& false === strpos($href, 'https://')
				&& false === strpos($href, '//')
			) {
				// do not process this link if scheme is not 'http', 'https' or
				// schemeless
				continue;
			}

			if (count($forbidden_domains) > 0)
			{
				// there are forbidden domains to check current link against
				$href_domain = self::_extract_domain($href);

				if ($this->_match_domain($href_domain, $forbidden_domains))
				{
					// this is a forbidden link, replace with pre-defined link
					$searches[]     = $link;
					$replacements[] = $this->_insert_attribute('href',
						$this->options['input_forbidden_replace'],
						$new_link, true);

					continue;
				}
			}

			$is_external = $this->_is_external($href);

			// set attributes correctly for external/local link
			// @since 1.1.3, if this link points to an external image, use a
			// different external CSS class
			if ($is_external)
			{
				$image_ext = apply_filters('bwp_ext_image_extensions', 'jpg|jpeg|png|gif');
				$is_image  = preg_match('/\.(' . $image_ext . ')([#?]|$)/iu', $href);

				$new_class = $is_image
					? $this->options['input_external_image_class']
					: $this->options['input_external_class'];
			}
			else
			{
				$new_class = $this->options['input_local_class'];
			}

			$new_target = $is_external ? $this->options['select_target'] : '';
			$new_rel    = $is_external ? $this->ext_relation : '';

			if (!empty($new_class))
			{
				$new_link = $this->_insert_attribute('class',
					$new_class, $new_link);
			}

			if (!empty($new_rel))
			{
				$new_link = $this->_insert_attribute('rel',
					$new_rel, $new_link);
			}

			if (!empty($new_target) && '_none' != $new_target)
			{
				// open external links in new windows, we have a few options
				if ('target' == $this->options['select_exe_type'])
				{
					// use `target` attribute in a standard-friendly way
					$new_link = $this->_insert_attribute('target',
						$new_target, $new_link, true);
				}
				else if ('onclick' == $this->options['select_exe_type'])
				{
					// use `onclick` attribute, which actually adds the
					// `target` attribute just before the link is followed
					$new_link = $this->_insert_attribute('onclick',
						"this.target='$new_target';", $new_link);
				}
			}

			$external_prefix = $this->anonymous_prefix;
			if ($is_external && !empty($external_prefix))
			{
				// add a prefix, such as `http://example.com/out?` to external
				// links, this allows user to create a friendly redirection page.
				// @since 1.1.3 link is urlencoded
				$new_link = $this->_insert_attribute('href',
					$external_prefix . urlencode($href),
					$new_link, true);
			}

			$searches[]     = $link;
			$replacements[] = $new_link;
		}

		if (isset($searches) && isset($replacements))
			$content = str_replace($searches, $replacements, $content);

		return $content;
	}
}
