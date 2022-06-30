<?php

namespace Drupal\creative\Service;

use Drupal\comment\Entity\Comment;

/**
 * Class BatchService provides batch process callbacks and op(s).
 * Documentation
 * @ https://api.drupal.org/api/drupal/core%21includes%21form.inc/group/batch/8.8.x
 * Also found this video with great examples/explanations on batch api
 * @ https://www.youtube.com/watch?v=Xw90GKoc6Kc
 */
class BatchService
{

  /**
   * @param array $items
   * @param array|\DrushBatchContext $context
   * @return void
   */
  public static function processSpamComments($items, &$context)
  {
    // Context sandbox is empty on initial load. Here we take care of things
    // that need to be done only once. This context is then subsequently
    // available for every subsequent batch run.
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      //$context['sandbox']['errors'] = [];
      $context['sandbox']['max'] = count($items);
    }

    // If we have nothing to process, mark the batch as 100% complete (0 = not started
    // , eg 0.5 = 50% completed, 1 = 100% completed).
    if (!$context['sandbox']['max']) {
      $context['finished'] = 1;
      return;
    }

    // If we haven't yet processed all
    if ($context['sandbox']['progress'] < $context['sandbox']['max']) {

      // This is a counter that's passed from batch run to batch run.
      if (isset($items[$context['sandbox']['progress']])) {
        $comment = Comment::load($items[$context['sandbox']['progress']]);

        // Let the editor know info about what is being run.
        // If via drush command, also let user know of the
        // progress percentage as they will not see the progress bar.
        if (PHP_SAPI === 'cli') {
          $context['message'] = t('[@percentage] Deleting comment subject: "@subject" and id: @id', [
            '@percentage' => round(($context['sandbox']['progress'] / $context['sandbox']['max']) * 100) . '%',
            '@subject' => $comment->getSubject(),
            '@id' => $comment->id(),
          ]);
        } else {
          $context['message'] = t('Deleting "@subject" @id', [
            '@subject' => $comment->getSubject(),
            '@id' => $comment->id(),
          ]);
        }

        // Delete/remove the comment entry.
        $comment?->delete();
      }
      $context['sandbox']['progress']++;
    }
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }

  /**
   * @param $success
   * @param array $results
   * @param array $operations
   * @return void
   */
  public static function processSpamCommentsFinished($success, array $results, array $operations): void
  {
    $messenger = \Drupal::messenger();

    if ($success) {
      $messenger->addMessage(t('Spam comments processed successfully.'));
    } else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $messenger->addMessage(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

}
