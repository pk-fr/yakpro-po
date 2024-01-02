# How to write tests?
1. Create directory, containing source which should be obfuscated
2. Create test file, working with these obfuscated files
    1. This file should extends TestAbstract class
    2. Override `setUpBeforeClass()` function and call `parent::obfuscateSources()` from there (dont forget parent::setUpBeforeClass()`)
3. Standard test should look like:
```
public function testClassObfuscated()
{
    $original = parent::getParsedFile(self::$sourcesDir . "/Class.php");
    $obfuscated = parent::getParsedFile(self::$obfuscatedDir . "/Class.php");

    //namespace \Obfuscator\Test\MyTests
    $this->assertObfuscated($original[0]->name->parts[0], $obfuscated[0]->name->parts[0]); //Obfuscator
    $this->assertObfuscated($original[0]->name->parts[1], $obfuscated[0]->name->parts[1]); //Test
    $this->assertObfuscated($original[0]->name->parts[2], $obfuscated[0]->name->parts[2]); //MyTests
}
```

## Namespace
All test classes should share the namespace `Obfuscator\Test`

## Running tests
1. `vendor/bin/phpunit --testdox tests` _Run all tests_
2. `vendor/bin/phpunit --testdox tests/InterfaceTest.php` _Run single tests_