<?php

namespace Drupal\creative\Commands;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\creative\Service\BatchService;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Faker\Factory;

/**
 * Provides/defines custom commands associated with creative module.
 */
class CreativeCommands extends DrushCommands {

  const SLURS = ['wolf', 'werewolf', 'moon', 'full-moon'];

  /**
   * Faker Generator instance.
   *
   * @var \Faker\Generator
   */
  protected $faker;

  /**
   * Constructor setter for $faker property.
   *
   * @return void
   */
  public function setFaker() : void {
    $this->faker = Factory::create();
  }

  /**
   * Drush command that generates dummy spam comments.
   *
   * @param int $howMany
   *   How many spam comments to generate
   *   argument provided to the drush command.
   *
   * @command creative:comments:generate
   * @aliases cr-co-gn
   *
   * @usage creative:comment:generate 20
   *   20 is the number of comments to generate
   *   || drush cr-co-gn 300 -y
   *   Where 300 is the number of comments to generate
   *   and -y flags all confirmations ($this->io()->confirm()).
   */
  public function generateSpamComments(int $howMany) {
    $confirm = $this->io()->confirm('Confirm you want to generate ' . $howMany . ' comments!');
    if (!$confirm) {
      throw new UserAbortException('Try again with the number of comments you wish to generate!');
    }

    $commentEntity = \Drupal::entityTypeManager()
      ->getStorage('comment');

    for ($i = 0; $i < $howMany; $i++) {

      $key = array_rand(self::SLURS);

      $subject = $this->faker->words(3, TRUE) . self::SLURS[$key] . $this->faker->words(2, TRUE);
      $body = $this->faker->paragraph(2) . self::SLURS[$key] . $this->faker->sentence(7);
      $values = [
      // Default with install.
        'comment_type' => 'comment',
      // Published or unpublished.
        'status' => 1,
      // Node id the comment belongs to.
        'entity_id' => 2,
        'subject' => $subject,
      // Id of the comment owner/author.
        'uid' => 1,
        'comment_body' => $body,
        'entity_type' => 'node',
      // Field id on this node form.
        'field_name' => 'comment',
      ];

      /** @var \Drupal\comment\Entity\Comment $comment */
      $comment = $commentEntity->create($values);
      $comment->save();

      $this->output()->writeln('Comment created: id = ' . $comment->id() . ' || Comment subject: ' . $comment->getSubject());
    }

  }

  /**
   * Drush command that deletes all spam comments.
   *
   * @command creative:comments:delete
   *
   * @aliases cr-co-del
   *
   * @usage creative:comments:delete
   */
  public function deleteSpamCommentsEntity() {
    $query = \Drupal::entityQuery('comment');

    // Get all unpublished comments,
    // status == 0 (unpublished)
    $commentIds = $query
      ->condition('status', 0)
      ->execute();

    $items = array_values($commentIds);

    $this->logger()->notice(t('Initializing deletion of @count comments', [
      '@count' => count($items),
    ]));

    // Called on each op
    $operation_callback = [
      BatchService::class,
      'processSpamComments'
    ];

    $finish_callback = [
      BatchService::class,
      'processSpamCommentsFinished'
    ];

    // Batch operation setup/definition instance.
    $batch_builder = (new BatchBuilder())
      ->setTitle(t('Delete Spam comments batch process'))
      ->setFinishCallback($finish_callback)
      ->setInitMessage(t('Deleting Spam comments initialized'))
      ->setProgressMessage(t('Running delete Spam comments batch process'))
      ->setErrorMessage(t('Deleting Spam comments has encountered an error!'));


    // Add as many ops as you would like, Each op goes through
    // the progress bar from start to finish, then goes on to the next batch.
    $batch_builder->addOperation($operation_callback, [$items]);

    // If we are not inside a form submit handler we also need to call
    // batch_process() to initiate the redirect if needed.

    batch_set($batch_builder->toArray());
    drush_backend_batch_process();
    // Log message when done.
    $this->logger()->notice("Batch operations finished.");
  }

}
