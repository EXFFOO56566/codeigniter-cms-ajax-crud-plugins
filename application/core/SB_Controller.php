<?php //if (!defined('BASEPATH')) exit('No direct script access allowed');

class SB_Controller extends CI_Controller
{

	var $data = array();
	function __construct() 
	{
		parent::__construct();

		if($this->session->userdata('lang') =='')
		{
			$this->session->set_userdata('lang','en');
			$this->load->language('core','en');
		} else {
			if(file_exists('./application/language/'.$this->session->userdata('lang').'/core_lang.php'))
			{
				
				$this->load->language('core',$this->session->userdata('lang'));	
			} else {
				$this->session->set_userdata('lang','en');
				$this->load->language('core','en');
				$this->load->language('core',$this->session->userdata('lang'));	
			}
			
		}

		$this->data['content'] = 'Welcome to Sximo Builder';
		$this->data['page'] = $this->input->get('page',true);
 
		// init for upload library
		$upload_config = array();
		$this->load->library('upload', $upload_config );
 
		$imagelib_config = array();
		$this->load->library('image_lib', $imagelib_config );

		$this->load->helper('ajax_helper');
 
	}

	function download()
	{
		$info = $this->model->makeInfo( $this->module);
		// Take param master detail if any

		$results 	= $this->model->getRows( array() );
		$fields		= $info['config']['grid'];
		$rows		= $results['rows'];

		$content = $this->data['pageTitle'];
		$content .= '<table border="1">';
		$content .= '<tr>';
		foreach($fields as $f )
		{
			if($f['download'] =='1') $content .= '<th style="background:#f9f9f9;">'. $f['label'] . '</th>';
		}
		$content .= '</tr>';

		foreach ($rows as $row)
		{
			$content .= '<tr>';
			foreach($fields as $f )
			{
				if($f['download'] =='1'):
					$conn = (isset($f['conn']) ? $f['conn'] : array() );
					$content .= '<td>'. SiteHelpers::gridDisplay($row->$f['field'],$f['field'],$conn) . '</td>';
				endif;
			}
			$content .= '</tr>';
		}
		$content .= '</table>';

		@header('Content-Type: application/ms-excel');
		@header('Content-Length: '.strlen($content));
		@header('Content-disposition: inline; filename="'.$title.' '.date("d/m/Y").'.xls"');

		echo $content;
		exit;
	}

	function search()
	{
		$keyword = $this->module;
		if(!is_null($this->input->get('keyword', true)))
		{
			$keyword = $this->module.'?search='.str_replace(' ','_',$this->input->get('keyword', true));
		}
		return redirect($keyword);

	}

	function multisearch()
	{
		//echo '<pre>';print_r($_POST);echo '</pre>';exit;
		$post = $_POST;
		$items ='';
		foreach($post as $item=>$val):
			if($_POST[$item] !='' and $item !='_token' and $item !='md' && $item !='id'):
				$items .= $item.':'.trim($val).'|';
			endif;

		endforeach;
		redirect($this->module.'?search='.substr($items,0,strlen($items)-1));
	}

	function filter()
	{
		$module = $this->module;
		$sort 	= (!is_null($this->input->get('sort', true)) ? $this->input->get('sort', true) : '');
		$order 	= (!is_null($this->input->get('order', true)) ? $this->input->get('order', true) : '');
		$rows 	= (!is_null($this->input->get('rows', true)) ? $this->input->get('rows', true) : '');
		$md 	= (!is_null($this->input->get('md', true)) ? $this->input->get('md', true) : '');

		$filter = '?';
		if($sort!='') $filter .= '&sort='.$sort;
		if($order!='') $filter .= '&order='.$order;
		if($rows!='') $filter .= '&rows='.$rows;
		if($md !='') $filter .= '&md='.$md;

		return redirect($module . $filter);

	}


	function infoFieldSearch()
	{
		$info =$this->model->makeInfo( $this->module);
		$forms = $info['config']['forms'];
		$data = array();
		foreach($forms as $f)
		{
			if($f['search'] ==1)
            	if($f['alias'] !='' )  {
				$data[] =  array('id'=> $f['alias'].".".$f['field']);
			}
		}
		return $data;
	}
	function buildSearch()
	{
		$keywords = ''; $fields = '';	$param ='';
		$allowsearch = $this->info['config']['forms'];
		foreach($allowsearch as $as) $arr[$as['field']] = $as ;
		if($this->input->get('search', true) !='')
		{
			$type = explode("|",$this->input->get('search', true));
			if(count($type) >= 1)
			{
				foreach($type as $t)
				{
					$keys = explode(":",$t);

					if(in_array($keys[0],array_keys($arr))):
						if($arr[$keys[0]]['type'] == 'select' || $arr[$keys[0]]['type'] == 'radio' )
						{
							$param .= " AND ".$arr[$keys[0]]['alias'].".".$keys[0]." = '".$keys[1]."' ";
						} else {
							$param .= " AND ".$arr[$keys[0]]['alias'].".".$keys[0]." REGEXP '".$keys[1]."' ";
						}
					endif;
				}
			}
		}
		return $param;

	}

