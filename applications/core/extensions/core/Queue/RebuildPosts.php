<?php
/**
 * @brief		Background Task: Rebuild posts
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		13 Jun 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Rebuild posts
 */
class _RebuildPosts
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public $rebuild	= 50;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array
	 */
	public function preQueueData( $data )
	{
		$classname = $data['class'];
		
		try
		{
			\IPS\Log::i( LOG_DEBUG )->write( "Getting preQueueData for " . $classname, 'rebuildPosts' );
		}
		catch( \RuntimeException $e ){}

		try
		{
			$data['count']		= $classname::db()->select( 'MAX(' . $classname::$databasePrefix . $classname::$databaseColumnId . ')', $classname::$databaseTable, ( is_subclass_of( $classname, 'IPS\Content\Comment' ) ) ? $classname::commentWhere() : array() )->first();
			$data['realCount']	= $classname::db()->select( 'COUNT(*)', $classname::$databaseTable, ( is_subclass_of( $classname, 'IPS\Content\Comment' ) ) ? $classname::commentWhere() : array() )->first();
		}
		catch( \Exception $ex )
		{
			throw new \OutOfRangeException;
		}

		try
		{
			\IPS\Log::i( LOG_DEBUG )->write( "PreQueue count for " . $classname . " is " . $data['count'], 'rebuildPosts' );
		}
		catch( \RuntimeException $e ){}

		if( $data['count'] == 0 )
		{
			return null;
		}

		$data['indexed']	= 0;
		
		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	int|null				New offset or NULL if complete
	 * @throws	\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( &$data, $offset )
	{
		$classname = $data['class'];
        $exploded = explode( '\\', $classname );
        if ( !class_exists( $classname ) or !\IPS\Application::appIsEnabled( $exploded[1] ) )
		{
			throw new \OutOfRangeException;
		}

		/* Make sure there's even content to parse */
		if( !isset( $classname::$databaseColumnMap['content'] ) )
		{
			throw new \OutOfRangeException;
		}

		try
		{
			\IPS\Log::i( LOG_DEBUG )->write( "Running " . $classname . ", with an offset of " . $offset, 'rebuildPosts' );
		}
		catch( \RuntimeException $e ){}

		$where	  = ( is_subclass_of( $classname, 'IPS\Content\Comment' ) ) ? ( is_array( $classname::commentWhere() ) ? array( $classname::commentWhere() ) : array() ) : array();
		$select   = $classname::db()->select( '*', $classname::$databaseTable, array_merge( $where, array( array( $classname::$databasePrefix . $classname::$databaseColumnId . ' > ?',  $offset ) ) ), $classname::$databasePrefix . $classname::$databaseColumnId . ' ASC', array( 0, $this->rebuild ) );
		$iterator = new \IPS\Patterns\ActiveRecordIterator( $select, $classname );
		$last     = NULL;
		
		foreach( $iterator as $item )
		{
			$idColumn = $classname::$databaseColumnId;

			/* If the ID is greater than the max we started with, don't rebuild - we're done because this post and any newer
				will have been posted under 4.0 already */
			if( $item->$idColumn > $data['count'] )
			{
				return NULL;
			}

			/* Did the rebuild previously time out on this? If so we need to skip it and move along */
			if( isset( \IPS\Data\Store::i()->currentRebuild ) )
			{
				if( is_array( \IPS\Data\Store::i()->currentRebuild ) AND \IPS\Data\Store::i()->currentRebuild[0] == $classname AND \IPS\Data\Store::i()->currentRebuild[1] == $item->$idColumn )
				{
					unset( \IPS\Data\Store::i()->currentRebuild );
					continue;
				}
			}

			$member     = \IPS\Member::load( $item->mapped('author') );
			$extensions = \IPS\Application::load( $classname::$application )->extensions( 'core', 'EditorLocations' );

			if( isset( $classname::$itemClass ) )
			{
				$itemClass	= $classname::$itemClass;
				$module		= ucfirst( $itemClass::$module );
			}
			else
			{
				$module     = ucfirst( $classname::$module );
			}
			
			$extension  = NULL;
			
			if ( isset( $extensions[ $module ] ) )
			{
				$extension = $extensions[ $module ];
			}
			
			$canUseHtml = (bool) $member->group['g_dohtml'];
			
			if ( $extension )
			{
				$extensionCanUseHtml = $extension->canUseHtml( $member );
				if ( $extensionCanUseHtml !== NULL )
				{
					$canUseHtml = $extensionCanUseHtml;
				}
			}
			
			if ( ! $canUseHtml )
			{
				$canUseHtml = $item->htmlParsingEnforced();
			}
		
			$contentColumn	= $classname::$databaseColumnMap['content'];

			/* Figure out ids for attachments */
			if( isset( $classname::$itemClass ) )
			{
				$itemIdColumn = $itemClass::$databaseColumnId;
				$module		= $itemClass::$module;

				try
				{
					$id1 = $item->item()->$itemIdColumn;
				}
				catch ( \OutOfRangeException $e )
				{
					/* Post is orphaned and can be skipped */
					$last = $item->$idColumn;
					continue;
				}

				$id2 = $item->$idColumn;
			}
			else
			{
				$id1 = $item->$idColumn;
				$id2 = 0;
				$module	= $classname::$module;
			}

			/* Before we start trying to rebuild, set a flag to note what we are trying to rebuild. If it times out, we can check
				this on the next load and skip the problematic content */
			\IPS\Data\Store::i()->currentRebuild = array( $classname, $item->$idColumn );

			try
			{
				$item->$contentColumn	= \IPS\Text\LegacyParser::parseStatic( $item->$contentColumn, $member, $canUseHtml, $classname::$application . '_' . ucfirst( $module ), $id1, $id2, NULL, isset( $classname::$itemClass ) ? $classname::$itemClass : get_class( $item ) );
			}
			catch( \InvalidArgumentException $e )
			{
				if( $e->getcode() == 103014 )
				{
					$item->$contentColumn	= preg_replace( "#\[/?([^\]]+?)\]#", '', $item->$contentColumn );
				}
				else
				{
					throw $e;
				}
			}

			$item->save();

			$last = $item->$idColumn;

			$data['indexed']++;

			/* Now we will reset the rebuild flag we previously set since it rebuilt and saved successfully */
			unset( \IPS\Data\Store::i()->currentRebuild );
		}

		return $last;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaning task and percentage complete
	 * @throws	\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( $data, $offset )
	{
		$class = $data['class'];
        $exploded = explode( '\\', $class );
        if ( !class_exists( $class ) or !\IPS\Application::appIsEnabled( $exploded[1] ) )
		{
			throw new \OutOfRangeException;
		}
		
		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('rebuilding_stuff', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $class::$title . '_pl', FALSE, array( 'strtolower' => TRUE ) ) ) ) ), 'complete' => $data['realCount'] ? ( round( 100 / $data['realCount'] * $data['indexed'], 2 ) ) : 100 );
	}	
}