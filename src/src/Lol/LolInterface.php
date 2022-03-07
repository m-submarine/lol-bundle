<?php
namespace Leland\LolBundle\Lol;

interface LolInterface
{
    public function setName(?string $name): void;

    public function getName(): ?string;
}
