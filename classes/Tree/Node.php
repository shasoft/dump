<?php

namespace Shasoft\Dump\Tree;

class Node
{
    private string $content;
    private bool $expand = false;
    private array $children = [];

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function setExpand(bool $expand): static
    {
        $this->expand = $expand;
        return $this;
    }

    public function expand(): bool
    {
        return $this->expand;
    }

    public function add(self $node): static
    {
        $this->children[] = $node;
        return $this;
    }

    public function children(): array
    {
        return $this->children;
    }
}
