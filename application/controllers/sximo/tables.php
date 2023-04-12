<?php //if (!defined('BASEPATH')) exit('No direct script access allowed');
    
class Tables extends SB_Controller{

    protected $layout = "layouts/main";

    public function __construct()
    {
        parent::__construct();
        $this->load->model('sximo/Modulemodel');
        $this->model = $this->Modulemodel;
    }

    public function index()
    {
        $this->data['tables'] = $this->model->getTableList($this->db->database); 
        $this->data['content'] = $this->load->view('sximo/tables/index',$this->data,true);  
        $this->load->view('layouts/main',$this->data);
    }

    public function tableConfig($table = null)
    {
        $columns = array();
        $info = $this->db->query("SHOW TABLE STATUS FROM `" . $this->db->database . "` WHERE `name` = '" . $table . "'")->result_array();
        if (count($info)>=1) 
        {
            $info = $info[0];

        }
        if($table != null)
        {
            $query = $this->db->query("SHOW FULL COLUMNS FROM `$table`");
            foreach($query->result_array() as $column)
            {
                $columns[] = $column;
            }
            //  print_r($column);
        }
        $this->data['default'] = array('NULL','USER_DEFINED','CURRENT_TIMESTAMP');
        $this->data['tbtypes'] = array('bigint','binary','bit','blob','bool','boolean','char','date','datetime','decimal','double','enum','float','int','longblob','longtext','mediumblob','mediuminit','mediumtext','numerice','real','set','smallint','text','time','timestamp','tinyblob','tinyint','tinytext','varbinary','varchar','year');
        
        $this->data['engine'] = array('MyISAM','InnoDB');
        $this->data['info'] = $info;
                
        $this->data['columns'] = $columns;
        $this->data['table'] = $table;
        $this->data['action'] = ($table ==null ? 'sximo/tables/tables/'.$table : 'sximo/tables/tableInfo/'.$table ) ;
        $this->load->view('sximo/tables/config',$this->data);

    }

    public function tables($currtable = null )
    {
        //exit;
        $table  = $this->input->post('table_name');
        $engine = $this->input->post('engine');

        $comma = ",";
        $sql = "CREATE TABLE `" . $table . "` (\n";
        $posts = $this->input->post('fields');
        for($i=0; $i<count($posts);$i++)
        {
            $field      = $this->input->post('fields')[$i];
            if(!empty($field ))
            {
                $type       = $this->input->post('types')[$i];
                $lenght     = self::lenght($type,$this->input->post('lenghts')[$i]);
                $default    = $this->input->post('defaults')[$i];
                $null       = (isset($this->input->post('null')[$i]) ? 'NOT NULL' : '') ;
                $ai         = (isset($this->input->post('ai')[$i]) ? 'AUTO_INCREMENT' : '') ;   

                if ($null != "" and $ai =='AUTO_INCREMENT') {
                    $default = '';  
                } elseif ($null == "" && $default !='') {

                    $default = "DEFAULT '".$default."'";
                } else {     
                    if($null == 'NOT NULL')   
                    {
                        $default = " ";
                    }  else {
                        $default = " DEFAULT NULL ";
                    }           
                    
                }

                    $sql .= " `$field` $type $lenght  $null $default $ai ". ",\n";  
            }

        }
        $primarykey  = $this->input->post('key');
        if(count( $primarykey ) >=1 )
        {
            $ai = array();
            for($i=0; $i<count($posts);$i++)
            {
                if(isset($this->input->post('key')[$i]) )
                {
                    $ai[] = $this->input->post('fields')[$i]; 
                }
            }   
            
            $sql .= 'PRIMARY KEY (`'.implode('`,`', $ai).'`)'. "\n";    
        }
       
        $sql .= ") ENGINE=$engine DEFAULT CHARSET=utf8 ";

            try {

                $this->db->query( $sql );

            }catch(Exception $e){

                echo "<pre>";
                    echo $e;
                    echo "</pre>";
                exit;
                header('content-type:application/json');    
                    echo json_encode(array(
                        'status'    =>'error',
                        'message'   => $e
                        ));
            }
            header('content-type:application/json');    
                    echo json_encode(array(
                        'status'    =>'success',
                        'message'   =>''
                        ));

    }

    public function tableRemove()
    {
         //print_r($_POST);exit;
        if(!is_null($this->input->post('id')) && count($this->input->post('id')) >=1 )
        {
            $ids = $this->input->post('id');
            for($i=0; $i<count($ids);$i++)
            {
                $table = $ids[$i];
                $sql = 'DROP TABLE IF EXISTS `' . $table . '`';
                $this->db->query($sql);   
            }
            SiteHelpers::alert('success'," Table(s) has been deleted"); 
            redirect('sximo/tables'); 
        } 
        SiteHelpers::alert('error',"No Table(s) deleted !");
        redirect('sximo/tables'); 
    }

