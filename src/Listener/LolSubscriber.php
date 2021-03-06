<?php

namespace Leland\LolBundle\Listener;

use Leland\LolBundle\Lol\LollableInterface;
use Leland\LolBundle\Lol\LolInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\ORM\Event\OnFlushEventArgs;

/**
 * Add tags to entities that implements LollableInterface.
 */
final class LolSubscriber implements EventSubscriber
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $manager;

    /**
     * @var \Doctrine\ORM\UnitOfWork
     */
    protected $uow;

    /**
     * @var LolInterface
     */
    protected $tag;

    /**
     * @var bool
     */
    protected $purge;

    /**
     * @param bool $purge whether to delete tags when entity is deleted
     *
     * @throws MappingException
     * @throws \InvalidArgumentException
     */
    public function __construct(string $tagClassName, bool $purge = false)
    {
        if (!\class_exists($tagClassName)) {
            throw MappingException::nonExistingClass($tagClassName);
        }
        $this->tag = new $tagClassName();
        if (!$this->tag instanceof LolInterface) {
            throw new \InvalidArgumentException(\sprintf('Class "%s" must implement LolInterface.', $tagClassName));
        }
        $this->purge = $purge;
    }

    public function getSubscribedEvents(): array
    {
        return ['onFlush'];
    }

    /**
     * Main method: call setTags() on entities scheduled to be inserted or updated, and
     * possibly call purgeTags() on entities scheduled to be deleted.
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $this->manager = $args->getEntityManager();
        $this->uow = $this->manager->getUnitOfWork();
        foreach ($this->uow->getScheduledEntityInsertions() as $key => $entity) {
            if ($entity instanceof LollableInterface) {
                $this->setTags($entity, false);
            }
        }
        foreach ($this->uow->getScheduledEntityUpdates() as $key => $entity) {
            if ($entity instanceof LollableInterface) {
                $this->setTags($entity, true);
            }
        }
        if ($this->purge) {
            foreach ($this->uow->getScheduledEntityDeletions() as $key => $entity) {
                if ($entity instanceof LollableInterface) {
                    $this->purgeTags($entity);
                }
            }
        }
    }

    /**
     * Do the stuff.
     *
     * @param bool $update true if entity is being updated, false otherwise
     */
    protected function setTags(LollableInterface $entity, bool $update = false): void
    {
        $tagNames = $entity->getTagNames();
        if (empty($tagNames) && !$update) {
            return;
        }
        // need to clone here, to avoid getting new tags
        $oldTags = \is_object($entityTags = $entity->getTags()) ? clone $entityTags : $entityTags;
        $tagClassMetadata = $this->manager->getClassMetadata(\get_class($this->tag));
        $repository = $this->manager->getRepository(\get_class($this->tag));
        foreach ($tagNames as $tagName) {
            $tag = $repository->findOneByName($tagName);
            if (empty($tag)) {
                // if tag doesn't exist, create it
                $tag = clone $this->tag;
                $tag->setName($tagName);
                $this->manager->persist($tag);
                // see http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html#onflush
                $this->uow->computeChangeSet($tagClassMetadata, $tag);
            }
            if (!$entity->hasTag($tag)) {
                // add tag only if not already added
                $entity->addTag($tag);
            }
        }
        // if updating, need to check if some tags were removed
        if ($update) {
            foreach ($oldTags as $oldTag) {
                if (!\in_array($oldTag->getName(), $tagNames)) {
                    $entity->removeTag($oldTag);
                }
            }
        }
        // see http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html#onflush
        $entityClassMetadata = $this->manager->getClassMetadata(\get_class($entity));
        $this->uow->computeChangeSets($entityClassMetadata, $entity);
    }

    /**
     * Purge oprhan tags
     * Warning: DO NOT purge tags if you have more than one entity
     * with tags, since this could lead to costraint violations.
     */
    protected function purgeTags(LollableInterface $entity): void
    {
        foreach ($entity->getTags() as $oldTag) {
            $this->manager->remove($oldTag);
        }
    }
}
