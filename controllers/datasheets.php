<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	/**
     *  This is the defaul class, shows menu of tables and acts as the starting place for the user to begind browsing.
     *
     *
     */

class DataSheets extends CI_Controller {

    public  function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->helper('form');
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
		$this->load->view('tables', $data);
    }
    
    /**
    *  Show table
    */   
    
    public function table($tableName = FALSE) {
        if($tableName && $this->db->table_exists($tableName)) {
            $this->load->model('DSTable', 'table');
            $this->table->setTable($tableName);
            $data['table'] = $this->table;
            $this->load->view('table_jqgrid', $data);
        } else {
            exit('No table selected');
        }
    }
    
    /**
     *  Show table **OLD**
     */
    
    public function table_old($tableName = FALSE) {
        if($tableName && $this->db->table_exists($tableName)) {
            //Return a single row using the standard query to get the full field details -
            //this is kinda pointless so could be optimized here!
            $this->db->limit(1, 0);
            $this->_setTableQueryLookups($tableName);
            $query = $this->db->get($tableName);
            $data['tablefields'] = $query->list_fields();
            $data['title'] = $tableName;
            $this->load->view('table_jqgrid', $data);
        } else {
            exit('No table selected');
        }
    }
    
    public function tabledata($tableName = FALSE) {
        if($tableName == FALSE) exit('Table name not specified');
        $this->load->model('DSTable', 'table');
        $this->table->setTable($tableName);
        
        // Code is taken from example on: http://www.trirand.com/jqgridwiki/doku.php?id=wiki:first_grid
        // Get the requested page. By default grid sets this to 1. 
        if(isset($_GET['page'])) {
            $data['page'] = (string) $_GET['page']; 
        } else {
            $data['page'] = (string) 1;
        }
        // get how many rows we want to have into the grid - rowNum parameter in the grid
        if(isset($_GET['rows'])) {
            $data['limit'] = (string) $_GET['rows'];
        } else {
            $data['limit'] = (string) 20;
        }
        // sorting order - at first time sortorder 
        if(isset($_GET['sord'])) {
            $data['sord'] = $_GET['sord'];
        } else {
            $data['sord'] = "asc";
        }
        // if we not pass at first time index use the first column for the index or what you want
        if(isset($_GET['sidx'])) {
            // get index row - i.e. user click to sort. At first time sortname parameter -
            // after that the index from colModel 
            $data['sidx'] = $_GET['sidx'];   
        } else {
            $data['sidx'] = "id";
        }
        
        $rows = $this->table->getData($data['limit'], $data['page'], $data['sidx'], $data['sord']);
        
        $jsonObj = array(
            'total' => $this->table->totalPages,
            'page' => $this->table->page,
            'records' => $this->table->count,
            'rows' => $rows
        );
        /*foreach($data['query']->result_array() as $row) {
            $rowObj = array('id' => $row['id']);
            $rowObj['cell'] = array_values($row);
            $jsonObj['rows'][] = $rowObj;
        }*/
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($jsonObj));
    }
    
    /**
     *  Provides the table data for the jqGrid
     */
    
    public function tabledata_old($tableName = FALSE) {
        if($tableName == FALSE) exit('Table name not specified');
        $this->load->model('DSTable', 'table');
        $this->table->setTable($tableName);
        
        // Code is taken from example on: http://www.trirand.com/jqgridwiki/doku.php?id=wiki:first_grid
        // Get the requested page. By default grid sets this to 1. 
        if(isset($_GET['page'])) {
            $data['page'] = (string) $_GET['page']; 
        } else {
            $data['page'] = (string) 1;
        }
        if(isset($_GET['rows'])) {
        // get how many rows we want to have into the grid - rowNum parameter in the grid
            $data['limit'] = (string) $_GET['rows'];
        } else {
            $data['limit'] = (string) 20;
        }
        // sorting order - at first time sortorder 
        if(isset($_GET['sord'])) {
            $data['sord'] = $_GET['sord'];
        } else {
            $data['sord'] = "asc";
        }
        // if we not pass at first time index use the first column for the index or what you want
        if(isset($_GET['sidx'])) {
            // get index row - i.e. user click to sort. At first time sortname parameter -
            // after that the index from colModel 
            $data['sidx'] = $_GET['sidx'];   
        } else {
            $data['sidx'] = "id";
        }
        
        // calculate the number of rows for the query. We need this for paging the result
        $data['count'] = (string) $this->db->count_all($tableName);
        // calculate the total pages for the query 
        if( $data['count'] > 0 && $data['limit'] > 0) { 
            $data['total_pages'] = (string) ceil($data['count']/$data['limit']); 
        } else { 
            $data['total_pages'] = (string) 0; 
        }
        // if for some reasons the requested page is greater than the total 
        // set the requested page to total page 
        if ($data['page'] > $data['total_pages']) $data['page'] = $data['total_pages'];
        // calculate the starting position of the rows 
        $data['start'] = $data['limit'] * $data['page'] - $data['limit'];
        // if for some reasons start position is negative set it to 0 
        // typical case is that the user type 0 for the requested page 
        if($data['start'] <0) $data['start'] = 0; 
        // the actual query for the grid data 
        $this->db->order_by($data['sidx'], $data['sord']);
        $this->db->limit($data['limit'], $data['start']);
        $this->_setTableQueryLookups($tableName);
        $data['query'] = $this->db->get($tableName);
        
        $jsonObj = array(
            'total' => $data['total_pages'],
            'page' => $data['page'],
            'records' => $data['count'],
            'rows' => array()
        );
        foreach($data['query']->result_array() as $row) {
            $rowObj = array('id' => $row['id']);
            $rowObj['cell'] = array_values($row);
            $jsonObj['rows'][] = $rowObj;
        }
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($jsonObj));
    }
    
    /**
     *  Builds the query with the lookup selects and joins
     */
    
    private function _setTableQueryLookups($tableName) {
        //Array to build up all the select fields
        $selectFields = array();
        //Go through each field in the central table
        $fields = $this->db->list_fields($tableName);
        foreach($fields as $field) {
            //Boolean switch to revert back to default if join can't be built
            $lookup = false;
            //If the field starts 'ref_' and ends '_id' then we'll try and join it
            if(preg_match('/ref_(.+)_id/', $field, $matches)) {
                $lookupTable = $matches[1];
                //Does the table exist, if so then we'll join to it, if not go back to default.
                if($this->db->table_exists($lookupTable)) {
                    $lookup = true;
                    $selectFields[] = $lookupTable.'.name AS '.$lookupTable.'_name';
                    $this->db->join($lookupTable, $lookupTable.'.id = '.$tableName.'.'.$field, 'left');
                } else {
                    //Go back to default just show the RAW field
                    $lookup = false;
                }
            }
            if(!$lookup) {
                // Fallback if the lookup building failed, just show the RAW field, no join needed.
                $selectFields[] = $tableName.'.'.$field;
            }
        }
        $this->db->select(implode(', ', $selectFields));
    }
    
    /**
     *  Update cell in a table
     *  id	1
     *  name	green
     *  oper	edit
     */
    
    public function editcell_old($tableName = FALSE) {
        if($tableName == FALSE) exit('Table name not specified');
        // Code is taken from example on: http://www.trirand.com/jqgridwiki/doku.php?id=wiki:first_grid
        // Get the requested page. By default grid sets this to 1. 
        if(isset($_POST['id'])) {
            $data['id'] = (string) $_POST['id']; 
        } 
        
        if(isset($_POST['oper'])) {
            $data['oper'] = (string) $_POST['oper'];
        }
        
        $editedCells = array_diff_key($_POST, array('id'=> 0, 'oper' => ''));
        switch ($data['oper']) {
            case 'edit':
                $this->db->where('id', $data['id']);
                $this->db->update($tableName, $editedCells);
            break;
            case 'add':
                $this->db->insert($tableName, $editedCells);
            break;
        }       
    }
    
    public function editcell($tableName = FALSE) {
        if($tableName == FALSE) exit('Table name not specified');
        // Code is taken from example on: http://www.trirand.com/jqgridwiki/doku.php?id=wiki:first_grid
        // Get the requested page. By default grid sets this to 1. 
        if(isset($_POST['id'])) {
            $data['id'] = (string) $_POST['id']; 
        } 
        if(isset($_POST['oper'])) {
            $data['oper'] = (string) $_POST['oper'];
        }
        $editedCells = array_diff_key($_POST, array('id'=> 0, 'oper' => ''));
        //Create table object
        $this->load->model('DSTable', 'table');
        $this->table->setTable($tableName);
        
        switch ($data['oper']) {
            case 'edit':
                $this->table->updateRow($data['id'], $editedCells);
            break;
            case 'add':
                $this->table->insertRow($editedCells);
            break;
        }       
    }
    
    /**
     *  Create a new basic table with ID and Name
     */
    
    public function createtable() {
        $this->load->dbforge();
        
        if(isset($_POST['name'])) {
            $newTableName = $_POST['name'];
        } else {
            exit('No table name');
        }
        //Sanitise the table names using the title builder
        $newTableName = url_title($newTableName, 'underscore', TRUE);
        
        //Basic template for a new table, just id ad name fields.
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'auto_increment' => TRUE
            ),
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => '255'
            )
        );
        
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table($newTableName, TRUE);
        redirect('/datasheets/table/'.$newTableName);
    }
    
    /**
     *  Add column
     */
    
    public function addcolumn($tableName = FALSE) {
        $this->load->dbforge();
        
        if($tableName && $this->db->table_exists($tableName)) {
            if(isset($_POST['name'])) {
                $newColumnName = url_title($_POST['name'], 'underscore', TRUE);
            } else {
                exit('No column name');
            }
            if(isset($_POST['type'])) {
                switch($_POST['type']) {
                    case 'text':
                        $column = array($newColumnName => array(
                                'type' => 'VARCHAR',
                                'constraint' => '255'
                            )
                        );
                        $this->dbforge->add_column($tableName, $column);
                    break;
                }
            }
            redirect('/datasheets/table/'.$tableName);          
        } else {
            exit('Tablename not specified or table doesn\'t exist');
        }
    }
    
    public function testQuery() {
        $infoDB = $this->load->database('info', TRUE);
        $query = $infoDB->query("SELECT COLUMN_NAME, TABLE_NAME FROM COLUMNS WHERE COLUMN_NAME LIKE 'ref_%_id' AND TABLE_SCHEMA = 'datasheets1'");
        foreach($query->result() as $row) {
            echo($row->COLUMN_NAME." is in ".$row->TABLE_NAME."<br />");
        }
    }
    
}

/* End of file datasheets.php */
/* Location: ./application/controllers/datasheets.php */