	function buildMasterDetail( $template = null)
	{
		// check if url contain $_GET['md'] , that mean master detail
		if(!is_null($this->input->get('md',true)) and $this->input->get('md',true) != '' )
		{
			
			$values 				= array();
			$data 					= explode(" ", $this->input->get('md',true) );
			// Split all param get 
			$master 				= $data[0] ; $master_key = $data[1]; $module = $data[2]; $key = $data[3];  $val = $data[4];

			$model 					= $master.'model';
			$this->load->model($model);
			$this->modeldetail = $this->$model;

			$values['row'] 			= $this->modeldetail->getRow( $val );
			$loadInfo 				= $this->modeldetail->makeInfo( $master);
			$values['grid']         = $loadInfo ['config']['grid'];
			$filter 				= 	" AND  ".$this->info['table'].".".$key."='".$val."ss' ";	
			if($template != null)
			{
				//$view 					= $this->load->view( $template, $values ,true) ; 			
			} else {	
				//$view 					= $this->load->view( 'layouts/masterview', $values ,true) ;   
			}	
			$result = array(
				'masterFilter' => $filter,
				//'masterView'	=> $view
			);
			return $result;	
			
		} else {
			$result = array(
				'masterFilter' => '',
				//'masterView'	=> ''
			);	
			return $result;		
		}
			
	
	}	

	function comboselect()
	{
		$param = explode(':',$this->input->get('filter', true));
		$rows = $this->model->getComboselect($param);
		$items = array();

		$fields = explode("|",$param[2]);

		foreach($rows as $row)
		{
			$value = "";
			foreach($fields as $item=>$val)
			{
				if($val != "") $value .= $row->$val." ";
			}
			$items[] = array($row->$param['1'] , $value);

		}

		echo json_encode($items);
	}

	function combotable()
	{
		$rows = $this->model->getTableList($this->db->database);
		
		$items = array();
		foreach($rows as $row) {
			$items[] = array($row , $row);
		}	
		
		echo json_encode($items);
	}

	function combotablefield()
	{
		$items = array();
		$table = $this->input->get('table', true);
		if($table !='')
		{
			$rows = $this->model->getTableField($this->input->get('table', true));
			foreach($rows as $row)
				$items[] = array($row , $row);
		}
		echo json_encode($items);
	}

	function validateListError( $rules )
	{
		$errMsg = $this->lang->line('core.note_error') ;
		$errMsg .= '<hr /> <ul>';
		foreach($rules as $key=>$val)
		{
			$errMsg .= '<li>'.$key.' : '.$val[0].'</li>';
		}
		$errMsg .= '</li>';
		return $errMsg;
	}
	
	function validateForm()
	{
		$forms = $this->info['config']['forms'];
		$rules = array();
		foreach($forms as $form)
		{
			if($form['required']== '' || $form['required'] !='0')
			{
				$rules[] = array('field'   => $form['field'],'label' => $form['label'], 'rules'   => 'required');	
			} elseif ($form['required'] == 'alpa'){
				$rules[] = array('field'   => $form['field'],'label' => $form['label'], 'rules'   => 'required|alpha');			
			} elseif ($form['required'] == 'alpa_num'){
				$rules[] = array('field'   => $form['field'],'label' => $form['label'], 'rules'   => 'required|alpha_numeric');			
			} elseif ($form['required'] == 'alpa_dash'){
				$rules[] = array('field'   => $form['field'],'label' => $form['label'], 'rules'   => 'required|alpha_dash');			
			}  elseif ($form['required'] == 'email'){
				$rules[] = array('field'   => $form['field'],'label' => $form['label'], 'rules'   => 'required|valid_email');			
			}  elseif ($form['required'] == 'numeric'){
				$rules[] = array('field'   => $form['field'],'label' => $form['label'], 'rules'   => 'required|numeric');			
			}  else {
			
			}				
			
		}
		
		//echo '<pre>';print_r($rules);echo '<pre>';exit;
		return $rules ;
	}

