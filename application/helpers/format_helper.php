<?php 
class FormatHelper {


	static public function status($status)
	{
		if($status ==1)
		{

			return '<span class="label label-primary"> Active </span>';
		} else {

			return '<span class="label label-danger"> Inactive </span>';
		}

	}

	static public function mailTo($mail)
	{

		return '<a href="mailto:'.$mail.'"> '.$mail.' </a>';

	}	

	static public function currency($value)
	{

		return '<span class="text-danger"> USD $ </span> '.$value;

	}		

}