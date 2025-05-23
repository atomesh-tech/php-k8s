<?php

namespace RenokiCo\PhpK8s\Kinds;

use Carbon\Carbon;
use RenokiCo\PhpK8s\Contracts\InteractsWithK8sCluster;
use RenokiCo\PhpK8s\Contracts\Podable;
use RenokiCo\PhpK8s\Contracts\Watchable;
use RenokiCo\PhpK8s\Traits\Resource\HasPods;
use RenokiCo\PhpK8s\Traits\Resource\HasSelector;
use RenokiCo\PhpK8s\Traits\Resource\HasSpec;
use RenokiCo\PhpK8s\Traits\Resource\HasStatus;
use RenokiCo\PhpK8s\Traits\Resource\HasStatusConditions;
use RenokiCo\PhpK8s\Traits\Resource\HasTemplate;

class K8sJob extends K8sResource implements
    InteractsWithK8sCluster,
    Podable,
    Watchable
{
    use HasPods {
        podsSelector as protected customPodsSelector;
    }
    use HasSelector;
    use HasSpec;
    use HasStatus;
    use HasStatusConditions;
    use HasTemplate;

    /**
     * The resource Kind parameter.
     *
     * @var null|string
     */
    protected static $kind = 'Job';

    /**
     * The default version for the resource.
     *
     * @var string
     */
    protected static $defaultVersion = 'batch/v1';

    /**
     * Wether the resource has a namespace.
     *
     * @var bool
     */
    protected static $namespaceable = true;

    /**
     * Set the TTL for the job availability.
     *
     * @param  int  $ttl
     * @return $this
     */
    public function setTTL(int $ttl = 100): self
    {
        return $this->setSpec('ttlSecondsAfterFinished', $ttl);
    }

    /**
     * Get the selector for the pods that are owned by this resource.
     *
     * @return array
     */
    public function podsSelector(): array
    {
        if ($podsSelector = $this->customPodsSelector()) {
            return $podsSelector;
        }

        return [
            'job-name' => $this->getName(),
        ];
    }

    /**
     * Get the amount of active pods.
     *
     * @return int
     */
    public function getActivePodsCount(): int
    {
        return $this->getStatus('active', 0);
    }

    /**
     * Get the amount of failed pods.
     *
     * @return int
     */
    public function getFailedPodsCount(): int
    {
        return $this->getStatus('failed', 0);
    }

    /**
     * Get the amount of succeded pods.
     *
     * @return int
     */
    public function getSuccededPodsCount(): int
    {
        return $this->getStatus('succeeded', 0);
    }

    /**
     * Get the start time.
     *
     * @return \DateTime|null
     */
    public function getStartTime(): ?Carbon
    {
        $time = $this->getStatus('startTime', null);

        return $time ? Carbon::parse($time) : null;
    }

    /**
     * Get the completion time.
     *
     * @return \DateTime|null
     */
    public function getCompletionTime(): ?Carbon
    {
        $time = $this->getStatus('completionTime', null);

        return $time ? Carbon::parse($time) : null;
    }

    /**
     * Get the total run time, in seconds.
     *
     * @return int
     */
    public function getDurationInSeconds(): int
    {
        $startTime = $this->getStartTime();
        $completionTime = $this->getCompletionTime();

        return $startTime && $completionTime
            ? $startTime->diffInSeconds($completionTime)
            : 0;
    }

    /**
     * Check if the job has completed.
     *
     * @return bool
     */
    public function hasCompleted(): bool
    {
        return ! is_null($this->getCompletionTime());
    }
}
