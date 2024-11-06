<?php

namespace Drupal\Tests\mimemail\Unit;

use Drupal\Tests\Core\Mail\Plugin\Mail\PhpMailTest;
use Drupal\mimemail\Plugin\Mail\MimeMail;

/**
 * Tests the MimeMail plugin by adapting its parent class's tests.
 *
 * @coversDefaultClass \Drupal\mimemail\Plugin\Mail\MimeMail
 *
 * @group mimemail
 */
class MimeMailTest extends PhpMailTest {

  /**
   * Creates a mocked MimeMail object.
   *
   * The method "doMail()" gets overridden to avoid a mail() call in tests.
   *
   * @return \Drupal\mimemail\Plugin\Mail\MimeMail|\PHPUnit\Framework\MockObject\MockObject
   *   A MimeMail plugin instance.
   */
  protected function createMimeMailInstance(): MimeMail {
    $mailer = $this->getMockBuilder(MimeMail::class)
      ->setConstructorArgs([
        \Drupal::configFactory(),
        $this->createMock('Drupal\Core\Extension\ModuleHandler'),
        $this->createMock('Drupal\Component\Utility\EmailValidator'),
        $this->createMock('Drupal\Core\Render\Renderer'),
      ])
      ->onlyMethods(['doMail'])
      ->getMock();

    $mailer->expects($this->once())->method('doMail')
      ->willReturn(TRUE);

    return $mailer;
  }

  /**
   * Tests that the parent class is correctly initialized.
   *
   * @covers ::mail
   */
  public function testMail() {
    $this->assertTrue($this->createMimeMailInstance()
      ->mail([
        'id' => 'test',
        'to' => 'test@example.com',
        'subject' => '',
        'body' => '',
        'headers' => [],
      ]));
  }

}