    public function tableInfo( $table )
    {
        
        $info = $this->db->query("SHOW TABLE STATUS FROM `" . $this->db->database . "` WHERE `name` = '" . $table . "'")->result_array();
        if(count($info)>=1)
        {
            $info = $info[0];

            $table_name = trim($this->input->post('table_name'));
            $engine = trim($this->input->post('engine'));

            if($table_name != $info['Name'] )
            {
                $sql = "RENAME TABLE `" . $info['Name'] . "` TO  `" . $table_name . "`";  
                try {

                    $this->db->query( $sql );

                }catch(Exception $e){
                    header('content-type:application/json');    
                    echo json_encode(array(
                        'status'    =>'error',
                        'message'   => $e
                        ));
                }               
            }
            if($engine != $info['Engine'] )
            {              
                $sql = "ALTER TABLE `" . $table_name . "` ENGINE = " . $engine;
                try {

                    $this->db->query( $sql );

                }catch(Exception $e){
                    header('content-type:application/json');    
                    echo json_encode(array(
                        'status'    =>'error',
                        'message'   => $e
                        ));
                }                 
            } 
            header('content-type:application/json');    
                    echo json_encode(array(
                        'status'    =>'success',
                        'message'   => ''
                        ));       

        }  

    }

    public function tableFieldRemove( $table,$field)
    {

        $sql = "ALTER TABLE `" . $table . "` DROP COLUMN `" . $field . "`";
        try {

            $this->db->query( $sql );

        }catch(Exception $e){

            header('content-type:application/json');    
                    echo json_encode(array(
                        'status'    =>'error',
                        'message'   => $e
                        )); 
        }

        header('content-type:application/json');    
                    echo json_encode(array(
                        'status'    =>'success',
                        'message'   => ''
                        ));
    }

    public function tableFieldEdit( $table )
    {
        //return Response::json(array('status'=>'success','message'=>''));
        $fields = $_GET;
        foreach($fields as $key=>$val)
        {
            $this->data[$key] = $val; 
        }

        $this->data['table'] = $table;
        $this->data['tbtypes'] = array('bigint','binary','bit','blob','bool','boolean','char','date','datetime','decimal','double','enum','float','int','longblob','longtext','mediumblob','mediuminit','mediumtext','numerice','real','set','smallint','text','time','timestamp','tinyblob','tinyint','tinytext','varbinary','varchar','year');

        $this->load->view('sximo/tables/field',$this->data);
    }

    public function tableFieldSave($table)
    {
        extract($_POST);

        $type       = $this->input->post('type');
        $lenght     = self::lenght($type,$this->input->post('lenght'));
        $default    = $this->input->post('default');
        $null       = (!is_null($this->input->post('null')) ? 'NOT NULL' : '') ;
        $ai         = (!is_null($this->input->post('ai')) ? 'AUTO_INCREMENT' : '') ;    

        if ($null != "" and $ai =='AUTO_INCREMENT') {
            $default = '';  
        } elseif ($null == "" && $default !='') {

                $default = "DEFAULT '".$default."'";
        } else {     
            if($null == 'NOT NULL')   
            {
                $default = "";
            }  else {
                $default = " DEFAULT NULL ";
            }           
            
        }
        $currentfield = $this->input->post('currentfield');
        if( $currentfield !='')
        {
            if($currentfield == $field )
            {
                $sql = " ALTER TABLE `" . $table . "` MODIFY COLUMN `$field` $type  $lenght   $null $default $ai ";
            }   else {
                $sql = " ALTER TABLE `" . $table . "` CHANGE  `$currentfield` `$field`  $type $lenght   $null $default $ai ";
            }

        } else {
               $sql = " ALTER TABLE `" . $table . "` ADD COLUMN `$field` $type  $lenght   $null $default $ai ";
        }      

        try {

            $this->db->query( $sql );

        }catch(Exception $e){          
            header('content-type:application/json');    
                    echo json_encode(array(
                        'status'    =>'error',
                        'message'   => $e
                        ));
        }
        header('content-type:application/json');    
                    echo json_encode(array(
                        'status'    =>'success',
                        'message'   => ''
                        ));
    }

    static function lenght( $type , $lenght)
    {
        if($lenght == '')
        {
            switch (strtolower(trim( $type))) {
                default ;
                    $lenght = '';
                    break;
                case 'bit':
                   $lenght = '(1)';
                    break;
                case 'tinyint':
                    $lenght = '(4)';
                    break;
                case 'smallint':
                    $lenght = '(6)';
                    break;
                case 'mediumint':
                   $lenght = '(9)';
                    break;
                case 'int':
                    $lenght = '(11)';
                    break;
                case 'bigint':
                   $lenght = '(20)';
                    break;
                case 'decimal':
                    $lenght = '(10,0)';
                    break;
                case 'char':
                    $lenght = '(50)';
                    break;
                case 'varchar':
                   $lenght = '(255)';
                    break;
                case 'binary':
                    $lenght = '(50)';
                    break;
                case 'varbinary':
                    $lenght = '(255)';
                    break;
                case 'year':
                    $lenght = '(4)';
                    break;

            }
            return $lenght;
        } else {
             return "( $lenght )" ;
        }       
    }

    public function mysqlEditor()
    {       
       $this->load->view('sximo/tables/editor');
    }   

    public function postMysqlEditor()
    {

        $sql = $this->input->post('statement');
         
     //  exit;
        preg_match_all( '/[\s]*(CREATE|DROP|TRUNCATE)(.*);/Usi',$sql, $sql_table );
        preg_match_all( '/[\s]*(INSERT|UPDATE|DELETE)(.*)[\s\)]+;/Usi',$sql, $sql_row );        
       
      
        try {
            
            $this->db->query( $sql );
            
        }catch(Exception $e){
            
            header('content-type:application/json');    
                    echo json_encode(array(
                        'status'    =>'error',
                        'message'   => $e
                        ));
        }

        header('content-type:application/json');    
                    echo json_encode(array(
                        'status'    =>'success',
                        'message'   => ''
                        ));
    } 


}