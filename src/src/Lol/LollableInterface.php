<?php
namespace Leland\LolBundle\Lol;

interface LollableInterface
{
    public function addTag(LolInterface $tag): void;

    public function getTagNames(): array;

    public function getTags(): iterable;

    public function hasTag(LolInterface $tag): bool;

    public function removeTag(LolInterface $tag): void;
}