<?php

class Module extends SB_Controller  {

    protected $layout = "layouts/main";
    public $module = 'module';
    
    public function __construct() {
        
        parent::__construct();
        $this->load->model('sximo/modulemodel');
        $this->model = $this->modulemodel;          
        $this->data = array(
            'pageTitle' => 'Module Management',
            'pageNote'  => 'Manage Setting COnfiguration'
        );  
        if(!$this->session->userdata('logged_in')) redirect('user/login',301);
        if($this->session->userdata('gid') !=1) redirect('dashboard',301);
            
    }
    
    public function index()
    {
        if($this->input->get('t') == 'core')
        {
            
            $this->db->where('module_type','core');
            $this->db->order_by('module_title','asc');
            $rowData =  $this->db->get('tb_module')->result();  
            $type = 'core';        
        } else {
            $this->db->where('module_type','addon');
            $this->db->order_by('module_title','asc');
            $rowData =  $this->db->get('tb_module')->result();  
            $type = 'addon';
        }        

        $this->data['type']    = $type;    
        $this->data['rowData']    = $rowData;
        $this->data['content'] = $this->load->view('sximo/module/index',$this->data, true );        
        $this->load->view('layouts/main', $this->data );            
        

    }
    
    function create()
    {

        $this->data = array(
            'pageTitle'    => 'Create New Module',
            'pageNote'    => 'Create Quick Module ',
        );          
        $this->data['tables'] = $this->model->getTableList($this->db->database);
        $this->data['content'] = $this->load->view('sximo/module/create',$this->data, true );       
        $this->load->view('layouts/main', $this->data );               
    
    }   
    
    
    function saveCreate()
    {
        $rules = array(
            array('field'   => 'module_name','label'   => 'module_name','rules'   => 'required|is_unique[tb_module.module_name]'),
            array('field'   => 'module_title','label'   => 'module_title','rules'   => 'required'),
            array('field'   => 'module_db','label'   => 'email','rules'   => 'required'),
        );  
             
        
        $this->form_validation->set_rules( $rules );
        if( $this->form_validation->run() ){
            
            $table = $this->input->post('module_db',true);    
            $primary = self::findPrimarykey( $this->input->post('module_db',true));
            
            $select = $this->input->post('sql_select',true);
            $where     = $this->input->post('sql_where',true);
            $group     = $this->input->post('sql_group',true);    

            if($this->input->post('creation') == 'manual')
            {
                if($where =="")                
                {
                    $this->session->set_flashdata('message',SiteHelpers::alert('error','SQL WHERE REQUIRED'));
                    redirect('sximo/module/create',301); 
                }
                
                try {
                    $this->db->query(  $select .' '.$where.' '.$group  );

                }catch(Exception $e){
                    // Do something when query fails. 
                    $error ='Error : '.$select .' '.$where.' '.$group ;
                    $this->session->set_flashdata('message',SiteHelpers::alert('error',$error));
                    redirect('sximo/module/create',301) ;                   
                  
                }                
                $columns = array();
                $results = $this->model->getColoumnInfo($select .' '.$where.' '.$group);
             //   echo '<pre>'; print_r($results); echo '</pre>';    exit;
                $primary_exits = '';
                foreach($results as $r)
                {
                    $Key = (isset($r['flags'][1]) && $r['flags'][1] =='primary_key'  ? 'PRI' : '');
                    if($Key !='') $primary_exits = $r['name'];
                    $columns[] = (object) array('Field'=> $r['name'],'Table'=> $r['table'],'Type'=>$r['native_type'],'Key'=>$Key); 
                }
                $primary  = ($primary_exits !='' ? $primary_exits : '');    
                
                        
                
            } else {
                $columns = $this->db->query("SHOW COLUMNS FROM ".$this->input->post('module_db'))->result();
                $select =  " SELECT {$table}.* FROM {$table} ";
                $where = " WHERE ".$table.".".$primary." IS NOT NULL";
                if($primary !='') {
                    $where     = " WHERE ".$table.".".$primary." IS NOT NULL";
                } else { $where  ='' ;}
                
            }
           // echo '<pre>'; print_r($columns); echo '</pre>';    exit;
            
            $i = 0; $rowGrid = array();$rowForm = array();
            foreach($columns as $column)
            {
                if(!isset($column->Table)) $column->Table = $table;
                if($column->Key =='PRI') $column->Type ='hidden';
                if($column->Table == $table) 
                {                
                    $form_creator = self::configForm($column->Field,$column->Table,$column->Type,$i);
                    $relation = self::buildRelation($table ,$column->Field);
                    foreach($relation as $row) 
                    {
                        $array = array('external',$row->table,$row->column);
                        $form_creator = self::configForm($column->Field,$table,'select',$i,$array);
                        
                    }
                    $rowForm[] = $form_creator;
                }    
                
                $rowGrid[] = self::configGrid($column->Field,$column->Table,$column->Type,$i);                
                $i++;
            }   

            $setting = array(
                'gridtype'        => $this->input->post('module_template'),
                'orderby'        => $primary ,
                'ordertype'        => 'asc' ,
                'perpage'        => '10'  ,
                'frozen'        => 'false',    
                
            );                                            

           // echo '<pre>'; print_r($rowGrid); echo '</pre>';    exit;
            $json_data['sql_select']     = $select;
            $json_data['sql_where']     = $where;
            $json_data['sql_group']        = $group;
            $json_data['table_db']        = $table ;
            $json_data['primary_key']    = $primary;
            $json_data['grid']    = $rowGrid ;
            $json_data['forms']    = $rowForm ;   
            $json_data['setting']    = $setting ;                                   
            //echo '<pre>'; print_r($json_data); echo '</pre>';    exit;    
              
            $data = array(
                'module_name'    => strtolower(trim($this->input->post('module_name',true))),
                'module_title'    => $this->input->post('module_title',true),
                'module_note'    => $this->input->post('module_note',true),
                'module_db'        => $this->input->post('module_db',true),    
                'module_db_key' => $primary,
                'module_type'     => 'addon',
                'module_created'     => date("Y-m-d H:i:s"),
                'module_config' => SiteHelpers::CF_encode_json($json_data),     

            );
            


            
            $this->db->insert('tb_module',$data);
            
            // Add Default permission
            $tasks = array(
                'is_global'        => 'Global',
                'is_view'        => 'View ',
                'is_detail'        => 'Detail',
                'is_add'        => 'Add ',
                'is_edit'        => 'Edit ',
                'is_remove'        => 'Remove ',
                'is_excel'        => 'Excel ',    
                
            );                    
            $groups = $this->db->get('tb_groups')->result();
            $row = $this->db->get_where('tb_module',array('module_name'=> $this->input->post('module_name')))->row();        
            if(count($row) >= 1)
            {
                
                
                foreach($groups as $g)
                {
                    $arr = array();
                    foreach($tasks as $t=>$v)            
                    {
                        if($g->group_id =='1') {
                            $arr[$t] = '1' ;
                        } else {
                            $arr[$t] = '0' ;
                        }    
                    
                    }        
                    $data = array
                    (
                        "access_data"    => json_encode($arr),
                        "module_id"        => $row->module_id,                
                        "group_id"        => $g->group_id,
                    );
                   $this->db->insert('tb_groups_access',$data);    
                }
                            
               
               redirect('sximo/module/rebuild/'.$row->module_id);        
            } else {
                 $this->session->set_flashdata('message',SiteHelpers::alert('error','Failed to create new module !'));                      
                redirect('sximo/module',301);
            }
            
                
            
        } else {
             $this->session->set_flashdata('message',SiteHelpers::alert('error','The following errors occurred'));
             redirect('sximo/module/create',301);
        }                    
    
    }
    
    function buildRelation( $table , $field)
    {

        
        $sql = "
        SELECT
            referenced_table_name AS 'table',
            referenced_column_name AS 'column'
        FROM
            information_schema.key_column_usage
        WHERE
            referenced_table_name IS NOT NULL
            AND table_schema = '".$this->db->database."'  AND table_name = '{$table}' AND column_name = '{$field}' ";
        $Q = $this->db->query($sql)->result();
        $rows = array();
        foreach($Q as $row)
        {
            $rows[] = $row;
        }

        return $rows;    

    
    }   
        
