<?php

namespace App\DependencyInjection\CompilerPass;

use App\Command\Worker\WorkerCommand;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass to register all workers with the worker command
 */
class WorkerCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(WorkerCommand::class)) {
            return;
        }

        $definition = $container->findDefinition(WorkerCommand::class);
        $taggedServices = $container->findTaggedServiceIds('app.worker');

        $workers = [];
        foreach ($taggedServices as $id => $tags) {
            $workers[] = new Reference($id);
        }

        $definition->setArgument(0, $workers);
    }
}
