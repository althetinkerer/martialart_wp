<?php

namespace threewp_broadcast\premium_pack\user_blog_settings;

use threewp_broadcast\premium_pack\user_blog_settings\actions;
use threewp_broadcast\premium_pack\user_blog_settings\db\modification;

/**
	@brief		All summary related methods.
	@details	Splitting out code.
	@since		2015-02-04 19:03:52
**/
trait trait_summarize
{
	/**
		@brief		Show the summary view.
		@since		2015-02-01 08:14:15
	**/
	public function admin_menu_summary()
	{
		$r = '';
		$table = $this->table();

		$row = $table->head()->row();
		$row->th( 'setting' )->text( 'Setting' );
		$row->th( 'modifications' )->text( 'Modifications' );

		$action = new actions\summarize_modifications();
		$action->set_modifications( $this->get_modifications() );
		$action->execute();

		$r .= $this->p( __( 'The tabs below show a combined view of all of the settings modified by all of the modifications.', 'threewp_broadcast' ) );
		$r .= $action->get_html();
		$r .= $this->include_jquery_ready( __DIR__ . '/js/summarize_modifications.js' );
		echo $r;
	}

	/**
		@brief		Helper method to summarize just the display options.
		@since		2015-02-04 19:09:47
	**/
	public function summarize_display_settings( $action )
	{
		// Our first summary is for the displays.
		$index = new summary_index();
		foreach( static::get_modification_display_settings() as $setting => $ignore )
		{
			$index->key( $setting )->key( 'yes' );
			$index->key( $setting )->key( 'no' );
			foreach( $action->modifications as $modification )
			{
				static::normalize_modification( $modification );
				$data = $modification->data;
				$value = $data->$setting ? 'yes' : 'no';
				$index->key( $setting )->key( $value )->add_modification( $modification );
			}
		}

		$index->trim();

		$table = $this->table();
		$row = $table->head()->row();
		$row->th( 'display_setting' )->text( 'Display setting' );
		$row->th( 'value' )->text( 'Value' );
		$row->th( 'modifications' )->text( 'Modifications' );

		foreach( $index as $setting => $values )
		{
			switch( $setting )
			{
				case 'display_broadcast_columns':
					$setting_name = 'Display broadcast columns';
				break;
				case 'display_broadcast_menu':
					$setting_name = 'Display broadcast menu';
				break;
				case 'display_broadcast_meta_box':
					$setting_name = 'Display broadcast meta box';
				break;
			}
			$row = $table->body()->row();
			$td = $row->td( 'display_setting' )
				->rowspan( $values->item_subcount() )
				->text( $setting_name );

			foreach( $values as $value => $value_index )
			{
				$row->td( 'value' )->text( $value );
				$row->td( 'modifications' )->text( $value_index->get_modifications() );
				$row = $table->body()->row();
			}
		}

		$action->add_html( 'Display settings', $table . '' );
	}

	/**
		@brief		Summarize the post bulk actions.
		@since		2015-02-04 19:11:00
	**/
	public function summarize_post_bulk_actions( $action )
	{
		$bulk_actions = new \threewp_broadcast\actions\get_post_bulk_actions();
		$bulk_actions->ubs_editing_modification = true;
		$bulk_actions->execute();
		// We need to sort the actions.
		$bulk_actions->actions->sort_by( function( $item )
		{
			return $item->name;
		} );

		$index = new summary_index();

		foreach( $bulk_actions->actions as $ignore => $bulk_action )
		{
			$id = $bulk_action->id;
			$name = $bulk_action->name;
			foreach( $action->modifications as $modification )
			{
				foreach( [
					'Visible' => true,
					'Hidden' => false,
				] as $visibility => $value )
				$invisible = $modification->data->hide_post_bulk_actions->has( $id );
				if ( $value && ! $invisible)
					$index->key( $name )->key( $visibility )->add_modification( $modification );
				if ( ! $value && $invisible)
					$index->key( $name )->key( $visibility )->add_modification( $modification );
			}
		}

		$index->trim();

		$table = $this->table();
		$row = $table->head()->row();
		$row->th( 'bulk_action' )->text( 'Bulk action' );
		$row->th( 'visiblity' )->text( 'Visibility' );
		$row->th( 'modifications' )->text( 'Modifications' );

		foreach( $index as $key => $values )
		{
			foreach( $values as $value => $value_index )
			{
				$row = $table->body()->row();

				$td = $row->td( 'bulk_action' )
					->rowspan( $values->item_subcount() )
					->text( $key );

				foreach( $values as $value => $value_index )
				{
					$row->td( 'visibility' )->text( $value );
					$row->td( 'modifications' )->text( $value_index->get_modifications() );
					$row = $table->body()->row();
				}
			}
		}

		$action->add_html( 'Post bulk actions', $table );
	}

