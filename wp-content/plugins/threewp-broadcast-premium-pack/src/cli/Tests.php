<?php

namespace threewp_broadcast\premium_pack\cli;

use Exception;
use WP_CLI;

/**
	@brief		Various tests of the pack add-ons.
	@since		2018-11-04 12:23:17
**/
class Tests
{
	/**
		@brief		Execute all tests.
		@since		2018-11-04 12:23:31
	**/
	public function execute()
	{
		$this->test_bulk_cloner();
	}

	/**
		@brief		Test the bulk cloner.
		@since		2018-11-04 12:23:48
	**/
	public function test_bulk_cloner()
	{
		// Check the generate_new_site_domain_data function.
		// domain is always without slashes
		// path should begin and end with a slash
		$domains =
		[
			[
				'old_url' => 'https://www.ariel.ac.il/wp/spokesperson-communication',
				'new_url' => 'https://www.ariel.ac.il/wp/spokesperson-communication-clone',
				'domain' => 'www.ariel.ac.il',
				'url' => 'https://www.ariel.ac.il/wp/spokesperson-communication-clone',
				'path' => '/wp/spokesperson-communication-clone/',
			],
			[
				'old_url' => 'http://localhost/',
				'new_url' => 'http://new.domain/',
				'domain' => 'new.domain',
				'url' => 'http://new.domain',
				'path' => '/',
			],
			[
				'old_url' => 'http://localhost/',
				'new_url' => 'http://localhost/wp',
				'domain' => 'localhost',
				'url' => 'http://localhost/wp',
				'path' => '/wp/',
			],
			[
				'old_url' => 'http://my.test/123/',
				'new_url' => 'http://anotherdomain.com',
				'domain' => 'anotherdomain.com',
				'url' => 'http://anotherdomain.com',
				'path' => '/',
			],
			[
				'old_url' => 'http://10.0.1',
				'new_url' => 'http://192.168.0.1',
				'domain' => '192.168.0.1',
				'url' => 'http://192.168.0.1',
				'path' => '/',
			],
			[
				'old_url' => 'http://10.0.1',
				'new_url' => 'http://192.168.0.1/subdir',
				'domain' => '192.168.0.1',
				'url' => 'http://192.168.0.1/subdir',
				'path' => '/subdir/',
			],
		];
		foreach( $domains as $domain )
		{
			$domain = (object) $domain;
			$new_data = \threewp_broadcast\premium_pack\bulk_cloner\Bulk_Cloner::generate_new_site_domain_data( $domain->old_url, $domain->new_url );
			if ( $new_data->domain != $domain->domain )
				throw new Exception( 'Domain mismatch' );
			if ( $new_data->url != $domain->url )
				throw new Exception( 'URL mismatch' );
			if ( $new_data->path != $domain->path )
				throw new Exception( 'Path mismatch' );
			WP_CLI::line( sprintf( 'test_bulk_cloner: PASS %s -> %s', $domain->old_url, $domain->new_url ) );
		}
	}
}
