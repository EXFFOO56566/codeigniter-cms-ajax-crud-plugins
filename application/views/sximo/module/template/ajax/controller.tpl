<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class {controller} extends SB_Controller 
{

	protected $layout 	= "layouts/main";
	public $module 		= '{class}';
	public $per_page	= '10';

	function __construct() {
		parent::__construct();
		
		$this->load->model('{class}model');
		$this->model = $this->{class}model;
		
		$this->info = $this->model->makeInfo( $this->module);
		$this->access = $this->model->validAccess($this->info['id']);	
		$this->data = array_merge( $this->data, array(
			'pageTitle'	=> 	$this->info['title'],
			'pageNote'	=>  $this->info['note'],
			'pageModule'	=> '{class}',
			'pageUrl'			=>  site_url('{class}'),
		));
		
		if(!$this->session->userdata('logged_in')) redirect('user/login',301);
		
	}

	public function index()
	{
		if($this->access['is_view'] ==0)
		{ 
			$this->session->set_flashdata('error',SiteHelpers::alert('error','Your are not allowed to access the page'));
			redirect('dashboard',301);
		}	
				
		$this->data['access']		= $this->access;	
		$this->data['content'] = $this->load->view('{class}/index',$this->data, true );		
    	$this->load->view('layouts/main', $this->data );
	}		
	
	function data() 
	{
		if($this->access['is_view'] ==0) { echo SiteHelpers::alert('error',' You are not allowed to view this page'); die; }		
		  
		// Filter sort and order for query 
		$sort = ($this->input->get('sort', true) !='' ? $this->input->get('sort', true) :  $this->info['setting']['orderby']); 
		$order = ($this->input->get('order', true) !='' ? $this->input->get('order', true) :  $this->info['setting']['ordertype']);
		// End Filter sort and order for query 
		// Filter Search for query		
		$filter = (!is_null($this->input->get('search', true)) ? $this->buildSearch() : '');
		// End Filter Search for query 
		
		$page = max(1, (int) $this->input->get('page', 1));
		$params = array(
			'page'		=> $page ,
			'limit'		=> ($this->input->get('rows', true) !='' ? filter_var($this->input->get('rows', true),FILTER_VALIDATE_INT) : $this->info['setting']['perpage'] ) ,
			'sort'		=> $sort ,
			'order'		=> $order,
			'params'	=> $filter,
			'global'	=> (isset($this->access['is_global']) ? $this->access['is_global'] : 0 )
		);
		// Get Query 
		$results = $this->model->getRows( $params );		
		
		// Build pagination setting
		$page = $page >= 1 && filter_var($page, FILTER_VALIDATE_INT) !== false ? $page : 1;	

		$this->data['rowData']		= $results['rows'];
		// Build Pagination
		
		$pagination = $this->paginatorajax( array(
			'total_rows' => $results['total'] ,
			'per_page'	 => $params['limit']
		));
		$this->data['pagination']	= $pagination;
		// Row grid Number 
		$this->data['i']			= ($page * $params['limit'])- $params['limit']; 
		// Grid Configuration
		$this->data['param']		= $params; 
		$this->data['tableGrid'] 	= $this->info['config']['grid'];
		$this->data['tableForm'] 	= $this->info['config']['forms'];
		$this->data['colspan'] 		= SiteHelpers::viewColSpan($this->info['config']['grid']);	
		$this->data['setting'] 		= $this->info['setting'];	
		// Group users permission
		$this->data['access']		= $this->access;
		// Render into template
		
		$this->load->view('{class}/table',$this->data );
    
	  
	}
	
	function show( $id = null) 
	{
		if($this->access['is_detail'] ==0) { echo SiteHelpers::alert('error',' You are not allowed to view this page'); die; }

		$row = $this->model->getRow($id);
		if($row)
		{
			$this->data['row'] =  $row;
		} else {
			$this->data['row'] = $this->model->getColumnTable('{class}'); 
		}

		$this->data['subgrid']	= (isset($this->info['config']['subgrid']) ? $this->info['config']['subgrid'] : array()); 
		$this->data['fields'] =  AjaxHelpers::fieldLang($this->info['config']['grid']);
		$this->data['id'] = $id;
		$this->data['setting'] 		= $this->info['setting'];
		$this->load->view('{class}/view', $this->data);	  
	}
  
	function add( $id = null ) 
	{

		if($id =='')
			if($this->access['is_add'] ==0) { echo SiteHelpers::alert('error',' You are not allowed to view this page'); die; }

		if($id !='')
			if($this->access['is_edit'] ==0) { echo SiteHelpers::alert('error',' You are not allowed to view this page'); die; }

		$row = $this->model->getRow( $id );
		if($row)
		{
			$this->data['row'] =  $row;
		} else {
			$this->data['row'] = $this->model->getColumnTable('{table}'); 
		}
		$this->data['subgrid']	= (isset($this->info['config']['subgrid']) ? $this->info['config']['subgrid'] : array()); 
		$this->data['id'] = $id;
		$this->data['setting'] 		= $this->info['setting'];
		$this->load->view('{class}/form',$this->data);		
	
	}
	
	function save() {
		
		$rules = $this->validateForm();

		$this->form_validation->set_rules( $rules );
		if( $this->form_validation->run() )
		{
			$data = $this->validatePost();
			$ID = $this->model->insertRow($data , $this->input->get_post( '{key}' , true ));
			// Input logs
			if( $this->input->get( '{key}' , true ) =='')
			{
				$this->inputLogs("New Entry row with ID : $ID  , Has Been Save Successfull");
			} else {
				$this->inputLogs(" ID : $ID  , Has Been Changed Successfull");
			}
			// Redirect after save	
			if($this->input->post('apply'))
			{ 
				$action = 'apply';
			} else {
				$action = 'submit';	

			}
				header('content-type:application/json');	
				echo json_encode(array(
					'status'	=>'success',
					'message'	=> ' Data has been saved succesfuly !',
					'action'	=>  $action
					));	
			
		} else {
			header('content-type:application/json');
			echo json_encode(array(
					'message'	=> validation_errors('<li>', '</li>'),
					'status'	=> 'error'
					));			
			
		}
	}

	function destroy()
	{
		if($this->access['is_remove'] ==0) { echo SiteHelpers::alert('error',' You are not allowed to view this page'); die; }
		if(!is_null($this->input->post('id')))
		{
			$this->model->destroy($this->input->post( 'id' , true ));
			$this->inputLogs("ID : ".implode(",",$this->input->post( 'id' , true ))."  , Has Been Removed Successfull");
			header('content-type:application/json');
			echo json_encode(array(
				'status'=>'success',
				'message'=> SiteHelpers::alert('success','Data Has Been Removed Successfull')
			));
		} else {
			header('content-type:application/json');
			echo json_encode(array(
				'status'=>'error',
				'message'=> 'Ops , Something Went Wrong !'
			));

		} 	
	}


}