	function validatePost(  $table = '')
	{
		$str = $this->info['config']['forms'];
		
		$data = array();
		
		foreach($str as $f){
			$field = $f['field'];
			if($f['view'] ==1)
			{

				if(!is_null($this->input->get( $field , true )))
				{
					$data[$field] = $this->input->get($field,true);
				}

				switch ($f['type']) {
					case 'textarea_editor':
					case 'textarea':
						$content = $this->input->get_post($field)? $this->input->get_post($field):'';
						$data[$field] =  $content;
 				   
					break;
					
					case 'file' :
						$this->load->library('upload');
						$destinationPath = "./".$f['option']['path_to_upload'];
					
						$config['upload_path'] = $destinationPath;
						$config['allowed_types'] = 'gif|jpg|png';
						
						$this->upload->initialize($config);
						if($this->upload->do_upload($field))
						{
							$file_data = $this->upload->data();
							$filename = $file_data['file_name'];
							$extension =$file_data['file_ext']; //if you need extension of the file


							 if($f['option']['resize_width'] != '0' && $f['option']['resize_width'] !='')
							 {
							 	if( $f['option']['resize_height'] ==0 )
								{
									$f['option']['resize_height']	= $f['option']['resize_width'];
								}
 
							 	$origFile = $destinationPath.'/'.$filename;
								 SiteHelpers::cropImage($f['option']['resize_width'] , $f['option']['resize_height'] , $orgFile ,  $extension,	 $orgFile)	;
							 }

							 $data[$field] = $filename;
						} else {
							unset($data[$field]);
						}
					
 					 break;
					
					case 'checkbox' :
						if(!is_null($this->input->get( $field , true )))
						{
							$data[$field] = implode(",",$this->input->get_post( $field , true ));
						}
 					 
 					 break;
					
					case 'date' :
						$data[$field] = date("Y-m-d",strtotime($this->input->get_post( $field , true )));
 					 break;
					
					case 'select' :
						if( isset($f['option']['is_multiple']) &&  $f['option']['is_multiple'] ==1 )
						{
							$data[$field] = implode(",",$this->input->get_post( $field , true ));
						} else {
							$data[$field] = $this->input->get_post( $field , true );
						}
 					 break;
					
					case 'text' :
					default:
						$data[$field] = $this->input->get_post( $field , true );
					break;
				}				

			}
		}
		 $global	= (isset($this->access['is_global']) ? $this->access['is_global'] : 0 );
		 if($global == 0 )
			 $data['entry_by'] = $this->session->userdata('uid');

		return $data;
	}

	function validAccess( $methode )
	{

		if($this->model->validAccess( $methode ,$this->info['id']) == false)
		{
			$this->session->setflashdata('message', SiteHelpers::alert('error',' Your are not allowed to access the page '));
			return redirect('home');
		}

	}

	function inputLogs( $note = NULL)
	{
		$data = array(
			'module'	=> $this->uri->segment(1),
			'task'		=> $this->uri->segment(2),
			'user_id'	=> $this->session->userdata('uid'),
			'ipaddress'	=> $this->input->ip_address(),
			'note'		=> $note
		);
		 $this->db->insert( 'tb_logs',$data);		;

	}
 
	function paginator($options = array() ) {

		$keepLive = '';
		$sort 	= (!is_null($this->input->get('sort', true)) ? $this->input->get('sort', true) : '');
		$order 	= (!is_null($this->input->get('order', true)) ? $this->input->get('order', true) : '');
		$rows 	= (!is_null($this->input->get('rows', true)) ? $this->input->get('rows', true) : '');
		$search 	= (!is_null($this->input->get('search', true)) ? $this->input->get('search', true) : '');

		$appends = array();
		if($sort!='') 	$keepLive .='&sort='.$sort;
		if($order!='') 	$keepLive .='&order='.$order;
		if($rows!='') 	$keepLive .='&rows='.$rows;
		if($search!='') $keepLive .='&search='.$search;

		$toptions = array_replace_recursive( array(
			'base_url' => site_url( $this->module ).'?'.$keepLive,
			'total_rows' => 0 ,
			'per_page' => $this->per_page,
		), $options ); 
 
		$pagination = $this->pagination->initialize( $toptions );

		return $this->pagination->create_links();

	} 
	
	function displayError($data)
	{
		$this->load->view('layouts/errors',$data);
	}

//BEGIN AJAX ADDITIONAL CODE	

	public function export( $t = 'excel')
	{
		$info 		= $this->model->makeInfo( $this->module);		
		$params = array(
			
		);		
		$results 	= $this->model->getRows( $params );
		$fields		= $info['config']['grid'];
		$rows		= $results['rows'];
		$content 	= array(
						'fields' => $fields,
						'rows' => $rows,
						'title' => $this->data['pageTitle'],
					);
		
		if($t == 'word')
		{			
			 $this->load->view('sximo/module/utility/word',$content);
			 
		} else if($t == 'csv') {		
		 
			$this->load->view('sximo/module/utility/csv',$content);
			
		} else if ($t == 'print') {
		
			 $this->load->view('sximo/module/utility/print',$content);
			 
		} else  {
		
			 $this->load->view('sximo/module/utility/excel',$content);
		}	
	}	

