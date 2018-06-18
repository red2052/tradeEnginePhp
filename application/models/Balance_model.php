<?php

class Balance_model extends CI_Model {

	public function __construct()
	{
        parent::__construct();
	}	

	public function getBalance($mbIdx, $coin)
	{
        $sql = " SELECT "
              ." IDX, MB_IDX, COIN, BALANCE "
              ." FROM wallet "
              ." WHERE "
              ." MB_IDX = ? "
              ." AND COIN = ? ";
        $query = $this->db->query($sql, array($mbIdx, $coin));
        if ($query->num_rows() > 0)
        {
            $row = $query->row_array();
            return $row;
        } else {
            return "";
        }
    }
}