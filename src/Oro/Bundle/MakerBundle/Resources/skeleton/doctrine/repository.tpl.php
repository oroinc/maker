<?php include __DIR__ . '/../include/php_file_start.tpl.php'; ?>

/**
 * Repository for ORM Entity <?= $entity_class_name; ?>.
 *
 * @method <?= $entity_class_name; ?>|null find($id, $lockMode = null, $lockVersion = null)
 * @method <?= $entity_class_name; ?>|null findOneBy(array $criteria, array $orderBy = null)
 * @method <?= $entity_class_name; ?>[] findAll()
 * @method <?= $entity_class_name; ?>[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class <?= $class_name; ?> extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, <?= $entity_class_name; ?>::class);
    }
}
