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
        include('/Applications/MAMP/htdocs/datasheets/application/models/dstable.php');
        include('/Applications/MAMP/htdocs/datasheets/application/models/dscolumn.php');
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
            //$this->load->model('DSTable_Mod', 'table');
            $table = new DSTable($this->db, $this->load, $tableName);
            //$table->setTable($tableName);
            $data['table'] = $table;
            $this->load->view('table_jqgrid', $data);
        } else {
            exit('No table selected');
        }
    }
    
    /**
     *  TODO: Just need to add a where clause when displaying data for sub tables
     */
    
    public function tabledata($tableName = FALSE) {
        
        if($tableName == FALSE) exit('Table name not specified');
        //$this->load->model('DSTable_Mod', 'table');
        //$this->table->setTable($tableName);
        $table = new DSTable($this->db, $this->load, $tableName);
        
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
        // if we get passed a row and lookup table, then data will be filtered with them.
        if(isset($_GET['filtercol']) && isset($_GET['filterid'])) {
            $data['filtercol'] = $_GET['filtercol'];
            $data['filterid'] = $_GET['filterid'];
        } else {
            $data['filtercol'] = false;
            $data['filterid'] = false;
        }
        
        $rows = $table->getData($data['limit'], $data['page'], $data['sidx'], $data['sord'], $data['filtercol'], $data['filterid']);
        
        $jsonObj = array(
            'total' => $table->totalPages,
            'page' => $table->page,
            'records' => $table->count,
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
        //$this->load->model('DSTable_Mod', 'table');
        //$this->table->setTable($tableName);
        $table = new DSTable($this->db, $this->load, $tableName);
        
        switch ($data['oper']) {
            case 'edit':
                $table->updateRow($data['id'], $editedCells);
            break;
            case 'add':
                $table->insertRow($editedCells);
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