<?php

/*
 * This file is a copy of {@see \Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator}
 *
 * Copyright (c) 2004-2020 Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 */

namespace Oro\Bundle\MakerBundle\Util;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\MakerBundle\Helper\OroEntityHelper;
use Oro\Bundle\MakerBundle\Renderer\AnnotationRenderer;
use PhpParser\Builder;
use PhpParser\BuilderHelpers;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser;
use Symfony\Bundle\MakerBundle\Doctrine\BaseCollectionRelation;
use Symfony\Bundle\MakerBundle\Doctrine\BaseRelation;
use Symfony\Bundle\MakerBundle\Doctrine\RelationManyToMany;
use Symfony\Bundle\MakerBundle\Doctrine\RelationManyToOne;
use Symfony\Bundle\MakerBundle\Doctrine\RelationOneToMany;
use Symfony\Bundle\MakerBundle\Doctrine\RelationOneToOne;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\PrettyPrinter;

/**
 * ClassSourceManipulator manipulates entity classes: adds properties and methods for fields and relations.
 *
 * Customization changes logic of original manipulator to support Oro Code Styles and adds Oro related annotations.
 * The list of supported types was extended as well.
 *
 * @SuppressWarnings(PHPMD)
 */
final class ClassSourceManipulator
{
    private const CONTEXT_OUTSIDE_CLASS = 'outside_class';
    private const CONTEXT_CLASS = 'class';
    private const CONTEXT_CLASS_METHOD = 'class_method';

    private bool $overwrite;
    private bool $fluentMutators;
    private Parser $parser;
    private Lexer $lexer;
    private PrettyPrinter $printer;
    private string $sourceCode;
    private $oldStmts;
    private $oldTokens;
    private $newStmts;

    private array $pendingComments = [];
    private AnnotationRenderer $annotationRenderer;

    public function __construct(
        string $sourceCode,
        AnnotationRenderer $annotationRenderer,
        bool $overwrite = false,
        bool $fluentMutators = true
    ) {
        $this->annotationRenderer = $annotationRenderer;
        $this->overwrite = $overwrite;
        $this->fluentMutators = $fluentMutators;
        $this->lexer = new Lexer\Emulative([
            'usedAttributes' => [
                'comments',
                'startLine',
                'endLine',
                'startTokenPos',
                'endTokenPos',
            ],
        ]);
        $this->parser = new Parser\Php7($this->lexer);
        $this->printer = new PrettyPrinter();

        $this->setSourceCode($sourceCode);
    }

    public function getSourceCode(): string
    {
        return $this->sourceCode;
    }

    public function addEntityField(
        string $fieldName,
        array $fieldConfig,
        array $identityFields = [],
        bool $isRelatedEntity = false
    ): void {
        $fieldOptions = OroEntityHelper::getFieldOptions($fieldName, $fieldConfig);
        $typeHint = $this->getEntityTypeHint($fieldConfig['type']);

        $fieldAnnotations = [];
        $fieldAnnotations[] = $this->annotationRenderer->getLines('ORM\Column', $fieldOptions);
        $this->addFieldConfig($fieldName, $fieldConfig, $identityFields, $fieldAnnotations, $isRelatedEntity);

        $this->addProperty($fieldName, array_merge(...$fieldAnnotations));
        $this->addGetter(
            $fieldName,
            $typeHint,
            // getter methods always have nullable return values
            // because even though these are required in the db, they may not be set yet
            true
        );

        $this->addSetter($fieldName, $typeHint, $fieldOptions['nullable']);
    }

    public function addManyToOneRelation(
        RelationManyToOne $manyToOne,
        string $fieldName,
        array $fieldConfig
    ): void {
        $this->addSingularRelation($manyToOne, $fieldName, $fieldConfig);
    }

    public function addOneToManyRelation(
        RelationOneToMany $oneToMany,
        string $fieldName,
        array $fieldConfig
    ): void {
        $this->addCollectionRelation($oneToMany, $fieldName, $fieldConfig);
    }

    public function addManyToManyRelation(
        RelationManyToMany $manyToMany,
        array $config,
        string $fieldName,
        array $fieldConfig
    ): void {
        $this->addCollectionRelation($manyToMany, $fieldName, $fieldConfig, $config);
    }

    public function addInterface(string $interfaceName): void
    {
        $this->addUseStatementIfNecessary($interfaceName);

        $this->getClassNode()->implements[] = new Node\Name(Str::getShortClassName($interfaceName));
        $this->updateSourceCodeFromNewStmts();
    }

