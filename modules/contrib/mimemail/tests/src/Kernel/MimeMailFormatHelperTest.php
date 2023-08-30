<?php

namespace Drupal\Tests\mimemail\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\mimemail\Utility\MimeMailFormatHelper;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests that Mime Mail utility functions work properly.
 *
 * @coversDefaultClass \Drupal\mimemail\Utility\MimeMailFormatHelper
 *
 * @group mimemail
 */
class MimeMailFormatHelperTest extends KernelTestBase {
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'mailsystem',
    'mimemail',
    'system',
    'user',
  ];

  /**
   * Administrator.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userA;

  /**
   * A different authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userB;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Sets up an anonymous and two authenticated users.
    $this->adminUser = $this->setUpCurrentUser([
      'uid' => 1,
      'name' => 'username with spaces',
      'mail' => 'admin@test.example.com',
    ]);
    $this->userA = $this->createUser([], 'CapitaL', FALSE, ['mail' => 'namea@example.com']);
    $this->userB = $this->createUser([], '', FALSE, ['mail' => 'nameb@example.com']);
  }

  /**
   * Tests formatting an address string.
   *
   * @param string|array|\Drupal\user\UserInterface $address
   *   MimeMailFormatHelper::mimeMailAddress() accepts addresses in one of
   *   four different formats:
   *   - A text email address, e.g. someone@example.com.
   *   - An array where the values are each a text email address.
   *   - An associative array to represent one email address, containing keys:
   *     - mail: A text email address, as above.
   *     - (optional) name: A text name to accompany the email address,
   *       e.g. 'John Doe'.
   *   - A fully loaded object implementing \Drupal\user\UserInterface.
   * @param string|array $result
   *   Email address after formatting.
   * @param string|array $simplified_result
   *   Simplified email address after formatting.
   *
   * @dataProvider providerAddress
   * @dataProvider providerArrayOfAddresses
   * @dataProvider providerAssociativeAddressArray
   * @covers ::mimeMailAddress
   */
  public function testAddress($address, $result, $simplified_result): void {
    // Test not simplified.
    $formatted = MimeMailFormatHelper::mimeMailAddress($address, $simplify = FALSE);
    $this->assertSame($result, $formatted);

    // Test simplified.
    $formatted = MimeMailFormatHelper::mimeMailAddress($address, $simplify = TRUE);
    $this->assertSame($simplified_result, $formatted);
  }

  /**
   * Provides test data for testAddress().
   *
   * Tests addresses provided as text email addresses, e.g. someone@example.com.
   *
   * @return array<string, array<int, string>>
   *   An array of test case data, where each array element contains an array
   *   of three items corresponding to the three input parameters needed for
   *   each case:
   *   - address: Email address to test.
   *   - result: Expected return value from
   *     MimeMailFormatHelper::mimeMailAddress($address, $simplify = FALSE).
   *   - simplified_result: Expected return value from
   *     MimeMailFormatHelper::mimeMailAddress($address, $simplify = TRUE).
   */
  public function providerAddress(): array {
    $addresses = [
      'Encoded display-name' => [
        '=?utf-8?Q?Drupal=20Supporters?= <support@association.drupal.org>',
        '=?utf-8?Q?Drupal=20Supporters?= <support@association.drupal.org>',
        'support@association.drupal.org',
      ],
      'Display-name needing quotes' => [
        'Acme Industries, Inc. <no-reply@acme.example.com>',
        '"Acme Industries, Inc." <no-reply@acme.example.com>',
        'no-reply@acme.example.com',
      ],
      'UTF-8 display-name' => [
        '山田太郎 <taro@example.com>',
        '=?utf-8?Q?=E5=B1=B1=E7=94=B0=E5=A4=AA=E9=83=8E?= <taro@example.com>',
        'taro@example.com',
      ],
      'No display-name' => [
        'alpher@example.com',
        'alpher@example.com',
        'alpher@example.com',
      ],
      'No display-name, address between < and >' => [
        '<tr@202830.no-reply.drupal.org>',
        '<tr@202830.no-reply.drupal.org>',
        'tr@202830.no-reply.drupal.org',
      ],
    ];

    return $addresses;
  }

  /**
   * Provides test data for testAddress().
   *
   * Tests addresses provided as an array of text email addresses.
   *
   * @return array<string, array<int, string>>
   *   An array of test case data, where each array element contains an array
   *   of three items corresponding to the three input parameters needed for
   *   - address: Array of email addresses to test.
   *   - result: Expected return value from
   *     MimeMailFormatHelper::mimeMailAddress($address, $simplify = FALSE).
   *   - simplified_result: Expected return value from
   *     MimeMailFormatHelper::mimeMailAddress($address, $simplify = TRUE).
   */
  public function providerArrayOfAddresses(): array {
    $addresses = [
      'Array of address strings' => [
        [
          '=?utf-8?Q?Drupal=20Supporters?= <support@association.drupal.org>',
          'Acme Industries, Inc. <no-reply@acme.example.com>',
          '山田太郎 <taro@example.com>',
          'bethe@example.com',
          '<subscriber@example.com>',
        ],
        [
          '=?utf-8?Q?Drupal=20Supporters?= <support@association.drupal.org>',
          '"Acme Industries, Inc." <no-reply@acme.example.com>',
          '=?utf-8?Q?=E5=B1=B1=E7=94=B0=E5=A4=AA=E9=83=8E?= <taro@example.com>',
          'bethe@example.com',
          '<subscriber@example.com>',
        ],
        [
          'support@association.drupal.org',
          'no-reply@acme.example.com',
          'taro@example.com',
          'bethe@example.com',
          'subscriber@example.com',
        ],
      ],
    ];

    return $addresses;
  }

  /**
   * Provides test data for testAddress().
   *
   * Tests addresses provided as associative arrays containing keys:
   * - mail: A text email address, as above.
   * - (optional) name: A text name to accompany the email address,
   *   e.g. 'John Doe'.
   *
   * @return array<string, array<int, string>>
   *   An array of test case data, where each array element contains an array
   *   of three items corresponding to the three input parameters needed for
   *   - address: Associative array of addresses, with 'mail' and 'name' keys:
   *     - mail: A text email address, as above.
   *     - (optional) name: A text name to accompany the email address,
   *       e.g. 'John Doe'.
   *   - result: Expected return value from
   *     MimeMailFormatHelper::mimeMailAddress($address, $simplify = FALSE).
   *   - simplified_result: Expected return value from
   *     MimeMailFormatHelper::mimeMailAddress($address, $simplify = TRUE).
   */
  public function providerAssociativeAddressArray(): array {
    $addresses = [
      'Encoded display-name in array' => [
        ['name' => '=?utf-8?Q?Drupal=20Supporters?=', 'mail' => 'support@association.drupal.org'],
        '=?utf-8?Q?Drupal=20Supporters?= <support@association.drupal.org>',
        'support@association.drupal.org',
      ],
      'Display-name needing quotes in array' => [
        ['name' => 'Acme Industries, Inc.', 'mail' => 'no-reply@acme.example.com'],
        '"Acme Industries, Inc." <no-reply@acme.example.com>',
        'no-reply@acme.example.com',
      ],
      'UTF-8 display-name in array' => [
        ['name' => '山田太郎', 'mail' => 'taro@example.com'],
        '=?utf-8?Q?=E5=B1=B1=E7=94=B0=E5=A4=AA=E9=83=8E?= <taro@example.com>',
        'taro@example.com',
      ],
      'No display-name' => [
        ['name' => '', 'mail' => 'gamow@example.com'],
        'gamow@example.com',
        'gamow@example.com',
      ],
    ];

    return $addresses;
  }

  /**
   * Tests MimeMailFormatHelper::mimeMailAddress() with user objects.
   *
   * Tests addresses provided as fully loaded objects implementing
   * \Drupal\user\UserInterface. This can't be done in a data provider
   * function because the User module will not be set up at the time
   * the data provider is executed.
   *
   * @covers ::mimeMailAddress
   */
  public function testAddressUserObject(): void {
    // Format of each element is:
    // - address: Instance of a User object containing an email field.
    // - result: Expected return value from
    //   MimeMailFormatHelper::mimeMailAddress($address, $simplify = FALSE).
    // - simplified_result: Expected return value from
    //   MimeMailFormatHelper::mimeMailAddress($address, $simplify = TRUE).
    $addresses = [
      'User name with spaces' => [
        $this->adminUser,
        'username with spaces <admin@test.example.com>',
        'admin@test.example.com',
      ],
      'User name with capital letters' => [
        $this->userA,
        'CapitaL <namea@example.com>',
        'namea@example.com',
      ],
      'Random user name' => [
        $this->userB,
        $this->userB->getAccountName() . ' <nameb@example.com>',
        'nameb@example.com',
      ],
    ];

    foreach ($addresses as $address) {
      // Test not simplified.
      $formatted = MimeMailFormatHelper::mimeMailAddress($address[0], $simplify = FALSE);
      $this->assertSame($address[1], $formatted);

      // Test simplified.
      $formatted = MimeMailFormatHelper::mimeMailAddress($address[0], $simplify = TRUE);
      $this->assertSame($address[2], $formatted);
    }
  }

  /**
   * Tests helper function for formatting URLs.
   *
   * @param string $url
   *   URL to test.
   * @param bool $absolute
   *   Whether the URL is absolute.
   * @param string $expected
   *   URL after formatting.
   *
   * @dataProvider providerTestUrl
   * @covers ::mimeMailUrl
   */
  public function testUrl(string $url, bool $absolute, string $expected): void {
    $result = MimeMailFormatHelper::mimeMailUrl($url, $absolute);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for testUrl().
   */
  public function providerTestUrl(): array {
    // Format of each element is:
    // - url: URL to test.
    // - absolute: Whether the URL is absolute.
    // - expected: URL after formatting.
    return [
      'Hash mark URL without fragment left intact.' => [
        '#',
        FALSE,
        '#',
      ],
      'Security token removed from styled image URL.' => [
        '/sites/default/files/styles/thumbnail/public/image.jpg?itok=Wrl6Qi9U',
        TRUE,
        '/sites/default/files/styles/thumbnail/public/image.jpg',
      ],
      'Space in the filename of the attachment left intact.' => [
        $url = 'public://' . $this->randomMachineName() . ' ' . $this->randomMachineName() . '.' . $this->randomMachineName(3),
        TRUE,
        $expected = $url,
      ],
      'URL-encoded spaces in the filename of the attachment left intact.' => [
        $url = 'public://' . $this->randomMachineName() . '+' . $this->randomMachineName() . '.' . $this->randomMachineName(3),
        TRUE,
        $expected = $url,
      ],
      'Base64 encoded image using data: scheme' => [
        $url = "data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDIyLjEuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzIiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCAyMTYwIDI4ODAiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDIxNjAgMjg4MDsiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8c3R5bGUgdHlwZT0idGV4dC9jc3MiPgoJLnN0MHtmaWxsOiNGRkZGRkY7fQo8L3N0eWxlPgo8ZyBpZD0iTGl2ZWxsb18yXzFfIj4KCTxnIGlkPSJMaXZlbGxvXzEtMl8xXyI+CgkJPHBhdGggY2xhc3M9InN0MCIgZD0iTTExODYsMTA4MC41Yy0zNi0zNi03NC4yLTc0LjYtMTA4LjEtMTEzLjFjLTMxLjcsMzYtNjUuMiw2OC44LTk4LjcsMTAyLjNjLTguMSw4LTE0LjcsMTcuNC0xOS41LDI3LjcKCQkJYy03LjQsMTYuMi05LjgsMzQuMy02LjgsNTEuOWMwLDEuOCwwLDMuNiwwLDUuNGMzLjEsOS43LDcuNywxOC44LDEzLjcsMjdjMSwxLjQsMS44LDIuOCwyLjUsNC4zYzIyLDI3LjQsNzQuMiw3Ny44LDEwMy40LDEwNS45CgkJCWwxNzMsMTgwLjJsNTEuOSw1NC44YzQyLjgsNDQuNCw4MS40LDkyLjYsMTE1LjMsMTQ0LjFsMCwwYzQuMSw3LjEsMTAuNiwxMi4zLDE4LjQsMTQuOGgzLjZjOC40LTEuNCwxNS4zLTcuNCwxOC0xNS41bDAsMAoJCQljMTEuMi0zNi4yLDE2LjktNzMuOCwxNi45LTExMS43Yy0xLTg3LjctMjguOC0xNzIuOS03OS42LTI0NC4zYy01OS45LTgzLjctMTI3LjktMTYxLjItMjAzLjItMjMxLjNMMTE4NiwxMDgwLjV6Ii8+CgkJPHBhdGggY2xhc3M9InN0MCIgZD0iTTEyMzQuMywxNjYwLjdjLTQyLjktNTIuMy04NC4zLTEwMC4yLTE0OS41LTE2OS43Yy01NS4xLDYzLjEtMTA1LjYsMTExLjctMTQ3LjQsMTYxLjEKCQkJYy03My42LDc5LjctNjguNywyMDMuOSwxMSwyNzcuNWM3OS43LDczLjYsMjAzLjksNjguNywyNzcuNS0xMWM2Mi41LTY3LjYsNjkuNi0xNjkuNiwxNy4xLTI0NS4zCgkJCUMxMjQwLjQsMTY2OC44LDEyMzcuNSwxNjY0LjYsMTIzNC4zLDE2NjAuN3oiLz4KCQk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNOTc5LjksMTQwNS41YzQuOC01LjUsNC44LTEzLjYsMC0xOS4xbC0xMjcuMi0xMzAuMWwtMjcuNC0yNS45Yy0yMiwyNS42LTQyLjYsNTIuNC02MS42LDgwLjQKCQkJYy0zMC44LDQ0LjQtNTMuNyw5My44LTY3LjcsMTQ1LjlsMCwwYy0xNS4zLDYwLTEzLjksMTIzLjEsNC4zLDE4Mi4zbDIuNSw1LjRjMy4yLDYuNCw2LjgsMTIuNSwxMC44LDE4LjRsMCwwCgkJCWMwLDAsMTQuOCwyMy44LDI3LjcsMjQuOWg0LjdjNC40LTEsOC00LjEsOS43LTguM2MxMi0yNS4yLDI3LTQ4LjcsNDQuNy03MC4zbDQ2LjUtNTYuNmwwLDBMOTc5LjksMTQwNS41eiIvPgoJCTxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0xMDgyLjYsMzYzLjRDNDg3LjMsMzYzLjIsNC42LDg0NS42LDQuNCwxNDQwLjhzNDgyLjIsMTA3OCwxMDc3LjQsMTA3OC4yCgkJCWM1OTUuMywwLjIsMTA3OC00ODIuMiwxMDc4LjItMTA3Ny40YzAtMC4xLDAtMC4yLDAtMC40QzIxNjAsODQ2LjEsMTY3Ny43LDM2My42LDEwODIuNiwzNjMuNHogTTEwNzYuNCwyMTI0LjgKCQkJYy0zMTMuMi0wLjgtNTY2LjgtMjU0LjctNTY3LjItNTY3LjljMC0yNjYuMywxNzcuNy00NDMuNiwzMzQtNjAwLjNjMTA0LjUtMTA0LjUsMjA0LjMtMjA0LDIzMy45LTMxNgoJCQljMjkuNSwxMTIuMSwxMjkuNCwyMTEuNSwyMzMuOSwzMTZjMTU2LjgsMTU2LjgsMzM0LDMzNCwzMzQsNjAwLjNDMTY0NC43LDE4NzAuNiwxMzkwLjIsMjEyNC44LDEwNzYuNCwyMTI0Ljh6Ii8+Cgk8L2c+CjwvZz4KPC9zdmc+Cg==",
        TRUE,
        $expected = $url,
      ],
    ];
  }

  /**
   * Tests making headers RFC822-compliant.
   *
   * @param array $headers
   *   An array of headers where the keys are header field names and the
   *   values are the header field bodies.
   * @param string $expected
   *   One string containing a concatenation of all formatted header fields.
   *
   * @dataProvider providerRfcHeaders
   * @covers ::mimeMailRfcHeaders
   */
  public function testRfcHeaders(array $headers, string $expected): void {
    $actual = MimeMailFormatHelper::mimeMailRfcHeaders($headers);
    $this->assertSame($expected, $actual);
  }

  /**
   * Provides test data for testRfcHeaders().
   */
  public function providerRfcHeaders(): array {
    // Format of each element is:
    // - headers: An associative array of header fields to test. Each element
    //   is keyed by the header field name, which the array value being the
    //   header field body.
    // - expected: Expected return value from
    //   MimeMailFormatHelper::mimeMailRfcHeaders($headers).
    $headers = [
      'Multipart mail message header' => [
        [
          'MIME-Version' => '1.0',
          'ContentType' => 'multipart/mixed; boundary="379e3cea84af565a8053ea9e61054fc1294536001"',
          'Content-Transfer-Encoding' => '8bit',
        ],
        "MIME-Version: 1.0\r\n" .
        "ContentType: multipart/mixed;\r\n boundary=\"379e3cea84af565a8053ea9e61054fc1294536001\"\r\n" .
        "Content-Transfer-Encoding: 8bit\r\n",
      ],
      'Plaintext mail message header' => [
        [
          'MIME-Version' => '1.0',
          'Content-Type' => 'text/plain; charset=utf-8',
          'Content-Transfer-Encoding' => '8bit',
        ],
        "MIME-Version: 1.0\r\n" .
        "Content-Type: text/plain; charset=utf-8\r\n" .
        "Content-Transfer-Encoding: 8bit\r\n",
      ],
      'Plaintext MIME part header' => [
        [
          'Content-Type' => 'text/plain; charset=utf-8',
          'Content-Disposition' => 'inline',
          'Content-Transfer-Encoding' => '8bit',
        ],
        "Content-Type: text/plain; charset=utf-8\r\n" .
        "Content-Disposition: inline\r\n" .
        "Content-Transfer-Encoding: 8bit\r\n",
      ],
      'HTML MIME part header' => [
        [
          'ContentType' => 'text/html; charset=utf-8',
          'Content-Disposition' => 'inline',
          'Content-Transfer-Encoding' => '8bit',
        ],
        "ContentType: text/html; charset=utf-8\r\n" .
        "Content-Disposition: inline\r\n" .
        "Content-Transfer-Encoding: 8bit\r\n",
      ],
      'Multipart alternative MIME part header' => [
        [
          'ContentType' => 'multipart/alternative; boundary="233fe3c3a9841692454109103d46c2362d0ebe050"',
          'Content-Transfer-Encoding' => '8bit',
        ],
        "ContentType: multipart/alternative;\r\n boundary=\"233fe3c3a9841692454109103d46c2362d0ebe050\"\r\n" .
        "Content-Transfer-Encoding: 8bit\r\n",
      ],
      'PNG image attachment part header' => [
        [
          'Content-ID' => '<6152ca67d63c8f310d767bcdcdef9563@test.example.com>',
          'ContentType' => 'image/png; name="selfie.png"',
          'Content-Disposition' => 'attachment; filename="selfie.png"',
          'Content-Transfer-Encoding' => 'base64',
        ],
        "Content-ID: <6152ca67d63c8f310d767bcdcdef9563@test.example.com>\r\n" .
        "ContentType: image/png; name=\"selfie.png\"\r\n" .
        "Content-Disposition: attachment; filename=\"selfie.png\"\r\n" .
        "Content-Transfer-Encoding: base64\r\n",
      ],
      'JPG image attachment part header' => [
        [
          'Content-ID' => '<ea5183a8aaaacf76b4676e33a7788cc4@test.example.com>',
          'ContentType' => 'image/jpeg; name="spruce.jpg"',
          'Content-Disposition' => 'attachment; filename="spruce.jpg"',
          'Content-Transfer-Encoding' => 'base64',
        ],
        "Content-ID: <ea5183a8aaaacf76b4676e33a7788cc4@test.example.com>\r\n" .
        "ContentType: image/jpeg; name=\"spruce.jpg\"\r\n" .
        "Content-Disposition: attachment; filename=\"spruce.jpg\"\r\n" .
        "Content-Transfer-Encoding: base64\r\n",
      ],
      'PDF file attachment part header' => [
        [
          'Content-ID' => '<3360eda02899acf5ec5965dbd6717332@test.example.com>',
          'ContentType' => 'application/pdf; name="Really_Long_Filename_That_Should_Be_Wrapped.pdf"',
          'Content-Disposition' => 'attachment; filename="Really_Long_Filename_That_Should_Be_Wrapped.pdf"',
          'Content-Transfer-Encoding' => 'base64',
        ],
        "Content-ID: <3360eda02899acf5ec5965dbd6717332@test.example.com>\r\n" .
        "ContentType: application/pdf;\r\n name=\"Really_Long_Filename_That_Should_Be_Wrapped.pdf\"\r\n" .
        "Content-Disposition: attachment;\r\n filename=\"Really_Long_Filename_That_Should_Be_Wrapped.pdf\"\r\n" .
        "Content-Transfer-Encoding: base64\r\n",
      ],
    ];

    return $headers;
  }

  /**
   * Tests the regular expression for extracting the mail address.
   *
   * @covers ::mimeMailHeaders
   */
  public function testHeaders(): void {
    $chars = ['-', '.', '+', '_'];
    $name = $this->randomString();
    $local = $this->randomMachineName() . $chars[array_rand($chars)] . $this->randomMachineName();
    $domain = $this->randomMachineName() . '-' . $this->randomMachineName() . '.' . $this->randomMachineName(rand(2, 4));
    $headers = MimeMailFormatHelper::mimeMailHeaders([], "$name <$local@$domain>");
    $result = $headers['Return-Path'];
    $expected = "<$local@$domain>";
    $this->assertSame($result, $expected, 'Return-Path header field correctly set.');
  }

}
