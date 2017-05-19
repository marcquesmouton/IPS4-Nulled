<?php
/**
 * @brief		File Storage Extension: Pages
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Board
 * @since		20 October 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: CMS Pages
 */
class _Pages
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count()
	{
		return 1; # Number of steps needed to clear/move files
	}
	
	/**
	 * Move stored files
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @param	int			$storageConfiguration	New storage configuration ID
	 * @param	int|NULL	$oldConfiguration		Old storage configuration ID
	 * @throws	\Underflowexception				When file record doesn't exist. Indicating there are no more files to move
	 * @return	void
	 */
	public function move( $offset, $storageConfiguration, $oldConfiguration=NULL )
	{
		/* Just remove page object data so it will rebuild on the next iteration */
		\IPS\cms\Pages\Page::deleteCachedIncludes();
		
		throw new \UnderflowException;
	}
	
	/**
	 * Fix all URLs
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @return void
	 */
	public function fixUrls( $offset )
	{
		/* Just remove page object data so it will rebuild on the next iteration */
		\IPS\cms\Pages\Page::deleteCachedIncludes();
		
		throw new \UnderflowException;
	}


	/**
	 * Check if a file is valid
	 *
	 * @param	\IPS\Http\Url	$file		The file to check
	 * @return	bool
	 */
	public function isValidFile( $file )
	{
		$bits = explode( '/', (string) $file );
		$name = array_pop( $bits );

		try
		{
			$count = \IPS\Db::i()->select( 'COUNT(*)', 'cms_pages', array( "page_js_css_objects LIKE '%" . \IPS\Db::i()->escape_string( $name ) . "%'") )->first();
			
			return $count ? TRUE : FALSE;
		}
		catch( \IPS\Db\Exception $e )
		{
			return FALSE;
		}
	}

	/**
	 * Delete all stored files
	 *
	 * @return	void
	 */
	public function delete()
	{
		\IPS\cms\Pages\Page::deleteCachedIncludes();
	}
}