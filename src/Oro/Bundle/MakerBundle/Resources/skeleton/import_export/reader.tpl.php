<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Reader\EntityReader;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Prepares the list of entities for export.
 * Responsible for creating a list for each batch during export
 */
class <?= $short_class_name ?>Reader extends EntityReader
{
    private int $holderEntityId;

    /**
     * {@inheritdoc}
     */
    protected function createSourceEntityQueryBuilder($entityName, Organization $organization = null, array $ids = [])
    {
        $qb = parent::createSourceEntityQueryBuilder($entityName, $organization, $ids);

        if ($this->holderEntityId) {
            $aliases = $qb->getRootAliases();
            $rootAlias = reset($aliases);
            $qb
                ->andWhere(
                    $qb->expr()->eq(sprintf('IDENTITY(%s.<?= $relation_owner_field ?>)', $rootAlias), ':holder_entity_id')
                )
                ->setParameter('holder_entity_id', $this->holderEntityId);
        }

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        $this->holderEntityId = (int)$context->getOption('holder_entity_id');

        parent::initializeFromContext($context);
    }

    protected function createQueryBuilderByEntityNameAndIdentifier(
        ObjectManager $entityManager,
        string $entityName,
        array $options = []
    ): QueryBuilder {
        if (!array_key_exists('holder_entity_id', $options)) {
            throw new \LogicException('Unable to read <?= $short_class_name ?>, <?= $relation_owner_field ?> should be defined.');
        }

        $queryBuilder = parent::createQueryBuilderByEntityNameAndIdentifier(
            $entityManager,
            $entityName,
            $options
        );
        $queryBuilder->andWhere('o.<?= $relation_owner_field ?> = :holder_entity_id');
        $queryBuilder->setParameter('holder_entity_id', $options['holder_entity_id']);

        return $queryBuilder;
    }
}
