<?php
/**
 * @brief		Template Plugin - Truncate
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		28 Aug 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Output\Plugin;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Plugin - Truncate
 */
class _Truncate
{
	/**
	 * @brief	Can be used when compiling CSS
	 */
	public static $canBeUsedInCss = TRUE;
	
	/**
	 * Run the plug-in
	 *
	 * @param	string 		$data	  The initial data from the tag
	 * @param	array		$options    Array of options
	 * @return	string		Code to eval
	 */
	public static function runPlugin( $data, $options )
	{
		if( !$options['length'] )
		{
			return "\"{$data}\"";
		}

		$options['start']	= ( !empty($options['start']) ) ? $options['start'] : 0;
		$options['append']	= ( !empty($options['append']) ) ? $options['append'] : "&hellip;";

		$_argument	= ( mb_strpos( $data, '$' ) !== FALSE ) ? $data : '"' . $data . '"';
		$_length	= ( mb_strpos( $options['length'], '$' ) !== FALSE ) ? $options['length'] : '"' . $options['length'] . '"';

		return "htmlspecialchars( mb_substr( {$_argument}, '{$options['start']}', {$_length} ), ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', FALSE ) . ( ( mb_strlen( {$_argument} ) > {$_length} ) ? '{$options['append']}' : '' )";
	}
}