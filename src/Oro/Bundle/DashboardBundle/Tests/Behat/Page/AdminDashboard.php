<?php

namespace Oro\Bundle\DashboardBundle\Tests\Behat\Page;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class AdminDashboard extends Page
{
    #[\Override]
    public function open(array $parameters = [])
    {
        $this->getMainMenu()->openAndClick('Dashboards/Dashboard');
    }
}
