<?php

namespace Drupal\creative\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class CreativeTwigExtension provides twig filter functions.
 */
class CreativeTwigExtension extends AbstractExtension {

  /**
   * @return \Twig\TwigFilter[]
   */
  public function getFilters() {
    return [
      new TwigFilter('creative_format_date', [$this, 'renderFormatDate']),
    ];
  }

  /**
   * @param $date
   * @return string
   */
  public function renderFormatDate($date) {
    return $this->timeElapsedString($date);
  }

  /**
   * @param $datetime
   * @return string
   */
  private function timeElapsedString($datetime) {
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $formatter */
    $formatter = \Drupal::service('date.formatter');
    $now = \Drupal::time()->getRequestTime();

    /** @var \Drupal\Core\Datetime\FormattedDateDiff $diff */
    $diff = $formatter->formatDiff($datetime, $now, [
      'granularity' => 7,
      'return_as_object' => TRUE,
      ]);

    $diff = $diff->getString();

    return ' '.$diff . ' ago.';
  }

}
