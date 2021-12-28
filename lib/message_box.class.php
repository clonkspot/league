<?php

class message_box
{
	var $messages;
	
	function __construct()
	{
		$this->messages = array();
	}
	
	
	function add_error($text)
	{
		$message = array();
		$message['text'] = $text;
		$message['type'] = 'error';
		$this->messages[] = $message;
	}	
	
	function add_info($text)
	{
		$message = array();
		$message['text'] = $text;
		$message['type'] = 'info';
		$this->messages[] = $message;
	}	
	
	function get_messages()
	{
		return $this->messages;
	}
	
	function get_message_count()
	{
		return count(array_values($this->messages));
	}
	
}

?>
