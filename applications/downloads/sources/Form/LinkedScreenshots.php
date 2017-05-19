<?php
/**
 * @brief		Form helper class for linked screenshots
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		13 Nov 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\downloads\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Form helper class for linked screenshots
 */
class _LinkedScreenshots extends \IPS\Helpers\Form\FormAbstract
{
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		if ( is_array( $this->value ) and !isset( $this->value['values'] ) )
		{
			$value = array( 'values' => $this->value, 'default' => \IPS\Request::i()->screenshots_primary_screenshot );
		}
		else
		{
			$value = $this->value;
		}
		return \IPS\Theme::i()->getTemplate( 'submit', 'downloads', 'front' )->linkedScreenshotField( $this->name, $value );
	}
}