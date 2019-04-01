<?php
/**
 * Created by PhpStorm.
 * User: alanstanley
 * Date: 2019-03-13
 * Time: 10:03
 */


namespace Drupal\preservation_reports\Services;


use Solarium\Core\Client\Adapter\Guzzle;

/**
 * Class SparqlService
 * @package Drupal\islandora_sparql
 */



class SparqlService {

    private $client;
    private $config;

    public function __construct($client, $config) {
        $this->client = $client;
        $this->config = $config;
    }

    public function getQueryResults($query) {
        $settings = $this->config->get('preservation_reports.settings');
        $uri = $settings->get('sparql_endpoint');
        $response = $this->client->request('POST', $uri,
            [
                'headers' => [
                    'Accept' => 'application/sparql-results+json, application/json'
                ],
                'form_params' => [
                    'query' => $query,
                ],
            ]);
        $json = $response->getBody()->getContents();
        $results = json_decode($json);
        return $results->results->bindings;
    }
}
