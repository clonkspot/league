<?php

require_once('log.class.php');


//assume magic_quotes = on

class database
{
	
	var $link;
	var $host;
	var $user;
	var $password;
	var $name;
	
	var $debug_sql;
	
	function database($host, $user, $password, $name = NULL)
	{
		$this->host = $host;
		$this->user = $user;
		$this->password = $password;
		$this->name = $name;
		
		$this->debug_sql = array();
		
		$this->connect();
	}	
	
	function connect()
	{
		$this->link = mysqli_connect($this->host,$this->user,$this->password)
			or die("Keine Verbindung möglich");
			
		if($this->name !== NULL)
		{
			mysqli_select_db($this->link,$this->name) 
				or die("Datenbank nicht gefunden oder keine Berechtigung");
		}

		// Disable strict mode.
		mysqli_query($this->link, "SET sql_mode=''")
		        or die ("Failed to set sql_mode");
	}
	
	function disconnect()
	{
		mysqli_close($this->link);
	}
	
	function query($sql)
	{
		global $debug;
		global $debug_user;
		global $debug_sql_slow_log;
		if($debug || $debug_user || $debug_sql_slow_log)
		{
			
			$profiling_start_time = microtime_float();
		}
		$r = mysqli_query($this->link,$sql);
		$error = mysqli_error($this->link);
		if (!$r) 
		{
			//$log = new log();
			//$log->add_error(mysqli_error($this->link)." - im Query: $sql");
			
			$file = fopen("logs/sql_error_log.txt",'a+');
			$date=date("d.m.Y",time());
			$time=date("H:i:s",time());
			fwrite($file,"\n [$date - $time] ".$error." - im Query: $sql");
			fclose($file);

			global $redis;
			if (isset($redis)) {
				$redis->incr('league:metrics:sql_errors_total');
			}
		}
		
		if($debug || $debug_user)
		{
			$debug_sql['query'] = $sql;
			$debug_sql['time'] = microtime_float() - $profiling_start_time;
			$debug_sql['error'] = $error;
			$this->debug_sql[] = $debug_sql;
		}
		
		if($debug_sql_slow_log)
		{
			$duration = microtime_float() - $profiling_start_time;
			
			if($duration > 1.0)
			{
				$duration = sprintf("%05.3f",$duration);
				$file = fopen("logs/sql_slow_log.txt",'a+');
				$date=date("d.m.Y",time());
				$time=date("H:i:s",time());
				$error = mysqli_error($this->link);
				fwrite($file,"\n [$date - $time] $duration: $sql");
				if(!$r)
					fwrite($file,"\n ERROR: $error");
				fclose($file);

				global $redis;
				if (isset($redis)) {
					$redis->incr('league:metrics:sql_slow_queries_total');
				}
			}
		}

		return $r;
	}
	
	function get_array($sql)
	{
		$r = $this->query($sql);
		$a = array();
		if ($r)
		{
			$i=0;
			while ($row = mysqli_fetch_assoc($r))
			{
				$a[$i]=$row;
				$i++;
			}
		}

		return $a;
	}
	
	function insert($table,$a)
	{
		foreach($a as $key=>$value)
		{
			$cols.="".$key.",";
			$vals.="'".mysqli_real_escape_string($this->link,$value)."',";
		}
		$cols=substr($cols,0,strlen($cols)-1);
		$vals=substr($vals,0,strlen($vals)-1);
		$sql="INSERT INTO $table ($cols) values ($vals)";

		$this->query($sql);
		if (mysqli_affected_rows($this->link) == -1)
			return FALSE;
		else
			return $this->get_last_insert_id();
	}
	
	function insert_update($table,$a)
	{
		if (!empty($table))
		{
			$setstr="";
			foreach($a as $key=>$value)
			{
				$setstr.="$key = '".mysqli_real_escape_string($this->link,$value)."', ";
			}
			$setstr=substr($setstr,0,strlen($setstr)-2);
			$sql="INSERT INTO $table SET $setstr 
			ON DUPLICATE KEY UPDATE $setstr";
			$this->query($sql);
			if (mysqli_affected_rows($this->link) == -1)
				return FALSE;
			else
				return $this->get_last_insert_id();
		}
		return FALSE;
	}
	

