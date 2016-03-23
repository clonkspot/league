<?php

use Phinx\Migration\AbstractMigration;

class DecayInterval extends AbstractMigration
{
    public function change()
    {
		$table = $this->table('lg_leagues');
		$table->addColumn('decay_interval', 'integer', array(
			'signed' => false,
			// Decay interval: 7 days minus 12 hours. Leaves some room for cron-job changes.
			'default' => (7 * 24 - 12) * 60 * 60,
			'comment' => 'interval in seconds after which score decay is applied',
		));
		$table->update();
    }
}
