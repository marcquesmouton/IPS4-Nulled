<?php
/**
 * @brief		File Storage Extension: Blog Entries (cover photos)
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Blog
 * @since		30 May 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\blog\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Blog Entries (cover photos)
 */
class _Entries
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count()
	{
		return \IPS\Db::i()->select( 'COUNT(*)', 'blog_entries', 'entry_cover_photo IS NOT NULL' )->first();
	}
	
	/**
	 * Move stored files
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @param	int			$storageConfiguration	New storage configuration ID
	 * @param	int|NULL	$oldConfiguration		Old storage configuration ID
	 * @throws	\Underflowexception					When file record doesn't exist. Indicating there are no more files to move
	 * @return	void
	 */
	public function move( $offset, $storageConfiguration, $oldConfiguration=NULL )
	{
		$record	= \IPS\Db::i()->select( '*', 'blog_entries', 'entry_cover_photo IS NOT NULL', 'entry_id', array( $offset, 1 ) )->first();
		
		try
		{
			$file	= \IPS\File::get( $oldConfiguration ?: 'blog_Entries', $record['entry_cover_photo'] )->move( $storageConfiguration );
			
			if ( (string) $file != $record['entry_cover_photo'] )
			{
				\IPS\Db::i()->update( 'blog_entries', array( 'entry_cover_photo' => (string) $file ), array( 'entry_id=?', $record['entry_id'] ) );
			}
		}
		catch( \Exception $e )
		{
			/* Any issues are logged and the \IPS\Db::i()->update not run as the exception is thrown */
		}
	}
	
	/**
	 * Fix all URLs
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @return void
	 */
	public function fixUrls( $offset )
	{
		$record	= \IPS\Db::i()->select( '*', 'blog_entries', 'entry_cover_photo IS NOT NULL', 'entry_id', array( $offset, 1 ) )->first();
		
		if ( $new = \IPS\File::repairUrl( $record['blog_cover_photo'] ) )
		{
			\IPS\Db::i()->update( 'blog_entries', array( 'blog_cover_photo' => $new ), array( 'entry_id=?', $record['entry_id'] ) );
		}
	}

	/**
	 * Check if a file is valid
	 *
	 * @param	\IPS\Http\Url	$file		The file to check
	 * @return	bool
	 */
	public function isValidFile( $file )
	{
		try
		{
			$record	= \IPS\Db::i()->select( '*', 'blog_entries', array( 'entry_cover_photo=?', (string) $file ) )->first();

			return TRUE;
		}
		catch ( \UnderflowException $e )
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
		foreach( \IPS\Db::i()->select( '*', 'blog_entries', 'entry_cover_photo IS NOT NULL' ) as $blog )
		{
			try
			{
				\IPS\File::get( 'blog_Entries', $blog['entry_cover_photo'] )->delete();
			}
			catch( \Exception $e ){}
		}
	}
}