<?php
/**
 * @brief		Background Task: Rebuild database editor fields
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		27 March 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Rebuild database editor fields
 */
class _RebuildEditorFields
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
		$classname  = $data['class'];
		$databaseId = mb_substr( $classname, 15 );
		$fieldId    = $data['fieldId'];

		try
		{
			$data['count'] = (int) \IPS\Db::i()->select( 'MAX(primary_id_field)', 'cms_custom_database_' . $databaseId )->first();
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
		$classname  = $data['class'];
		$databaseId = mb_substr( $classname, 15 );
		$fieldId    = $data['fieldId'];

		$parsed	= 0;
		$class  = '\IPS\cms\Records' . $databaseId;
		$last   = NULL;
		
		if ( \IPS\Db::i()->checkForTable( 'cms_custom_database_' . $databaseId ) AND \IPS\Db::i()->checkForColumn( 'cms_custom_database_' . $databaseId, 'field_' . $fieldId ) )
		{
			foreach ( \IPS\Db::i()->select( '*', 'cms_custom_database_' . $databaseId, array( 'primary_id_field > ?', $offset ), 'primary_id_field asc', array( 0, $this->rebuild ) ) as $row )
			{
				$item = $class::constructFromData( $row );
				$contentColumn = 'field_' . $fieldId;
				
				$member     = \IPS\Member::load( $item->mapped('author') );
				$extensions = \IPS\Application::load( $classname::$application )->extensions( 'core', 'EditorLocations' );
				$idColumn   = $classname::$databaseColumnId;
				
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
			
				try
				{
					$item->$contentColumn	= \IPS\Text\LegacyParser::parseStatic( $item->$contentColumn, $member, $canUseHtml, 'cms_Records', $item->$idColumn, $data['fieldId'], $databaseId, isset( $classname::$itemClass ) ? $classname::$itemClass : get_class( $item ) );
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
			}
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
		$classname  = $data['class'];
		$databaseId = mb_substr( $classname, 15 );
				
		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('rebuilding_cms_database_records', FALSE, array( 'sprintf' => array( \IPS\cms\Databases::load( $databaseId )->_title ) ) ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
	}	
}