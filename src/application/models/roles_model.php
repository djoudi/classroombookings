<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Classroombookings. Hassle-free resource booking for schools. <http://classroombookings.com/>
 * Copyright (C) 2006-2011 Craig A Rodway <craig.rodway@gmail.com>
 *
 * This file is part of Classroombookings.
 * Classroombookings is licensed under the Affero GNU GPLv3 license.
 * Please see license-classroombookings.txt for the full license text.
 */

class Roles_model extends School_Model
{

	protected $_table = 'roles';		// DB table
	protected $_sch_key = 'r_s_id';		// Foreign key for school
	
	public $error;


	function __construct()
	{
		parent::__construct();
	}
	
	
	
	
	/**
	 * Get all roles ordered by weight
	 *
	 * @return object Roles
	 */
	function get_all()
	{
		$sql = 'SELECT *
				FROM roles
				' . $this->sch_sql() . '
				ORDER BY r_weight ASC, r_name ASC';
		
		return $this->db->query($sql)->result_array();
	}
	
	
	
	
	/**
	 * Add a role to the database
	 *
	 * @param array $data       Array of data for the role
	 * @return bool
	 */
	function insert($data = array())
	{
		// Get weight for new role
		$data['r_weight'] = $this->get_weight('max') + 1;
		
		return parent::insert($data);
	}
	
	
	
	
	/**
	 * Get the current min or max weight of the roles
	 *
	 * @param string        Which number (min or max) to get
	 * @return int          The weight
	 */
	function get_weight($which = 'max')
	{
		$sql['max'] = 'SELECT MAX(r_weight) AS r_weight FROM roles WHERE 1 = 1 ' . $this->sch_sql();
		$sql['min'] = 'SELECT MIN(r_weight) AS r_weight FROM roles WHERE 1 = 1 ' . $this->sch_sql();
		
		if ( ! array_key_exists($which, $sql))
		{
			return FALSE;
		}
		
		$row = $this->db->query($sql[$which])->row_array();
		return (int) $row->r_weight;
	}
	
	
	
	
	/**
	 * Assign an existing role to a user, group or department
	 *
	 * @param int $r_id		ID of role to assign
	 * @param string $e_type		Type of entity to assign (U: user, D: department, G: group)
	 * @param int $e_id		ID of the entity that the role is being assigned to
	 */
	function assign_role($r_id = 0, $e_type = '', $e_id = 0)
	{
		$table = NULL;
		
		switch ($entity_type)
		{
			case 'U': $table = 'r2u'; break;
			case 'G': $table = 'r2g'; break;
			case 'D': $table = 'r2d'; break;
		}
		
		if ( ! $table) return FALSE;
		
		log_message('debug', "Assigning role ID $r_id to $e_type ID $e_id.");
		
		$sql = "INSERT INTO `$table` VALUES (?, ?) 
				ON DUPLICATE KEY UPDATE `{$table}_r_id` = VALUES(`{$table}_r_id`)";
		
        return $this->db->query($sql, array($r_id, $e_id));
	}
	
	
	
	
	/**
	 * Unassign a role from a user, group or department
	 *
	 * @param int $r_id		ID of role to unassign
	 * @param string $e_type		Type of entity to be unassigned
	 * @param int $e_id		ID of the entity that the role is being unassigned from
	 */
	function unassign_role($r_id = 0, $e_type = '', $e_id = 0)
	{
		$table = NULL;
		$key = NULL;
		
		switch ($entity_type)
		{
			case 'U':
				$table = 'r2u';
				$key = 'u_id';
			break;
			case 'G':
				$table = 'r2g';
				$key = 'g_id';
			break;
			case 'D':
				$table = 'r2d';
				$key = 'd_id';
			break;
		}
		
		if ( ! $table) return FALSE;
		
		log_message('debug', "*UN*assigning role ID $r_id from $e_type ID $e_id.");

		$sql = "DELETE FROM `$table`
				WHERE `{$table}_r_id` = ?
				AND `{$table}_$key` = ?
				LIMIT 1";
		
		return $this->db->query($sql, array($r_id, $e_id));
	}
	
	
	
	
	/**
	 * Get the things that roles are assigned to
	 *
	 * @param int r_id		ID of single role to lookup (Optional)
	 * @return array
	 */
	function get_role_assignments($r_id = 0)
	{
		$where = NULL;
		
		if ($r_id !== 0 && is_numeric($role_id))
		{
			$where = 'WHERE r2e.r_id = ' . (int) $r_id;
		}
		
		$sql = "SELECT
					r2e.r_id,
					r2e.e_id,
					r2e.e_type,
					roles.r_weight,
					CASE
						WHEN d.d_name IS NOT NULL THEN d.name
						WHEN g.g_name IS NOT NULL THEN g.name
						WHEN u.u_username IS NOT NULL THEN u_display
					END AS name
				FROM
					v_r2e AS r2e
				LEFT JOIN
					departments d 
					ON r2e.e_id = d.d_id
					AND r2e.e_type = 'D'
				LEFT JOIN
					groups g
					ON r2e.e_id = g.g_id
					AND r2e.e_type = 'G'
				LEFT JOIN
					users u
					ON r2e.e_id = u.user_id
					AND r2e.e_type = 'U'
				LEFT JOIN
					roles
					ON r2e.r_id = roles.r_id
				$where
				AND
					roles.r_s_id = " . (int) $this->_s_id . "
				ORDER BY
					roles.weight ASC, entity_type DESC";
		
		$query = $this->db->query($sql);
		
		if ($query->num_rows() > 0)
		{
			$roles = array();
			$result = $query->result_array();
			foreach ($result as $row)
			{
				$roles[$row['r_id']][] = $row;
			}
			
			return ($role_id === 0) ? $roles : $roles[$r_id];
		}
		
		return FALSE;
	}
	
	
	
	
	/**
	 * Get all assigned roles for a user (via group/department membership or direct)
	 *
	 * @param int u_id		ID of user to get all roles for
	 * @return array 		Array of roles in weight order (highest first)
	 */
	function for_user($u_id = 0)
	{
		$u_id = (int) $u_id;
		
		$sql = "SELECT
					r2u_r_id AS r_id,
					r_name,
					r_weight,
					r2u_u_id AS e_id,
					'U' AS e_type,
					users.u_display AS entity_name
				FROM
					r2u
				LEFT JOIN
					roles
					ON r2u_r_id = r_id
				LEFT JOIN
					users
					ON r2u_u_id = u_id
				WHERE
					r2u_u_id = $u_id
				
				UNION
				
				SELECT
					r2d_r_id AS r_id,
					r_name,
					r_weight,
					r2d_d_id AS e_id,
					'D' AS e_type,
					d_name AS entity_name
				FROM
					r2d
				LEFT JOIN
					roles
					ON r2d_r_id = r_id
				LEFT JOIN
					departments
					ON r2d_d_id = d_id
				LEFT JOIN
					u2d
					ON u2d_d_id = d_id
				WHERE
					u2d_u_id = $u_id
				
				UNION
				
				SELECT
					r2g_r_id AS r_id,
					r_name,
					r_weight,
					r2g_g_id AS e_id,
					'G' AS entity_type,
					g_name AS entity_name
				FROM
					r2g
				LEFT JOIN
					roles
					ON r2g_r_id = r_id
				LEFT JOIN
					groups g
					ON r2g_g_id = g_id
				LEFT JOIN
					users
					ON g_id = u_g_id
				WHERE
					u_id = $u_id
				
				ORDER BY
					r_weight ASC";
		
		$query = $this->db->query($sql);
		
		if ($query->num_rows() > 0)
		{
			$result = $query->result_array();
			$roles = array();
			foreach ($result as $row)
			{
				$roles[$row['r_id']] = $row;
			}
			return $roles;
		}
		
		return FALSE;
	}
	
	
	
	
}

/* End of file: ./application/models/roles_model.php */