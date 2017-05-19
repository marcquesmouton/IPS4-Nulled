<?php
/**
 * @brief		Editor Extension: Record Form
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		20 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\extensions\core\EditorLocations;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor Extension: Record Content
 */
class _Widgets
{
	/**
	 * Can we use HTML in this editor?
	 *
	 * @param	\IPS\Member	$member	The member
	 * @return	bool|null	NULL will cause the default value (based on the member's permissions) to be used, and is recommended in most cases. A boolean value will override that.
	 */
	public function canUseHtml( $member )
	{
		return TRUE;
	}
	
	/**
	 * Can we use attachments in this editor?
	 *
	 * @param	\IPS\Member	$member	The member
	 * @return	bool|null	NULL will cause the default value (based on the member's permissions) to be used, and is recommended in most cases. A boolean value will override that.
	 */
	public function canAttach( $member )
	{
		return TRUE;
	}
	
	/**
	 * Permission check for attachments
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	int|null	$id1	Primary ID
	 * @param	int|null	$id2	Secondary ID
	 * @param	string|null	$id3	Arbitrary data
	 * @return	bool
	 */
	public function attachmentPermissionCheck( $member, $id1, $id2, $id3 )
	{
		if ( ! $id3 )
		{
			throw new \OutOfRangeException;
		}
		
		$pageId = $this->getPageIdFromWidgetUniqueId( $id3 );
		
		if ( $pageId === NULL )
		{
			throw new \OutOfRangeException;
		}
		
		return \IPS\cms\Pages\Page::load( $pageId )->can( 'view', $member );
	}
	
	/**
	 * Attachment lookup
	 *
	 * @param	int|null	$id1	Primary ID
	 * @param	int|null	$id2	Secondary ID
	 * @param	string|null	$id3	Arbitrary data
	 * @return	\IPS\Http\Url|\IPS\Content|\IPS\Node\Model
	 * @throws	\LogicException
	 */
	public function attachmentLookup( $id1, $id2, $id3 )
	{
		$pageId = $this->getPageIdFromWidgetUniqueId( $id3 );
		
		if ( $pageId === NULL )
		{
			return FALSE;
		}
		
		return \IPS\cms\Pages\Page::load( $pageId );
	}
	
	/**
	 * Returns the page ID based on the widget's unique ID
	 *
	 * @param	string	$uniqueId	The widget's unique ID
	 * @return	null|int
	 */
	protected function getPageIdFromWidgetUniqueId( $uniqueId )
	{
		$pageId = NULL;
		foreach( \IPS\Db::i()->select( '*', 'cms_page_widget_areas' ) as $item )
		{
			$widgets = json_decode( $item['area_widgets'], TRUE );

			foreach( $widgets as $widget )
			{
				if ( $widget['unique'] == $uniqueId )
				{
					$pageId = $item['area_page_id'];
				}
			}
		}
		
		return $pageId;
	}
}