<?php

namespace Obfuscator\Test;

use PhpParser\NodeDumper;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

/**
 * Parent class for all tests which obfuscated some sources directory and then run tests on the obfuscated results.
 * Contains helper functions and properties
 */
abstract class TestAbstract extends TestCase
{

    /** @var string Application root directory path */
    protected static string $rootDir;

    /** @var string Application tests directory path */
    protected static string $testsDir;

    /** @var string Application sources directory path */
    protected static string $sourcesDir;

    /** @var string Application obfuscated results directory path */
    protected static string $obfuscatedDir;

    /** @var string Application obfuscated context directory path */
    protected static string $contextDir;

    /** @var array Array of contexts, generated during obfuscation */
    private array $context;

    /** @var Parser PHP Parser to iterate components in original / obfuscated file */
    private Parser $parser;

    /** @var NodeDumper PHP Parser to dumper for test debugging processes */
    protected NodeDumper $dumper;

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
        $this->dumper = new NodeDumper();
    }

    /**
     * Initialize directories used during obfuscation
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$rootDir = __DIR__ . "/..";
        self::$testsDir = self::$rootDir . "/tests";
        self::$sourcesDir = self::$testsDir . "/" . static::getSourcesDir();
        self::$obfuscatedDir = self::$testsDir . "/obfuscated/" . static::getSourcesDir() . "/yakpro-po/obfuscated";
        self::$contextDir = self::$testsDir . "/obfuscated/" . static::getSourcesDir() . "/yakpro-po/context";
    }

    /**
     * Getter for dir name of sources (pending to be obfuscated) for this test
     */
    abstract protected static function getSourcesDir(): string;

    /**
     * Run obfuscator and obfuscates sources, specified in <strong>getSourcesDir()</strong> directory
     * @return void
     */
    protected static function obfuscateSources(): void
    {
        $command = "php " . self::$rootDir . "/yakpro-po.php " . self::$sourcesDir . " -o " . self::$testsDir . "/obfuscated/" . static::getSourcesDir();
        shell_exec($command);
        $this->assertDirectoryExists(self::$testsDir . "/obfuscated/" . self::getSourcesDir(), "Folder with obfuscated results failed to generate");
    }

    /**
     * Parse file by string by filename
     *
     * @param string $filename
     * @return array[Stmt] Parsed statements from file
     */
    protected function getParsedFile(string $filename): array
    {
        $this->assertFileExists($filename);

        return $this->parser->parse(file_get_contents($filename));
    }

    /**
     * Assert that string has been obfuscated.
     * Checks these two strings re not equal, that originalString has been obfuscated and its obfuscated name equals to obfuscated string (using obfuscation context)
     *
     * @param string $originalString
     * @param string $obfuscatedString
     * @param string $component
     * @return void
     */
    protected function assertObfuscated(string $originalString, string $obfuscatedString, string $component = ''): void
    {
        $this->assertNotEquals($obfuscatedString, $originalString, $component);

        $obfuscatedName = $this->getObfuscatedName($originalString);

        $this->assertNotEmpty($obfuscatedName, "$component has not been obfuscated");
        $this->assertEquals($obfuscatedName, strtolower($obfuscatedString), "$component obfuscation detected from context differs from actual obfuscation"); //context always store lowercase value
    }

    /**
     * Detects obfuscated name from component name, using context
     *
     * @param string $component
     * @return string|null
     */
    private function getObfuscatedName(string $component): ?string
    {
        foreach ($this->getContext() as $props) {
            $this->assertArrayHasKey(1, $props);
            $mapping = $props[1];

            if (array_key_exists(strtolower($component), $mapping)) {
                return $mapping[strtolower($component)];
            }
            if (array_key_exists($component, $mapping)) {
                return $mapping[$component];
            }
        }

        return null;
    }

    /**
     * Loads context from files for further analysis.
     * Context is loaded only once in order to save performance
     *
     * @return array Array of contexts with scramblers type by its key
     */
    private function getContext(): array
    {
        if (!isset($this->context)) {
            $this->context = [];
            foreach (glob(self::$contextDir . "/*") as $file) {
                $this->context[basename($file)] = unserialize(file_get_contents($file));
            }
        }

        return $this->context;
    }
}
