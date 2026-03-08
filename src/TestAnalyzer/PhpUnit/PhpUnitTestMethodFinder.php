<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\TestAnalyzer\PhpUnit;

use DaveLiddament\TestMapper\Exception\ParseException;
use DaveLiddament\TestMapper\Model\TestMethod;
use DaveLiddament\TestMapper\TestAnalyzer\TestMethodFinder;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

final class PhpUnitTestMethodFinder implements TestMethodFinder
{
    private const string TEST_ATTRIBUTE = 'PHPUnit\\Framework\\Attributes\\Test';

    /**
     * @return list<TestMethod>
     */
    public function findTestMethods(string $filePath): array
    {
        $code = file_get_contents($filePath);

        if (false === $code) {
            throw new ParseException(sprintf('Could not read file: %s', $filePath));
        }

        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        $stmts = $parser->parse($code);

        if (null === $stmts) {
            throw new ParseException(sprintf('Could not parse file: %s', $filePath));
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        /** @var list<Stmt> $stmts */
        $stmts = $traverser->traverse($stmts);

        $testMethods = [];

        $this->findTestMethodsInStmts($stmts, $filePath, $testMethods);

        return $testMethods;
    }

    /**
     * @param array<Stmt> $stmts
     * @param list<TestMethod> $testMethods
     */
    private function findTestMethodsInStmts(array $stmts, string $filePath, array &$testMethods): void
    {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Class_ && !$stmt->isAbstract()) {
                $this->processClass($stmt, $filePath, $testMethods);
            }

            if ($stmt instanceof Namespace_) {
                $this->findTestMethodsInStmts($stmt->stmts, $filePath, $testMethods);
            }
        }
    }

    /**
     * @param list<TestMethod> $testMethods
     */
    private function processClass(Class_ $class, string $filePath, array &$testMethods): void
    {
        /** @var string $fqcn */
        $fqcn = $class->namespacedName?->toString() ?? '';

        foreach ($class->getMethods() as $method) {
            if ($this->isTestMethod($method)) {
                $startLine = $this->getStartLine($method);
                $endLine = $method->getEndLine();

                $testMethods[] = new TestMethod(
                    $fqcn,
                    $method->name->toString(),
                    $startLine,
                    $endLine,
                    $filePath,
                );
            }
        }
    }

    private function isTestMethod(ClassMethod $method): bool
    {
        foreach ($method->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (self::TEST_ATTRIBUTE === $attr->name->toString()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getStartLine(ClassMethod $method): int
    {
        $docCommentStartLine = $method->getDocComment()?->getStartLine() ?? \PHP_INT_MAX;

        $attributeStartLine = \PHP_INT_MAX;
        foreach ($method->attrGroups as $attrGroup) {
            $line = $attrGroup->getStartLine();
            if ($line < $attributeStartLine) {
                $attributeStartLine = $line;
            }
        }

        return min($docCommentStartLine, $attributeStartLine, $method->getStartLine());
    }
}
