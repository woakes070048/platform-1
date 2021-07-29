<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RestApiTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
    }

    /**
     * @return array
     */
    public function testGetCountries()
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_countries')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        return array_slice($result, 0, 5);
    }

    /**
     * @depends testGetCountries
     */
    public function testGetCountry($countries)
    {
        foreach ($countries as $country) {
            $this->client->jsonRequest(
                'GET',
                $this->getUrl('oro_api_get_country', array('id' => $country['iso2code']))
            );

            $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

            $this->assertEquals($country, $result);
        }
    }

    public function testGetRegion()
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_region', ['id' => 'US-LA'])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals('US-LA', $result['combinedCode']);
    }

    public function testGetCountryRegions()
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_country_get_regions', array('country' => 'US'))
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        foreach ($result as $region) {
            $this->client->jsonRequest(
                'GET',
                $this->getUrl('oro_api_get_region', ['id' => $region['combinedCode']]),
                [],
                $this->generateWsseAuthHeader()
            );

            $expectedResult = $this->getJsonResponseContent($this->client->getResponse(), 200);

            $this->assertEquals($expectedResult, $region);
        }
    }
}
