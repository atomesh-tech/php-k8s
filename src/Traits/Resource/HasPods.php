<?php

namespace RenokiCo\PhpK8s\Traits\Resource;

use Closure;

trait HasPods
{
    /**
     * Custom closure to set a dynamic pod selector.
     *
     * @var Closure|null
     */
    protected static ?Closure $podSelectorCallback = null;

    /**
     * Get the selector for the pods that are owned by this resource.
     *
     * @return array
     */
    public function podsSelector(): array
    {
        if ($callback = static::$podSelectorCallback) {
            return $callback($this);
        }

        return [
            //
        ];
    }

    /**
     * Reset the pods selector callback.
     *
     * @return void
     */
    public static function resetPodsSelector(): void
    {
        static::$podSelectorCallback = null;
    }

    /**
     * Dynamically select the pods based on selectors.
     *
     * @param  Closure  $callback
     * @return void
     */
    public static function selectPods(Closure $callback): void
    {
        static::$podSelectorCallback = $callback;
    }

    /**
     * Get the pods owned by this resource.
     *
     * @param  array  $query
     * @return \RenokiCo\PhpK8s\ResourcesList
     */
    public function getPods(array $query = ['pretty' => 1]): \RenokiCo\PhpK8s\ResourcesList
    {
        $labelSelector = urldecode(http_build_query(
            $this->podsSelector()
        ));

        return $this->cluster->pod()->setNamespace($this->getNamespace())->all(
            array_merge(['labelSelector' => $labelSelector], $query)
        );
    }

    /**
     * Check if all scheduled pods are running.
     *
     * @return bool
     */
    public function allPodsAreRunning(): bool
    {
        $pods = $this->getPods();

        return $pods->count() > 0 && $pods->reject(function ($pod) {
            return $pod->isReady();
        })->isEmpty();
    }
}