    function findPrimarykey( $table ='')
    {
        $query = "SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'";
        $primaryKey = '';
        $row = $this->db->query("SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'")->row();
        if(count($row)>=1)
        {
            $primaryKey = $row->Column_name;
        }
        return  $primaryKey;    
    }       
    
    function config( $id )
    {

        $row = $this->db->get_where('tb_module',array('module_name'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);

        $this->data['row'] = $row;            
        $this->data['module'] = 'module';
        $this->data['module_name'] = $row->module_name;
        $this->data['module_lang'] = json_decode($row->module_lang,true);  
        $config = SiteHelpers::CF_decode_json($row->module_config,true);  
        $this->data['setting'] = array(
            'gridtype'        => (isset($config['setting']) ? $config['setting']['gridtype'] : 'native'  ),
            'orderby'        => (isset($config['setting']) ? $config['setting']['orderby'] : $row->module_db_key  ),
            'ordertype'        => (isset($config['setting']) ? $config['setting']['ordertype'] : 'asc'  ),
            'perpage'        => (isset($config['setting']) ? $config['setting']['perpage'] : '10'  ),
            'frozen'        => (isset($config['setting']['frozen'])  ? $config['setting']['frozen'] : 'false'  ),
            'form-method'        => (isset($config['setting']['form-method'])  ? $config['setting']['form-method'] : 'native'  ),
            'view-method'        => (isset($config['setting']['view-method'])  ? $config['setting']['view-method'] : 'native'  ),
            'inline'        => (isset($config['setting']['inline'])  ? $config['setting']['inline'] : 'false'  ),            
        );  

        $this->data['tables']     = $config['grid'];   
        $this->data['content'] = $this->load->view('sximo/module/config',$this->data, true );       
        $this->load->view('layouts/main', $this->data );      
                                                
    }   
    
    function saveconfig($id)
    {
        
        $validation_rules = array( 
            array(
                'field' => 'module_title' ,
                'label' => 'Module Title' ,
                'rules' => 'required' ,
            ),
            array(
                'field' => 'module_id' ,
                'label' => 'Module ID' ,
                'rules' => 'required' ,
            ),
        );
                
        $this->form_validation->set_rules( $validation_rules );
        if ($this->form_validation->run() ) {
            $data = array(
                'module_title'    => $this->input->post('module_title',true), 
                'module_note'    => $this->input->post('module_note', true),
             );

            $lang = SiteHelpers::langOption();
            $language =array();
            foreach($lang as $l)
            {
                if($l['folder'] !='en'){
                    $label_lang = (isset($_POST['language_title'][$l['folder']]) ? $_POST['language_title'][$l['folder']] : ''); 
                    $note_lang = (isset($_POST['language_note'][$l['folder']]) ? $_POST['language_note'][$l['folder']] : ''); 
                    
                    $language['title'][$l['folder']] = $label_lang;    
                    $language['note'][$l['folder']] = $note_lang;        
                }    
            }
            
            $data['module_lang'] = json_encode($language);          
                            
            $id = $this->input->post('module_id',true); 
            $this->db->where(array( 'module_id' => $id ));
            $affected = $this->db->update('tb_module',$data);
            
            $this->session->set_userdata(array(
                'message' => SiteHelpers::alert('success','Module Info Has Been Save Successfull') ,
            ));
            
            redirect( 'sximo/module/config/'.$this->input->post('module_name', true ) );

        } else {
                
            $this->session->set_userdata(array(
                'message' => SiteHelpers::alert('error','The following errors occurred') ,
            ));
            redirect( 'sximo/module/config/'. $this->input->post('module_name', true ) );
                        
        }        
    }   
    
    public function savesetting()
    {
        
        $id = $this->input->post('module_id',true);
   
        $row = $this->db->get_where('tb_module',array('module_id'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);

        $config = SiteHelpers::CF_decode_json($row->module_config); 
        $setting = array(
            'gridtype'        => $this->input->post('grid-type',true) ,
            'orderby'        => $this->input->post('orderby',true) ,
            'ordertype'        => $this->input->post('ordertype',true) ,
            'perpage'        => $this->input->post('perpage',true) ,
            'frozen'        => (!is_null($this->input->post('frozen',true))  ? 'true' : 'false' ) ,
            'form-method'   => (!is_null( $this->input->post('form-method',true))  ? $this->input->post('form-method',true) : 'native' ) ,
            'view-method'        => (!is_null($this->input->post('view-method',true))  ? $this->input->post('view-method',true) : 'native' ) ,
            'inline'        => (isset($_POST['inline'])  ? 'true' : 'false' ) ,            
        
            
        );
        if(isset($config['setting'])) unset($config['setting']);

        $new_config =     array_merge($config,array("setting" => $setting));
        $data['module_config'] = SiteHelpers::CF_encode_json($new_config);

        $this->db->where('module_id',$id);
        $this->db->update('tb_module',array('module_config' => SiteHelpers::CF_encode_json($new_config)));       
        
        $this->session->set_flashdata('message',SiteHelpers::alert('success','Module Setting Has Been Save Successfull'));        
        redirect('sximo/module/config/'.$row->module_name,301);                   
        
    }  

    function sql( $id )
    {

        $row = $this->db->get_where('tb_module',array('module_name'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);
                                           
        $this->data['row'] = $row;        
        $config = SiteHelpers::CF_decode_json($row->module_config); 
        $this->data['sql_select']     = $config['sql_select'];
        $this->data['sql_where']     = $config['sql_where'];
        $this->data['sql_group']     = $config['sql_group'];            
        $this->data['module_name'] = $row->module_name;    
        $this->data['module'] = $this->module;
        $this->data['content'] = $this->load->view('sximo/module/sql',$this->data, true );      
        $this->load->view('layouts/main', $this->data );   
                            
    }   
    

    function saveSql( $id ) 
    { 

        $select = $this->input->post( 'sql_select' , true ); 
        $where   = $this->input->post( 'sql_where' , true ); 
        $group   = $this->input->post( 'sql_group' , true ); 
 
        if( FALSE ==    ($query = $this->db->query( $select .' '.$where.' '.$group ))){
        
            echo "
            error applying sql:<br><br>
            {$select} {$where} {$group}<br><br>
            <input type='button' onclick='history.back()' value='Back' class='btn btn-success' >
            ";
 
        } 
        
        
        $query = $this->db->get_where( 'tb_module', array( 'module_name' => $id ));
        $row = $query->result();
        
        if(count($row) <= 0){ 
        
            $this->session->flashdata(
                array(
                  'message' => SiteHelpers::alert('error','Can not find module') ,
                )
            );
        
            redirect( $this->module);
 
        } 
 
        $row = $row[0]; 
        $config = SiteHelpers::CF_decode_json($row->module_config); 
 
        $this->data['row'] = $row; 
        
        $query = $this->db->query( $select .' '.$where.' '.$group );
        $res = $query->result_array();


        $columns = $this->model->getColoumnInfo(); 
        $columns = json_decode( json_encode( $columns ), true  );
        
        $i =0;$form =array(); $grid = array(); 
        
        foreach($columns as $field) 
        { 
            $name = $field['name']; $alias = $field['table']; 
            $grids =  $this->configGrid( $name , $alias , '' ,$i); 

            foreach($config['grid'] as $g) 
            { 
                if(!isset($g['type'])) $g['type'] = 'text'; 
                if($g['field'] == $name && $g['alias'] == $alias) 
                { 
                    $grids = $g; 
                } 
            } 
            $grid[] = $grids ; 
 
            if($row->module_db == $alias ) 
            { 
                $forms = $this->configForm($name,$alias,'text',$i); 
                foreach($config['forms'] as $f) 
                { 
                    if($f['field'] == $name && $f['alias'] == $alias) 
                    { 
                        $forms = $f; 
                    } 
                } 
                $form[] = $forms ; 
            } 
 
 
             $i++; 
        } 

        //echo '<pre>';print_r($grid); echo '</pre>'; exit; 
            // Remove Old Grid 
            unset($config["forms"]); 
            // Remove Old Form 
            unset($config["grid"]); 
            // Remove Old Query 
            unset($config["sql_group"]); 
            unset($config["sql_select"]); 
            unset($config["sql_where"]); 
 
            // Inject New Grid 
            $new_config = array( 
                "sql_select"         => $select , 
                "sql_where"         => $where , 
                "sql_group"         => $group, 
                "grid"               => $grid, 
                "forms"          => $form 
            ); 
 
        $config =    array_merge($config,$new_config); 
       
        $this->db->where( array( 'module_id'=> $row->module_id ));
        $affected = $this->db->update('tb_module', array('module_config' => SiteHelpers::CF_encode_json($config)) );
    
        
        $this->session->flashdata(array(
          'message' => SiteHelpers::alert('success','SQL Has Changed Successful.'),
        ));
        
        redirect( 'sximo/module/sql/'.$row->module_name );
 
    } 
        
        
    function table( $id )
    {

        $row = $this->db->get_where('tb_module',array('module_name'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);
                                            
        $this->data['row'] = $row;    
        $config = SiteHelpers::CF_decode_json($row->module_config); 
        $this->data['tables']     = $config['grid'];
                        
        $this->data['module'] = $this->module;
        $this->data['module_name'] = $row->module_name;
        $this->data['content'] = $this->load->view('sximo/module/table',$this->data, true );        
        $this->load->view('layouts/main', $this->data ); 
                            
    }   
    
    public function saveTable()
    {
        $id = $this->input->post('module_id');
        $row = $this->db->get_where('tb_module',array('module_id'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);  
        
        $config = SiteHelpers::CF_decode_json($row->module_config); 
         $lang = SiteHelpers::langOption();
        $grid = array();
        $total = count($_POST['field']);
        extract($_POST);
        for($i=1; $i<= $total ;$i++) {    
            $language =array();
            foreach($lang as $l)
            {
                if($l['folder'] !='en'){
                    $label_lang = (isset($_POST['language'][$i][$l['folder']]) ? $_POST['language'][$i][$l['folder']] : ''); 
                    $language[$l['folder']] =$label_lang;        
                }    
            }            
            $grid[] = array(
                'field'        => $field[$i],
                'alias'        => $alias[$i],
                'language'    => $language,
                'label'        => $label[$i],
                'view'        => (isset($view[$i]) ? 1 : 0 ),
                'detail'    => (isset($detail[$i]) ? 1 : 0 ),
                'sortable'    => (isset($sortable[$i]) ? 1 : 0 ),
                'search'    => (isset($search[$i]) ? 1 : 0 ) ,
                'download'    => (isset($download[$i]) ? 1 : 0 ),
                'frozen'    => (isset($frozen[$i]) ? 1 : 0 ),
                'width'        => $width[$i],
                'align'        => $align[$i],
                'sortlist'    => $sortlist[$i],
                'conn'    =>     array(
                            'valid'     => $conn_valid[$i],
                            'db'        => $conn_db[$i],
                            'key'        => $conn_key[$i],
                            'display'    => $conn_display[$i]
                ),
                'attribute'    => array(
                    'hyperlink'    => array(
                            'active'        => (isset($attr_link_active[$i]) ? 1 : 0 ) ,
                            'link'            => $attr_link[$i],
                            'target'        => (isset($attr_target[$i]) ? $attr_target[$i] : '') ,
                            'html'            => $attr_link_html[$i],
                        ),
                    'image'        => array(
                            'active'        => (isset($attr_image_active[$i]) ? 1 : 0 ),
                            'path'            => $attr_image[$i],
                            'size_x'        => $attr_image_width[$i],
                            'size_y'        => $attr_image_height[$i],
                            'html'            => $attr_image_html[$i],
                        ),
                    'formater'        => array(
                            'active'        => (isset($attr_formater_active[$i]) ? 1 : 0 ),
                            'value'            => (isset($attr_formater_value[$i]) ? $attr_formater_value[$i] : '' ),
                        )                      
                )                     
            );
            
        }

        unset($config["grid"]);
        $new_config =     array_merge($config,array("grid" => $grid));
        $data['module_config'] = SiteHelpers::CF_encode_json($new_config);
        
       // echo '<pre>'; print_r($new_config); echo '</pre>';    exit;
        
        $this->db->where('module_id',$id);
        $this->db->update('tb_module',array('module_config' => SiteHelpers::CF_encode_json($new_config)));       
        
        $this->session->set_flashdata('message',SiteHelpers::alert('success','Module Table Has Been Save Successfull'));        
        redirect('sximo/module/table/'.$row->module_name,301);     
        
        
    }    
    
    
    function form( $id )
    {
    
        $row = $this->db->get_where('tb_module',array('module_name'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);
                                            
        $this->data['row'] = $row;    
        $config = SiteHelpers::CF_decode_json($row->module_config); 
        
        $this->data['forms']     = $config['forms'];    
        $this->data['form_column'] = (isset($config['form_column']) ? $config['form_column'] : 1 );        
        $this->data['module'] = $this->module;
        $this->data['module_name'] = $row->module_name;
        $this->data['content'] = $this->load->view('sximo/module/form',$this->data, true );     
        $this->load->view('layouts/main', $this->data ); 
                           
    }   
    
    
    function saveForm()
    {
        
        $id = $this->input->post('module_id');
        $row = $this->db->get_where('tb_module',array('module_id'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);                              
                                
        $this->data['row'] = $row;    
        $config = SiteHelpers::CF_decode_json($row->module_config); 
        $lang = SiteHelpers::langOption();
        $this->data['tables']     = $config['grid'];
        $total = count($_POST['field']);
        extract($_POST);    
        $f = array();
        for($i=1; $i<= $total ;$i++) {        

            $language =array();  
            foreach($lang as $l)
            {
                if($l['folder'] !='en'){
                    $label_lang = (isset($_POST['language'][$i][$l['folder']]) ? $_POST['language'][$i][$l['folder']] : ''); 
                    $language[$l['folder']] =$label_lang;        
                }    
            }                
            $f[] = array(
                "field"         => $field[$i],
                "alias"         => $alias[$i],
                "language"         => $language,
                "label"         => $label[$i],
                'form_group'    => $form_group[$i],
                'required'        => (isset($required[$i]) ? $required[$i] : 0 ),
                'view'            => (isset($view[$i]) ? 1 : 0 ),
                'type'            => $type[$i],
                'add'            => 1,
                'size'            => '0',
                'edit'            => 1,
                'search'        => (isset($search[$i]) ? $search[$i] : 0 ),
                "sortlist"         => $sortlist[$i] ,
                'option'        => array(
                    "opt_type"                 => $opt_type[$i],
                    "lookup_query"             => $lookup_query[$i],
                    "lookup_table"             => $lookup_table[$i],
                    "lookup_key"             => $lookup_key[$i],
                    "lookup_value"            => $lookup_value[$i],
                    'is_dependency'            => $is_dependency[$i],
                    'is_multiple'            => (isset($is_multiple[$i]) ? $is_multiple[$i] : 0),
                    'lookup_dependency_key'    => $lookup_dependency_key[$i],
                    'path_to_upload'        => $path_to_upload[$i],
                    'resize_width'            => $resize_width[$i],
                    'resize_height'            => $resize_height[$i],                    
                    'upload_type'            => $upload_type[$i],
                    'tooltip'                => $tooltip[$i],
                    'attribute'                => $attribute[$i],
                    'extend_class'            => $extend_class[$i]
                    ),    
                );
        }
        
        unset($config["forms"]);
        $new_config =     array_merge($config,array("forms" => $f));
        
        $this->db->where('module_id',$id);
        $this->db->update('tb_module',array('module_config' => SiteHelpers::CF_encode_json($new_config)));       
        
        $this->session->set_flashdata('message',SiteHelpers::alert('success','Module Forms Has Been Saved Successfull'));        
        redirect('sximo/module/form/'.$row->module_name,301);  
                          
    }    
    
    
    function formdesign( $id)
    {
    
        $row = $this->db->get_where('tb_module',array('module_name'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);
                                          
        $this->data['row'] = $row;    
        $config = SiteHelpers::CF_decode_json($row->module_config); 
        $this->data['forms']     = $config['forms'];                
        $this->data['module'] = $this->module;
        $this->data['form_column'] = (isset($config['form_column']) ? $config['form_column'] : 1 );    
        if($this->input->get('block') != '')     $this->data['form_column'] = $this->input->get('block');
   
        if(!isset($config['form_layout']))
        {
            $this->data['title'] = array($row->module_name);
            $this->data['format'] = 'grid';
            $this->data['display'] = 'horizontal';
            
            
        } else {
            $this->data['title']     =    explode(",",$config['form_layout']['title']);
            $this->data['format']     =    $config['form_layout']['format'];    
            $this->data['display']     =    (isset($config['form_layout']['display']) ? $config['form_layout']['display']: 'horizontal');        
        }
        $this->data['module_name'] = $row->module_name;
        $this->data['content'] = $this->load->view('sximo/module/formdesign',$this->data, true );       
        $this->load->view('layouts/main', $this->data );   
    }   
    
    function saveFormdesign()
    {
        $id = $this->input->post('module_id');
        
        $row = $this->db->get_where('tb_module',array('module_id'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);                               
                                
        $this->data['row'] = $row;    
        $config = SiteHelpers::CF_decode_json($row->module_config); 
        $data = $_POST['reordering'];
        $data = explode('|',$data);
        $currForm = $config['forms'];
        
        foreach($currForm as $f)
        {
            $cform[$f['field']] = $f;     
        }    
    
        $i = 0; $order = 0;
        $f = array();
        foreach($data as $dat)
        {
            
            $forms = explode(",",$dat);
            foreach($forms as $form)
            {
                if(isset($cform[$form]))
                {
                    $cform[$form]['form_group'] = $i;
                    $cform[$form]['sortlist'] = $order;
                    $f[] = $cform[$form];
                }
                $order++;
            }
            $i++;
            
        }    
    //    echo '<pre>'; print_r($f); echo '</pre>';    exit;
        $config['form_column'] = count($data);
        $config['form_layout'] = array(
            'column'    => count($data),
            'title' => implode(',',$this->input->post('title')) ,
            'format' => $this->input->post('format'),
            'display' => $this->input->post('display')
            
        );
        
    //    echo '<pre>'; print_r($config); echo '</pre>';    exit;
        unset($config["forms"]);
        $new_config =     array_merge($config,array("forms" => $f));
        $data['module_config'] = SiteHelpers::CF_encode_json($new_config);
        
       // echo '<pre>'; print_r($new_config); echo '</pre>';    exit;
        
        $this->db->where('module_id',$id);
        $this->db->update('tb_module',array('module_config' => SiteHelpers::CF_encode_json($new_config)));       
        
        $this->session->set_flashdata('message',SiteHelpers::alert('success','Module Forms Has Been Saved Successfull'));        
        redirect('sximo/module/formdesign/'.$row->module_name,301);   


    }
    
    
    function editform( $id )
    {
    
        $row = $this->db->get_where('tb_module',array('module_id'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);
                                                    
        $this->data['row'] = $row;    
        $config = SiteHelpers::CF_decode_json($row->module_config); 

        $module_id = $id;
        $field_id       = $this->input->get('field'); 
        $alias          = $this->input->get('alias'); 
                
        $f = array();
        foreach( $config['forms'] as $form )
        {            
            $tooltip = '';$attribute = '';
            if(isset($form['option']['tooltip'])) $tooltip = $form['option']['tooltip'];
            if(isset($form['option']['attribute'])) $attribute = $form['option']['attribute'];
            $size = isset($form['size']) ? $form['size'] : 'span12'; 
            if($form['field'] == $field_id && $form['alias'] == $alias)
            {
                //$multiVal = explode(":",$form['option']['lookup_value']);
                $f = array(
                    "field"     => $form['field'],
                    "alias"     => $form['alias'],
                    "label"     =>  $form['label'],
                    'form_group'    =>  $form['form_group'],
                    'required'        => $form['required'],
                    'view'            => $form['view'],
                    'type'            => $form['type'],
                    'add'            => $form['add'],
                    'size'            => $size,
                    'edit'            => $form['edit'],
                    'search'        => $form['search'],
                    "sortlist"         => $form['sortlist'] ,
                    'option'        => array(
                        "opt_type"                 => $form['option']['opt_type'],
                        "lookup_query"             => $form['option']['lookup_query'],
                        "lookup_table"             => $form['option']['lookup_table'],
                        "lookup_key"             => $form['option']['lookup_key'],
                        "lookup_value"            => $form['option']['lookup_value'],
                        'is_dependency'            => $form['option']['is_dependency'],
                        'is_multiple'            => (isset($form['option']['is_multiple']) ? $form['option']['is_multiple'] : 0 ) ,
                        'lookup_dependency_key'    => $form['option']['lookup_dependency_key'],
                        'path_to_upload'        => $form['option']['path_to_upload'],
                        'upload_type'            => $form['option']['upload_type'],
                        'resize_width'            => isset( $form['option']['resize_width'])?$form['option']['resize_width']:'' ,
                        'resize_height'            => isset( $form['option']['resize_height'])? $form['option']['resize_height']:'',
                        'extend_class'            => isset( $form['option']['extend_class'])?$form['option']['extend_class']:'',
                        'tooltip'                => $tooltip ,
                        'attribute'                => $attribute,
                        'extend_class'            => isset( $form['option']['extend_class'])?$form['option']['extend_class']:''
                        ),    
                    );                
            }
        }


        $this->data['field_type_opt'] = array(
            'text'            => 'Text' ,
            'text_date'        => 'Date',
            'text_datetime'        => 'Date & Time',
            'textarea'        => 'Textarea',
            'textarea_editor'    => 'Textarea With Editor ',
            'select'        => 'Select Option',
            'radio'            => 'Radio' ,
            'checkbox'        => 'Checkbox',
            'file'            => 'Upload File',            
            'hidden'        => 'Hidden',
                    
        );
        
        $this->data['tables']        = $this->model->getTableList($this->db->database);    
        $this->data['f']     = $f;    
        $this->data['module_id']     = $id;    
        
        $this->data['module'] = $this->module;
        $this->data['module_name'] = $row->module_name;
        
       $this->load->view('sximo/module/editform',$this->data);
    }  
    
    function saveField()
    {   
        $lookup_value = (is_array($this->input->post('lookup_value')) ? implode("|",array_filter($this->input->post('lookup_value'))) : '');        
        $id = $this->input->post('module_id');
        
        $row = $this->db->get_where('tb_module',array('module_id'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);
                                            
        $this->data['row'] = $row;    
        $config = SiteHelpers::CF_decode_json($row->module_config);     

        $view = 0;$search = 0;
        if(!is_null($this->input->post('view'))) $view = 1; 
        if(!is_null($this->input->post('search'))) $search = 1; 
    
        if(preg_match('/(select|radio|checkbox)/',$this->input->post('type'))) 
        {
            if($this->input->post('opt_type') == 'datalist')
            {
                $datalist = '';
                $cf_val     = $this->input->post('custom_field_val');
                $cf_display = $this->input->post('custom_field_display');
                for($i=0; $i<count($cf_val);$i++)
                {
                    $value         = $cf_val[$i];
                    if(isset($cf_display[$i])) { $display = $cf_display[$i]; } else { $display ='none';}
                    $datalist .= $value.':'.$display.'|';
                }
                $datalist = substr($datalist,0,strlen($datalist)-1);
            
            } else {
                $datalist = ''; 
            }
        }  else {
            $datalist = '';
        }
                 
        $new_field = array(
            "field"         => $this->input->post('field'),
            "alias"         => $this->input->post('alias'),
            "label"         => $this->input->post('label'),
            "form_group"     => $this->input->post('form_group'),
            'required'        => $this->input->post('required'),
            'view'            => $view,
            'type'            => $this->input->post('type'),
            'add'            => 1,
            'edit'            => 1,
            'search'        => $this->input->post('search'),
            'size'            =>     '',
            'sortlist'        => $this->input->post('sortlist'),
            'option'        => array(
                "opt_type"         =>  $this->input->post('opt_type'),
                "lookup_query"     =>  $datalist,
                "lookup_table"     =>  $this->input->post('lookup_table'),
                "lookup_key"     => $this->input->post('lookup_key'),
                "lookup_value"    =>     $lookup_value,
                'is_dependency'    =>  $this->input->post('is_dependency'),
                'is_multiple'    =>  $this->input->post('is_multiple'),
                'lookup_dependency_key'=>  $this->input->post('lookup_dependency_key'),
                'path_to_upload'=>  $this->input->post('path_to_upload'),
                'upload_type'    =>  $this->input->post('upload_type'),
                'resize_width'    =>  $this->input->post('resize_width'),
                'resize_height'    =>  $this->input->post('resize_height'),
                'tooltip'        =>  $this->input->post('tooltip'),
                'attribute'        =>  $this->input->post('attribute'),
                'extend_class'    =>  $this->input->post('extend_class')
                )            
        );
        //print_r($_POST);
        $forms = array();
        foreach($config['forms'] as $form_view)
        {
            if($form_view['field'] == $this->input->post('field') && $form_view['alias'] == $this->input->post('alias') ) 
            {
                $new_form = $new_field;        
            } else     {
                $new_form  = $form_view;
            }    
            $forms[] = $new_form ;
    
        }    
    
        
        unset($config["forms"]);
        $new_config =     array_merge($config,array("forms" => $forms));    
        
        $this->db->where('module_id',$id);
        $this->db->update('tb_module',array('module_config' => SiteHelpers::CF_encode_json($new_config)));       
        
        $this->session->set_flashdata('message',SiteHelpers::alert('success','Forms Has Changed Successful.'));        
        redirect('sximo/module/form/'.$row->module_name,301);    
    }    
        
        
        
    function permission( $id )
    {

        $row = $this->db->get_where('tb_module',array('module_name'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);
                                   
        $this->data['row'] = $row;            
        $this->data['module'] = $this->module;
        $this->data['module_name'] = $row->module_name;
        $config = SiteHelpers::CF_decode_json($row->module_config);                         
    
        $tasks = array(
            'is_global'        => 'Global ',
            'is_view'        => 'View ',
            'is_detail'        => 'Detail',
            'is_add'        => 'Add ',
            'is_edit'        => 'Edit ',
            'is_remove'        => 'Remove ',
            'is_excel'        => 'Excel ',            
        );    
        /* Update permission global / own access new ver 1.1
           Adding new param is_global
           End Update permission global / own access new ver 1.1
        */   
        if(isset($config['tasks'])) {
            foreach($config['tasks'] as $row)
            {
                $tasks[$row['item']] = $row['title'];
            }
        }
        $this->data['tasks'] = $tasks;        
        $this->data['groups'] = $this->db->get('tb_groups')->result();

        $access = array();
        foreach($this->data['groups'] as $r)        
        {
        //    $GA = $this->model->gAccessss($this->uri->rsegment(3),$row['group_id']);
            $group = ($r->group_id !=null ? "and group_id ='".$r->group_id."'" : "" );
            $GA = $this->db->query("SELECT * FROM tb_groups_access where module_id ='".$row->module_id."' $group")->row();
            if(count($GA) >=1){
                $GA = $GA;
            }
            
            $access_data = (isset($GA->access_data) ? json_decode($GA->access_data,true) : array());
            
            $rows = array();
            //$access_data = json_decode($AD,true);
            $rows['group_id'] = $r->group_id;
            $rows['group_name'] = $r->name;
            foreach($tasks as $item=>$val)
            {
                $rows[$item] = (isset($access_data[$item]) && $access_data[$item] ==1  ? 1 : 0);
            }
            $access[$r->name] = $rows;
            
            
        
        }
        //echo '<pre>';print_r($access);echo '</pre>';exit;
        $this->data['access'] = $access;                    
        $this->data['groups_access'] =$this->db->query("select * from tb_groups_access where module_id ='".$row->module_id."'")->result();
        
        $this->data['content'] = $this->load->view('sximo/module/permission',$this->data, true );       
        $this->load->view('layouts/main', $this->data );  
                               
                            
    }       


    function savePermission()
    {
    
        $id = $this->input->post('module_id');
        $row = $this->db->get_where('tb_module',array('module_id'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);  
                                  
        $this->data['row'] = $row;    
        $config = SiteHelpers::CF_decode_json($row->module_config); 
        $tasks = array(
            'is_global'        => 'Global ',
            'is_view'        => 'View ',
            'is_detail'        => 'Detail',
            'is_add'        => 'Add ',
            'is_edit'        => 'Edit ',
            'is_remove'        => 'Remove ',
            'is_excel'        => 'Excel ',            
        );    
        /* Update permission global / own access new ver 1.1
           Adding new param is_global
           End Update permission global / own access new ver 1.1
        */         
        if(isset($config['tasks'])) {
            foreach($config['tasks'] as $row)
            {
                $tasks[$row['item']] = $row['title'];
            }
        }    
        
        $permission = array();

        $this->db->where('module_id',$id);
        $this->db->delete('tb_groups_access');      
        
        $groupID = $_POST['group_id'];
        for($i=0;$i<count($groupID); $i++)  
        {
            // remove current group_access             
            $group_id = $groupID[$i];
            $arr = array();
            $id = $groupID[$i];
            foreach($tasks as $t=>$v)            
            {
                $arr[$t] = (isset($_POST[$t][$id]) ? "1" : "0" );
            
            }
            $permissions = json_encode($arr); 
            
            $data = array
            (
                "access_data"    => $permissions,
                "module_id"       => $this->input->post('module_id'),                
                "group_id"        => $groupID[$i],
            );
            $this->db->insert('tb_groups_access',$data);    
        }
        
        $this->session->set_flashdata('message',SiteHelpers::alert('success','Permission Has Changed Successful.'));        
        redirect('sximo/module/permission/'.$row->module_name,301);
    } 
    
    
    function conn( $id )
    {
        $row = $this->db->get_where('tb_module',array('module_id'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);  
                                        
        $this->data['row'] = $row;    
        $config = SiteHelpers::CF_decode_json($row->module_config); 

        $module_id = $id;
        $field_id     = $this->input->get('field'); 
        $alias         = $this->input->get('alias'); 
        $f = array();
        foreach($config['grid'] as $form)
        {
            if($form['field'] == $field_id)
            {
                
                $f = array(
                    'db'        => (isset($form['conn']['db']) ? $form['conn']['db'] : ''),
                    'key'        => (isset($form['conn']['key']) ? $form['conn']['key'] : ''),
                    'display'    => (isset($form['conn']['display']) ? $form['conn']['display'] : ''),
                    );    
            }    
        }
        
        $this->data['module_id']     = $id;    
        $this->data['f']     = $f;
        $this->data['module'] = $this->module;
        $this->data['module_name'] = $row->module_name;    
        $this->data['field_id'] = $field_id ;    
        $this->data['alias'] = $alias; 
        $this->load->view('sximo/module/conn',$this->data);
    }
    
    function saveConn()
    {
        $id = $this->input->post('module_id');
        $field_id     = $this->input->post('field_id',true); 
        $alias         = $this->input->post('alias',true);         
        $row =  $this->db->get_where('tb_module',array('module_id'=> $id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);                             
                                
        $this->data['row'] = $row;
        $fr = array();    
        $config = SiteHelpers::CF_decode_json($row->module_config); 
        foreach($config['grid'] as $form)
        {
            if($form['field'] == $field_id && $form['alias'] == $alias )
            {
                if($this->input->post('db') !='')
                {   
                                   
                    $value = implode("|",$this->input->post('display',true));
                    $form['conn'] = array(
                        'valid'        => '1',
                        'db'        => $this->input->post('db',true),
                        'key'        => $this->input->post('key',true),
                        'display'    => implode("|",array_filter($this->input->post('display',true))),
                        );                        
                } else {
                    
                    $form['conn'] = array(
                        'valid'        => '0',
                        'db'        => '',
                        'key'        => '',
                        'display'    => '',
                        );    

                }
                $fr[] =  $form;    
            }    else {
                $fr[] =  $form;
            }
        }    
        unset($config["grid"]);
        $new_config =     array_merge($config,array("grid" => $fr));
        
      // echo '<pre>'; print_r($new_config); echo '</pre>';    exit;

        $this->db->where('module_id',$id);
        $this->db->update('tb_module',array('module_config' => SiteHelpers::CF_encode_json($new_config)));       
        
        $this->session->set_flashdata('message',SiteHelpers::alert('success','Module Table Connection Has Been Save Successfull'));        
        redirect('sximo/module/table/'.$row->module_name,301);         
       

    }   
    
    function build( $id )
    {
    
        $row = $this->db->get_where('tb_module',array('module_name'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);      
    
        $this->data['module'] = $this->module;
        $this->data['module_name'] = $id;
        $this->data['module_id'] = $row->module_id;
        $this->load->view('sximo/module/build', $this->data );  
                                    
    }   
    
    function dobuild( $id )
    {
        
        $c = (isset($_POST['controller']) ? 'y' : 'n');
        $m = (isset($_POST['model']) ? 'y' : 'n');
        $g = (isset($_POST['grid']) ? 'y' : 'n');
        $f = (isset($_POST['form']) ? 'y' : 'n');
        $v = (isset($_POST['view']) ? 'y' : 'n');
     
        //return redirect('')
        $url ='sximo/module/rebuild/'.$id."?rebuild=y&c={$c}&m={$m}&g={$g}&f={$f}&v={$v}";
        
        redirect($url,301);
    }   
    
    function configGrid ( $field , $alias , $type, $sort ) {
        $grid = array ( 
            "field"     => $field,
            "alias"     => $alias,
            "label"     => ucwords(str_replace('_',' ',$field)),
            "language"    => array(),
            "search"     => '1' ,
            "download"     => '1' ,
            "align"     => 'left' ,
            "view"         => '1' ,
            "detail"      => '1',
            "sortable"     => '1',
            "frozen"     => '0',
            'hidden'    => '0',            
            "sortlist"     => $sort ,
            "width"     => '100',
            "conn"         => array('valid'=>'0','db'=>'','key'=>'','display'=>''),
            'attribute'    => array(
                'hyperlink'    => array(
                        'active'            => '0',
                        'link'            => '',
                        'target'        => '',
                        'html'            => '',
                    ),
                'image'        => array(
                        'active'            => '0',
                        'path'            => '',
                        'size_x'        => '',
                        'size_y'        => '',
                        'html'            => '',
                    )
            )              
        );     
        return $grid;
    
    }    
 
    function configForm( $field , $alias, $type , $sort, $opt = array()) {
        
        $opt_type = ''; $lookup_table =''; $lookup_key ='';
        if(count($opt) >=1) {
            $opt_type = $opt[0]; $lookup_table = $opt[1]; $lookup_key = $opt[2];
        }
        
    
        $forms = array(
            "field"     => $field,
            "alias"     => $alias,
            "label"     => ucwords(str_replace('_',' ',$field)),
            "language"    => array(),
            'required'        => '0',
            'view'            => '1',
            'type'            => self::configFieldType($type),
            'add'            => '1',
            'edit'            => '1',
            'search'        => '1',

            'size'            => 'span12',
            "sortlist"     => $sort ,
            'form_group'    => '',
            'option'        => array(
                "opt_type"                 => $opt_type,
                "lookup_query"             => '',
                "lookup_table"             =>     $lookup_table,
                "lookup_key"             =>  $lookup_key,
                "lookup_value"            => $lookup_key,
                'is_dependency'            => '',
                'lookup_dependency_key'    => '',
                'path_to_upload'        => '',
                'upload_type'        => '',
                'tooltip'        => '',
                'attribute'        => '',
                'extend_class'        => ''
                )
            );
        return $forms;    
    
    } 
        
    function configFieldType( $type )
    {
        switch($type)
        {
            default: $type = 'text'; break;
            case 'timestamp'; $type = 'text_datetime'; break;
            case 'datetime'; $type = 'text_datetime'; break;
            case 'string'; $type = 'text'; break;
            case 'int'; $type = 'text'; break;
            case 'text'; $type = 'textarea'; break;
            case 'blob'; $type = 'textarea'; break;
            case 'select'; $type = 'select'; break;
        }
        return $type;
    
    }
    
    function rebuild( $id = 0)
    {
        $row = $this->db->get_where('tb_module',array('module_id'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);      
                                   
        $this->data['row'] = $row;    
        $config = SiteHelpers::CF_decode_json($row->module_config); 
        $class         = $row->module_name;
        $ctr = ucwords($row->module_name);
        $path         = $row->module_name;
        // build Field entry 
        $f = '';
        $req = '';
        
        $codes = array(
            'controller'        => ucwords($class),
            'class'              => $class,
            'fields'            => $f,
            'required'          => $req,
            'table'             => $row->module_db ,
            'title'             => $row->module_title ,
            'note'              => $row->module_note ,
            'key'               => $row->module_db_key,
            'sql_select'        => $config['sql_select'],
            'sql_where'         => $config['sql_where'],
            'sql_group'         => $config['sql_group'],
        );    
                                         
        if(!isset($config['form_layout'])) 
        $config['form_layout'] = array('column'=>1,'title'=>$row->module_title,'format'=>'grid','display'=>'horizontal');
            
        $codes['form_javascript'] = SiteHelpers::toJavascript($config['forms'],$path,$class);
        $codes['form_entry'] = SiteHelpers::toForm($config['forms'],$config['form_layout']);
        $codes['form_display'] = (isset($config['form_layout']['display']) ? $config['form_layout']['display'] : 'horizontal');
        $codes['form_view'] = SiteHelpers::toView($config['grid']);
    

            $setting = array(
            'gridtype'        => (isset($config['setting']) ? $config['setting']['gridtype'] : 'native'  ),
            'orderby'        => (isset($config['setting']) ? $config['setting']['orderby'] : $row->module_db_key  ),
            'ordertype'        => (isset($config['setting']) ? $config['setting']['ordertype'] : 'asc'  ),
            'perpage'        => (isset($config['setting']) ? $config['setting']['perpage'] : '10'  ),
        );  

        if($setting['gridtype'] =='ajax')
        {

            $controller = file_get_contents(  'application/views/sximo/module/template/ajax/controller.tpl' );
            $grid       = file_get_contents(  'application/views/sximo/module/template/ajax/index.tpl' );                
            $view       = file_get_contents(  'application/views/sximo/module/template/ajax/view.tpl' );
            $form       = file_get_contents(  'application/views/sximo/module/template/ajax/form.tpl' );
            $model      = file_get_contents(  'application/views/sximo/module/template/ajax/model.tpl' );    
            $table      = file_get_contents(  'application/views/sximo/module/template/ajax/table.tpl' );
            $toolbar    = file_get_contents(  'application/views/sximo/module/template/ajax/toolbar.tpl' );        

            if($row->module_db_key =='')
            {
                $controller = file_get_contents(  'application/views/sximo/module/template/ajax/controller_view.tpl' );
                $grid = file_get_contents(  'application/views/sximo/module/template/index_view.tpl' );
                $toolbar = file_get_contents(  'application/views/sximo/module/template/ajax/toolbar_view.tpl' );
                $table = file_get_contents(  'application/views/sximo/module/template/ajax/table_view.tpl' );
                
            }                   


            $build_controller   = SiteHelpers::blend($controller,$codes);    
            $build_view         = SiteHelpers::blend($view,$codes);    
            $build_form         = SiteHelpers::blend($form,$codes);    
            $build_grid         = SiteHelpers::blend($grid,$codes);    
            $build_table        = SiteHelpers::blend($table,$codes);    
            $build_model        = SiteHelpers::blend($model,$codes);
            $build_toolbar      = SiteHelpers::blend($toolbar,$codes);

            if(!is_dir("application/views/{$class}"))
            {
                mkdir( "application/views/{$class}" ,0777 );            
            }     


            if($this->input->get('rebuild') =='')
            {
                if($row->module_db_key =='')
                {               
                
                    file_put_contents(  "application/controllers/{$class}.php" , $build_controller) ;    
                    file_put_contents(  "application/models/{$class}.php" , $build_model) ;
                    file_put_contents(  "application/views/{$class}/index.php" , $build_grid) ;    
                    file_put_contents(  "application/views/{$class}/table.php" , $build_table) ;    
                    file_put_contents(  "application/views/{$class}/toolbar.php" , $build_toolbar) ;        
                
                } else {

                    file_put_contents(  "application/controllers/{$class}.php" , $build_controller) ;    
                    file_put_contents(  "application/models/{$class}model.php" , $build_model) ;
                    file_put_contents(  "application/views/{$class}/index.php" , $build_grid) ;    
                    file_put_contents(  "application/views/{$class}/table.php" , $build_table) ;    
                    file_put_contents(  "application/views/{$class}/toolbar.php" , $build_toolbar) ;                    
                    file_put_contents(  "application/views/{$class}/form.php" , $build_form) ;
                    file_put_contents(  "application/views/{$class}/view.php" , $build_view) ; 
                }               

            } else {

                if($this->input->get('c') =='y') file_put_contents(  "application/controllers/{$ctr}.php" , $build_controller) ;    
                if($this->input->get('m') =='y') file_put_contents(  "application/models/{$ctr}model.php" , $build_model) ;                
                if($this->input->get('g') =='y') file_put_contents(  "application/views/{$class}/index.php" , $build_grid) ;
                if($this->input->get('g') =='y') file_put_contents(  "application/views/{$class}/table.php" , $build_table) ;
                if($row->module_db_key !='')
                {        
                    if($this->input->get('f') =='y') file_put_contents(  "application/views/{$class}/form.php" , $build_form) ;
                    if($this->input->get('v') =='y') file_put_contents(  "application/views/{$class}/view.php" , $build_view) ;
                }  

            }


        } else {    
            // Start Native CRUD 
                
            if($row->module_db_key =='')
            {
                // No CRUD 
                $controller = file_get_contents(  'application/views/sximo/module/template/controller_view.tpl' );
                $grid = file_get_contents(  'application/views/sximo/module/template/index_view.tpl' );        
            } else {
                $controller = file_get_contents(  'application/views/sximo/module/template/controller.tpl' );
                $grid = file_get_contents(  'application/views/sximo/module/template/index.tpl' );        
            }        
        
            $view = file_get_contents(  'application/views/sximo/module/template/view.tpl' );
            $form = file_get_contents( 'application/views/sximo/module/template/form.tpl' );
            $model = file_get_contents(  'application/views/sximo/module/template/model.tpl' );

            $build_controller     = SiteHelpers::blend($controller,$codes);    
            $build_view            = SiteHelpers::blend($view,$codes);    
            $build_form            = SiteHelpers::blend($form,$codes);    
            $build_grid            = SiteHelpers::blend($grid,$codes);    
            $build_model        = SiteHelpers::blend($model,$codes);
            
                    
            if(!is_dir("application/views/{$class}"))
            {
                    mkdir( "application/views/{$class}" ,0777 );            
            }     
                
                            
            if($this->input->get('rebuild') !='')
            {
                // rebuild spesific files
                if($this->input->get('c') =='y'){
                    file_put_contents( "application/controllers/{$class}.php" , $build_controller) ;   
                    
                }
                if($this->input->get('m') =='y'){
                    file_put_contents(  "application/models/{$class}model.php" , $build_model) ;
                    
                }    
                
                if($this->input->get('g') =='y'){
                    file_put_contents(  "application/views/{$class}/index.php" , $build_grid) ;
                }    
                if($row->module_db_key !='')
                {            
                    if($this->input->get('f') =='y'){
                        file_put_contents(  "application/views/{$class}/form.php" , $build_form) ;
                    }    
                    if($this->input->get('v') =='y'){
                        file_put_contents(  "application/views/{$class}/view.php" , $build_view) ;
                    }
                }        
            
            } else {
            
                file_put_contents(  "application/controllers/{$class}.php" , $build_controller) ;    
                file_put_contents(  "application/models/{$class}model.php" , $build_model) ;

                file_put_contents(  "application/views/{$class}/index.php" , $build_grid) ;
                if($row->module_db_key !='')
                {
                    file_put_contents(  "application/views/{$class}/form.php" , $build_form) ;
                    file_put_contents( "application/views/{$class}/view.php" , $build_view) ;        
                }                         
            
            }                    
                
        }   
            
        $this->session->set_flashdata('message',SiteHelpers::alert('success','Code Script has been replaced successfull'));  
        redirect('sximo/module',301);               
   
    }
    
    function destroy( $id = null )
    {
        $row = $this->db->get_where('tb_module',array('module_id'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301); 

        $path = $row->module_name;    
        $class = ucwords($row->module_name);                            
        if($row->module_type !='core')
        {
            
            if($class !='') {

                $this->db->where('module_id',$row->module_id);
                $this->db->delete('tb_module');

                $this->db->where('module_id',$row->module_id);
                $this->db->delete('tb_groups_access');

    
            
                
                if(file_exists(  "application/controllers/{$class}.php")) 
                    unlink( "application/controllers/{$class}.php");
                    
                if(file_exists( "application/models/{$class}model.php")) 
                    unlink( "application/models/{$class}model.php");
                    
                self::removeDir(    "application/views/{$path}");

                $this->session->set_flashdata('message',SiteHelpers::alert('success','Module has been removed successfull'));  
                redirect('sximo/module',301);                                         
            }    
            
        }
        $this->session->set_flashdata('message',SiteHelpers::alert('error','No Module removed'));  
        redirect('sximo/module',301);   
                                
    }
    
    function removeDir($dir) {
        foreach(glob($dir . '/*') as $file) {
            if(is_dir($file))
                removedir($file);
            else
                unlink($file);
        }
        rmdir($dir);
    }   

    function sub( $id ='')
    {

        $row = $this->db->get_where('tb_module',array('module_name'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);
                                            
        $this->data['row'] = $row;   

        $config = SiteHelpers::CF_decode_json($row->module_config); 
        //   echo '<pre>'; print_r($config['subgrid']); echo '</pre>';    exit;
        $this->data['row'] = $row;
        $this->data['fields'] = $config['grid'];
        $this->data['subs'] = (isset($config['subgrid']) ? $config['subgrid'] : array());
        $this->data['tables'] = $this->model->getTableList($this->db->database);
        $this->data['module'] = $this->module;
        $this->data['module_name'] = $id;    
        $this->data['modules'] = $this->db->get_where('tb_module',array('module_type'=>'addon'));    

        $this->data['content'] = $this->load->view('sximo/module/sub',$this->data, true );        
        $this->load->view('layouts/main', $this->data ); 
   

    }    

    function savesub()
    {
        $rules = array(
            array('field'   => 'title','label'   => 'Link Title','rules'   => 'required'),
            array('field'   => 'master','label'   => 'Master  ','rules'   => 'required'),
            array('field'   => 'master_key','label'   => 'master key','rules'   => 'required'),
            array('field'   => 'module','label'   => 'module','rules'   => 'required'),
        );  
                    
        $this->form_validation->set_rules( $rules );
        if( $this->form_validation->run() )
        {   
            $id = $this->uri->segment(4);
            $row = $this->db->get_where('tb_module',array('module_name'=>$id))->row();
            if(count($row) <= 0) redirect('sximo/module',301);
                                              
            $this->data['row'] = $row;                            
            $config = SiteHelpers::CF_decode_json($row->module_config); 


            $newData[] = array(
                'title'             => $this->input->post('title',true), 
                'master'            => $this->input->post('master',true),
                'master_key'        => $this->input->post('master_key',true),
                'module'            => $this->input->post('module',true),
                'table'             => $this->input->post('table',true),
                'key'               => $this->input->post('key',true),
            );
            
            $subgrid = array();
            if(isset($config["subgrid"]))
            {
                foreach($config['subgrid'] as $sb)
                {
                    $subgrid[] =$sb;
                }    
                
            }
            $subgrid = array_merge($subgrid,$newData);
            
            if(isset($config["subgrid"])) unset($config["subgrid"]);
            $new_config =     array_merge($config,array("subgrid" => $subgrid));    
            // echo '<pre>'; print_r($new_config); echo '</pre>';    exit;
        

            $this->db->where('module_id',$this->input->post('module_id',true));
            $this->db->update('tb_module',array('module_config' => SiteHelpers::CF_encode_json($new_config)));       
            
            $this->session->set_flashdata('message',SiteHelpers::alert('success','Module Forms Has Been Saved Successfull'));        
            redirect('sximo/module/sub/'.$row->module_name,301);  

        }    else {

            $this->session->set_flashdata('message',SiteHelpers::alert('error','The following errors occurred'));        
            redirect('sximo/module/sub/'.  $this->uri->segment(4),301);

        }            

    }   

    function removesub()
    {
        $id = $this->input->get('id');
         $module = $this->input->get('mod');
        $row = $this->db->get_where('tb_module',array('module_id'=>$id))->row();
        if(count($row) <= 0) redirect('sximo/module',301);
                                          
        $this->data['row'] = $row;          

        $config = SiteHelpers::CF_decode_json($row->module_config); 
        $subgrid = array();

        foreach($config['subgrid'] as $sb)
        {
            if($sb['module'] != $module) {
                $subgrid[] = $sb;
            }    
        }    
        unset($config["subgrid"]);
        $new_config =     array_merge($config,array("subgrid" => $subgrid));    

        $this->db->where('module_id',$id);
        $this->db->update('tb_module',array('module_config' => SiteHelpers::CF_encode_json($new_config)));               
        $this->session->set_flashdata('message',SiteHelpers::alert('success','Module Forms Has Been Saved Successfull'));        
        redirect('sximo/module/sub/'.$row->module_name,301);     

    }       
        

  function package()
  {
    if( count( $id = $this->input->get_post('id', true ))<1){

        $this->session->set_userdata(array(
          'message' => SiteHelpers::alert('warning',' Nothing to select, please reselect module ') ,
        ));
        redirect("module"); 

    };

    //$id = explode(',', $id );

    $_id = array();
    foreach ( $id as $k => $v ){
      if( !is_numeric( $v )) continue;
      $_id[] = $v;
    }

    $ids = implode(',',$_id);

    $sql = "
        SELECT * FROM tb_module
        WHERE module_id IN (".$ids.")
        ORDER by module_id
        ";

    $query = $this->db->query($sql);
    $rows = $query->result();

    $this->data['zip_content'] = array();
    $app_info = array();
    $inc_tables = array();

    foreach ( $rows as $k => $row ){

      $zip_content[] = array(
        'module_id'   =>  $row->module_id,
        'module_name' =>  $row->module_name,
        'module_db'   =>  $row->module_db,
        'module_type' =>  $row->module_type,
      );

    }

    // encrypt info
    $this->data['enc_module'] = base64_encode( serialize( $zip_content ));
    $this->data['enc_id'] = base64_encode( serialize( $id ));

    // module info
    $this->data['zip_content'] = $zip_content;

    /* CHANGE START HERE */
    $app_path = APPPATH;

    // file helper list
    $_path_inc = array( 'libraries','helpers','language/en' );

    foreach( $_path_inc as $path){
      $file_inc[$path]  = scandir( $app_path .'/'. $path);
      foreach ( $file_inc[$path] as $k => $v ){
        if( $v=='.' || $v=='..') unset( $file_inc[$path][ $k ] );
        if( ! preg_match( '/.php$/i', $v )) unset( $file_inc[$path][ $k ] );
      }
    }

    $this->data['file_inc'] = $file_inc;

    /* CHANGE END HERE */

    $this->data['content'] = $this->load->view('sximo/module/package',$this->data, true );
    $this->load->view('layouts/main',$this->data);
  }

  function dopackage() {

    // app name
    $app_name     = $this->input->get_post( 'app_name' , true );

    // encrypt info
    $enc_module   = $this->input->get_post( 'enc_module' , true );
    $enc_id       = $this->input->get_post( 'enc_id' , true );

    // query command || file
    $sql_cmd      = $this->input->get_post( 'sql_cmd' , true );
    if( ( isset($_FILES['sql_cmd_upload']) && !$_FILES['sql_cmd_upload']['error'])){
      $sql_path     = $_FILES['sql_cmd_upload']['tmp_name'] ;
      if( $sql_content = file_get_contents( $sql_path )){
        $sql_cmd = $sql_content;
      }
    }

    /* CHANGE START */

    // file to include
    $file_helpers   = $this->input->get_post( 'file_helpers' , true );
    $file_lang      = $this->input->get_post( 'file_lang' , true );
    $file_libraries = $this->input->get_post( 'file_libraries' , true );


    /* CHANGE END */

    // create app name
    $tapp_code    = preg_replace('/([\s[:punct:]]+)/',' ',$app_name);
    $app_code     = str_replace(' ','_', trim( $tapp_code ));

    $module_id    = unserialize( base64_decode( $enc_id ));
    $modules      = unserialize( base64_decode( $enc_module  ));
    $c_module_id  = implode( ',',$module_id );

    $zip_file =  FCPATH ."uploads/zip/{$app_code}.zip";

    $this->load->helper('zip');
    $cf_zip = new ZipHelpers;

    $app_path = APPPATH ;

    $cf_zip->add_data( ".mysql" , $sql_cmd );

    // App ID Name
    $ain = $module_id;
    $cf_zip->add_data( ".ain", base64_encode( serialize($ain )));

    // setting
    $sql = " select * from tb_module where module_id in ( {$c_module_id} )";

    $query = $this->db->query( $sql );
    $_modules = $query->result();

    foreach ( $_modules as $n => $_module ){
      $_modules[$n]->module_id = '';
    }

    $setting['tb_module'] = $_modules;

    $cf_zip->add_data( ".setting", base64_encode(serialize($setting)));

    unset( $_module );

    foreach ( $_modules as $n => $_module ){
      $file = $_module->module_name;
      $cf_zip->add_data( "controllers/". ucwords($file).".php",
                          file_get_contents( $app_path."/controllers/". ucwords($file).".php")) ;
      $cf_zip->add_data( "models/". ucwords($file)."model.php", file_get_contents($app_path."/models/". ucwords($file)."model.php")) ;
      $cf_zip->get_files_from_folder( $app_path."/views/{$file}/","views/{$file}/" );

    }

    // CHANGE START

    // push libraries files
    if( ! empty( $file_libraries )){
      foreach ( $file_libraries as $k => $file ){
        $cf_zip->add_data( "libraries/". $file ,
                             file_get_contents( $app_path."/libraries/".$file)) ;
      }
    }

    // push hepler files
    if( ! empty( $file_helpers )){
      foreach ( $file_helpers as $k => $file ){
        $cf_zip->add_data( "helpers/". $file ,
                             file_get_contents( $app_path."/helpers/".$file)) ;
      }
    }

    // push language files

    if( ! empty ( $file_lang )){
      $lang_path = scandir( $app_path . '/language' );
      foreach ( $lang_path as $k => $path ){
        if( $path=='.' || $path=='..') continue;
        if( is_file( $app_path . '/' . $path )) continue;

        foreach ( $file_lang as $k => $file ){
          $cf_zip->add_data( 'language/'. $path .'/'. $file ,
                   file_get_contents( $app_path."/language/". $path . '/'. $file)) ;

        }
      }
      $this->data['lang_path'] = $lang_path;

    }


    // CHANGE END

    if( !(is_dir( FCPATH.'uploads/zip' ))){
      mkdir( FCPATH.'uploads/zip' );
    }

    $_zip = $cf_zip->archive( $zip_file );
    $cf_zip->clear_data();
    $this->data['download_link'] = '<a href="'. base_url('uploads/zip').'/'.basename($zip_file).'" target="_blank" >Download here</a> ';

    $this->data['module_title'] = "ZIP Packager";
    $this->data['app_name'] = $app_name;

    SiteHelpers::alert('success',' Module(s) zipped successful ! <br>'. $this->data['download_link']) ;

    redirect("sximo/module");

  }

  function install( $id =0)
  {
    if( !isset($_FILES['installer']) && $_FILES['installer']['error']!=0 ){
      SiteHelpers::alert('danger','Zipfile can not be empty!' . $msg) ;
      redirect("sximo/module");

    }

        $this->load->helper('sximo');
        $path = $_FILES['installer']['tmp_name'];
        $data = SximoHelpers::cf_unpackage($path);

        $msg = '.';
        if( isset($data['sql_error'])){
        $msg = ", with SQL error ". $data['sql_error'];
    }

        SiteHelpers::alert('success','New Modules Installed' . $msg) ;
        redirect("sximo/module");

  }



    
}