<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	/**
     *  This is the defaul class, shows menu of tables and acts as the starting place for the user to begind browsing.
     *
     *
     */

class DataSheers extends CI_Controller {

    public  function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
    }
    
    /**
     *  Show a simple menu, just display a list of tables in the Databse, simple.
     */
    
	public function index()
	{
        $data['tables'] = $this->db->list_tables();
		$this->load->view('contents', $data);
	}
    
    /**
     *  List tables
     */
    
    public function tables() {
        $data['tables'] = $this->db->list_tables();
		$this->load->view('contents', $data);
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */