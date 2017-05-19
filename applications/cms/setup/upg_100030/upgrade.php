<?php
/**
 * @brief		4.0.5 Upgrade Code
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Pages
 * @since		20 Apr 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\setup\upg_100030;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.0.5 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Fixes BBCode type pages
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		foreach( \IPS\Db::i()->select( '*', 'cms_pages', array( 'page_type=?', 'bbcode' ) ) as $page )
		{
			$update = array(
				'page_content' => \IPS\Text\LegacyParser::parseStatic( $page['page_content'] ),
				'page_type'    => 'html'
			);
			
			\IPS\Db::i()->update( 'cms_pages', $update, array( 'page_id=?', $page['page_id'] ) );
		}

		return TRUE;
	}

	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step1CustomTitle()
	{
		return "Converting bbcode pages to HTML pages";
	}
}