    /**
     * @param string $trait the fully-qualified trait name
     */
    public function addTrait(string $trait): void
    {
        $importedClassName = $this->addUseStatementIfNecessary($trait);

        /** @var Node\Stmt\TraitUse[] $traitNodes */
        $traitNodes = $this->findAllNodes(function ($node) {
            return $node instanceof Node\Stmt\TraitUse;
        });

        foreach ($traitNodes as $node) {
            if ($node->traits[0]->toString() === $importedClassName) {
                return;
            }
        }

        $traitNodes[] = new Node\Stmt\TraitUse([new Node\Name($importedClassName)]);

        $classNode = $this->getClassNode();

        if (!empty($classNode->stmts) && 1 === \count($traitNodes)) {
            $traitNodes[] = $this->createBlankLineNode(self::CONTEXT_CLASS);
        }

        // avoid all the use traits in class for unshift all the new UseTrait
        // in the right order.
        foreach ($classNode->stmts as $key => $node) {
            if ($node instanceof Node\Stmt\TraitUse) {
                unset($classNode->stmts[$key]);
            }
        }

        array_unshift($classNode->stmts, ...$traitNodes);

        $this->updateSourceCodeFromNewStmts();
    }

    public function addGetter(
        string $propertyName,
        $returnType,
        bool $isReturnTypeNullable,
        array $commentLines = []
    ): void {
        $methodName = 'get' . Str::asCamelCase($propertyName);

        $this->addCustomGetter($propertyName, $methodName, $returnType, $isReturnTypeNullable, $commentLines);
    }

