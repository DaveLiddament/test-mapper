<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\TestAnalyzer\PhpUnit;

use DaveLiddament\TestMapper\Exception\ParseException;
use DaveLiddament\TestMapper\Model\LineRange;
use DaveLiddament\TestMapper\Model\TestMethod;
use DaveLiddament\TestMapper\TestAnalyzer\TestMethodFinder;
use PhpParser\Node\Scalar\String_;
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
    private const string DATA_PROVIDER_ATTRIBUTE = 'PHPUnit\\Framework\\Attributes\\DataProvider';
    private const string TICKET_ATTRIBUTE = 'PHPUnit\\Framework\\Attributes\\Ticket';

    /**
     * @return list<TestMethod>
     */
    public function findTestMethods(string $filePath): array
    {
        $code = @file_get_contents($filePath);

        if (false === $code) {
            throw new ParseException(sprintf('Could not read file: %s', $filePath));
        }

        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        $stmts = $parser->parse($code);

        if (null === $stmts) {
            throw new ParseException(sprintf('Could not parse file: %s', $filePath)); // @codeCoverageIgnore
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

        /** @var array<string, ClassMethod> $methodsByName */
        $methodsByName = [];
        foreach ($class->getMethods() as $method) {
            $methodsByName[$method->name->toString()] = $method;
        }

        foreach ($class->getMethods() as $method) {
            if ($this->isTestMethod($method)) {
                $startLine = $this->getStartLine($method);
                $endLine = $method->getEndLine();
                $providerNames = $this->getDataProviderNames($method);
                $dependentRanges = $this->getDependentRanges($providerNames, $methodsByName);
                $ticketIds = $this->getTicketIds($method);

                $testMethods[] = new TestMethod(
                    $fqcn,
                    $method->name->toString(),
                    $startLine,
                    $endLine,
                    $filePath,
                    $dependentRanges,
                    $ticketIds,
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

    /**
     * @return list<string>
     */
    private function getDataProviderNames(ClassMethod $method): array
    {
        $names = [];

        foreach ($method->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (self::DATA_PROVIDER_ATTRIBUTE === $attr->name->toString()) {
                    $arg = $attr->args[0]->value ?? null;
                    if ($arg instanceof String_) {
                        $names[] = $arg->value;
                    }
                }
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    private function getTicketIds(ClassMethod $method): array
    {
        $ticketIds = [];

        foreach ($method->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (self::TICKET_ATTRIBUTE === $attr->name->toString()) {
                    $arg = $attr->args[0]->value ?? null;
                    if ($arg instanceof String_) {
                        $ticketIds[] = $arg->value;
                    }
                }
            }
        }

        return $ticketIds;
    }

    /**
     * @param list<string> $providerNames
     * @param array<string, ClassMethod> $methodsByName
     *
     * @return list<LineRange>
     */
    private function getDependentRanges(array $providerNames, array $methodsByName): array
    {
        $ranges = [];

        foreach ($providerNames as $name) {
            if (isset($methodsByName[$name])) {
                $method = $methodsByName[$name];
                $ranges[] = new LineRange(
                    $this->getStartLine($method),
                    $method->getEndLine(),
                );
            }
        }

        return $ranges;
    }

    private function getStartLine(ClassMethod $method): int
    {
        $docCommentStartLine = $method->getDocComment()?->getStartLine() ?? \PHP_INT_MAX;

        $attributeStartLine = \PHP_INT_MAX;
        foreach ($method->attrGroups as $attrGroup) {
            $line = $attrGroup->getStartLine();
            /** @infection-ignore-all Equivalent mutant: $method->getStartLine() already includes attribute lines */
            if ($line < $attributeStartLine) {
                $attributeStartLine = $line;
            }
        }

        return min($docCommentStartLine, $attributeStartLine, $method->getStartLine());
    }
}
