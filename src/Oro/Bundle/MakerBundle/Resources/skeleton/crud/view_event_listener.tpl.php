<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Add data grids to the <?= $short_relation_class ?> entity view page.
 */
class <?= $class_name . PHP_EOL ?>
{
    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator
    ) {
    }

    public function onView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $id = $request->get('id');

<?php foreach ($sections as $section): ?>
        $this->add<?= \Symfony\Bundle\MakerBundle\Str::asLowerCamelCase(str_replace('-', '_', $section['grid_name'])); ?>(
            $event,
            $id
        );
<?php endforeach; ?>
    }
<?php foreach ($sections as $section): ?>

    private function add<?= \Symfony\Bundle\MakerBundle\Str::asCamelCase(str_replace('-', '_', $section['grid_name'])); ?>(
        BeforeListRenderEvent $event,
        int|string $id
    ): void {
        $template = $event->getEnvironment()->render(
            '<?= $template_path_prefix ?>includes/relationGrid.html.twig',
            [
                'relation_grid_name' => '<?= $section['grid_name'] ?>',
                'holder_entity_id' => $id
            ]
        );

        $blockLabel = $this->translator->trans('<?= $section['section_label'] ?>');
        $scrollData = $event->getScrollData();
        $blockId = $scrollData->addBlock($blockLabel, 0);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $template);
    }
<?php endforeach; ?>
}
