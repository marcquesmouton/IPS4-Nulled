<?php
/**
 * @brief		Template Plugin - File
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		21 April 2015
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
 * Template Plugin - File
 */
class _File
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
		$extension = ( isset( $options['extension'] )  ? $options['extension'] : 'core_Attachment' );
		
		if ( $data instanceof \IPS\File )
		{
			return "(string) " . $data . "->url";
		}
		
		if ( mb_substr( $extension, 0, 1 ) === '$' )
		{
			return "\\IPS\\File::get( {$extension}, " . $data . " )->url";
		}
		else
		{
			return "\\IPS\\File::get( \"{$extension}\", " . $data . " )->url";
		}
	}
}