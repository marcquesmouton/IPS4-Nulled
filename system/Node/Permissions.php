<?php
/**
 * @brief		Permissions Interface for Nodes
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		2 Aug 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Node;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Permissions Interface for Nodes
 *
 * @note	Node classes will gain special functionality by implementing this interface
 */
interface Permissions
{
}