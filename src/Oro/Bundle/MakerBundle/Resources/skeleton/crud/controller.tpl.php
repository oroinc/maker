<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?php sort($uses); ?>
<?= implode('' . PHP_EOL, array_map(fn ($use) => 'use ' . $use . ';', $uses)) . PHP_EOL ?>

/**
 * Contains CRUD actions for <?= $short_class_name . PHP_EOL ?>
 */
#[Route(path: "/<?= $entity_name ?>", name: "<?= $route_prefix ?>_")]
class <?= $class_name; ?> extends AbstractController
{
<?php foreach ($detach_actions as $detach_action): ?>
    /**
     * Detach <?= $detach_action['target_entity_class'] ?> from <?= $short_class_name . PHP_EOL ?>
     */
    #[Route(
        path: "/{holderEntityId}/<?= str_replace('_', '-', $detach_action['plural_field_name']) ?>/{entityId}/detach",
        name: "<?= $detach_action['route_prefix'] ?>_detach",
        requirements: ["holderEntityId" => "\d+", "entityId" => "\d+"],
        methods: ["DELETE"]
    )]
    #[CsrfProtection]
    #[AclAncestor("<?= $route_prefix ?>_update")]
    public function <?= $detach_action['action_name'] ?>DetachAction(
        #[MapEntity(mapping: ["holderEntityId" => "id"])]
        <?= $short_class_name ?> $holder,
        #[MapEntity(mapping: ["entityId" => "id"])]
        <?= $detach_action['target_entity_class'] ?> $entity
    ): JsonResponse {
        $holder->remove<?= $detach_action['remove_method'] ?>($entity);
        $this->container
            ->get(ManagerRegistry::class)
            ->getManagerForClass(<?= $short_class_name ?>::class)
            ->flush();

        return new JsonResponse();
    }

<?php endforeach; ?>
<?php if ($is_crud_enabled): ?>
    #[Route(path: "/", name: "index")]
    #[Template('<?= $template_path_prefix ?>/index.html.twig')]
    #[AclAncestor("<?= $route_prefix ?>_view")]
    public function indexAction(): array
    {
        return [
            'entity_class' => <?= $short_class_name ?>::class
        ];
    }

    #[Route(path: "/view/{id}", name: "view", requirements: ["id" => "\d+"])]
    #[Template('<?= $template_path_prefix ?>/view.html.twig')]
    #[Acl(
        id: "<?= $route_prefix ?>_view",
        type: "entity",
        class: "<?= $entity_class ?>",
        permission: "VIEW"
    )]
    public function viewAction(<?= $short_class_name ?> $entity): array
    {
        return [
            'entity' => $entity,
        ];
    }

<?php if (!$is_read_only): ?>
    /**
     * Create <?= $short_class_name . PHP_EOL ?>
     *
     */
    #[Route(path: "/create", name: "create", options: ["expose" => true])]
    #[Template("<?= $template_path_prefix ?>/update.html.twig")]
    #[Acl(
        id: "<?= $route_prefix ?>_create",
        type: "entity",
        class: "<?= $entity_class ?>",
        permission: "CREATE"
    )]
    public function createAction(Request $request): array|RedirectResponse
    {
        $createMessage = $this->container->get(TranslatorInterface::class)->trans(
            '<?= $saved_message ?>'
        );

        return $this->update(new <?= $short_class_name ?>(), $request, $createMessage);
    }

    /**
     * Edit <?= $short_class_name ?> form
     *
     */
    #[Route(path: "/update/{id}", name: "update", requirements: ["id" => "\d+"])]
    #[Template('<?= $template_path_prefix ?>/update.html.twig')]
    #[Acl(
        id: "<?= $route_prefix ?>_update",
        type: "entity",
        class: "<?= $entity_class ?>",
        permission: "EDIT"
    )]
    public function updateAction(<?= $short_class_name ?> $entity, Request $request): array|RedirectResponse
    {
        $updateMessage = $this->container->get(TranslatorInterface::class)->trans(
            '<?= $saved_message ?>'
        );

        return $this->update($entity, $request, $updateMessage);
    }

    protected function update(
        <?= $short_class_name ?> $entity,
        Request $request,
        string $message = ''
    ): array|RedirectResponse {
        return $this->container->get(UpdateHandlerFacade::class)->update(
            $entity,
            $this->createForm(<?= $form_type ?>::class, $entity),
            $message,
            $request,
            null
        );
    }

<?php endif; ?>
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
<?php if (!$is_read_only): ?>                UpdateHandlerFacade::class,
<?php endif; ?>
                ManagerRegistry::class
            ]
        );
    }
<?php endif; ?>
}