    public function addSetter(string $propertyName, $type, bool $isNullable, array $commentLines = []): void
    {
        $propertyName = Str::asLowerCamelCase($propertyName);
        $builder = $this->createSetterNodeBuilder($propertyName, $type, $isNullable, $commentLines);
        $builder->addStmt(
            new Node\Stmt\Expression(new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $propertyName),
                new Node\Expr\Variable($propertyName)
            ))
        );
        $this->makeMethodFluent($builder);
        $this->addMethod($builder->getNode());
    }

    public function addProperty(string $name, array $annotationLines = [], $defaultValue = null): void
    {
        $name = Str::asLowerCamelCase($name);
        if ($this->propertyExists($name)) {
            // we never overwrite properties
            return;
        }

        $newPropertyBuilder = (new Builder\Property($name))->makePrivate();

        if ($annotationLines) {
            $newPropertyBuilder->setDocComment($this->createDocBlock($annotationLines));
        }

        if (null !== $defaultValue) {
            $newPropertyBuilder->setDefault($defaultValue);
        }
        $newPropertyNode = $newPropertyBuilder->getNode();

        $this->addNodeAfterProperties($newPropertyNode);
    }

    private function addCustomGetter(
        string $propertyName,
        string $methodName,
        $returnType,
        bool $isReturnTypeNullable,
        array $commentLines = [],
        $typeCast = null
    ): void {
        $propertyName = Str::asLowerCamelCase($propertyName);
        $propertyFetch = new Node\Expr\PropertyFetch(
            new Node\Expr\Variable('this'),
            $propertyName
        );

        if (null !== $typeCast) {
            switch ($typeCast) {
                case 'string':
                    $propertyFetch = new Node\Expr\Cast\String_($propertyFetch);
                    break;
                default:
                    // implement other cases if/when the library needs them
                    throw new \Exception('Not implemented');
            }
        }

        $getterNodeBuilder = (new Builder\Method($methodName))
            ->makePublic()
            ->addStmt(
                new Node\Stmt\Return_($propertyFetch)
            );

        if (null !== $returnType) {
            $getterNodeBuilder->setReturnType($isReturnTypeNullable ? new Node\NullableType($returnType) : $returnType);
        }

        if ($commentLines) {
            $getterNodeBuilder->setDocComment($this->createDocBlock($commentLines));
        }

        $this->addMethod($getterNodeBuilder->getNode());
    }

    private function createSetterNodeBuilder(
        string $propertyName,
        $type,
        bool $isNullable,
        array $commentLines = []
    ): Builder\Method {
        $methodName = 'set' . Str::asCamelCase($propertyName);
        $setterNodeBuilder = (new Builder\Method($methodName))->makePublic();

        if ($commentLines) {
            $setterNodeBuilder->setDocComment($this->createDocBlock($commentLines));
        }

        $paramBuilder = new Builder\Param($propertyName);
        if (null !== $type) {
            $paramBuilder->setTypeHint($isNullable ? new Node\NullableType($type) : $type);
        }
        $setterNodeBuilder->addParam($paramBuilder->getNode());

        return $setterNodeBuilder;
    }

    private function addSingularRelation(BaseRelation $relation, string $fieldName, array $fieldConfig): void
    {
        $typeHint = $this->addUseStatementIfNecessary($relation->getTargetClassName());
        if ($relation->getTargetClassName() == $this->getThisFullClassName()) {
            $typeHint = 'self';
        }

        $annotationOptions = [
            'targetEntity' => $relation->getTargetClassName(),
        ];
        if ($relation->isOwning()) {
            // sometimes, we don't map the inverse relation
            if ($relation->getMapInverseRelation()) {
                $annotationOptions['inversedBy'] = Str::asLowerCamelCase($relation->getTargetPropertyName());
            }
        } else {
            $annotationOptions['mappedBy'] = Str::asLowerCamelCase($relation->getTargetPropertyName());
        }

        if ($relation instanceof RelationOneToOne) {
            $annotationOptions['cascade'] = ['persist', 'remove'];
        }

        $annotations = [];
        $annotations[] = $this->annotationRenderer->getLines(
            $relation instanceof RelationManyToOne ? 'ORM\\ManyToOne' : 'ORM\\OneToOne',
            $annotationOptions
        );


        if ($relation->isOwning()) {
            $annotations[] = $this->annotationRenderer->getLines(
                'ORM\\JoinColumn',
                [
                    'name' => Str::asSnakeCase($relation->getPropertyName()) . '_id',
                    'nullable' => $relation->isNullable(),
                    'onDelete' => $relation->isNullable() ? 'SET NULL' : 'CASCADE'
                ]
            );
        }
        if (!$relation->isOwning()) {
            $fieldConfig = ['disable_data_audit' => true, 'disable_import_export' => true];
        }
        $this->addFieldConfig($fieldName, $fieldConfig, [], $annotations);
        $this->addProperty($relation->getPropertyName(), array_merge(...$annotations));

        $this->addGetter(
            $relation->getPropertyName(),
            $relation->getCustomReturnType() ?: $typeHint,
            // getter methods always have nullable return values
            // unless this has been customized explicitly
            $relation->getCustomReturnType() ? $relation->isCustomReturnTypeNullable() : true
        );

        $this->addRelationSetter($relation, $typeHint);
    }

    private function addRelationSetter(BaseRelation $relation, string $typeHint): void
    {
        if ($relation->shouldAvoidSetter()) {
            return;
        }

        $propertyName = Str::asLowerCamelCase($relation->getPropertyName());
        $setterNodeBuilder = $this->createSetterNodeBuilder(
            $propertyName,
            $typeHint,
            // make the type-hint nullable always for ManyToOne to allow the owning
            // side to be set to null, which is needed for orphanRemoval
            // (specifically: when you set the inverse side, the generated
            // code will *also* set the owning side to null - so it needs to be allowed)
            // e.g. $userAvatarPhoto->setUser(null);
            $relation instanceof RelationOneToOne ? $relation->isNullable() : true
        );

        // set the *owning* side of the relation
        // OneToOne is the only "singular" relation type that
        // may be the inverse side
        if ($relation instanceof RelationOneToOne && !$relation->isOwning()) {
            $this->addNodesToSetOtherSideOfOneToOne($relation, $setterNodeBuilder);
        }

        $setterNodeBuilder->addStmt(
            new Node\Stmt\Expression(new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $propertyName),
                new Node\Expr\Variable($propertyName)
            ))
        );
        $this->makeMethodFluent($setterNodeBuilder);
        $this->addMethod($setterNodeBuilder->getNode());
    }

    private function addCollectionRelation(
        BaseCollectionRelation $relation,
        string $fieldName,
        array $fieldConfig,
        array $config = []
    ): void {
        $typeHint = $relation->isSelfReferencing()
            ? 'self'
            : $this->addUseStatementIfNecessary($relation->getTargetClassName());

        $arrayCollectionTypeHint = $this->addUseStatementIfNecessary(ArrayCollection::class);
        $collectionTypeHint = $this->addUseStatementIfNecessary(Collection::class);

        $annotationOptions = [
            'targetEntity' => $relation->getTargetClassName(),
        ];
        if ($relation->isOwning()) {
            // sometimes, we don't map the inverse relation
            if ($relation->getMapInverseRelation()) {
                $annotationOptions['inversedBy'] = Str::asLowerCamelCase($relation->getTargetPropertyName());
            }
        } else {
            $annotationOptions['mappedBy'] = Str::asLowerCamelCase($relation->getTargetPropertyName());
        }

        if ($relation->getOrphanRemoval()) {
            $annotationOptions['orphanRemoval'] = true;
        }

        $annotations = [];
        if ($relation instanceof RelationManyToMany) {
            $annotations[] = $this->annotationRenderer->getLines('ORM\\ManyToMany', $annotationOptions);
            $annotations[] = $this->annotationRenderer->getLines(
                'ORM\JoinTable',
                [
                    'name' => $config['join_table'],
                    'joinColumns' => [
                        implode('', $this->annotationRenderer->getLines(
                            'ORM\JoinColumn',
                            ['name' => $config['join_column'], 'referencedColumnName' => 'id', 'onDelete' => 'CASCADE'],
                            true
                        ))
                    ],
                    'inverseJoinColumns' => [
                        implode('', $this->annotationRenderer->getLines(
                            'ORM\JoinColumn',
                            [
                                'name' => $config['inverse_join_column'],
                                'referencedColumnName' => 'id',
                                'onDelete' => 'CASCADE'
                            ],
                            true
                        ))
                    ]
                ]
            );

        } else {
            $annotations[] = $this->annotationRenderer->getLines('ORM\\OneToMany', $annotationOptions);
        }

        if (!$relation->isOwning()) {
            $fieldConfig = ['disable_data_audit' => true, 'disable_import_export' => true];
        }
        $this->addFieldConfig($fieldName, $fieldConfig, [], $annotations);
        $this->addProperty($relation->getPropertyName(), array_merge(...$annotations));

        // logic to avoid re-adding the same ArrayCollection line
        $this->updateConstructWithCollection($relation, $arrayCollectionTypeHint);

        $this->addGetter(
            $relation->getPropertyName(),
            $collectionTypeHint,
            false,
            // add @return that advertises this as a collection of specific objects
            [sprintf('@return %s<int, %s>', $collectionTypeHint, $typeHint)]
        );

        $this->addCollectionItemAdderMethod($relation, $typeHint);
        $this->addCollectionItemRemoverMethod($relation, $typeHint);
    }

    private function updateConstructWithCollection(
        BaseCollectionRelation $relation,
        string $arrayCollectionTypeHint
    ): void {
        $propertyName = Str::asLowerCamelCase($relation->getPropertyName());
        $addArrayCollection = true;
        if ($this->getConstructorNode()) {
            // We print the constructor to a string, then
            // look for "$this->propertyName = "

            $constructorString = $this->printer->prettyPrint([$this->getConstructorNode()]);
            if (false !== strpos($constructorString, sprintf('$this->%s = ', $propertyName))) {
                $addArrayCollection = false;
            }
        }

        if ($addArrayCollection) {
            $this->addStatementToConstructor(
                new Node\Stmt\Expression(new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $propertyName),
                    new Node\Expr\New_(new Node\Name($arrayCollectionTypeHint))
                ))
            );
        }
    }

    private function addCollectionItemRemoverMethod(BaseCollectionRelation $relation, string $typeHint): void
    {
        $propertyName = Str::asLowerCamelCase($relation->getPropertyName());
        $argName = Str::pluralCamelCaseToSingular($relation->getPropertyName());
        $removerNodeBuilder = (new Builder\Method($relation->getRemoverMethodName()))->makePublic();

        $paramBuilder = new Builder\Param($argName);
        $paramBuilder->setTypeHint($typeHint);
        $removerNodeBuilder->addParam($paramBuilder->getNode());

        // $this->avatars->removeElement($avatar)
        $removeElementCall = new Node\Expr\MethodCall(
            new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $propertyName),
            'removeElement',
            [new Node\Expr\Variable($argName)]
        );

        // set the owning side of the relationship
        if ($relation->isOwning()) {
            // $this->avatars->removeElement($avatar);
            $removerNodeBuilder->addStmt(BuilderHelpers::normalizeStmt($removeElementCall));
        } else {
            // if ($this->avatars->removeElement($avatar))
            $ifRemoveElementStmt = new Node\Stmt\If_($removeElementCall);
            $removerNodeBuilder->addStmt($ifRemoveElementStmt);
            if ($relation instanceof RelationOneToMany) {
                // OneToMany: $student->setCourse(null);
                /*
                 * // set the owning side to null (unless already changed)
                 * if ($student->getCourse() === $this) {
                 *     $student->setCourse(null);
                 * }
                 */

                $ifRemoveElementStmt->stmts[] = $this->createSingleLineCommentNode(
                    'set the owning side to null (unless already changed)',
                    self::CONTEXT_CLASS_METHOD
                );

                // if ($student->getCourse() === $this) {
                $ifNode = new Node\Stmt\If_(new Node\Expr\BinaryOp\Identical(
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable($argName),
                        $relation->getTargetGetterMethodName()
                    ),
                    new Node\Expr\Variable('this')
                ));

                // $student->setCourse(null);
                $ifNode->stmts = [
                    new Node\Stmt\Expression(new Node\Expr\MethodCall(
                        new Node\Expr\Variable($argName),
                        $relation->getTargetSetterMethodName(),
                        [new Node\Arg($this->createNullConstant())]
                    )),
                ];

                $ifRemoveElementStmt->stmts[] = $ifNode;
            } elseif ($relation instanceof RelationManyToMany) {
                // $student->removeCourse($this);
                $ifRemoveElementStmt->stmts[] = new Node\Stmt\Expression(
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable($argName),
                        $relation->getTargetRemoverMethodName(),
                        [new Node\Expr\Variable('this')]
                    )
                );
            } else {
                throw new \Exception('Unknown relation type');
            }
        }

        $this->makeMethodFluent($removerNodeBuilder);
        $this->addMethod($removerNodeBuilder->getNode());
    }

    private function addCollectionItemAdderMethod(BaseCollectionRelation $relation, string $typeHint): void
    {
        $propertyName = Str::asLowerCamelCase($relation->getPropertyName());
        $argName = Str::pluralCamelCaseToSingular($relation->getPropertyName());

        // adder method
        $adderNodeBuilder = (new Builder\Method($relation->getAdderMethodName()))->makePublic();

        $paramBuilder = new Builder\Param($argName);
        $paramBuilder->setTypeHint($typeHint);
        $adderNodeBuilder->addParam($paramBuilder->getNode());

        // if (!$this->avatars->contains($avatar))
        $containsMethodCallNode = new Node\Expr\MethodCall(
            new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $propertyName),
            'contains',
            [new Node\Expr\Variable($argName)]
        );
        $ifNotContainsStmt = new Node\Stmt\If_(
            new Node\Expr\BooleanNot($containsMethodCallNode)
        );
        $adderNodeBuilder->addStmt($ifNotContainsStmt);

        // append the item
        $ifNotContainsStmt->stmts[] = new Node\Stmt\Expression(
            new Node\Expr\Assign(
                new Node\Expr\ArrayDimFetch(
                    new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $propertyName)
                ),
                new Node\Expr\Variable($argName)
            ));

        // set the owning side of the relationship
        if (!$relation->isOwning()) {
            $ifNotContainsStmt->stmts[] = new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    new Node\Expr\Variable($argName),
                    $relation->getTargetSetterMethodName(),
                    [new Node\Expr\Variable('this')]
                )
            );
        }

        $this->makeMethodFluent($adderNodeBuilder);
        $this->addMethod($adderNodeBuilder->getNode());
    }

    private function addStatementToConstructor(Node\Stmt $stmt): void
    {
        if (!$this->getConstructorNode()) {
            $constructorNode = (new Builder\Method('__construct'))->makePublic()->getNode();

            // add call to parent::__construct() if there is a need to
            try {
                $ref = new \ReflectionClass($this->getThisFullClassName());

                if ($ref->getParentClass() && $ref->getParentClass()->getConstructor()) {
                    $constructorNode->stmts[] = new Node\Stmt\Expression(
                        new Node\Expr\StaticCall(new Node\Name('parent'), new Node\Identifier('__construct'))
                    );
                }
            } catch (\ReflectionException $e) {
            }

            $this->addNodeAfterProperties($constructorNode);
        }

        $constructorNode = $this->getConstructorNode();
        $constructorNode->stmts[] = $stmt;
        $this->updateSourceCodeFromNewStmts();
    }

    /**
     * @throws \Exception
     */
    private function getConstructorNode(): ?Node\Stmt\ClassMethod
    {
        foreach ($this->getClassNode()->stmts as $classNode) {
            if ($classNode instanceof Node\Stmt\ClassMethod && '__construct' == $classNode->name) {
                return $classNode;
            }
        }

        return null;
    }

    /**
     * @return string The alias to use when referencing this class
     */
    public function addUseStatementIfNecessary(string $class): string
    {
        $shortClassName = Str::getShortClassName($class);
        if ($this->isInSameNamespace($class)) {
            return $shortClassName;
        }

        $namespaceNode = $this->getNamespaceNode();

        $targetIndex = null;
        $addLineBreak = false;
        $lastUseStmtIndex = null;
        foreach ($namespaceNode->stmts as $index => $stmt) {
            if ($stmt instanceof Node\Stmt\Use_) {
                // I believe this is an array to account for use statements with {}
                foreach ($stmt->uses as $use) {
                    $alias = $use->alias ? $use->alias->name : $use->name->getLast();

                    // the use statement already exists? Don't add it again
                    if ($class === (string)$use->name) {
                        return $alias;
                    }

                    if ($alias === $shortClassName) {
                        // we have a conflicting alias!
                        // to be safe, use the fully-qualified class name
                        // everywhere and do not add another use statement
                        return '\\' . $class;
                    }
                }

                // if $class is alphabetically before this use statement, place it before
                // only set $targetIndex the first time you find it
                if (null === $targetIndex && Str::areClassesAlphabetical($class, (string)$stmt->uses[0]->name)) {
                    $targetIndex = $index;
                }

                $lastUseStmtIndex = $index;
            } elseif ($stmt instanceof Node\Stmt\Class_) {
                if (null !== $targetIndex) {
                    // we already found where to place the use statement

                    break;
                }

                // we hit the class! If there were any use statements,
                // then put this at the bottom of the use statement list
                if (null !== $lastUseStmtIndex) {
                    $targetIndex = $lastUseStmtIndex + 1;
                } else {
                    $targetIndex = $index;
                    $addLineBreak = true;
                }

                break;
            }
        }

        if (null === $targetIndex) {
            throw new \Exception('Could not find a class!');
        }

        $newUseNode = (new Builder\Use_($class, Node\Stmt\Use_::TYPE_NORMAL))->getNode();
        array_splice(
            $namespaceNode->stmts,
            $targetIndex,
            0,
            $addLineBreak ? [$newUseNode, $this->createBlankLineNode(self::CONTEXT_OUTSIDE_CLASS)] : [$newUseNode]
        );

        $this->updateSourceCodeFromNewStmts();

        return $shortClassName;
    }

    private function updateSourceCodeFromNewStmts(): void
    {
        $newCode = $this->printer->printFormatPreserving(
            $this->newStmts,
            $this->oldStmts,
            $this->oldTokens
        );

        // replace the 3 "fake" items that may be in the code (allowing for different indentation)
        $newCode = preg_replace('/(\ |\t)*private\ \$__EXTRA__LINE;/', '', $newCode);
        $newCode = preg_replace('/use __EXTRA__LINE;/', '', $newCode);
        $newCode = preg_replace('/(\ |\t)*\$__EXTRA__LINE;/', '', $newCode);

        // process comment lines
        foreach ($this->pendingComments as $i => $comment) {
            // sanity check
            $placeholder = sprintf('$__COMMENT__VAR_%d;', $i);
            if (false === strpos($newCode, $placeholder)) {
                // this can happen if a comment is createSingleLineCommentNode()
                // is called, but then that generated code is ultimately not added
                continue;
            }

            $newCode = str_replace($placeholder, '// ' . $comment, $newCode);
        }
        $this->pendingComments = [];

        $this->setSourceCode($newCode);
    }

    private function setSourceCode(string $sourceCode): void
    {
        $this->sourceCode = $sourceCode;
        $this->oldStmts = $this->parser->parse($sourceCode);
        $this->oldTokens = $this->lexer->getTokens();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NodeVisitor\CloningVisitor());
        $traverser->addVisitor(new NodeVisitor\NameResolver(null, [
            'replaceNodes' => false,
        ]));
        $this->newStmts = $traverser->traverse($this->oldStmts);
    }

    private function getClassNode(): Node\Stmt\Class_
    {
        $node = $this->findFirstNode(function ($node) {
            return $node instanceof Node\Stmt\Class_;
        });

        if (!$node) {
            throw new \Exception('Could not find class node');
        }

        return $node;
    }

    private function getNamespaceNode(): Node\Stmt\Namespace_
    {
        $node = $this->findFirstNode(function ($node) {
            return $node instanceof Node\Stmt\Namespace_;
        });

        if (!$node) {
            throw new \Exception('Could not find namespace node');
        }

        return $node;
    }

    private function findFirstNode(callable $filterCallback): ?Node
    {
        $traverser = new NodeTraverser();
        $visitor = new NodeVisitor\FirstFindingVisitor($filterCallback);
        $traverser->addVisitor($visitor);
        $traverser->traverse($this->newStmts);

        return $visitor->getFoundNode();
    }

    private function findLastNode(callable $filterCallback, array $ast): ?Node
    {
        $traverser = new NodeTraverser();
        $visitor = new NodeVisitor\FindingVisitor($filterCallback);
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        $nodes = $visitor->getFoundNodes();
        $node = end($nodes);

        return false === $node ? null : $node;
    }

    /**
     * @return Node[]
     */
    private function findAllNodes(callable $filterCallback): array
    {
        $traverser = new NodeTraverser();
        $visitor = new NodeVisitor\FindingVisitor($filterCallback);
        $traverser->addVisitor($visitor);
        $traverser->traverse($this->newStmts);

        return $visitor->getFoundNodes();
    }

    private function createBlankLineNode(string $context)
    {
        switch ($context) {
            case self::CONTEXT_OUTSIDE_CLASS:
                return (new Builder\Use_('__EXTRA__LINE', Node\Stmt\Use_::TYPE_NORMAL))
                    ->getNode();
            case self::CONTEXT_CLASS:
                return (new Builder\Property('__EXTRA__LINE'))
                    ->makePrivate()
                    ->getNode();
            case self::CONTEXT_CLASS_METHOD:
                return new Node\Expr\Variable('__EXTRA__LINE');
            default:
                throw new \Exception('Unknown context: ' . $context);
        }
    }

    private function createSingleLineCommentNode(string $comment, string $context): Node\Stmt
    {
        $this->pendingComments[] = $comment;
        switch ($context) {
            case self::CONTEXT_OUTSIDE_CLASS:
                // just not needed yet
                throw new \Exception('not supported');
            case self::CONTEXT_CLASS:
                // just not needed yet
                throw new \Exception('not supported');
            case self::CONTEXT_CLASS_METHOD:
                return BuilderHelpers::normalizeStmt(new Node\Expr\Variable(sprintf('__COMMENT__VAR_%d',
                    \count($this->pendingComments) - 1)));
            default:
                throw new \Exception('Unknown context: ' . $context);
        }
    }

    private function createDocBlock(array $commentLines): string
    {
        $docBlock = "/**\n";
        foreach ($commentLines as $commentLine) {
            if ($commentLine) {
                $docBlock .= " * $commentLine\n";
            } else {
                // avoid the empty, extra space on blank lines
                $docBlock .= " *\n";
            }
        }
        $docBlock .= "\n */";

        return $docBlock;
    }

    private function addMethod(Node\Stmt\ClassMethod $methodNode): void
    {
        $classNode = $this->getClassNode();
        $methodName = $methodNode->name;
        $existingIndex = null;
        if ($this->methodExists($methodName)) {
            if (!$this->overwrite) {
                return;
            }

            // record, so we can overwrite in the same place
            $existingIndex = $this->getMethodIndex($methodName);
        }

        $newStatements = [];

        // put new method always at the bottom
        if (!empty($classNode->stmts)) {
            $newStatements[] = $this->createBlankLineNode(self::CONTEXT_CLASS);
        }

        $newStatements[] = $methodNode;

        if (null === $existingIndex) {
            // add them to the end!

            $classNode->stmts = array_merge($classNode->stmts, $newStatements);
        } else {
            array_splice(
                $classNode->stmts,
                $existingIndex,
                1,
                $newStatements
            );
        }

        $this->updateSourceCodeFromNewStmts();
    }

    private function makeMethodFluent(Builder\Method $methodBuilder): void
    {
        if (!$this->fluentMutators) {
            return;
        }

        $methodBuilder
            ->addStmt($this->createBlankLineNode(self::CONTEXT_CLASS_METHOD))
            ->addStmt(new Node\Stmt\Return_(new Node\Expr\Variable('this')));
        $methodBuilder->setReturnType('self');
    }

    protected function getEntityTypeHint(string $type): ?string
    {
        switch ($type) {
            case 'string':
            case 'text':
            case 'guid':
            case 'bigint':
            case 'decimal':
            case 'html':
            case 'wysiwyg':
            case 'email':
            case 'wysiwyg_style':
                return 'string';

            case 'array':
            case 'simple_array':
            case 'json':
            case 'json_array':
            case 'wysiwyg_properties':
                return 'array';

            case 'boolean':
                return 'bool';

            case 'integer':
            case 'smallint':
                return 'int';

            case 'float':
            case 'percent':
                return 'float';

            case 'datetime':
            case 'datetimetz':
            case 'date':
            case 'time':
                return '\\' . \DateTimeInterface::class;

            case 'datetime_immutable':
            case 'datetimetz_immutable':
            case 'date_immutable':
            case 'time_immutable':
                return '\\' . \DateTimeImmutable::class;

            case 'dateinterval':
                return '\\' . \DateInterval::class;

            case 'object':
            case 'binary':
            case 'blob':
            default:
                return null;
        }
    }

    private function isInSameNamespace($class): bool
    {
        $namespace = substr($class, 0, strrpos($class, '\\'));

        return $this->getNamespaceNode()->name->toCodeString() === $namespace;
    }

    private function getThisFullClassName(): string
    {
        return (string)$this->getClassNode()->namespacedName;
    }

    /**
     * Adds this new node where a new property should go.
     *
     * Useful for adding properties, or adding a constructor.
     */
    private function addNodeAfterProperties(Node $newNode): void
    {
        $classNode = $this->getClassNode();

        // try to add after last property
        $targetNode = $this->findLastNode(function ($node) {
            return $node instanceof Node\Stmt\Property;
        }, [$classNode]);

        // otherwise, try to add after the last constant
        if (!$targetNode) {
            $targetNode = $this->findLastNode(function ($node) {
                return $node instanceof Node\Stmt\ClassConst;
            }, [$classNode]);
        }

        // otherwise, try to add after the last trait
        if (!$targetNode) {
            $targetNode = $this->findLastNode(function ($node) {
                return $node instanceof Node\Stmt\TraitUse;
            }, [$classNode]);
        }

        // add the new property after this node
        if ($targetNode) {
            $index = array_search($targetNode, $classNode->stmts);

            array_splice(
                $classNode->stmts,
                $index + 1,
                0,
                [$this->createBlankLineNode(self::CONTEXT_CLASS), $newNode]
            );

            $this->updateSourceCodeFromNewStmts();

            return;
        }

        // put right at the beginning of the class
        // add an empty line, unless the class is totally empty
        if (!empty($classNode->stmts)) {
            array_unshift($classNode->stmts, $this->createBlankLineNode(self::CONTEXT_CLASS));
        }
        array_unshift($classNode->stmts, $newNode);
        $this->updateSourceCodeFromNewStmts();
    }

    private function createNullConstant(): Node\Expr\ConstFetch
    {
        return new Node\Expr\ConstFetch(new Node\Name('null'));
    }

    private function addNodesToSetOtherSideOfOneToOne(
        RelationOneToOne $relation,
        Builder\Method $setterNodeBuilder
    ): void {
        if (!$relation->isNullable()) {
            $setterNodeBuilder->addStmt($this->createSingleLineCommentNode(
                'set the owning side of the relation if necessary',
                self::CONTEXT_CLASS_METHOD
            ));

            $ifNode = new Node\Stmt\If_(new Node\Expr\BinaryOp\NotIdentical(
                new Node\Expr\MethodCall(
                    new Node\Expr\Variable($relation->getPropertyName()),
                    $relation->getTargetGetterMethodName()
                ),
                new Node\Expr\Variable('this')
            ));

            $ifNode->stmts = [
                new Node\Stmt\Expression(new Node\Expr\MethodCall(
                    new Node\Expr\Variable($relation->getPropertyName()),
                    $relation->getTargetSetterMethodName(),
                    [new Node\Arg(new Node\Expr\Variable('this'))]
                )),
            ];
            $setterNodeBuilder->addStmt($ifNode);
            $setterNodeBuilder->addStmt($this->createBlankLineNode(self::CONTEXT_CLASS_METHOD));

            return;
        }

        // at this point, we know the relation is nullable
        $setterNodeBuilder->addStmt($this->createSingleLineCommentNode(
            'unset the owning side of the relation if necessary',
            self::CONTEXT_CLASS_METHOD
        ));

        $ifNode = new Node\Stmt\If_(new Node\Expr\BinaryOp\BooleanAnd(
            new Node\Expr\BinaryOp\Identical(
                new Node\Expr\Variable($relation->getPropertyName()),
                $this->createNullConstant()
            ),
            new Node\Expr\BinaryOp\NotIdentical(
                new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    $relation->getPropertyName()
                ),
                $this->createNullConstant()
            )
        ));
        $ifNode->stmts = [
            // $this->user->setUserProfile(null)
            new Node\Stmt\Expression(new Node\Expr\MethodCall(
                new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    $relation->getPropertyName()
                ),
                $relation->getTargetSetterMethodName(),
                [new Node\Arg($this->createNullConstant())]
            )),
        ];
        $setterNodeBuilder->addStmt($ifNode);

        $setterNodeBuilder->addStmt($this->createBlankLineNode(self::CONTEXT_CLASS_METHOD));
        $setterNodeBuilder->addStmt($this->createSingleLineCommentNode(
            'set the owning side of the relation if necessary',
            self::CONTEXT_CLASS_METHOD
        ));

        // if ($user === null && $this->user !== null)
        $ifNode = new Node\Stmt\If_(new Node\Expr\BinaryOp\BooleanAnd(
            new Node\Expr\BinaryOp\NotIdentical(
                new Node\Expr\Variable($relation->getPropertyName()),
                $this->createNullConstant()
            ),
            new Node\Expr\BinaryOp\NotIdentical(
                new Node\Expr\MethodCall(
                    new Node\Expr\Variable($relation->getPropertyName()),
                    $relation->getTargetGetterMethodName()
                ),
                new Node\Expr\Variable('this')
            )
        ));
        $ifNode->stmts = [
            new Node\Stmt\Expression(new Node\Expr\MethodCall(
                new Node\Expr\Variable($relation->getPropertyName()),
                $relation->getTargetSetterMethodName(),
                [new Node\Arg(new Node\Expr\Variable('this'))]
            )),
        ];
        $setterNodeBuilder->addStmt($ifNode);

        $setterNodeBuilder->addStmt($this->createBlankLineNode(self::CONTEXT_CLASS_METHOD));
    }

    private function methodExists(string $methodName): bool
    {
        return false !== $this->getMethodIndex($methodName);
    }

    private function getMethodIndex(string $methodName)
    {
        foreach ($this->getClassNode()->stmts as $i => $node) {
            if ($node instanceof Node\Stmt\ClassMethod && strtolower($node->name->toString()) === strtolower($methodName)) {
                return $i;
            }
        }

        return false;
    }

    private function propertyExists(string $propertyName): bool
    {
        foreach ($this->getClassNode()->stmts as $i => $node) {
            if ($node instanceof Node\Stmt\Property && $node->props[0]->name->toString() === $propertyName) {
                return true;
            }
        }

        return false;
    }

    private function addFieldConfig(
        string $fieldName,
        array $fieldConfig,
        array $identityFields,
        array &$fieldAnnotations,
        bool $isRelatedEntity = false
    ): void {
        $configData = [];
        if (empty($fieldConfig['disable_data_audit'])) {
            $configData = ['dataaudit' => ['auditable' => true]];
        }
        if (!empty($fieldConfig['disable_import_export'])) {
            $configData['importexport']['excluded'] = true;
        } elseif (in_array($fieldName, $identityFields, true)) {
            $configData['importexport']['identity'] = true;
        }

        if (!$isRelatedEntity) {
            $type = $fieldConfig['type'] ?? null;
            if ($type === 'email') {
                $configData['entity']['contact_information'] = 'email';
            } elseif ($type === 'string') {
                $parts = explode('_', Str::asSnakeCase($fieldName));
                if (in_array('phone', $parts, true)) {
                    $configData['entity']['contact_information'] = 'phone';
                } elseif (in_array('email', $parts, true)) {
                    $configData['entity']['contact_information'] = 'email';
                }
            }
        }

        if ($configData) {
            $fieldAnnotations[] = $this->annotationRenderer->getLines(
                'ConfigField',
                ['defaultValues' => $configData]
            );
        }
    }
}
