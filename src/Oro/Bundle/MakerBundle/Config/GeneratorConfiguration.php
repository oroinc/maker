<?php

namespace Oro\Bundle\MakerBundle\Config;

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

/**
 * Defines the structure of generator config file.
 */
class GeneratorConfiguration implements ConfigurationInterface
{
    private const SUPPORTED_FIELD_TYPES = [
        'boolean',
        'integer',
        'smallint',
        'bigint',
        'float',
        'decimal',
        'percent',
        'string',
        'text',
        'email',
        'html',
        'wysiwyg',
        'date',
        'datetime',
        'image',
        'enum',
        'enum[]',
        'relation'
    ];

    public function processConfiguration(array $config): array
    {
        $processedConfig = (new Processor())->processConfiguration($this, $config);

        // Normalize field and entity names to be in snake_case.
        foreach ($processedConfig['entities'] as $entityName => &$entityData) {
            foreach ($entityData['fields'] as $fieldName => &$fieldData) {
                // Disable data_audit on field level if it is disabled on entity level
                if (empty($fieldData['disable_data_audit'])
                    && empty($entityData['configuration']['auditable'])
                ) {
                    $fieldData['disable_data_audit'] = true;
                }
                $nameNormalized = strtolower(Str::asSnakeCase($fieldName));
                if ($nameNormalized === $fieldName) {
                    continue;
                }
                $entityData['fields'][$nameNormalized] = $fieldData;
                unset($entityData['fields'][$fieldName]);
            }

            $nameNormalized = strtolower(Str::asSnakeCase($entityName));
            if ($nameNormalized === $entityName) {
                continue;
            }
            $processedConfig['entities'][$nameNormalized] = $entityData;
            unset($processedConfig['entities'][$entityName]);
        }

        return $processedConfig;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('generate');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->arrayNode('options')
                ->isRequired()
                ->children()
                    ->scalarNode('organization')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('package')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end()
            ->append($this->getEntityConfig())
        ->end();

        return $treeBuilder;
    }

    protected function getEntityConfig(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('entities');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->useAttributeAsKey('name')
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->prototype('array')
                ->children()
                    ->scalarNode('name')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('label')
                        ->defaultNull()
                    ->end()
                    ->arrayNode('configuration')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->enumNode('owner')
                                ->defaultNull()
                                ->values(['user', 'organization', 'business_unit'])
                            ->end()
                            ->enumNode('frontend_owner')
                                ->defaultNull()
                                ->values(['customer', 'customer_user'])
                            ->end()
                            ->booleanNode('is_related_entity')
                                ->defaultFalse()
                            ->end()
                            ->booleanNode('create_crud')
                                ->defaultTrue()
                            ->end()
                            ->booleanNode('configure_api')
                                ->defaultTrue()
                            ->end()
                            ->booleanNode('create_import_export')
                                ->defaultTrue()
                            ->end()
                            ->booleanNode('configure_search')
                                ->defaultTrue()
                            ->end()
                            ->booleanNode('auditable')
                                ->defaultTrue()
                            ->end()
                        ->end()
                    ->end()
                    ->append($this->getFieldsConfig())
                ->end()
            ->end();

        return $rootNode;
    }

    protected function getFieldsConfig(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('fields');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->useAttributeAsKey('name')
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->prototype('array')
                ->children()
                    ->scalarNode('name')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('label')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('type')
                        ->defaultValue('string')
                        ->validate()
                            ->always(function ($type) {
                                if (in_array($type, self::SUPPORTED_FIELD_TYPES, true)
                                    || str_starts_with($type, '@')
                                    || str_contains($type, '\\')
                                ) {
                                    return $type;
                                }

                                throw new \InvalidArgumentException(sprintf(
                                    'Unsupported field type "%s" given. ' .
                                    'Supported types are: %s, relations starting with @ or FQCNs',
                                    $type,
                                    implode(', ', self::SUPPORTED_FIELD_TYPES)
                                ));
                            })
                        ->end()
                    ->end()
                    ->enumNode('relation_type')
                        ->defaultNull()
                        ->values(['many-to-one', 'one-to-many', 'many-to-many'])
                    ->end()
                    ->scalarNode('relation_target')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('default_value')
                        ->defaultNull()
                    ->end()
                    ->arrayNode('values')
                        ->prototype('variable')
                        ->end()
                    ->end()
                    ->booleanNode('required')
                        ->defaultFalse()
                    ->end()
                    ->booleanNode('disable_data_audit')
                        ->defaultFalse()
                    ->end()
                    ->booleanNode('force_show_on_grid')
                        ->defaultFalse()
                    ->end()
                    ->booleanNode('disable_import_export')
                        ->defaultFalse()
                    ->end()
                    ->booleanNode('is_owning_side')
                        ->defaultTrue()
                    ->end()
                    ->integerNode('min_length')
                        ->defaultNull()
                    ->end()
                    ->integerNode('max_length')
                        ->defaultNull()
                    ->end()
                ->end()
            ->end();

        return $rootNode;
    }
}