	public function flysearch()
	{
	
		$this->data['tableForm'] 	= $this->info['config']['forms'];	
		$this->data['tableGrid'] 	= $this->info['config']['grid'];
		 $this->load->view('sximo/module/utility/search',$this->data);
	
	}	

	function paginatorajax($options = array() ) {

		$keepLive = '';
		$sort 	= (!is_null($this->input->get('sort', true)) ? $this->input->get('sort', true) : '');
		$order 	= (!is_null($this->input->get('order', true)) ? $this->input->get('order', true) : '');
		$rows 	= (!is_null($this->input->get('rows', true)) ? $this->input->get('rows', true) : '');
		$search 	= (!is_null($this->input->get('search', true)) ? $this->input->get('search', true) : '');

		$appends = array();
		if($sort!='') 	$keepLive .='&sort='.$sort;
		if($order!='') 	$keepLive .='&order='.$order;
		if($rows!='') 	$keepLive .='&rows='.$rows;
		if($search!='') $keepLive .='&search='.$search;

		$toptions = array_replace_recursive( array(
			'base_url' => site_url( $this->module ).'/data?'.$keepLive,
			'total_rows' => 0 ,
			'per_page' => $this->per_page,
		), $options ); 
 
		$this->pagination->initialize( $toptions );

		return $this->pagination->create_links();

	} 		

	function detailview( $mode = 'view')
	{
		if($this->access['is_view'] ==0) { echo SiteHelpers::alert('error',Lang::get('core.note_restric')); die; }	
		
		$sort 		= (!is_null($this->input->get_post('sort')) ? $this->input->get_post('sort') : $this->info['setting']['orderby']); 
		$order 		= (!is_null($this->input->get_post('order')) ? $this->input->get_post('order') : $this->info['setting']['ordertype']);	
		$filter 	= (!is_null($this->input->get_post('search')) ? $this->buildSearch() : '');
		$page 		=  $this->input->get_post('page', 1);	
		$master  	= $this->buildMasterDetail(); 
		$filter 	.=  $master['masterFilter'];
		$params 	= array(
			'params'	=> $filter,
			'global'	=> (isset($this->access['is_global']) ? $this->access['is_global'] : 0 )
		);
		$results = $this->model->getRows( $params );	
		$this->data['rowData']		= $results['rows'];
		$this->data['tableGrid'] 	= $this->info['config']['grid'];
		$this->data['tableForm'] 	= $this->info['config']['forms'];	
		$this->data['filtermd'] 	= str_replace(" ","+",$this->input->get_post('md')); 
		$md 						= explode(" ", $this->input->get_post('md') );
		$this->data['foreignKey']	= $md[3];
		$this->data['foreignVal']	= $md[4];
		$this->data['access'] 	= $this->access; 
		$this->data['primarykey'] 	= $this->info['key']; 
		if($mode =='form'){
			$this->load->view('sximo/module/utility/detailform',$this->data);	
		} else {
			$this->load->view('sximo/module/utility/detailview',$this->data);		
		}
		
			
	}

	function postCopy()
	{
		$table 	= $this->info['table'];		
		$key 	= $this->info['key'];
	    foreach(DB::select("SHOW COLUMNS FROM $table ") as $column)
        {
			if( $column->Field != $key)
				$columns[] = $column->Field;
        }
		$toCopy = implode(",",Input::get('id'));
		
				
		$sql = "INSERT INTO $table (".implode(",", $columns).") ";
		$sql .= " SELECT ".implode(",", $columns)." FROM $table WHERE $key IN (".$toCopy.")";
		DB::insert($sql);
		return Redirect::to( $this->data['pageModule'])->with('message', SiteHelpers::alert('success',Lang::get('core.note_success')));		
	}	
	
	public function getSearch()
	{
	
		$this->data['tableForm'] 	= $this->info['config']['forms'];	
		$this->data['tableGrid'] 	= $this->info['config']['grid'];
		return View::make('sximo/module/utility/search',$this->data);
	
	}	

	function quicksave( $id =0)
	{
		$rules = $this->validateForm();
		$this->form_validation->set_rules( $rules );
		if( $this->form_validation->run() )
		{
			$data = $this->validatePost($this->info['table']);
			unset($data[$this->info['key']]);
			$ID = $this->model->insertRow($data , $this->input->post($this->info['key'],true));
			header('content-type:application/json');
			echo json_encode(array(
				'status'	=>'success',
				'message'	=> ' Data has been saved succesfuly !',
				));	

		} else {
			header('content-type:application/json');
			echo json_encode(array(
				'status'	=>'error',
				'message'	=> ' Ops , Something went wrong !',
				));	
		}						
	}

	function removerow( $id )
	{
		$id = array('0'=>$id );
		$this->model->destroy($id);
		echo json_encode(array(
			'status'=>'success',
			'message'=> ' Data has been saved succesfuly !'
		));		

	}								

//END AJAX ADDITIONAL CODE	

}