	/**
		@brief		Summarize the post meta box settings.
		@since		2015-02-04 19:11:20
	**/
	public function summarize_post_meta_box( $action )
	{
		$post = $this->fake_a_post();
		$meta_box_form = $this->form2();
		$meta_box_data = ThreeWP_Broadcast()->create_meta_box( $post );
		$meta_box_data->form = $meta_box_form;

		// Allow all modules to modify the box
		$prepare_action = new \threewp_broadcast\actions\prepare_meta_box;
		$prepare_action->meta_box_data = $meta_box_data;
		$prepare_action->execute();

		$checkbox_actions = static::get_meta_box_checkbox_options();
		$checkbox_actions = array_flip( $checkbox_actions );
		$select_actions = static::get_meta_box_select_options();
		$select_actions = array_flip( $select_actions );

		$index = new summary_index();
		foreach( $meta_box_form->inputs as $input )
		{
			$input_class = get_class( $input );
			$modification_name = modification::input_id( $input );
			foreach( $action->modifications as $modification )
			{
				$value = $modification->data->meta_box_modifications->get( $modification_name, '' );
				switch( $input_class )
				{
					case 'plainview\\sdk_broadcast\\form2\\inputs\\checkbox':
						if ( $value != '' )
							$value = $checkbox_actions[ $value ];
					break;
					case 'plainview\\sdk_broadcast\\form2\\inputs\\checkboxes':
						foreach( $input->inputs as $checkbox_name => $checkbox_input )
						{
							$modification_name = modification::input_id( $checkbox_input );
							$value = $modification->data->meta_box_modifications->get( $modification_name, '' );
							$key = sprintf( '%s: %s',
								$input->get_label()->content,
								$meta_box_form::unfilter_text( $checkbox_input->get_label()->content )
							);

							if ( strlen( $value ) < 1 )
								continue;
							$value = $checkbox_actions[ $value ];
							$index->key( $key )
								->key( $value )
								->add_modification( $modification );
						}
						$value = null;
					break;
					case 'plainview\\sdk_broadcast\\form2\\inputs\\select':
						$select_ubs_setting = $modification_name . '_ubs_setting';
						if ( $value != '' )
						{
							$value = $modification->data->meta_box_modifications->get( $select_ubs_setting, '' );
							$value = $select_actions[ $value ];
							$select_value = $modification->data->meta_box_modifications->get( $modification_name, '' );
							if ( $select_value != '' )
								$value = sprintf( '%s: %s', $value, $select_value );
						}
					break;
				}
				if ( strlen( $value ) < 1 )
					continue;
				$key = $input->get_label()->content;
				$index->key( $key )
					->key( $value )
					->add_modification( $modification );
			}
		}

		$table = $this->table();
		$row = $table->head()->row();
		$row->th( 'post_meta_setting' )->text( 'Setting' );
		$row->th( 'value' )->text( 'Value' );
		$row->th( 'modifications' )->text( 'Modifications' );

		foreach( $index as $key => $values )
		{
			foreach( $values as $value => $value_index )
			{
				$row = $table->body()->row();

				$td = $row->td( 'post_meta_setting' )
					->rowspan( $values->item_subcount() )
					->text( $key );

				foreach( $values as $value => $value_index )
				{
					$row->td( 'value' )->text( $value );
					$row->td( 'modifications' )->text( $value_index->get_modifications() );
					$row = $table->body()->row();
				}
			}
		}

		$action->add_html( 'Meta box', $table );
	}

	/**
		@brief		Summarize the given modifications.
		@since		2015-02-03 21:18:21
	**/
	public function threewp_broadcast_ubs_summarize_modifications( $action )
	{
		$this->summarize_display_settings( $action );
		$this->summarize_post_bulk_actions( $action );
		$this->summarize_post_meta_box( $action );
	}
}
