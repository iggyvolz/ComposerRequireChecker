<?php

namespace ComposerRequireCheckerTest\NodeVisitor;

use ComposerRequireChecker\NodeVisitor\UsedSymbolCollector;
use PhpParser\NodeTraverser;
use PhpParser\NodeTraverserInterface;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 *
 * @group functional
 */
final class UsedSymbolCollectorFunctionalTest extends TestCase
{
    /**
     * @var UsedSymbolCollector
     */
    private $collector;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var NodeTraverserInterface
     */
    private $traverser;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->collector = new UsedSymbolCollector();
        $this->parser    = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->traverser = new NodeTraverser();

        $this->traverser->addVisitor(new NameResolver());
        $this->traverser->addVisitor($this->collector);
    }

    public function testWillCollectSymbolsUsedInThisFile(): void
    {
        $this->traverseClassAST(self::class);

        self::assertSameCollectedSymbols(
            [
                'ComposerRequireChecker\NodeVisitor\UsedSymbolCollector',
                'PHPUnit\Framework\TestCase',
                'PhpParser\NodeTraverser',
                'PhpParser\ParserFactory',
                'file_get_contents',
                'ReflectionClass',
                'array_diff',
                'self',
                'PhpParser\NodeVisitor\NameResolver',
                'string',
                'array',
                'void',
            ],
            $this->collector->getCollectedSymbols()
        );
    }

    public function testWillCollectFunctionDefinitionTypes(): void
    {
        $this->traverseStringAST('<?php function foo(My\ParameterType $bar, array $fooBar) {}');

        self::assertSameCollectedSymbols(
            [
                'My\ParameterType',
                'array',
            ],
            $this->collector->getCollectedSymbols()
        );
    }

    public function testWillCollectMethodDefinitionTypes(): void
    {
        $this->traverseStringAST('<?php class Foo { function foo(My\ParameterType $bar, array $fooBar) {}}');

        self::assertSameCollectedSymbols(
            [
                'My\ParameterType',
                'array',
            ],
            $this->collector->getCollectedSymbols()
        );
    }

    public function testWillCollectFunctionReturnTypes(): void
    {
        $this->traverseStringAST('<?php function foo($bar) : My\ReturnType {}');

        self::assertSameCollectedSymbols(
            [
                'My\ReturnType',
            ],
            $this->collector->getCollectedSymbols()
        );
    }

    public function testWillCollectMethodReturnTypes(): void
    {
        $this->traverseStringAST('<?php class Foo { function foo($bar) : My\ReturnType {}}');

        self::assertSameCollectedSymbols(
            [
                'My\ReturnType',
            ],
            $this->collector->getCollectedSymbols()
        );
    }

    public function testWillCollectSimpleFunctionReturnTypes(): void
    {
        $this->traverseStringAST('<?php function foo($bar) : int {}');

        self::assertSameCollectedSymbols(
            [
                'int',
            ],
            $this->collector->getCollectedSymbols()
        );
    }

    public function testWontCollectAnyUsageTypes(): void
    {
        $this->traverseStringAST('<?php function foo($bar) {}');

        self::assertSameCollectedSymbols(
            [],
            $this->collector->getCollectedSymbols()
        );
    }

    public function testUseTraitAdaptionAlias(): void
    {
        $this->traverseStringAST('<?php namespace Foo; trait BarTrait { protected function test(){}} class UseTrait { use BarTrait {test as public;} }');

        self::assertSameCollectedSymbols(
            ['Foo\BarTrait'],
            $this->collector->getCollectedSymbols()
        );
    }

    private function traverseStringAST(string $stringAST)
    {
        return $this->traverser->traverse(
            $this->parser->parse(
                $stringAST
            )
        );
    }

    private function traverseClassAST(string $className): array
    {
        return $this->traverseStringAST(
            file_get_contents((new \ReflectionClass($className))->getFileName())
        );
    }

    private static function assertSameCollectedSymbols(array $expected, array $actual): void
    {
        self::assertSame(array_diff($expected, $actual), array_diff($actual, $expected));
    }
}
