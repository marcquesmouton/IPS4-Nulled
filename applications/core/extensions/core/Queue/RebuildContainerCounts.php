<?php
/**
 * @brief		Background Task: Rebuild Container Counts
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		14 Aug 2014
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
 * Background Task: Rebuild Container Counts
 */
class _RebuildContainerCounts
{
	/**
	 * @brief Number of content items to index per cycle
	 */
	public $index	= 500;
	
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
			$data['count'] = \IPS\Db::i()->select( 'MAX(' . $classname::$databasePrefix . $classname::$databaseColumnId . ')', $classname::$databaseTable )->first();
		}
		catch( \Exception $ex )
		{
			throw new \OutOfRangeException;
		}
		
		if( $data['count'] == 0 )
		{
			return null;
		}
		
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
	public function run( $data, $offset )
	{
		$classname = $data['class'];
        $exploded = explode( '\\', $classname );
        if ( !class_exists( $classname ) or !\IPS\Application::appIsEnabled( $exploded[1] ) )
		{
			throw new \OutOfRangeException;
		}
		
		$last = NULL;
		
		\IPS\Log::i( LOG_DEBUG )->write( "Running " . $classname . ", with an offset of " . $offset, 'rebuildItemCounts' );
		
		/* A pages database may have been deleted */
		try
		{
			$select   = \IPS\Db::i()->select( '*', $classname::$databaseTable, array( $classname::$databasePrefix . $classname::$databaseColumnId . ' > ?',  $offset ), $classname::$databasePrefix . $classname::$databaseColumnId . ' ASC', array( 0, $this->index ) );
			$idColumn = $classname::$databaseColumnId;
			$iterator = new \IPS\Patterns\ActiveRecordIterator( $select, $classname );
	
			foreach( $iterator as $item )
			{
				$item->resetCommentCounts();
				$item->setLastComment();
				$item->save();
				
				$last = $item->$idColumn;
			}
		}
		catch( \Exception $e )
		{
			throw new \OutOfRangeException;
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
		
		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('rebuilding_item_counts', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $class::$nodeTitle, FALSE, array( 'strtolower' => TRUE ) ) ) ) ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
	}	
}