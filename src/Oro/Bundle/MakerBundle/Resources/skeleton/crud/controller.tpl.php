<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?php sort($uses); ?>
<?= implode('' . PHP_EOL, array_map(fn ($use) => 'use ' . $use . ';', $uses)) . PHP_EOL ?>

/**
 * Contains CRUD actions for <?= $short_class_name . PHP_EOL ?>
 *
 * @Route("/<?= $entity_name ?>", name="<?= $route_prefix ?>_")
 */
class <?= $class_name; ?> extends AbstractController
{
<?php foreach ($detach_actions as $detach_action): ?>
    /**
     * Detach <?= $detach_action['target_entity_class'] ?> from <?= $short_class_name . PHP_EOL ?>
     *
     * @Route(
     *     "/{holderEntityId}/<?= str_replace('_', '-', $detach_action['plural_field_name']) ?>/{entityId}/detach",
     *     name="<?= $detach_action['route_prefix'] ?>_detach",
     *     requirements={"holderEntityId"="\d+","entityId"="\d+"},
     *     methods={"DELETE"}
     * )
     * @ParamConverter("holder", options={"id"="holderEntityId"})
     * @ParamConverter("entity", options={"id"="entityId"})
     * @CsrfProtection()
     * @AclAncestor("<?= $route_prefix ?>_update")
     */
    public function <?= $detach_action['action_name'] ?>DetachAction(
        <?= $short_class_name ?> $holder,
        <?= $detach_action['target_entity_class'] ?> $entity
    ): JsonResponse {
        $holder->remove<?= $detach_action['remove_method'] ?>($entity);
        $this->get('doctrine')->getManagerForClass(<?= $short_class_name ?>::class)->flush();

        return new JsonResponse();
    }

<?php endforeach; ?>
<?php if ($is_crud_enabled): ?>
    /**
     * @Route("/", name="index")
     * @Template
     * @AclAncestor("<?= $route_prefix ?>_view")
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => <?= $short_class_name ?>::class
        ];
    }

    /**
     * @Route("/view/{id}", name="view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="<?= $route_prefix ?>_view",
     *      type="entity",
     *      class="<?= $entity_class ?>",
     *      permission="VIEW"
     * )
     */
    public function viewAction(<?= $short_class_name ?> $entity): array
    {
        return [
            'entity' => $entity,
        ];
    }

    /**
     * Create <?= $short_class_name . PHP_EOL ?>
     *
     * @Route("/create", name="create", options={"expose"=true})
     * @Template("<?= $template_path_prefix ?>/update.html.twig")
     * @Acl(
     *      id="<?= $route_prefix ?>_create",
     *      type="entity",
     *      class="<?= $entity_class ?>",
     *      permission="CREATE"
     * )
     */
    public function createAction(Request $request): array|RedirectResponse
    {
        $createMessage = $this->get(TranslatorInterface::class)->trans(
            '<?= $saved_message ?>'
        );

        return $this->update(new <?= $short_class_name ?>(), $request, $createMessage);
    }

    /**
     * Edit <?= $short_class_name ?> form
     *
     * @Route("/update/{id}", name="update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="<?= $route_prefix ?>_update",
     *      type="entity",
     *      class="<?= $entity_class ?>",
     *      permission="EDIT"
     * )
     */
    public function updateAction(<?= $short_class_name ?> $entity, Request $request): array|RedirectResponse
    {
        $updateMessage = $this->get(TranslatorInterface::class)->trans(
            '<?= $saved_message ?>'
        );

        return $this->update($entity, $request, $updateMessage);
    }

    protected function update(
        <?= $short_class_name ?> $entity,
        Request $request,
        string $message = ''
    ): array|RedirectResponse {
        return $this->get(UpdateHandlerFacade::class)->update(
            $entity,
            $this->createForm(<?= $form_type ?>::class, $entity),
            $message,
            $request,
            null
        );
    }

    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                UpdateHandlerFacade::class,
            ]
        );
    }
<?php endif; ?>
}
