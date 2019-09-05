<?php

use Phinx\Migration\AbstractMigration;

class FixShortColumns extends AbstractMigration
{
    public function up()
    {
	$this->execute("ALTER TABLE `lg_games` MODIFY COLUMN `host_ip` varchar(45) COLLATE latin1_general_ci NOT NULL DEFAULT ''");
	$this->execute("ALTER TABLE `lg_game_players` MODIFY COLUMN `ip` varchar(45) NOT NULL");
	$this->execute("ALTER TABLE `lg_games` MODIFY COLUMN `scenario_title` varchar(255) NOT NULL");
    }

    public function down()
    {
	// not supported - no reason to make the columns narrower again
    }
}
