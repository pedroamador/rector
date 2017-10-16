<?php declare(strict_types=1);

namespace Rector\Rector\Dynamic;

use Nette\Utils\Arrays;
use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\UseUse;
use Rector\Node\Attribute;
use Rector\Node\NodeFactory;
use Rector\Rector\AbstractRector;

/**
 * Basically inversion of https://github.com/nikic/PHP-Parser/blob/master/doc/2_Usage_of_basic_components.markdown#example-converting-namespaced-code-to-pseudo-namespaces
 *
 *
 * Requested on SO: https://stackoverflow.com/questions/29014957/converting-pseudo-namespaced-classes-to-use-real-namespace
 *
 * @todo might handle current @see phpunit60.yml @see \Rector\Rector\Dynamic\NamespaceReplacerRector
 */
final class PseudoNamespaceToNamespaceRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private $pseudoNamespacePrefixes = [];

    /**
     * @var string[]
     */
    private $oldToNewUseStatements = [];

    /**
     * @var string
     */
    private $newNamespace;

    /**
     * @var NodeFactory
     */
    private $nodeFactory;

    /**
     * @param string[] $pseudoNamespacePrefixes
     */
    public function __construct(array $pseudoNamespacePrefixes, NodeFactory $nodeFactory)
    {
        $this->pseudoNamespacePrefixes = $pseudoNamespacePrefixes;
        $this->nodeFactory = $nodeFactory;
    }

    /**
     * @param mixed[] $nodes
     */
    public function beforeTraverse(array $nodes): void
    {
        $this->newNamespace = null;
        $this->oldToNewUseStatements = [];
    }

    public function isCandidate(Node $node): bool
    {
        $name = $this->resolveNameFromNode($node);
        if ($name === null) {
            return false;
        }

        foreach ($this->pseudoNamespacePrefixes as $pseudoNamespacePrefix) {
            if (Strings::startsWith($name, $pseudoNamespacePrefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Name|Identifier $nameOrIdentifierNode
     */
    public function refactor(Node $nameOrIdentifierNode): ?Node
    {
        $oldName = $this->resolveNameFromNode($nameOrIdentifierNode);
        $newNameParts = explode('_', $oldName);
        $parentNode = $nameOrIdentifierNode->getAttribute(Attribute::PARENT_NODE);
        $lastNewNamePart = $newNameParts[count($newNameParts) - 1];

        if ($nameOrIdentifierNode instanceof Name) {
            if ($parentNode instanceof UseUse) {
                $this->oldToNewUseStatements[$oldName] = $lastNewNamePart;
            } elseif (isset($this->oldToNewUseStatements[$oldName])) {
                // to prevent "getComments() on string" error
                $nameOrIdentifierNode->setAttribute('origNode', null);
                $newNameParts = [$this->oldToNewUseStatements[$oldName]];
            }

            $nameOrIdentifierNode->parts = $newNameParts;

            return $nameOrIdentifierNode;
        }

        if ($nameOrIdentifierNode instanceof Identifier && $parentNode instanceof Class_) {
            $namespaceParts = $newNameParts;
            array_pop($namespaceParts);

            $this->newNamespace = implode('\\', $namespaceParts);

            $nameOrIdentifierNode->name = $lastNewNamePart;

            return $nameOrIdentifierNode;
        }

        return null;
    }

    /**
     * @param Node[] $nodes
     * @return Node[]
     */
    public function afterTraverse(array $nodes): array
    {
        if ($this->newNamespace) {
            $namespaceNode = $this->nodeFactory->createNamespace($this->newNamespace);

            foreach ($nodes as $key => $node) {
                if ($node instanceof Class_) {
                    $nodes = $this->insertBefore($nodes, $namespaceNode, $key);

                    break;
                }
            }
        }

        return $nodes;
    }

    private function resolveNameFromNode(Node $node): ?string
    {
        if ($node instanceof Identifier) {
            return $node->name;
        }

        if ($node instanceof Name) {
            return $node->toString();
        }

        return null;
    }

    /**
     * @param Node[] $nodes
     * @param int|string $key
     * @return Node[]
     */
    private function insertBefore(array $nodes, Node $addedNode, $key): array
    {
        Arrays::insertBefore($nodes, $key, [
            'before_' . $key => $addedNode,
        ]);

        // recound ids
        $recountedNodes = [];
        $i = 0;

        foreach ($nodes as $node) {
            $recountedNodes[$i] = $node;
            ++$i;
        }

        return $recountedNodes;
    }
}
