<?php
/**
 * @brief		Magic Template Class for advanced theme (designers) mode
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		06 Aug 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Theme\Advanced;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Magic Template Class for advanced theme (designers) mode
 */
class _Template extends \IPS\Theme\Dev\Template
{
	/**
	 * @brief	Source Folder
	 */
	public $sourceFolder = NULL;
	
	/**
	 * Contructor
	 *
	 * @param	string	$app				Application Key
	 * @param	string	$templateLocation	Template location (admin/public/etc.)
	 * @param	string	$templateName		Template Name
	 * @return	void
	 */
	public function __construct( $app, $templateLocation, $templateName )
	{
		parent::__construct( $app, $templateLocation, $templateName );
		
		if ( \IPS\Theme\Advanced\Theme::$buildingFiles !== true )
		{
			$this->sourceFolder = \IPS\ROOT_PATH . "/themes/" . \IPS\Theme\Advanced\Theme::$currentThemeId . "/html/{$app}/{$templateLocation}/{$templateName}/";
		}
	}
}