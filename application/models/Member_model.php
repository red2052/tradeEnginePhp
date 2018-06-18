<?php

class Member_model extends CI_Model {

	public function __construct()
	{
        parent::__construct();
        //$this->load->database();
	}	

	public function getMember($mbId, $mbPass, $sel_googleSe = 'N')
	{
		//print("model getMember\n");
		
        // $sql = `SELECT 
        //             IDX, MB_ID, MB_NAME, HPHONE, LOGINFLAG, TRADEFLAG, TRANSFERFLAG,
        //             ISMEMBER, PHONEAUTH, EMAILAUTH, GOOGLEAUTH, BANKAUTH, BANKNAME, BANKACCOUNT
        //         FROM member 
        //         WHERE 
        //             MB_ID = ? 
        //             AND PASSWD = PASSWORD(?) `;
        // $query = $this->db->query($sql, array($mbId, $mbPass));
        $sql = 'IDX, MB_ID, MB_NAME, HPHONE, LOGINFLAG, 
                TRADEFLAG, TRANSFERFLAG, ISMEMBER, PHONEAUTH, EMAILAUTH, 
                GOOGLEAUTH, BANKAUTH, BANKNAME, BANKACCOUNT '
                .($sel_googleSe == 'Y' ? ", GOOGLESE " : "");  
        $this->db->select($sql);
        $this->db->from('member');
        $this->db->where('MB_ID', $mbId);
        if(!empty($mbPass)){
            $this->db->where('PASSWD', '*'.strtoupper(hash('sha1',pack('H*',hash('sha1', $mbPass)))));
        }
                
        $query = $this->db->get();

        if ($query->num_rows() > 0)
        {
            $row = $query->row_array();
            return $row;
        }
    }

	public function getMemberList($paramArr) {
		//print("model getMember\n");
		// $sqlArr = array();
        $sql = 'IDX, MB_ID, MB_NAME, HPHONE, LOGINFLAG, TRADEFLAG, TRANSFERFLAG,
                ISMEMBER, PHONEAUTH, EMAILAUTH, GOOGLEAUTH, BANKAUTH, BANKNAME, BANKACCOUNT '
                .($paramArr['sel_googleSe'] == 'Y' ? ", GOOGLESE " : "");

        $this->db->select($sql)
                 ->from('member');              
        
        if(!empty($paramArr['hPhone'])){
            $this->db->like('HPHONE', $paramArr['hPhone']);
        }
        if(!empty($paramArr['mb_id'])){
            $this->db->like('MB_ID', $paramArr['mb_id']);
        }
        if(!empty($paramArr['mb_name'])){
            $this->db->like('MB_NAME', $paramArr['mb_name']);
        }
        if(!empty($paramArr['orderby'])){
            $this->db->order_by($paramArr['orderby'], empty($paramArr['orderByOption'])? 'ASC' : $paramArr['orderByOption']);
        } else {
            $this->db->order_by('CREATIONDATE', 'DESC');
        }                       

        $query = $this->db->query($sql, $sqlArr);
        if ($query->num_rows() > 0)
        {
            return $query->result_array();
        }
    }    
    
 
}