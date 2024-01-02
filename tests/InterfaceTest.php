<?php

namespace Obfuscator\Test;

final class InterfaceTest extends TestAbstract
{

    protected static function getSourcesDir(): string
    {
        return "interface";
    }

    //Run obsfusction to be able to run tests comparing original and obfuscated files
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        parent::obfuscateSources();
    }

    public function testInterfacedClassObfuscated()
    {
        $original = parent::getParsedFile(self::$sourcesDir . "/InterfacedClass.php");
        $obfuscated = parent::getParsedFile(self::$obfuscatedDir . "/InterfacedClass.php");

        //namespace obfuscation checks
        $this->assertObfuscated($original[0]->name->parts[0], $obfuscated[0]->name->parts[0]); //Obfuscator
        $this->assertObfuscated($original[0]->name->parts[1], $obfuscated[0]->name->parts[1]); //Test
        $this->assertEquals($original[0]->name->parts[2], $obfuscated[0]->name->parts[2]); //Interface is not obfuscated since it is ignored
        //implements obfuscation checks
        $this->assertEquals($original[0]->stmts[0]->implements[0]->parts[0], $obfuscated[0]->stmts[0]->implements[0]->parts[0]); /** Interface is not obfuscated since it is ignored @todo */
        //function Foo obfuscation checks
        $this->assertObfuscated($original[0]->stmts[0]->stmts[0]->name->name, $obfuscated[0]->stmts[0]->stmts[0]->name->name); // function name Foo is obfuscated
    }

    public function testInterfaceObfuscated()
    {
        $original = parent::getParsedFile(self::$sourcesDir . "/TestInterface.php");
        $obfuscated = parent::getParsedFile(self::$obfuscatedDir . "/TestInterface.php");

        //namespace obfuscation checks
        $this->assertObfuscated($original[0]->name->parts[0], $obfuscated[0]->name->parts[0]); //Obfuscator
        $this->assertObfuscated($original[0]->name->parts[1], $obfuscated[0]->name->parts[1]); //Test
        $this->assertEquals($original[0]->name->parts[2], $obfuscated[0]->name->parts[2]); //Interface is not obfuscated since it is ignored

        //function Foo obfuscation checks
        $this->assertObfuscated($original[0]->stmts[0]->stmts[0]->name->name, $obfuscated[0]->stmts[0]->stmts[0]->name->name); // function name Foo is obfuscated
    }
}
