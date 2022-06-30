<?php

namespace Drupal\creative\Controller;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Controller\ControllerBase;

/**
 * Provides the spam comments listing page.
 */
class SpamController extends ControllerBase {

  /**
   * Returns a render-able array for spam comments page.
   */
  public function getComments(): array {
    $query = \Drupal::entityQuery('comment');
    $ids = $query
      ->condition('status', 0)
      ->pager(30)
      ->execute();

    /** @var \Drupal\comment\Entity\Comment[] $comments */
    $comments = Comment::loadMultiple($ids);

    $render_array = [
      // Your theme hook name defined in creative.module file.
      '#theme' => 'creative_spam_comments',
      '#attached' => [
        'library' => 'creative/creative_bootstrap',
      ],
      // The items twig variable as an array as defined in creative hook_theme().
      '#items' => $comments,
      // The pager twig variable as a render array as defined in creative hook_theme()
      '#pager' => [
        '#type' => 'pager',
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    return $render_array;
  }

}
