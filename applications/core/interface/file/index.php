<?php
/**
 * @brief		Database File Handler
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		07 May 2013
 * @version		SVN_VERSION_NUMBER
 */

require_once str_replace( 'applications/core/interface/file/index.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';

try
{	
	if ( isset( \IPS\Request::i()->id ) and isset( \IPS\Request::i()->salt ) )
	{
		$where = array( 'id=? AND salt=?', \IPS\Request::i()->id, \IPS\Request::i()->salt );
	}
	else
	{
		$exploded = explode( '/', trim( urldecode( \IPS\Request::i()->file ), '/' ) );
		$filename = array_pop( $exploded );
		$container = implode( '/', $exploded );
		
		$where = array( 'container=? AND filename=?', $container, $filename );
	}
	
	$file = \IPS\Db::i()->select( '*', 'core_files', $where )->first();
		
	if ( $file['id'] )
	{
		$headers	= array_merge( \IPS\Output::getCacheHeaders( time(), 360 ), array( "Content-Disposition" => \IPS\Output::getContentDisposition( 'inline', $file['filename'] ), "X-Content-Type-Options" => "nosniff" ) );
		\IPS\Output::i()->sendOutput( $file['contents'], 200, \IPS\File::getMimeType( $file['filename'] ), $headers );
	}
}
catch ( \UnderflowException $e )
{
	\IPS\Output::i()->sendOutput( '', 404 );
}