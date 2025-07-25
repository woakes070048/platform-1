<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Encoder;

use Oro\Bundle\SecurityBundle\Encoder\RepetitiveCrypter;
use PHPUnit\Framework\TestCase;

class RepetitiveCrypterTest extends TestCase
{
    public function testCryptData(): void
    {
        $testString = 'test string';

        $crypter1 = new RepetitiveCrypter('test');
        $crypter2 = new RepetitiveCrypter('test');

        $crypted1 = $crypter1->encryptData($testString);
        $crypted2 = $crypter2->encryptData($testString);

        $this->assertNotEquals($testString, $crypted1);
        $this->assertEquals($crypted1, $crypted2);
        $this->assertEquals($testString, $crypter1->decryptData($crypted2));
        $this->assertEquals($testString, $crypter2->decryptData($crypted1));
    }
}
