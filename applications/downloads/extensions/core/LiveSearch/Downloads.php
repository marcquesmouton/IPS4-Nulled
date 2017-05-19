<?php
/**
 * @brief		ACP Live Search Extension
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage
 * @since		07 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\downloads\extensions\core\LiveSearch;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Live Search Extension
 */
class _downloads
{
	/**
	 * Check we have access
	 *
	 * @return	void
	 */
	public function hasAccess()
	{
		/* Check Permissions */
		return \IPS\Member::loggedIn()->hasAcpRestriction( 'downloads', 'downloads', 'categories_manage' );
	}
	
	/**
	 * Get the search results
	 *
	 * @param	string	Search Term
	 * @return	array 	Array of results
	 */
	public function getResults( $searchTerm )
	{
		if( !$this->hasAccess() )
		{
			return array();
		}

		/* Init */
		$results = array();
		$searchTerm = mb_strtolower( $searchTerm );
		
		/* Perform the search */
		$categories = \IPS\Db::i()->select(
						"*",
						'downloads_categories',
						array( "word_custom LIKE CONCAT( '%', ?, '%' ) AND lang_id=?", $searchTerm, \IPS\Member::loggedIn()->language()->id ),
						NULL,
						NULL
				)->join(
						'core_sys_lang_words',
						"word_key=CONCAT( 'downloads_category_', cid )"
					);
		
		/* Format results */
		foreach ( $categories as $category )
		{
			$category = \IPS\downloads\Category::constructFromData( $category );
			
			$results[] = \IPS\Theme::i()->getTemplate( 'livesearch', 'downloads', 'admin' )->category( $category );
		}
		
		return $results;
	}
}