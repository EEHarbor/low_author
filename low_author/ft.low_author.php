<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Low Author Fieldtype class
 *
 * @package        low_author
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @copyright      Copyright (c) 2009-2012, Low
 */

class Low_author_ft extends EE_Fieldtype {

	/**
	 * Info array
	 *
	 * @access     public
	 * @var        array
	 */
	public $info = array(
		'name'    => 'Low Author',
		'version' => '0.0.1'
	);

	// --------------------------------------------------------------------
	//  METHODS
	// --------------------------------------------------------------------

	/**
	 * Post Save settings - Pre-populates values for this field
	 *
	 * @access     public
	 * @param      array
	 * @return     void
	 */
	public function post_save_settings($data)
	{
		// -------------------------------------
		// Shortcut to field in DB
		// -------------------------------------

		$field_name = 'field_id_'.$data['field_id'];

		// -------------------------------------
		// Get channels for this field
		// -------------------------------------

		$query = $this->EE->db->select('channel_id')
		       ->from('channels')
		       ->where('field_group', $data['group_id'])
		       ->get();

		if ($query->num_rows())
		{
			$channel_ids = $this->_flatten_results($query->result_array(), 'channel_id');

			// -------------------------------------
			// Get authors screen_name + their entry ids for these channels
			// -------------------------------------

			$query = $this->EE->db->select('t.entry_id, m.member_id, m.screen_name')
			       ->from('members m')
			       ->join('channel_titles t', 'm.member_id = t.author_id')
			       ->where_in('t.channel_id', $channel_ids)
			       ->get();

			$authors = array();
			
			foreach ($query->result() AS $row)
			{
				$authors[$row->screen_name][] = $row->entry_id;
			}

			// -------------------------------------
			// Update the field with current author's screen name
			// -------------------------------------

			foreach ($authors AS $screen_name => $entry_ids)
			{
				$this->EE->db->where_in('entry_id', $entry_ids);
				$this->EE->db->update('channel_data', array($field_name => $screen_name));
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Display field in publish form
	 *
	 * @access     public
	 * @param      string    Current value for field
	 * @return     string    HTML containing input field
	 */
	public function display_field($data = '')
	{
		// -------------------------------------
		// Check if entry is new or not
		// -------------------------------------

		if (($entry_id = $this->EE->input->get_post('entry_id')) && is_numeric($entry_id))
		{
			// Get Screen Name from DB if entry is not new
			$query = $this->EE->db->select('m.screen_name')
			       ->from('members m')
			       ->join('channel_titles t', 'm.member_id = t.author_id')
			       ->where('t.entry_id', $entry_id)
			       ->get();

			$data = $query->row('screen_name');
		}
		else
		{
			// Use screen_name in session if entry is new
			// $data = $this->EE->session->userdata('screen_name');
		}

		// -------------------------------------
		// Return readonly input field with screen_name
		// -------------------------------------

		return sprintf(
			'<input type="text" name="%s" value="%s" disabled="disabled" />',
			$this->field_name,
			$data
		);
	}

	/**
	 * Make sure the screen name is saved properly
	 *
	 * @access     public
	 * @param      string    Current value for field
	 * @return     string    Screen name for author
	 */
	public function save($data = '')
	{
		if ($author = $this->EE->input->post('author'))
		{
			$query = $this->EE->db->select('screen_name')
			       ->from('members')
			       ->where('member_id', $author)
			       ->get();
			$data = $query->row('screen_name');
		}
		return $data;
	}

	// --------------------------------------------------------------------

	 /**
	 * Helper function
	 *
	 * @param      array
	 * @param      string    key of array to use as value
	 * @param      string    key of array to use as key (optional)
	 * @return     array
	 */
	private function _flatten_results($resultset, $val, $key = FALSE)
	{
		$array = array();

		foreach ($resultset AS $row)
		{
			if ($key !== FALSE)
			{
				$array[$row[$key]] = $row[$val];
			}
			else
			{
				$array[] = $row[$val];
			}
		}

		return $array;
	}

}
// END Low_author_ft class