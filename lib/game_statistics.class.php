<?php

// Saves game statistics in an SQLite database for easy analysis.
class game_statistics
{
	private $db;

	// Creates, opens and initializes the database at the given path.
	function __construct($filename)
	{
		$this->db = new SQLite3($filename);
		$this->db->exec('PRAGMA foreign_keys = true');
		$this->migrate();
	}

	private function migrate()
	{
		do {
			$this->db->exec('BEGIN');
			$v = $this->db->querySingle('PRAGMA user_version');
			if ($v == 0)
			{
				$this->db->exec(
'CREATE TABLE games (
	id             INTEGER PRIMARY KEY,
	date_started   TEXT NOT NULL,
	date_ended     TEXT NOT NULL,
	type           TEXT NOT NULL,
	scenario       TEXT NOT NULL,
	scenario_title TEXT NOT NULL,
	statistics     TEXT
)');
				$v = 1;
				$this->db->exec("PRAGMA user_version = $v");
			}
		} while (!$this->db->exec('COMMIT'));
	}

	// Collect statistics from the given reference.
	function collect($game, &$game_reference)
	{
		$ref = $game_reference->data['[Reference]'][0];

		$stmt = $this->db->prepare('INSERT INTO games VALUES (:id, :date_started, :date_ended, :type, :scenario, :scenario_title, :statistics)');
		$stmt->bindValue('id', $game->data['id'], SQLITE3_INTEGER);
		$stmt->bindValue('date_started', date(DATE_ISO8601, $game->data['date_started']), SQLITE3_TEXT);
		$stmt->bindValue('date_ended', date(DATE_ISO8601), SQLITE3_TEXT);
		$stmt->bindValue('type', $game->data['type'], SQLITE3_TEXT);
		$stmt->bindValue('scenario', game_reference_format::string($ref['[Scenario]'][0]['Filename']), SQLITE3_TEXT);
		$stmt->bindValue('scenario_title', game_reference_format::string($ref['Title']), SQLITE3_TEXT);
		if (isset($ref['Statistics']))
			$stmt->bindValue('statistics', $ref['Statistics'], SQLITE3_TEXT);
		else
			$stmt->bindValue('statistics', null, SQLITE3_NULL);
		$stmt->execute();
	}
}
