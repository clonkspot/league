<?php

include_once('game_reference_utils.inc.php');

class game_reference
{
	var $data;
	
	var $current_path;
	var $old_tab_count;
	
	var $ini_string;
	
	
	function game_reference()
	{
		$this->data = array();
		$this->current_path = array();
		$this->old_tab_count = 0;
		
		$this->ini_string = "";
	}
	
	
	function parse_ini($string)
	{
		$this->data = array();
		$this->current_path = array();
		$this->old_tab_count = 0;
		
		$lines = explode("\n", $string);
		foreach($lines AS $line)
		{
			$this->parse_line($line);
		}
		return $this->data;
	}
	
	function parse_line($line)
	{
		$tab_count = 0;
		while($line[0] == " " && $line[1] == " ") //2 whitespaces instead of a \t
		{
			$tab_count++;
			$line = substr($line,2);
		}
		
		if($line[0] != "[")
			$tab_count++;
		
		$line = trim($line);
		if(!$line)
			return;
		
		if($line[0] == "[")
		{
				
			//echo $line;print_r($this->current_path);
			$key= 0;
			if(count($this->current_path) > 1 && 
				FALSE !== array_search(trim($line), $this->current_path, true)
				//$this->current_path[count($this->current_path)-2] == $line
				&& $tab_count < $this->old_tab_count)
			{
				$key = array_search(trim($line), $this->current_path, true);
				$current_path_count = count($this->current_path);
				/*if(trim($line) == "[Player]")
				{
					echo "\n####";
					echo "Player:";print_r($this->current_path);
					echo "key. $key - $current_path_count - line: $line\n";
					echo "###\n";
				}*/
				for($i=0;$i < $current_path_count - $key - 2; $i++)
				{
					array_pop($this->current_path);
				}
				$new_index = $this->current_path[count($this->current_path)-1]+1;
				array_pop($this->current_path);
				array_pop($this->current_path);
				$this->current_path[] = $line;
				$this->current_path[] = $new_index;

			}
			else
			{
				//echo $line."#$tab_count#".$this->old_tab_count."#\n";
				if($tab_count < $this->old_tab_count)
				{
					$i = $this->old_tab_count - $tab_count;
					while($i)
					{
						array_pop($this->current_path);
						array_pop($this->current_path);
						$i--;
					}
				}
				$this->current_path[] = $line;
				$this->current_path[] = 0;
			}
		}
		else
		{
			if($tab_count < $this->old_tab_count)
			{
				$i = $this->old_tab_count - $tab_count;
				while($i)
				{
					array_pop($this->current_path);
					array_pop($this->current_path);
					$i--;
				}
			}
			
			$a = &$this->data;
			foreach($this->current_path AS $path)
			{
				$a = &$a[$path];
			}
			$line_data = explode("=",$line,2);
			
			
			//just create an array when there are multiple values
			/*if(isset($a[$line_data[0]]))
			{
				if(!is_array($a[$line_data[0]]))
					$a[$line_data[0]] = array($a[$line_data[0]]);
				$a[$line_data[0]][] = $line_data[1];
			}
			else*/
				$a[$line_data[0]] = $line_data[1];
		}
		
		$this->old_tab_count = $tab_count;
		
	}
	
	function get_ini()
	{
		$this->ini_string = "";
		reset($this->data);
		$this->create_line($this->data);
		return $this->ini_string;
	}
	
	function create_line($array, $depth = 0, $last_key = "")
	{
		foreach($array AS $key => $a)
		{
			if(is_array($a))
			{
				if(is_int($key))
				{
					$this->ini_string .= "\n";
					for($i=0;$i<$depth-1;$i++)
						//$this->ini_string .= "\t"; 
						$this->ini_string .= "  "; 
					$this->ini_string .= $last_key."\n";
				}
				reset($a);
				if(is_int($key))
					$this->create_line($a, $depth, $key);
				else
					$this->create_line($a, $depth+1, $key);	
			}
			else
			{
				/*if(is_int($key))
				{
					for($i=0;$i<$depth-2;$i++)
						$this->ini_string .= "\t";
					$this->ini_string .= $last_key."=".$a."\n";
				}
				else*/
				{
					for($i=0;$i<$depth-1;$i++)
						//$this->ini_string .= "\t";
						$this->ini_string .= "  "; 
					$this->ini_string .= $key."=".$a."\n";
				}
			}	
		}
	}
	
	
	function get_data()
	{
		return $this->data;
	}
	
	function update($game_reference)
	{
		$this->data = $game_reference->get_data();
		//deactivate (working) update-code for now
		//$this->update_array($this->data,$game_reference->get_data(), NULL);
	}
	

	
	
	function update_array(&$this_data, &$new_data, $last_this_data_key)
	{
		foreach($new_data AS $key => $data)
		{
			if(is_array($data))
			{
				$array_id = $this->get_array_id($last_this_data_key);
				if(is_array($this_data) &&
					NULL != $array_id)
				{
					//search for array with the same array_id
					foreach($this_data AS $this_inner_key => $this_inner_data)
					{
						if(isset($this_inner_data[$array_id]) && $this_inner_data[$array_id] == $data[$array_id])
						{
							$key_to_update = $this_inner_key;
						}
					}
					//if no such array was found, add a new entry:
					if($key_to_update === NULL)
					{
						//add new entry and get its key
						$this_data[] = array();
						end($this_data);
						$key_to_update = key($this_data);
					}				
				}
				else
				{
					$key_to_update = $key;
				}
				//do the update:
				$this->update_array($this_data[$key_to_update],$data, $key_to_update);
			}
			else
			{
				$this_data[$key] = $data;
			}
		}	
	}
	
	function get_array_id($array_name)
	{
	
		if(!is_string($array_name))
			return NULL;
			
		switch($array_name) {
		    case '[Player]':
		    {
		        return "ID";
		    }
			case '[ResCore]':
		    {
		        return "ID";
		    }
			default:
			{
				return NULL;
			}
		}
	}
	
	
	function get_serialized_data()
	{
		$data = array();
		array_deep_copy($this->data, $data);
		//$data = $this->data;
		base64_encode_multi($data);
		return serialize($data);
	}
	
	function set_serialized_data($data)
	{
		$data = unserialize(stripslashes($data));
		base64_decode_multi($data);
		//$this->data = $data;
		$this->data = array();
		array_deep_copy($data, $this->data);
	}

}

?>
