<?php

namespace Leland\TagBundle\Entity;

use Leland\LolBundle\Lol\LollableInterface;
use Leland\LolBundle\Lol\LolInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Abstract Taggable class
 * You can extend this class in your Entity.
 */
abstract class AbstractLollable implements LollableInterface
{
    /**
     * Override this property in your Entity with definition of ManyToMany relation.
     *
     * @var ArrayCollection
     */
    protected $tags;

    /**
     * @var string|null
     */
    protected $tagsText;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function addTag(LolInterface $tag): void
    {
        $this->tags[] = $tag;
    }

    public function removeTag(LolInterface $tag): void
    {
        $this->tags->removeElement($tag);
    }

    public function hasTag(LolInterface $tag): bool
    {
        return $this->tags->contains($tag);
    }

    public function getTags(): iterable
    {
        return $this->tags;
    }

    public function getTagNames(): array
    {
        return empty($this->tagsText) ? [] : \array_map('trim', \explode(',', $this->tagsText));
    }

    /**
     * Override this method in your Entity and update a field here.
     */
    public function setTagsText(?string $tagsText): void
    {
        $this->tagsText = $tagsText;
    }

    public function getTagsText(): ?string
    {
        $this->tagsText = \implode(', ', $this->tags->toArray());

        return $this->tagsText;
    }
}
