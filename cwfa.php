<?php
/*
* Copyright 2014 Jeremy O'Connell  (email : cwplugins@cyberws.com)
* License: GPL2 .:. http://opensource.org/licenses/GPL-2.0
* Version 1.3
*/
	
////////////////////////////////////////////////////////////////////////////
//	Open Class
////////////////////////////////////////////////////////////////////////////
class cwfa_dq {
	
	////////////////////////////////////////////////////////////////////////////
	//	Sanitize Functions
	////////////////////////////////////////////////////////////////////////////
	
	//	Integers
	//	v: 1.0
	function cwf_san_int($value) {
		$value=preg_replace('/[^0-9]/','',$value);
		return $value;
	}
	
	//	Integer - commas
	//	v: 1.0
	function cwf_san_ilist($value) {
		$value=preg_replace('/[^0-9,]/','',$value);
		return $value;
	}
	
	//	Alphanumeric
	//	v: 1.0
	function cwf_san_an($value) {
		$value=preg_replace('/[^a-zA-Z0-9]/','',$value);
		return $value;
	}
	
	//	Alphanumeric - spaces
	//	v: 1.0
	function cwf_san_ans($value) {
		$value=preg_replace('/[^a-zA-Z0-9 ]/','',$value);
		$value=trim($value);
		return $value;
	}
	
	//	Alphanumeric - spaces, line returns
	//	v: 1.0
	function cwf_san_ansr($value) {
		$value=preg_replace('/[^a-zA-Z0-9\n ]/','',$value);
		$value=trim($value);
		return $value;
	}
	
	//	Alphanumeric - line returns
	//	v: 1.0
	function cwf_san_anr($value) {
		$value=preg_replace('/[^a-zA-Z0-9\n]/','',$value);
		return $value;
	}
	
	//	Alphanumeric with spaces, line returns, pipe
	//	v: 1.3 - Updated for hyphen (1.0 orig)
	function cwf_san_ansrp($value) {
		$value=preg_replace('/[^a-zA-Z0-9|\n\- ]/','',$value);
		$value=trim($value);
		return $value;
	}
	
	//	URL
	//	v: 1.3 - Updated for tilde (1.0 orig)
	function cwf_san_url($value) {
		$value=trim($value);
		$value=preg_replace('/\~/','%7e',$value);
		$value=preg_replace('/[^a-zA-Z0-9!@#\$%^&*()+<>,:;.?{}\/|_\- ]/','',$value);
		$value=preg_replace('/\s/','%20',$value);
		return $value;
	}
	
	//	Title
	//	v: 1.0
	function cwf_san_title($value) {
		$value=preg_replace('/[^a-zA-Z0-9&.+()\-:; ]/','',$value);
		$value=trim($value);
		return $value;
	}
	
	//	Multi character - Dangerous without slashes
	//	v: 1.0
	function cwf_san_all($value) {
		$value=preg_replace('/\n+/',' ',$value);
		$value=preg_replace('/\r+/','',$value);
		$value=trim($value);
		return $value;
	}
	
	//	Multi character with returns - Dangerous without slashes
	//	v: 1.0
	function cwf_san_alls($value) {
		$value=trim($value);
		return $value;
	}
	
	//	Filename
	//	v: 1.1
	function cwf_san_filename($value) {
		$value=preg_replace('/[^a-zA-Z0-9\-_.]/','',$value);
		return $value;
	}
	
	//	Absolute Path
	//	v: 1.1
	function cwf_san_abspath($value) {
		$value=preg_replace('/[^a-zA-Z0-9\\\-_.\/]/','',$value);
		return $value;
	}
	
	////////////////////////////////////////////////////////////////////////////
	//	Formatting
	////////////////////////////////////////////////////////////////////////////
	
	//	Format In Thousands - USA
	//	v: 1.0
	function cwf_fmt_tho($value) {
		$value=number_format($value,'0','.',',');
		return $value;
	}
	
	//	Date Format  - Full month name day with th/nd/st/etc comma year
	//	v: 1.0
	function cwf_dt_fmt($value) {
		$value=date('F jS, Y',$value);
		return $value;
	}
	
	//	Trailing slash
	//	v: 1.0
	function cwf_trailing_slash_on($value) {
		$value_len=strlen($value);
		$value_len--;
		$value_len_lch=substr($value,$value_len,'1');
		if ($value_len_lch != '/') {
			$value .='/';
		}
		return $value;
	}
	
	//	Format spaces to hyphens
	//	v: 1.1
	function cwf_fmt_sth($value) {
		$value=preg_replace('/\s/','-',$value);
		return $value;
	}

	//	Stripe slashes and trim
	//	v: 1.2
	function cwf_fmt_striptrim($value) {
		$value=trim(stripslashes($value));
		return $value;
	}
	
	////////////////////////////////////////////////////////////////////////////
	//	Random String
	////////////////////////////////////////////////////////////////////////////
	
	//	v: 1.0
	function cwf_gen_randstr($rstringlength) {
		if ($rstringlength < '1') {
			$rstringlength='25';
		}
	
		$chars='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$i='0';
		$randomize='';
	
		while ($i < $rstringlength) {
			$num=mt_rand(0,61);
			$tmp=substr($chars,$num,1);
			$randomize .=$tmp;
			$i++;
		}
	
		return $randomize;
	}
	
	////////////////////////////////////////////////////////////////////////////
	//	Filesize Human
	////////////////////////////////////////////////////////////////////////////
	
	//	v: 1.0
	function cwf_human_filesize($value) {
		if ($value > 1073741824) {
			$value=round($value/1073741824,1) .' GB';
		} elseif ($value > 1048576) {
			$value=round($value/1048576,1) .' MB';
		} elseif ($value > 1024) {
			$value=round($value/1024,1) .' KB';
		} else {
			$value .=' B';
		}
		return($value);
	}

////////////////////////////////////////////////////////////////////////////
//	Close Class
////////////////////////////////////////////////////////////////////////////
}
	