	function delete($table,$id)
	{
		$sql="DELETE FROM $table WHERE id = '".mysqli_real_escape_string($this->link,$id)."'";
		$this->query($sql);
		if (mysqli_affected_rows($this->link) == -1)
			return FALSE;
		else
			return TRUE;
	}
	
	function delete_where($table,$where)
	{
		if (!empty($where))
		{
			$sql="DELETE FROM $table WHERE $where";
			$this->query($sql);
			
		/*	if($table == 'lg_game_players') // DEBUG:
			{
				$file = fopen("sqlerror.txt",'a+');
				$date=date("d.m.Y",time());
				$time=date("H:i:s",time());
				fwrite($file,"\n [$date - $time] DELETE: ".$sql);
				fclose($file);
			}            */
			
			if (mysqli_affected_rows($this->link) == -1)
				return FALSE;
			else
				return TRUE;
		}
		return FALSE;
	}

	function update($table,$arr)
	{
		$setstr="";
		foreach($arr as $key=>$value)
		{
			if ($key != "id")
				$setstr.="$key = '".mysqli_real_escape_string($this->link,$value)."', ";
			else
				$id=$value;
		}
		$setstr=substr($setstr,0,strlen($setstr)-2);
		$sql="UPDATE $table SET $setstr WHERE id = '$id'";
	//	$this->query("start");
		$this->query($sql);
		//$rand = rand();
		//$this->query("S#$setstr#$sql#$rand<hr>");
	//	print_a($arr);
		//echo "#$setstr#$sql#$rand<hr>";
		if (mysqli_affected_rows($this->link) == -1)
			return FALSE;
		else
			return TRUE;
	}

	function update_where($table,$where,$a)
	{
		if (!empty($where) && !empty($table))
		{
			$setstr="";
			foreach($a as $key=>$value)
			{
				$setstr.="$key = '".mysqli_real_escape_string($this->link,$value)."', ";
			}
			$setstr=substr($setstr,0,strlen($setstr)-2);
			$sql="UPDATE $table SET $setstr WHERE $where";
			$this->query($sql);
			if (mysqli_affected_rows($this->link) == -1)
				return FALSE;
			else
				return TRUE;
		}
		return FALSE;
	}
	
	function exists($query)
	{
		$query = "SELECT EXISTS ($query LIMIT 1) AS e";
		$a = $this->get_array($query);
		
		if($a[0]['e']==1)
			return TRUE;
		else
			return FALSE;
	}
	
	function get_last_insert_id()
	{
		return mysqli_insert_id($this->link);
	}

	function get_num_rows(&$result)
	{
		return mysqli_num_rows($result);
	}
	
	function escape($s)
	{
        return mysqli_real_escape_string($this->link,$s);
    }
	
	function get_affected_rows()
	{
		return mysqli_affected_rows($this->link);
	}
	
	function display_debug_sql()
	{
		global $debug;
		global $debug_user;
		if(!$debug && !$debug_user)
			return;
			
		echo "<table>";
		foreach($this->debug_sql AS $key => $sql)
		{
			echo "<tr style=\"background-color:".($key % 2 ? '#CCCCCC':'#EEEEEE').";\">
				<td><b>SQL:</b></td>
				<td>";
				printf("%05.5f",$sql['time']);
			echo "s</td>
				<td><pre style=\"display:inline;\">".$sql['query']."</pre>";
			if(isset($sql['error']) && $sql['error'] != "")
				echo "<br><b>ERROR:</b> <pre style=\"display:inline;\">".$sql['error']."</pre>";
			echo "</td>
			</tr>";	
		}
		echo "</table>";
	}
	

	/*function profiler_print_time($str = "", $str2 = "")
	{
		global $search_debug;
		if(!$search_debug) return;
		global $profiler_last_time;
		echo "<font color=red size=smaller>";
		printf("%05.5f",(microtime_float() - $profiler_last_time));
		echo ": $str - <b>$str2</b></font><br>";
		$profiler_last_time = microtime_float();
	}*/
}

function microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}


?>
