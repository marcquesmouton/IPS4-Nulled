<?php
/**
 * @brief		Blog RSS/Atom feed active record
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Blog
 * @since		02 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\blog\Blog;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Blog RSS/Atom feed active record
 */
class _Feed extends \IPS\Patterns\ActiveRecord
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'blog_rss_import';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'rss_';

	/**
	 * Run
	 *
	 * @return	void
	 * @throws	\UnexpectedValueException
	 */
	public function run()
	{
		$previouslyImportedGuids = iterator_to_array( \IPS\Db::i()->select( 'rss_imported_guid', 'blog_rss_imported', array( 'rss_imported_impid=?', $this->id ) ) );
		
		$request = \IPS\Http\Url::external( $this->url )->request();
		if ( $this->auth_user or $this->auth_pass )
		{
			$request = $request->login( $this->auth_user, $this->auth_pass );
		}
		$request = $request->get();
		
		$container = \IPS\blog\Blog::load( $this->blog_id );
		
		$inserts=array();
		$i = 0;
		foreach ( $request->decodeXml()->articles( $this->id ) as $guid => $article )
		{
			if ( !in_array( $guid, $previouslyImportedGuids ) )
			{
				$entry = \IPS\blog\Entry::createItem( \IPS\Member::load( $this->member ), NULL, $article['date'], $container );
				$entry->name = $article['title'];
				$entry->content = \IPS\Text\Parser::parseStatic( $article['content'] );
				
				if ( $article['link'] )
				{
					$link = htmlspecialchars( $this->import_show_link, ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', FALSE );
					$entry->content .= "<br><p><a href='{$article['link']}'>{$link}</a></p>";
				}
				
				$entry->status = 'published';
				$entry->save();
				
				/* Add to search index */
				\IPS\Content\Search\Index::i()->index( $entry );
				
				$entry->setTags( json_decode( $this->tags, TRUE ) );
		
				$inserts[] = array(
						'rss_imported_guid'	=> $guid,
						'rss_imported_entry_id'	=> $entry->id,
						'rss_imported_impid'=> $this->id
				);
		
				$i++;
		
				if ( $i >= 10 )
				{
					break;
				}
			}
		}	
		
		if( count( $inserts ) )
		{
			\IPS\Db::i()->insert( 'blog_rss_imported', $inserts );
		}		
		
		$this->last_import = time();
		$this->save();
		
		$container->setLastComment();
		$container->save();
	}

	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		\IPS\Db::i()->delete( 'blog_rss_import', array( 'rss_id=?', $this->id ) );
		return parent::delete();
	}
}