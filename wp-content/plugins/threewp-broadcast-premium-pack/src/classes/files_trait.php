<?php

namespace threewp_broadcast\premium_pack\classes;

/**
	@brief		File handling functions.
	@since		2019-09-24 19:15:41
**/
trait files_trait
{
	/**
		@brief		Copy source dir to target dir.
		@details	Thank you https://stackoverflow.com/questions/5707806/recursive-copy-of-directory
		@since		2019-07-04 21:46:26
	**/
	public function copy_recursive( $source, $dest )
	{
		mkdir( $dest, 0755, true );
		foreach ( $iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $source, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST ) as $item )
		{
			if ($item->isDir())
				mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
			else
				copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
		}
	}
}
