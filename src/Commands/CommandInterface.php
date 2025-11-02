<?php

namespace Scy\Commands;

interface CommandInterface
{
    /**
     * Execute the command
     */
    public function execute(array $args): int;

    /**
     * Get command name
     */
    public function getName(): string;

    /**
     * Get command description
     */
    public function getDescription(): string;
}