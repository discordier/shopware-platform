<?php declare(strict_types=1);

namespace Shopware\Storefront\Theme\Aggregate;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<ThemeTranslationEntity>
 */
#[Package('framework')]
class ThemeTranslationCollection extends EntityCollection
{
    /**
     * @return array<string>
     */
    public function getThemeIds(): array
    {
        return $this->fmap(fn (ThemeTranslationEntity $themeTranslation) => $themeTranslation->getThemeId());
    }

    public function filterByThemeId(string $id): self
    {
        return $this->filter(fn (ThemeTranslationEntity $themeTranslation) => $themeTranslation->getThemeId() === $id);
    }

    /**
     * @return array<string>
     */
    public function getLanguageIds(): array
    {
        return $this->fmap(fn (ThemeTranslationEntity $themeTranslation) => $themeTranslation->getLanguageId());
    }

    public function filterByLanguageId(string $id): self
    {
        return $this->filter(fn (ThemeTranslationEntity $themeTranslation) => $themeTranslation->getLanguageId() === $id);
    }

    protected function getExpectedClass(): string
    {
        return ThemeTranslationEntity::class;
    }
}
