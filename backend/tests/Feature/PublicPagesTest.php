<?php

namespace Tests\Feature;

use Tests\KfhiTestCase;

class PublicPagesTest extends KfhiTestCase
{
    public function test_home_page_loads(): void
    {
        $this->get('/')->assertOk()->assertSee('Empowering Children');
    }

    public function test_application_page_loads(): void
    {
        $this->get('/app')->assertOk();
    }

    public function test_scan_page_loads(): void
    {
        $this->get('/scan')->assertOk();
    }
}