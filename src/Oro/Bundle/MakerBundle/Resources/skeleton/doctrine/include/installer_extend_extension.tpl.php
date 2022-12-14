<?php if ($requires_extend_extension): ?>
    protected ExtendExtension $extendExtension;

    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }
<?php endif; ?>