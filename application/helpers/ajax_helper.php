<?php
class AjaxHelpers
{
	public static function gridFormater($val , $row, $attribute = array() , $arr = array()) {
		$_this = & get_Instance();
		// Handling Image & file Field
		if($attribute['image']['active'] =='1' && $attribute['image']['active'] !='') {
			$val =  SiteHelpers::showUploadedFile($val,$attribute['image']['path']) ;
		}
		// Handling Quick Display As 
		if(isset($arr['valid']) && $arr['valid'] ==1)
		{
			$fields = str_replace("|",",",$arr['display']);
			$Q = $_this->db->query(" SELECT ".$fields." FROM ".$arr['db']." WHERE ".$arr['key']." = '".$val."' ")->row();
			if(count($Q) >= 1 )
			{
				$rowObj = $Q;
				$fields = explode("|",$arr['display']);
				$v= '';
				$v .= (isset($fields[0]) && $fields[0] !='' ?  $rowObj->$fields[0].' ' : '');
				$v .= (isset($fields[1]) && $fields[1] !=''  ? $rowObj-> $fields[1].' ' : '');
				$v .= (isset($fields[2]) && $fields[2] !=''  ? $rowObj->$fields[2].' ' : '');
				
				
				$val = $v;
			} 
		} 	
		
		// Handling format function 	
		if(isset($attribute['formater']['active']) and $attribute['formater']['active']  ==1)
		{
			$val = $attribute['formater']['value'];
			foreach($row as $k=>$i)
			{
				if (preg_match("/$k/",$val))
					$val = str_replace($k,$i,$val);				
			}
			$c = explode("|",$val);
			if(isset($c[0]) && class_exists($c[0]))
			{
				$val = call_user_func( array($c[0],$c[1]), str_replace(":",",",$c[2])); 
				//$val = $c[2];
			}	
			
		}
		// Handling Link  function 	
		if(isset($attribute['hyperlink']['active']) && $attribute['hyperlink']['active'] ==1 && $attribute['hyperlink']['link'] != '')
		{	
	
			$attr = '';
			$linked = $attribute['hyperlink']['link'];
			foreach($row as $k=>$i)
			{
				
				if (preg_match("/$k/",$attribute['hyperlink']['link']))
					$linked = str_replace($k,$i, $linked);				
			}
			if($attribute['hyperlink']['target'] =='modal')
			{
				$attr = 'onclick="SximoModal(this.href,\''.addslashes ($val).'\'); return false"';
			}
			
			$val =  "<a href='".site_url($linked)."' $attr style='display:block' >".$val." <span class='fa fa-arrow-circle-right pull-right'></span></a>";
		}
		
		return $val;
		
	}	
	
	static public function fieldLang( $fields ) 
	{ 
		$l = array();
		foreach($fields as $fs)
		{			
			foreach($fs as $f)
				$l[$fs['field']] = $fs; 									
		}
		return $l;	
	}	
	
	static public function instanceGrid(  $class) 
	{
		$_this = & get_Instance();
		$data = array(
			'class'	=> $class ,
		);
		$_this->load->view('sximo/module/utility/instance',$data);
	
	} 



	static function inlineFormType( $field  ,$forms )
	{
		$type = '';
		foreach($forms as $f)
		{
			if($f['field'] == $field )
			{
				$type = ($f['type'] !='file' ? $f['type'] : ''); 			
			}	
		}
		if($type =='select' || $type="radio" || $type =='checkbox')
		{
			$type = 'select';
		} else if($type=='file') {
			$type = '';
		} else {
			$type = 'text';
		}
		return $type;
	}

	static public function buttonAction( $module , $access , $id , $setting)
	{
		$_this = & get_Instance();

		$html ='
			<div class="btn-group action" >
			<button class="btn btn-primary btn-xs dropdown-toggle" data-toggle="dropdown"  aria-expanded="false">
			<i class="fa fa-cog"></i> 
			</button>
				<ul  class="dropdown-menu  icons-left pull-right">';
		if($access['is_detail'] ==1) {
			if($setting['view-method'] != 'expand')
			{
				$onclick = " onclick=\"ajaxViewDetail('#".$module."',this.href); return false; \"" ;
				if($setting['view-method'] =='modal')
						$onclick = " onclick=\"SximoModal(this.href,'View Detail'); return false; \"" ;
				$html .= '<li><a href="'.site_url($module.'/show/'.$id).'" '.$onclick.'><i class="fa fa-search"></i> '. $_this->lang->line('core.btn_view').'</a></li>';
			}
		}
		if($access['is_edit'] ==1) {
			$onclick = " onclick=\"ajaxViewDetail('#".$module."',this.href); return false; \"" ;
			if($setting['form-method'] =='modal')
					$onclick = " onclick=\"SximoModalLarge(this.href,'Edit Form'); return false; \"" ;			
			
			$html .= '<li><a href="'.site_url($module.'/add/'.$id).'" '.$onclick.'><i class="fa  fa-edit"></i> '.$_this->lang->line('core.btn_edit').'</a></li>';
		}
		$html .= '</ul></div>';
		return $html;
	}	

	static public function buttonActionInline( $id ,$key )
	{
		$divid = 'form-'.$id;	
		$html = '
		<div class="actionopen" style="display:none">
			<button onclick="saved(\''.$divid.'\')" class="btn btn-primary btn-xs" type="button"><i class="fa  fa-save"></i></button>
			<button onclick="canceled(\''.$divid.'\')" class="btn btn-danger btn-xs " type="button"><i class="fa  fa-repeat"></i></button>
			<input type="hidden" value="'.$id.'" name="'.$key.'">
		</div>	
		';
		return $html;
	}			

	static public function buttonActionCreate( $module  ,$setting)
	{
		$_this = & get_Instance();
		$onclick = " onclick=\"ajaxViewDetail('#".$module."',this.href); return false; \"" ;
		if($setting['form-method'] =='modal')
				$onclick = " onclick=\"SximoModalLarge(this.href,'Create Detail'); return false; \"" ;


		$html = '
			<a href="'.site_url($module.'/add').'" class="tips btn btn-xs btn-primary"  title="'.$_this->lang->line('core.btn_create').'" '.$onclick.'>
			<i class="fa fa-plus-circle"></i> '.$_this->lang->line('core.btn_create').'</a>
		';
		return $html;
	}





}