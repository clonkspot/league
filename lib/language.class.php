<?php



class language
{
	var $data;
	var $strings;
	var $engine_placeholder_strings;
	
	function load_data($code)
	{
		global $database;
		$a = $database->get_array("SELECT * FROM lg_languages 
			WHERE code = '".$database->escape($code)."'");
		$this->data = $a[0];
	}
	
	function load_data_by_id($id)
	{
		global $database;
		$a = $database->get_array("SELECT * FROM lg_languages 
			WHERE id = '".$database->escape($id)."'");
		$this->data = $a[0];
	}

	//add strings in every language
	function add_strings($strings)
	{
		global $database;
		
		$sid = NULL;
		$data = array();
		//get new string-id:
		$a = $database->get_array("SELECT MAX(id) AS id FROM lg_strings");
		$data['id'] = $a[0]['id']+1;
		
		if(is_array($strings))
		{
			foreach($strings AS $lang_id => $string)
			{
				if($string != "") //do not add empty strings, so a fallback-language can be used...
				{
					$data['language_id'] = $lang_id;
					$data['string'] = $string;
					$database->insert('lg_strings',$data);
				}
			}
		}
		
		return $data['id'];
	}
	
	//edit/add strings in every language
	function edit_strings($sid,$strings)
	{
		global $database;
		
		foreach($strings AS $lang_id => $string)
		{
			if(trim($string != ""))
			{
				$database->query("INSERT INTO lg_strings 
					SET language_id = '".$database->escape($lang_id)."',
					string = '".$database->escape($string)."',
					id = '".$database->escape($sid)."'
					ON DUPLICATE KEY UPDATE string = '".$database->escape($string)."'");
			}
			else
			{
				//delete when empty
				$database->query("DELETE FROM lg_strings 
					WHERE language_id = '".$database->escape($lang_id)."'
					AND id = '".$database->escape($sid)."'");
			}
		}
	}
	
	function get_strings_by_sid($sid)
	{
		global $database;
		$a = $database->get_array("SELECT * FROM lg_strings WHERE id = '".$database->escape($sid)."'");
		return $a;
	}
	
	
	function get_string_with_fallback_sql($sid_code) 
	{
		if(is_array($this->data))
			return "(SELECT string FROM lg_strings WHERE id = ($sid_code) ORDER BY language_id = ".$this->data['id']." DESC, language_id ASC LIMIT 1)";
		else
			return "(SELECT string FROM lg_strings WHERE id = ($sid_code) ORDER BY language_id ASC LIMIT 1)";
	}
	
	function get_string_with_fallback($sid)
	{
		global $database;
		$a = $database->get_array($this->get_string_with_fallback_sql("'".$database->escape($sid)."'"));
		return $a[0]['string'];
	}
	
	//delete strings in every language
	function delete_strings($id)
	{
		global $database;
		$database->delete_where('lg_strings',"id = '".$database->escape($id)."'");
	}
	
	
	function load_stringtable()
	{
		include_once('lang/engine_placeholder_strings.php');
	
		//get new lang and set it to session
		if(isset($_REQUEST['lang']))
			$_SESSION['lang'] = $_REQUEST['lang'];
			
		if(isset($_SESSION['lang']))
			$lang = $_SESSION['lang'];
		else
		{
			//check all Accept-Language-values from the http-header
			//used as standard-selection-mechanism for backend-communication
			$http_languages = $this->get_http_header_languages();
			if(is_array($http_languages))
			{
				foreach($http_languages AS $hl)
				{
					$hl = strtolower($hl);
					if($hl == 'us')
						$hl = "en"; //just a hack for now.
					global $database;
					//check if language exists in database:
					if($database->exists("SELECT * FROM lg_languages
						WHERE code = '".$database->escape($hl)."'"))
						{
							$lang = $hl;
							break; //if it does, break loop.
						}
				}
			}
		}
		
		
		if(!$lang)
			$lang = 'de'; //DEFAULT if nothing else fits...
		
		$this->load_data($lang);
			
		//check if $lang is in db:
		if(!$this->data['name'])
		{
			$log = new log();
			$log->add_error("stringtable for $lang not found");
			
			//use fallback-language: hardcode en for the moment:
			$this->load_data_by_id($this->get_fallback_language_id());
		}
		include_once('lang/strings_'.$this->data['code'].'.php');
		return true;
	}
	
	function get_string($code, $logerror = true)
	{
		return $this->s($code, $logerror);
	}
	
	//get_string:
	function s($code, $logerror = true)
	{
		if(isset($this->strings[$code]))
			return $this->strings[$code];
		else if(true == $logerror)
		{
			$log = new log();
			$trace = debug_backtrace(false);
			$log->add_error("string $code not found, from ".
				$trace[0]["file"].".".$trace[0]["line"].", ".
				$trace[1]["file"].".".$trace[1]["line"]);
			return false;
		}
	}

	function get_placeholder($code, $logerror = true)
	{
		$result = $code;
		if(isset($this->engine_placeholder_strings[$code])) {
			$result = $this->engine_placeholder_strings[$code];
		}
		else if(true == $logerror)
		{
			$log = new log();
			$trace = debug_backtrace(false);
			$log->add_error("string $code not found, from ".
				$trace[0]["file"].".".$trace[0]["line"].", ".
				$trace[1]["file"].".".$trace[1]["line"]);
		}
		return $result;
	}
	
	function get_strings()
	{
		return $this->strings;
	}

	function get_languages()
	{
		global $database;
		$a = $database->get_array("SELECT * FROM lg_languages");
		return $a;
	}
	
	function get_http_header_languages()
	{
		$languages = split(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
		for($i=0;$i<count($languages);$i++)
		{
			if(is_string(substr($languages[$i],0,2)))
			{
				$languages[$i] = substr($languages[$i],0,2);
			}
		}	
		return array_values($languages);
	}

	
	function get_current_language_code()
	{
		return $this->data['code'];
	}
	
	function get_current_language_id()
	{
		return $this->data['id'];
	}
	
	
	function get_fallback_language_id()
	{
		//todo ... for now, use the first language as fallback-language:
		global $database;
		$a = $database->get_array("SELECT id FROM lg_languages ORDER BY id ASC LIMIT 1, 1");
		return $a[0]['id'];
	}
	
	
}

?>