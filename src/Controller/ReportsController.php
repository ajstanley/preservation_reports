<?php

namespace Drupal\preservation_reports\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller.
 */
class ReportsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * ReportsController constructor.
   * @param $sparql
   */
  public function __construct( $sparql) {
    $this->sparql = $sparql;
  }

  /**
   * @param ContainerInterface $container
   * @return ControllerBase|ReportsController
   */
  public static function create(ContainerInterface $container) {
    $sparql = $container->get('preservation_reports.sparqlqueryrunner');
    return new static($sparql);
  }

    /**
     * Basic reports.
     *
     * @return array
     */

    public function reports() {
        return [
            '#theme' => 'preservation_reports_chart',
            '#chart' => TRUE,
            '#pieheading' => "Relative percentages of Original File MIME types",
            '#lineheading' => "Sample chart showing fixity check event failures over time",
        ];
    }

  /**
   * @return array
   * @throws InvalidPluginDefinitionException
   * @throws PluginException
   */
    public function summary() {

        $data['media'] = $this->getMediaMimeSummary();
        $data['media2'] = $this->getMediaUseSummary();
        $data['islandora_object'] = $this->getIslandoraSummary();
        $totals = $this->getTriplestoreTotals();


        return [
            '#theme' => 'preservation_summary',
            '#data' => $data,
            '#attached' => [
                'library' => [
                    'preservation_reports/preservation_reports',
                ]
            ]

        ];
    }

  /**
   * Gets and parses summary data from database.
   * @return array
   */
    private function getIslandoraSummary() {
        $query = \Drupal::entityQueryAggregate('node');
        $results = $query
            ->condition('type', 'islandora_object')
            ->groupBy('field_model')
            ->aggregate('nid', 'COUNT')
            ->execute();
        $data = [];
        $item_count = 0;
        foreach ($results as $result) {
            $term = Term::load($result['field_model_target_id']);
            $name = $term->getName();
            $data['filter'][$name] = $result['nid_count'];
            $item_count += $result['nid_count'];

        }
        $data['title'] = $this->t('Islandora Objects in Drupal database by content type');
        $data['total'] = $item_count;
        $data['filter_type'] = $this->t("Content Type");
        return $data;
    }

  /**
   * @return array
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
    private function getMediaMimeSummary() {
        $term_name = 'Original File';
        $terms = \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->loadByProperties(['name' => $term_name]);
        $term = reset($terms);
        $tid = $term->id();

        $query = \Drupal::entityQueryAggregate('media');
        $results = $query
            ->groupBy('field_mime_type')
            ->condition('field_media_use', $tid)
            ->aggregate('mid', 'COUNT')
            ->execute();
        $data = [];
        $item_count = 0;
        foreach ($results as $result) {
            $data['filter'][$result['field_mime_type']] = $result['mid_count'];
            $item_count += $result['mid_count'];

        }
        $data['title'] = $this->t('Media Objects in Drupal database by mimetype');
        $data['total'] = $item_count;
        $data['filter_type'] = $this->t("Mime Type");
        return $data;
    }

  /**
   * @return array
   */
    private function getMediaUseSummary() {
        $query = \Drupal::entityQueryAggregate('media');
        $results = $query
            ->groupBy('field_media_use')
            ->aggregate('mid', 'COUNT')
            ->execute();
        $data = [];
        $item_count = 0;
        foreach ($results as $result) {
            $term = Term::load($result['field_media_use_target_id']);
            if ($term) {
                $name = $term->getName();
                $data['filter'][$name] = $result['mid_count'];
                $item_count += $result['mid_count'];
            }

        }
        $data['title'] = $this->t('Media Objects in Drupal database by Media Use');
        $data['total'] = $item_count;
        $data['filter_type'] = $this->t("Media");
        return $data;
    }

  /**
   * @return mixed
   */
    private function getTriplestoreTotals() {
        $all_count = 'SELECT (COUNT(?s) AS ?triples) WHERE { ?s ?p ?o }';
        $object_count = 'SELECT (COUNT(?s) AS ?triples) WHERE {?s rdf:type <http://pcdm.org/models#Object>}';
        $mimetypes_by_count = "SELECT ?o(COUNT(?s) AS ?triples) WHERE {?s <http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#hasMimeType> ?o }group by ?o";
        // Get count of all triples
        $results = $this->sparql->getQueryResults($all_count);
        $totals['triples'] = $results[0]->triples->value;
        // Get count of all Islandora objects
        $results = $this->sparql->getQueryResults($object_count);
        $totals['objects'] = $results[0]->triples->value;
        // Get all represented mimetypes
        $mimes = $this->sparql->getQueryResults($mimetypes_by_count);
        foreach ($mimes as $mime) {
            $mime_totals[$mime->o->value] = $mime->triples->value;
        }
        $totals['mimes'] = $mime_totals;

        return $totals;

    }

}
