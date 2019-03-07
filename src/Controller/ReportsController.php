<?php

namespace Drupal\preservation_reports\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Controller.
 */
class ReportsController extends ControllerBase {
    /**
     * Basic reports.
     *
     * @return string
     */
    public function reports() {
        return [
            '#theme' => 'preservation_reports_chart',
            '#chart' => TRUE,
            '#pieheading' => "Relative percentages of Original File MIME types",
            '#lineheading' => "Sample chart showing fixity check event failures over time",
        ];
    }

    public function summary() {
        $data['media'] = $this->getMediaMimeSummary();
        $data['media2'] = $this->getMediaUseSummary();
        $data['islandora_object'] = $this->getIslandoraSummary();


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
            $name = $term->getName();
            $data['filter'][$name] = $result['mid_count'];
            $item_count += $result['mid_count'];

        }
        $data['title'] = $this->t('Media Objects in Drupal database by Media Use');
        $data['total'] = $item_count;
        $data['filter_type'] = $this->t("Media");
        return $data;
    }
}
