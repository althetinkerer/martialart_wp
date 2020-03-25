<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\spreadsheet;

use Exception;

/**
	@brief		Creates clones via uploads of a spreadsheet.
	@since		2017-09-25 11:33:22
**/
class Spreadsheet
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		The action name for cleaning up after ourselves.
		@since		2017-09-27 22:44:31
	**/
	public static $cleanup_action = 'broadcast_bulk_cloner_spreadsheet_cleanup';

	public function _construct()
	{
		$this->add_action( 'broadcast_bulk_cloner_admin_tabs' );
		$this->add_action( 'broadcast_bulk_cloner_spreadsheet_cleanup' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		broadcast_bulk_cloner_admin_tabs
		@since		2017-09-25 12:24:41
	**/
	public function broadcast_bulk_cloner_admin_tabs( $action )
	{
		$action->tabs->tab( 'spreadsheet' )
			->callback_this( 'spreadsheet' )
			// Page heading for tab
			->heading( __( 'Bulk Cloner Spreadsheet Clone', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Spreadsheet', 'threewp_broadcast' ) );
	}

	/**
		@brief		broadcast_bulk_cloner_spreadsheet_cleanup
		@since		2017-09-27 22:45:24
	**/
	public function broadcast_bulk_cloner_spreadsheet_cleanup()
	{
		// Clean up the export file so it doesn't get left lying around.
		$export_file_data = $this->get_export_file_data();
		$this->debug( 'Deleting %s', $export_file_data[ 'path' ] );
		@ unlink( $export_file_data[ 'path' ] );
	}

	/**
		@brief		Handle import and exporting of a spreadsheet.
		@since		2017-09-25 12:29:10
	**/
	public function spreadsheet()
	{
		$form = $this->form();
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '';

		// Export
		// ------

		$fs = $form->fieldset( 'fs_export' )
			// Fieldset label for spreadsheet export
			->label( __( 'Export', 'threewp_broadcast' ) );

		$fs->markup( 'm_export' )
			->p( __( 'Use the button below to generate a spreadsheet for the network. You can then modify and import the spreadsheet to implement your changes.', 'threewp_broadcast' ) );

		$blogs_to_export = $this->add_blog_list_input( [
			// Blog selection input description
			'description' => __( 'Select the blogs to export. If no blogs are selected, all blogs will be exported.', 'threewp_broadcast' ),
			'form' => $fs,
			// Blog selection input label
			'label' => __( 'Blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'name' => 'blogs',
			'required' => false,
		] );

		$export_button = $fs->secondary_button( 'export_spreadsheet' )
			->value( __( 'Export spreadsheet', 'threewp_broadcast' ) );

		$export_file_data = $this->get_export_file_data();

		if ( is_readable( $export_file_data[ 'path' ] ) )
		{
			$delete_export_button = $fs->secondary_button( 'delete_export_spreadsheet' )
				->value( __( 'Delete export spreadsheet', 'threewp_broadcast' ) );

			$r .= $this->info_message_box()
				->_( '<a href="%s">An export file</a>, created %s ago, is ready for download.',
					$export_file_data[ 'url' ],
					human_time_diff( filemtime( $export_file_data[ 'path' ] ), time() )
				);
		}

		// Import
		// ------

		$fs = $form->fieldset( 'fs_import' )
			// Fieldset label for spreadsheet import
			->label( __( 'Import', 'threewp_broadcast' ) );

		$fs->markup( 'm_export' )
			->p( __( 'Upload your previously exported spreadsheet here to implement your changes to the network.', 'threewp_broadcast' ) );

		$import_file = $fs->file( 'import_file' )
			->set_attribute( 'accept', '.ods, .xls' )
			->label( __( 'File to upload', 'threewp_broadcast' ) );

		$test_import = $fs->checkbox( 'test_import' )
			->description( __( 'Enable Broadcast debug mode to see what the importer will do. Debug data displayed through your choice in Broadcast debugging settings.', 'threewp_broadcast' ) )
			->label( __( 'Test mode', 'threewp_broadcast' ) );

		$import_button = $fs->primary_button( 'import' )
			// Save button
			->value( __( 'Import spreadsheet', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			if ( $import_button->pressed() )
			{
				$file = $import_file->get_post_value();
				$test = $test_import->is_checked();

				try
				{
					$this->import_file( [
						'test' => $test,
						'filename' => $file->tmp_name,
					] );

					if ( $test )
						$r = $this->info_message_box()
							->_( 'Test import complete.' );
					else
						$r = $this->info_message_box()
							->_( 'Import complete.' );
				}
				catch ( Exception $e )
				{
					$r = $this->error_message_box()
						->_( $e->getMessage() );
				}
			}

			if ( $export_button->pressed() )
			{
				$this->create_export_file( [
					'blogs' => $blogs_to_export->get_post_value(),
				] );

				// Delete this file in 6 hours automatically.
				wp_schedule_single_event( time() + ( 6 * HOUR_IN_SECONDS ), static::$cleanup_action );

				if ( is_readable( $export_file_data[ 'path' ] ) )
				{
					$delete_export_button = $fs->secondary_button( 'delete_export_spreadsheet' )
						->value( __( 'Delete export spreadsheet', 'threewp_broadcast' ) );

					$r = $this->info_message_box()
						->_( '<a href="%s">An export file</a>, created %s ago, is ready for download.',
							$export_file_data[ 'url' ],
							human_time_diff( filemtime( $export_file_data[ 'path' ] ), time() )
						);
				}
			}

			if ( isset( $delete_export_button ) )
				if ( $delete_export_button->pressed() )
				{
					unlink( $export_file_data[ 'path' ] );
					$r = $this->info_message_box()
						->_( 'The export file has been deleted!' );
				}
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Convenience method to refer to BBC.
		@since		2017-10-09 14:54:15
	**/
	public function bbc()
	{
		return broadcast_bulk_cloner();
	}

	/**
		@brief		Create the export file.
		@since		2017-09-27 23:11:29
	**/
	public function create_export_file( $options )
	{
		$options = ( object) $options;

		$export_file_data = $this->get_export_file_data();

		$blog_states = $this->bbc()->generate_blog_states( [
			'blogs' => $options->blogs,
		] );

		// And now put those blog states into a spreadsheet.
		$ss = new \PHPExcel();
		// Set properties
		$ss->getProperties()->setCreator( "Broadcast Bulk Cloner Spreadsheet" )
			->setTitle( "Export" );
		$sheet = $ss->getActiveSheet();
		$sheet->setTitle( 'Blogs' );

		$column = 'A';
		$start_column = $column;
		$row = 1;

		// Set the colors of the first, sticky row.
		$foreground_color = new \PHPExcel_Style_Color();
		$foreground_color->setRGB('ffffff');

		// Write all of the keys first.
		foreach( $blog_states->get_data_types() as $data_type )
		{
			$keys = $blog_states->get_data_keys( $data_type );

			foreach( $keys as $key )
			{
				$cr = $column . $row;
				$sheet->setCellValue( $cr , $data_type . '_' . $key );

				// Autosizing makes the text easier to read.
				$sheet->getColumnDimension( $column )
					->setAutoSize( true );

				// And styling to make the top row look nice.
				$sheet->getStyle( $cr )
					->getFont()
					->setColor( $foreground_color );
				$sheet->getStyle( $cr )->applyFromArray(
					[
						'fill' =>
						[
							'type' => \PHPExcel_Style_Fill::FILL_SOLID,
							'color' => [ 'rgb' => '097ef8' ],
						]
					] );
				$column++;
			}
		}

		$row++;

		// And now the data.
		foreach( $blog_states as $index => $blog_state )
		{
			$column = $start_column;
			foreach( $blog_states->get_data_types() as $data_type )
			{
				$keys = $blog_states->get_data_keys( $data_type );
				foreach( $keys as $key )
				{
					$cr = $column . $row;
					$value = $blog_state->collection( $data_type )->get( $key );
					$sheet->setCellValue( $cr , $value );

					if ( $data_type == 'clone' )
						if ( $key == 'status' )
						{
							$validation = $sheet->getCell( $cr )->getDataValidation();
							$validation->setType( \PHPExcel_Cell_DataValidation::TYPE_LIST );
							$validation->setErrorStyle( \PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
							$validation->setAllowBlank( false );
							$validation->setShowInputMessage( false );
							$validation->setShowDropDown( true );
							$statuses = $blog_state::get_clone_statuses();
							$statuses = implode( ',', $statuses );
							$validation->setFormula1( '"' . $statuses . '"' );
						}
					$column++;
				}
			}
			$row++;
		}

		// Requires the use of Excel. :(
		$sheet->freezePane( 'A2' );

		$objWriter = \PHPExcel_IOFactory::createWriter( $ss, 'Excel5' );
		$objWriter->save( $export_file_data[ 'path' ] );
	}

	/**
		@brief		Return the data used for the export file.
		@since		2017-09-27 22:46:22
	**/
	public function get_export_file_data()
	{
		$wp_upload_dir = wp_upload_dir();
		$export_filename = sprintf( 'Broadcast Bulk Cloner Export.%s.xls', substr( md5( AUTH_KEY . 'broadcast_bulk_cloner_spreadsheet' ), 0, 8 ) );

		$r = [
			'filename' => $export_filename,
			'path' => $wp_upload_dir[ 'basedir' ] .  '/' . $export_filename,
			'url' => $wp_upload_dir[ 'baseurl' ] .  '/' . $export_filename,
		];

		return $r;
	}

	/**
		@brief		import_file
		@since		2017-09-27 23:35:28
	**/
	public function import_file( $options )
	{
		$options = (object) $options;

		$inputFileType = \PHPExcel_IOFactory::identify( $options->filename );
		$objReader = \PHPExcel_IOFactory::createReader( $inputFileType );
		$ss = $objReader->load( $options->filename );
		$ss->setActiveSheetIndex( 0 );

		// Convert to array that is easier to work with.
		$array = $ss->getActiveSheet()->toArray( null, true, true, true );

		// The array must contain more than just the headers row.
		if ( count( $array ) < 2 )
			throw new Exception( 'The file must contain at least two rows: one for the headers and one blog.' );

		// Generate a new Blog State so that we know what data we need.
		$bs_action = $this->bbc()->new_action( 'generate_blog_state' );
		$bs_action->blog_id = 1;	// 1 always exists.
		$bs_action->execute();
		$blog_state = $bs_action->blog_state;

		$headings = array_shift( $array );
		$heading_index = array_flip( $headings );

		// Check all headings for requiredness.
		foreach( $blog_state->collection( 'expected_data' ) as $data_type => $keys )
		{
			foreach( $keys as $key => $required )
			{
				if ( ! $required )
					continue;
				$array_key = $data_type . '_' . $key;
				if ( ! in_array( $array_key, $headings ) )
					throw new Exception( sprintf( 'Required column %s was not found in the spreadsheet!', $array_key ) );
			}
		}

		// Split the spreadsheet into blog states.
		$blog_states = $this->bbc()->new_blog_states();

		// Convert each blog array to a blog state.
		foreach( $array as $blog )
		{
			$bs = $this->bbc()->new_blog_state();

			// Go through all of the headings
			foreach( $headings as $heading_index => $heading )
			{
//				$heading_type = preg_replace( '/_.*/', '', $heading );
//				// Is this a heading type we know about?
//				if ( ! $blog_state->collection( 'data_types' )->has( $heading_type ) )
//					continue;

				// The heading type we've already checked.
				$data_type = preg_replace( '/_.*/', '', $heading );
				// Extract only the key.
				$key = preg_replace( '/^' . $data_type . '_/', '', $heading );
				$value = $blog[ $heading_index ];
				$bs->collection( $data_type )->set( $key, $value );
			}
			$blog_states->add( $bs );
		}

		$current_blog_states = $this->bbc()->generate_blog_states( [] );

		$this->debug( '%d blogs found.', count( $blog_states ) );

		$spreadsheet_preprocess = $this->new_action( 'preprocess' );
		$spreadsheet_preprocess->blog_states = $blog_states;
		$spreadsheet_preprocess->execute();

		// Test all of the states first.
		foreach( $blog_states as $blog_state )
		{
			if ( ! $blog_state->is_processable() )
				continue;

			$this->debug( 'Testing blog %s (%d).', $blog_state->get_domain(), $blog_state->get_blog_id() );

			$process_action = $this->bbc()->new_action( 'process_blog_state' );
			$process_action->blog_state = $blog_state;
			$process_action->blog_states = $current_blog_states;
			$process_action->test = true;
			$process_action->execute();
		}

		if ( $options->test )
			return;

		// All blog states imported.
		foreach( $blog_states as $blog_state )
		{
			// Should this blog_state be processed?
			if ( ! $blog_state->is_processable() )
			{
				$this->debug( 'Blog %s (%d) does not need to be processed.', $blog_state->get_domain(), $blog_state->get_blog_id() );
				continue;
			}

			$this->debug( 'Processing blog %s (%d).', $blog_state->get_domain(), $blog_state->get_blog_id() );
			// Do a test to check that this state is valid.
			$process_action = $this->bbc()->new_action( 'process_blog_state' );
			$process_action->blog_state = $blog_state;
			$process_action->blog_states = $current_blog_states;
			$process_action->test = $options->test;
			$process_action->execute();
		}
	}
}
